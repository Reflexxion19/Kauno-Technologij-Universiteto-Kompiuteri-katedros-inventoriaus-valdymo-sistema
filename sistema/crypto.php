<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__. '/config/functions.php';

$server_base64_private_key = getPrivateKey();
$server_base64_public_key = getPublicKey();

$rawData = file_get_contents("php://input");

if (!isset($_SERVER["CONTENT_TYPE"]) || $_SERVER["CONTENT_TYPE"] !== "application/json") {
    if(isset($_GET['generate_key_pair'])) {
        $keypair = generateRandomKeyPair();
        $public_key = $keypair['public_key'];
        $private_key = $keypair['private_key'];
    
        $base64_public_key = base64_encode($public_key);
        $base64_private_key = base64_encode($private_key);
        
        echo '<div>';
        echo '<h2>Generated Key Pair</h2>';
        echo '<p>Public Key (Base64): '. htmlspecialchars($base64_public_key, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p>Private Key (Base64): '. htmlspecialchars($base64_private_key, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</div>';
        exit();
    }

    header("HTTP/1.1 400 Bad Request");
    exit();
} else {
    $data = json_decode($rawData, true);

    if ($data === NULL && json_last_error() !== JSON_ERROR_NONE) {
        header("HTTP/1.1 400 Bad Request");
        exit();
    } else {
        $device_name = $data['device_name']?? NULL;
        $type = $data['type'] ?? NULL;
        $message = $data['message']?? NULL;

        if ($device_name && $type && $message) {
            $device_data = getStorageByDeviceName($device_name);

            if ($type === "auth") {
                $randomString = generateRandomString();
                storeRandomStringInDB($device_name,  $randomString);

                $base64_encrypted_message = encryptMessage($device_data['public_key'], $randomString);

                $data_array = [
                    'type' => 'auth_message',
                    'message' => $base64_encrypted_message
                ];
    
                send_data($data_array, $device_data['address']);
            } elseif ($type === "auth_response") {
                $card_data = $data['card_data'] ?? NULL;

                if($card_data){
                    $rand_str = getRandomStringFromDB($device_name);
                    $base64_decrypted_message = decryptMessage($server_base64_private_key, $server_base64_public_key, urldecode($message));

                    if($rand_str === $base64_decrypted_message){
                        $decrypted_card_data = decryptMessage($server_base64_private_key, $server_base64_public_key, $card_data);

                        if($decrypted_card_data){
                            $card_data_array = explode("__", $decrypted_card_data);
                            $user_id = $card_data_array[0];
                            $user_name = $card_data_array[1];

                            if(authenticateUser($user_id, $user_name, $device_name)){
                                registerStorageUnlockAttempt($device_name, $user_id, 'Sėkmingas bandymas');

                                $base64_encrypted_message = encryptMessage($device_data['public_key'], "unlock");
    
                                $data_array = [
                                    'type' => "auth_confirmation",
                                    'message' => $base64_encrypted_message
                                ];
                    
                                send_data($data_array, $device_data['address']);
                            } else{
                                registerStorageUnlockAttempt($device_name, $user_id, 'Nepavykęs bandymas');
                                echo htmlspecialchars("Naudotojo autentifikacijos klaida", ENT_QUOTES, 'UTF-8');
                            }
                        } else{
                            registerStorageUnlockAttempt($device_name, NULL, 'Nepavykęs bandymas');
                            echo htmlspecialchars("Duomenų iššifravimo klaida", ENT_QUOTES, 'UTF-8');
                        }
                    } else{
                        registerStorageUnlockAttempt($device_name, NULL, 'Nepavykęs bandymas');
                        echo htmlspecialchars("Įrenginio autentifikacijos klaida", ENT_QUOTES, 'UTF-8');
                    }
                } else{
                    registerStorageUnlockAttempt($device_name, NULL, 'Nepavykęs bandymas');
                    echo htmlspecialchars("Klaida", ENT_QUOTES, 'UTF-8');
                }
            }
        } else {
            header("HTTP/1.1 400 Bad Request");
            exit(); 
        }
    }
}




// $keypair = generateRandomKeyPair();
// $public_key = $keypair['public_key'];
// $private_key = $keypair['private_key'];

// $base64_public_key = base64_encode($public_key);
// $base64_private_key = base64_encode($private_key);

// $message = generateRandomString(10);

// $base64_encrypted_message = encryptMessage($public_key, $private_key, $message);
// decryptMessage($base64_public_key, $base64_private_key, $base64_encrypted_message);

?>