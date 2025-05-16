#pragma once
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "driver/spi_master.h"
#include "driver/gpio.h"
#include <esp_event.h>
#include "esp_log.h"
#include "esp_mac.h"
#include <freertos/FreeRTOS.h>
#include <freertos/task.h>

extern QueueHandle_t http_queue;
extern char card_data[722];
void rfid_reader_task(void *pvParameters);