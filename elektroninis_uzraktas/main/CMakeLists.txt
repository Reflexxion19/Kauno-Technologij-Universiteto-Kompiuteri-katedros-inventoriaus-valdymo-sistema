idf_component_register(SRCS "main.c" "src/https_client.c" "src/rfid.c" "src/wifi.c" "src/crypto.c" "src/webserver.c" "src/MFRC522.c" "src/lock.c"
                    INCLUDE_DIRS "."
                    EMBED_TXTFILES "certs/servercert.pem"
                                   "certs/prvtkey.pem"
                                   "certs/certificate.pem")