#include "headers/webserver.h"

#include "headers/device_details.h"
#include "headers/crypto.h"
#include "headers/https_client.h"
#include "headers/lock.h"
#include "headers/rfid.h"

#define TAG "HTTPS_SERVER"

static char hex_char_to_int(char c) {
    if ('0' <= c && c <= '9') return c - '0';
    if ('a' <= c && c <= 'f') return c - 'a' + 10;
    if ('A' <= c && c <= 'F') return c - 'A' + 10;
    return -1;
}

void url_decode(char *src, char *dst) {
    while (*src) {
        if (*src == '%') {
            if (isxdigit((unsigned char) *(src + 1)) && isxdigit((unsigned char) *(src + 2))) {
                char high = hex_char_to_int(*(src + 1));
                char low = hex_char_to_int(*(src + 2));
                *dst++ = (char)((high << 4) | low);
                src += 3;
            } else {
                *dst++ = *src++;
            }
        } else if (*src == '+') {
            *dst++ = ' ';
            src++;
        } else {
            *dst++ = *src++;
        }
    }
    *dst = '\0';
}

void urlencode(const char *input, char *output, size_t max_len) {
    const char hex[] = "0123456789ABCDEF";
    size_t i, j = 0;

    for (i = 0; input[i] != '\0' && j < max_len - 1; i++) {
        if (isalnum((unsigned char)input[i]) || 
            input[i] == '-' || input[i] == '_' || 
            input[i] == '.' || input[i] == '~') {
            output[j++] = input[i];
        } else {
            if (j + 3 >= max_len) break; // Prevent buffer overflow
            output[j++] = '%';
            output[j++] = hex[(input[i] >> 4) & 0xF];
            output[j++] = hex[input[i] & 0xF];
        }
    }
    output[j] = '\0'; // Null-terminate
}

void https_client_task(void *pvParameters) {
    char *received_data;
    while(1) {
        if (xQueueReceive(http_queue, &received_data, portMAX_DELAY)) {
            if (received_data) {
                send_data(received_data);
                free(received_data);
            }
        }
    }
}

static esp_err_t post_handler(httpd_req_t *req)
{
    if (req->content_len > 4096) {  // Set reasonable max
        httpd_resp_send_err(req, HTTPD_413_CONTENT_TOO_LARGE, "Content too large");
        ESP_LOGI(TAG, "Starting server"); // For debugging
        return ESP_FAIL;
    }

    char content[400];
    size_t recv_size = MIN(req->content_len, sizeof(content) - 1);
    int ret = httpd_req_recv(req, content, recv_size);

    if(ret <= 0)
    {
        if (ret == HTTPD_SOCK_ERR_TIMEOUT)
        {
            httpd_resp_send_408(req);
        }
        return ESP_FAIL;
    }

    content[ret] = '\0';
    printf("\nPOST content: %s\n", content); // For debugging

    typedef struct {
        char key[50];
        char value[150];
    } KeyValuePair;

    #define MAX_PAIRS 3  // Maximum number of key-value pairs
    KeyValuePair pairs[MAX_PAIRS];
    int pair_count = 0;
    
    char *saveptr;
    char *token = strtok_r(content, "&", &saveptr);
    while (token != NULL)
    {
        char *saveptr2;
        char *key = strtok_r(token, "=", &saveptr2);
        char *value = strtok_r(NULL, "=", &saveptr2);

        if (key && value)
        {
            url_decode(value, value);

            strncpy(pairs[pair_count].key, key, sizeof(pairs[pair_count].key) - 1);
            strncpy(pairs[pair_count].value, value, sizeof(pairs[pair_count].value) - 1);
            pairs[pair_count].key[sizeof(pairs[pair_count].key) - 1] = '\0';
            pairs[pair_count].value[sizeof(pairs[pair_count].value) - 1] = '\0';

            printf("Stored pair %d: %s=%s\n", pair_count+1, pairs[pair_count].key, pairs[pair_count].value); // For debugging
            pair_count++;
        }

        token = strtok_r(NULL, "&", &saveptr); // Move to the next key-value pair
    }

    printf("\nTotal pairs received: %d\n", pair_count); // For debugging

    // Process based on number of pairs received
    switch(pair_count) {
        case 2:
            if(strcmp(pairs[0].value, "auth_message") == 0){
                char *b64_re_encrypted = decrypt_encrypt(pairs[1].value);

                char url_encoded[512];
                urlencode(b64_re_encrypted, url_encoded, sizeof(url_encoded));

                char *processed_data = malloc(2048);
                if (processed_data) {
                    snprintf(processed_data, 2048, "{\"device_name\":\"%s\",\"type\":\"auth_response\",\"message\":\"%s\",\"card_data\":\"%s\"}", 
                                                    DEVICE_NAME, url_encoded, card_data);
                    if (xQueueSend(http_queue, &processed_data, portMAX_DELAY) != pdTRUE) {
                        free(processed_data);
                        strncpy(card_data, 0, 61);
                    }
                } else {
                    ESP_LOGE(TAG, "Failed to allocate processed_data");
                }
                free(b64_re_encrypted);
            } else if (strcmp(pairs[0].value, "card_data_wait") == 0){

            } else if (strcmp(pairs[0].value, "auth_confirmation") == 0){
                unsigned char *decrypted_message = NULL;
                size_t decrypted_len = 0;

                if(!decrypt_message(pairs[1].value, &decrypted_message, &decrypted_len)){
                    ESP_LOGE(TAG, "Failed to decode message");
                    free(decrypted_message);

                    const char resp_fail[] = "Failed";
                    httpd_resp_set_type(req, "text/html");
                    httpd_resp_send(req, resp_fail, HTTPD_RESP_USE_STRLEN);

                    return ESP_FAIL;
                }
                
                decrypted_message = realloc(decrypted_message, decrypted_len + 1);
                decrypted_message[decrypted_len] = '\0';

                if (strcmp((char *)decrypted_message, "unlock") == 0){
                    xTaskCreate(storage_open, "storage open", 2048, NULL, 0, NULL);

                    free(decrypted_message);
                }
            }
            break;
            
        default:
            break;
    }

    const char resp[] = "Success";
    httpd_resp_set_type(req, "text/html");
    httpd_resp_send(req, resp, HTTPD_RESP_USE_STRLEN);

    return ESP_OK;
}

static const httpd_uri_t uri_post = {
    .uri = "/post",
    .method = HTTP_POST,
    .handler = post_handler
};

httpd_handle_t start_webserver(void)
{
    httpd_handle_t server = NULL;

    ESP_LOGI(TAG, "Starting server"); // For debugging

    httpd_ssl_config_t conf = HTTPD_SSL_CONFIG_DEFAULT();

    extern const unsigned char servercert_start[] asm("_binary_servercert_pem_start");
    extern const unsigned char servercert_end[]   asm("_binary_servercert_pem_end");
    conf.servercert = servercert_start;
    conf.servercert_len = servercert_end - servercert_start;

    extern const unsigned char prvtkey_pem_start[] asm("_binary_prvtkey_pem_start");
    extern const unsigned char prvtkey_pem_end[]   asm("_binary_prvtkey_pem_end");
    conf.prvtkey_pem = prvtkey_pem_start;
    conf.prvtkey_len = prvtkey_pem_end - prvtkey_pem_start;

#if CONFIG_EXAMPLE_ENABLE_HTTPS_USER_CALLBACK
    conf.user_cb = https_server_user_callback;
#endif
    esp_err_t ret = httpd_ssl_start(&server, &conf);
    if (ESP_OK != ret) {
        ESP_LOGI(TAG, "Error starting server!"); // For debugging
        return NULL;
    }

    // Set URI handlers
    ESP_LOGI(TAG, "Registering URI handlers"); // For debugging
    httpd_register_uri_handler(server, &uri_post);
    return server;
}