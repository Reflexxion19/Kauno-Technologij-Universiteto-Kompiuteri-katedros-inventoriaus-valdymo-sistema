#pragma once
#include <string.h>
#include <nvs_flash.h>
#include <nvs.h>
#include <esp_event.h>
#include <esp_log.h>
#include <sodium.h>
bool decrypt_message(const char *base64_encrypted_message, unsigned char **decrypted, size_t *decrypted_len);
char *decrypt_encrypt(const char *input);
void init_sodium();