#include <esp_event.h>
#include <esp_log.h>
#include <nvs_flash.h>
#include "esp_sntp.h"
#include <sodium.h>

#include "headers/wifi.h"
#include "headers/webserver.h"
#include "headers/https_client.h"
#include "headers/rfid.h"
#include "headers/lock.h"

#define TAG "Main"

QueueHandle_t http_queue = NULL;

void app_main(void)
{
    if (sodium_init() < 0) {
        ESP_LOGE(TAG, "libsodium initialization failed"); // For debugging
        return;
    }

    esp_err_t ret = nvs_flash_init();
    if (ret == ESP_ERR_NVS_NO_FREE_PAGES) {
        ESP_ERROR_CHECK(nvs_flash_erase());
        ret = nvs_flash_init();
    }
    ESP_ERROR_CHECK(ret);
    
    lock_init();
    wifi_init();
    esp_sntp_init();
    start_webserver();

    xTaskCreate(rfid_reader_task, "rfid_reader", 4096, NULL, 5, NULL);

    http_queue = xQueueCreate(5, sizeof(char *));
    if (http_queue == NULL) {
        ESP_LOGE(TAG, "Failed to create HTTP queue");
        return;
    }
    xTaskCreate(https_client_task, "https_client_task", 8192, NULL, 5, NULL);

    vTaskDelay(2000 / portTICK_PERIOD_MS);
}