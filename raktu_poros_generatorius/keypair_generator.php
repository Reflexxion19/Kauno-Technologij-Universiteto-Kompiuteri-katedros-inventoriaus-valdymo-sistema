<?php

    function generateRandomKeyPair() {
        $keypair = sodium_crypto_box_keypair();
    
        $public_key = sodium_crypto_box_publickey($keypair);
        $private_key = sodium_crypto_box_secretkey($keypair);
    
        return [
            'public_key' => $public_key,
            'private_key' => $private_key
        ];
    }

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
    
?>