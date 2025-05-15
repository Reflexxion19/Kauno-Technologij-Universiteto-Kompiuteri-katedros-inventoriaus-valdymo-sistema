#include "headers/rfid.h"

#include "headers/MFRC522.h"


static const char *RFID_TAG = "RFID";

char data1[17] = {0};
char data2[17] = {0};
char data3[17] = {0};

uint8_t card_rx_buffer[18];
uint8_t card_rx_len = sizeof(card_rx_buffer);

MIFARE_Key key = 
{
 .keyByte = {0xff,0xff,0xff,0xff,0xff,0xff}
};
uint8_t status = 0;

void rfidRead(spi_device_handle_t spi){
    PCD_Authenticate(spi, PICC_CMD_MF_AUTH_KEY_A, 5, &key, &(uid));
    card_rx_len = sizeof(card_rx_buffer);
    MIFARE_Read(spi, 4, card_rx_buffer, &card_rx_len);
    card_rx_buffer[16] = '\0';  // Force null-termination at 16 bytes
    strncpy(data1, (char *)card_rx_buffer, 16);
    data1[16] = '\0';
    ESP_LOGI(RFID_TAG, "MIFARE block 4: %s", (char*)card_rx_buffer);

    memset(card_rx_buffer, 0, sizeof(card_rx_buffer));  // Clear buffer
    card_rx_len = sizeof(card_rx_buffer);
    MIFARE_Read(spi, 5, card_rx_buffer, &card_rx_len);
    card_rx_buffer[16] = '\0';
    strncpy(data2, (char *)card_rx_buffer, 16);
    data2[16] = '\0';
    ESP_LOGI(RFID_TAG, "MIFARE block 5: %s", (char*)card_rx_buffer);

    memset(card_rx_buffer, 0, sizeof(card_rx_buffer));
    card_rx_len = sizeof(card_rx_buffer);
    MIFARE_Read(spi, 6, card_rx_buffer, &card_rx_len);
    card_rx_buffer[16] = '\0';
    strncpy(data3, (char *)card_rx_buffer, 16);
    data3[16] = '\0';
    ESP_LOGI(RFID_TAG, "MIFARE block 6: %s", (char*)card_rx_buffer);
}

char *createString(){
    char *result = malloc(45);
    if (!result) return NULL;

    result[0] = '\0';

    strncat(result, data1, 16);
    strncat(result, data2, 16);
    strncat(result, data3, 16);

    return result;
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
            printf("Card detected\n");

            PICC_Select(spi, &uid, 0);

            rfidRead(spi);

            printf("Data1: %s\n", (char*)data1);
            printf("Data2: %s\n", (char*)data2);
            printf("Data3: %s\n", (char*)data3);

            char *card_data = createString();
            if (card_data) {
                printf("Card data: %s\n", card_data);

                free(card_data);
            }

            memset(data1, 0, 17);
            memset(data2, 0, 17);
            memset(data3, 0, 17);

            PCD_StopCrypto1(spi);
        }

        vTaskDelay(500 / portTICK_PERIOD_MS);
    }
}