#include "headers/lock.h"
#include "relay.h"

Relay my_relay;

void lock_init() {
    relay_init(&my_relay, 35, RELAY_NO, 0);
}

void storage_open(void *pvParameters){
    printf("Lock Open...\n");

    relay_pulse(&my_relay, 1000);

    printf("Lock Closed...\n");

    vTaskDelete(NULL);
}