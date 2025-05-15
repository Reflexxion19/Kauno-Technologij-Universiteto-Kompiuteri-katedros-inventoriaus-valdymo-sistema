#pragma once
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "driver/spi_master.h"
#include "driver/gpio.h"
#include <esp_event.h>
#include "esp_log.h"
#include "freertos/queue.h"
#include "esp_mac.h"

void rfid_reader_task(void *pvParameters);