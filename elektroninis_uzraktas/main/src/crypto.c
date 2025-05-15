#include "headers/crypto.h"
#include "headers/device_details.h"

static const char* TAG = "CRYPTO";

size_t base64_decode(const char *b64_input, unsigned char *output) {
    size_t len = strlen(b64_input);
    size_t output_len = 0;
    
    if (sodium_base642bin(output, crypto_box_PUBLICKEYBYTES + crypto_box_SECRETKEYBYTES + crypto_box_MACBYTES,
                          b64_input, len, NULL, &output_len, NULL,
                          sodium_base64_VARIANT_ORIGINAL) != 0) {
        ESP_LOGE(TAG, "Base64 decoding failed");
        return 0;
    }
    
    return output_len;
}

bool decrypt_message(const char *base64_encrypted_message, unsigned char **decrypted, 
                    size_t *decrypted_len) {
    unsigned char private_key[crypto_box_SECRETKEYBYTES];
    unsigned char public_key[crypto_box_PUBLICKEYBYTES];
    
    size_t private_key_len = base64_decode(microcontroller_base64_private_key, private_key);
    size_t public_key_len = base64_decode(microcontroller_base64_public_key, public_key);
    
    
    if (private_key_len != crypto_box_SECRETKEYBYTES ||
        public_key_len != crypto_box_PUBLICKEYBYTES) {
        ESP_LOGE(TAG, "Invalid key length");
        return false;
    }
    
    size_t encrypted_len = strlen(base64_encrypted_message) * 3 / 4;
    unsigned char *encrypted_message = malloc(encrypted_len);
    encrypted_len = base64_decode(base64_encrypted_message, encrypted_message);
    
    if (encrypted_len == 0) {
        ESP_LOGE(TAG, "Failed to decode message");
        free(encrypted_message);
        return false;
    }
    
    *decrypted_len = encrypted_len - crypto_box_SEALBYTES;
    *decrypted = malloc(*decrypted_len);
    
    if (*decrypted == NULL) {
        ESP_LOGE(TAG, "Memory allocation failed");
        free(encrypted_message);
        return false;
    }
    
    if (crypto_box_seal_open(*decrypted, encrypted_message, encrypted_len,
                            public_key, private_key) != 0) {
        ESP_LOGE(TAG, "Decryption failed - message tampered or keys incorrect");
        free(encrypted_message);
        free(*decrypted);
        *decrypted = NULL;
        return false;
    }
    
    free(encrypted_message);
    ESP_LOGI(TAG, "Decrypted message: %.*s", (int)*decrypted_len, *decrypted);
    return true;
}

bool encrypt_message(const unsigned char *message, size_t message_len,
                    unsigned char **encrypted, size_t *encrypted_len) {
    unsigned char public_key[crypto_box_PUBLICKEYBYTES];
    base64_decode(server_base64_public_key, public_key);

    *encrypted_len = crypto_box_SEALBYTES + message_len;
    *encrypted = malloc(*encrypted_len);
    
    if (*encrypted == NULL) {
        ESP_LOGE(TAG, "Memory allocation failed");
        return false;
    }
    
    if (crypto_box_seal(*encrypted, message, message_len, public_key) != 0) {
        ESP_LOGE(TAG, "Encryption failed");
        free(*encrypted);
        *encrypted = NULL;
        return false;
    }
    
    ESP_LOGI(TAG, "Message encrypted successfully");
    return true;
}

char *decrypt_encrypt(const char *base64_encrypted_message) {
    unsigned char *decrypted_message = NULL;
    size_t decrypted_len = 0;

    if (!decrypt_message(base64_encrypted_message, 
                       &decrypted_message, 
                       &decrypted_len) || 
        !decrypted_message) {
        ESP_LOGE(TAG, "Decryption failed");
        free(decrypted_message);
        return NULL;
    }

    unsigned char *re_encrypted = NULL;
    size_t re_encrypted_len = 0;

    if (!encrypt_message(decrypted_message, decrypted_len,
                       &re_encrypted, &re_encrypted_len) ||
        !re_encrypted) {
        ESP_LOGE(TAG, "Encryption failed");
        free(decrypted_message);
        return NULL;
    }

    size_t b64_maxlen = sodium_base64_encoded_len(re_encrypted_len, sodium_base64_VARIANT_ORIGINAL);
    char *b64_re_encrypted = malloc(b64_maxlen);
    if (!b64_re_encrypted) {
        ESP_LOGE(TAG, "Memory allocation for base64 failed");
        free(decrypted_message);
        free(re_encrypted);
        return NULL;
    }

    sodium_bin2base64(b64_re_encrypted, b64_maxlen,
                     re_encrypted, re_encrypted_len,
                     sodium_base64_VARIANT_ORIGINAL);

    free(decrypted_message);
    free(re_encrypted);

    ESP_LOGI(TAG, "Re-encrypted message (Base64): %s", b64_re_encrypted);
    return b64_re_encrypted;
}