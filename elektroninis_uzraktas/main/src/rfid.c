#include "headers/rfid.h"

#include "headers/device_details.h"
#include "headers/MFRC522.h"
#include "headers/https_client.h"

#define TAG "RFID"

#define MAX_SEGMENTS 45
#define SEGMENT_SIZE 17
#define STARTING_BLOCK 4

char card_data[722] = {0};

size_t data_length = 0;

char segments[MAX_SEGMENTS][SEGMENT_SIZE] = {0};
size_t needed_segments = 0;

uint8_t card_rx_buffer[18];
uint8_t card_rx_len = sizeof(card_rx_buffer);

MIFARE_Key key = 
{
 .keyByte = {0xff,0xff,0xff,0xff,0xff,0xff}
};
uint8_t status = 0;

char *rfidRead(spi_device_handle_t spi){
    uint8_t block2_data[18];
    uint8_t len = sizeof(block2_data);

    if(PCD_Authenticate(spi, PICC_CMD_MF_AUTH_KEY_A, 0, &key, &(uid)) != ESP_OK){
        ESP_LOGE(TAG, "Klaida autentifikuojant sektorių naudojant bloką 2");
        return 0;
    }

    if (MIFARE_Read(spi, 2, block2_data, &len) != ESP_OK) {
        ESP_LOGE(TAG, "Nepavyko perskaityti 2 bloko");
        return 0;
    }

    needed_segments = (size_t)block2_data[0];

    uint8_t current_block = STARTING_BLOCK;
    size_t segments_read = 0;

    size_t total_length = needed_segments * 16;
    char *result_string = malloc(total_length + 1);
    result_string[0] = '\0';

    while (segments_read < needed_segments && current_block < 64) {
        if (current_block % 4 == 0) {
            if(PCD_Authenticate(spi, PICC_CMD_MF_AUTH_KEY_A, current_block, &key, &(uid)) != ESP_OK){
                ESP_LOGE(TAG, "Klaida autentifikuojant sektorių naudojant bloką %d", current_block);
                break;
            }
        }

        if (current_block % 4 == 3) {
            current_block++;
            continue;
        }

        memset(card_rx_buffer, 0, sizeof(card_rx_buffer));
        card_rx_len = sizeof(card_rx_buffer);
        if (MIFARE_Read(spi, current_block, card_rx_buffer, &card_rx_len) != ESP_OK) {
            ESP_LOGE(TAG, "Klaida skaitant duomenis iš %d bloko", current_block);
            break;
        }
        card_rx_buffer[16] = '\0';
        strncat(result_string, (char*)card_rx_buffer, 16);
        ESP_LOGI(TAG, "Blokas %d: %s", current_block, (char*)card_rx_buffer);

        segments_read++;
        current_block++;
    }

    ESP_LOGI(TAG, "Nuskaityti duomenys: %s", result_string);
    return result_string;
}

void rfid_reader_task(void *pvParams){
    esp_err_t ret;
    spi_device_handle_t spi;
    spi_bus_config_t buscfg={
        .miso_io_num=13,
        .mosi_io_num=11,
        .sclk_io_num=12,
        .quadwp_io_num=-1,
        .quadhd_io_num=-1
    };
    spi_device_interface_config_t devcfg={
        .clock_speed_hz=5000000,
        .mode=0,
        .spics_io_num=10,
        .queue_size=7
    };

    ret=spi_bus_initialize(SPI2_HOST, &buscfg, SPI_DMA_CH_AUTO);
    assert(ret==ESP_OK);
    ret=spi_bus_add_device(SPI2_HOST, &devcfg, &spi);
    assert(ret==ESP_OK);

    PCD_Init(spi);

    while(1){
        if(PICC_IsNewCardPresent(spi)){
            printf("Kortelė užfiksuota...\n");
            PICC_Select(spi, &uid, 0);

            char *data = rfidRead(spi);
            data_length = strlen(data);

            if (data) {
                strncpy(card_data, data, data_length);

                char *data_send = malloc(1024);
                snprintf(data_send, 1024, "{\"device_name\":\"%s\",\"type\":\"auth\",\"message\":\"auth\"}", DEVICE_NAME);

                if (xQueueSend(http_queue, &data_send, portMAX_DELAY) != pdTRUE) {
                    free(data_send);
                }
                
                free(data);
            }
            PCD_StopCrypto1(spi);
        }

        vTaskDelay(500 / portTICK_PERIOD_MS);
    }
}