#include "headers/https_client.h"
#include "headers/device_details.h"

#define TAG "HTTPS_CLIENT"

extern const uint8_t certificate_pem_start[] asm ("_binary_certificate_pem_start");
extern const uint8_t certificate_pem_end[] asm ("_binary_certificate_pem_end");

esp_err_t client_event_get_handler(esp_http_client_event_handle_t evt) {
    switch (evt->event_id)
    {
    case HTTP_EVENT_ON_DATA:
        printf("HTTP_EVENT_ON_DATA %.*s\n", evt->data_len, (char*)evt->data);
        break;
    
    default:
        break;
    }

    return ESP_OK;
}

void send_data(char *data_value) {
    if (data_value == NULL) {
        ESP_LOGE(TAG, "Null data in send_data");
        return;
    }

    esp_http_client_config_t config_post = {
        .url = SERVER_ADDRESS,
        .method = HTTP_METHOD_POST,
        .cert_pem = (const char *)certificate_pem_start,
        .skip_cert_common_name_check = SKIP_CERT_COMMON_NAME_CHECK,
        .timeout_ms = 15000,
        .event_handler = client_event_get_handler
    };
    
    esp_http_client_handle_t client = esp_http_client_init(&config_post);

    printf("Sending data: %.*s\n", strlen(data_value),  data_value);

    esp_http_client_set_post_field(client, data_value, strlen(data_value));
    esp_http_client_set_header(client, "Content-Type", "application/json");
    
    esp_http_client_perform(client);
    esp_http_client_cleanup(client);
}