#pragma once
#include <ctype.h>
#include <sys/param.h>
#include <esp_event.h>
#include <esp_log.h>
#include <esp_https_server.h>
#include <freertos/FreeRTOS.h>
#include <freertos/task.h>

extern QueueHandle_t http_queue;
void https_client_task(void *pvParameters);
httpd_handle_t start_webserver(void);