#pragma once
#include <freertos/FreeRTOS.h>
#include <freertos/task.h>
#include "driver/ledc.h"

void lock_init();
void storage_open(void *pvParameters);