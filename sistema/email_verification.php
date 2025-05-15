<?php

header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

session_save_path("/tmp");
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
require_once 'config/config.php';
require_once 'config/functions.php';

if(isset($_GET['token']) && preg_match('/^[a-f0-9]{32}$/', $_GET['token'])) {
    $token = $_GET['token'];

    $stmt = mysqli_prepare($conn, "SELECT id 
                                    FROM users 
                                    WHERE verification_token = ?");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Tikrinama ar toks el. pašto adresas jau yra
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $user_id = $user['id'];

        $stmt = mysqli_prepare($conn, "UPDATE users
                                        SET verification_token = NULL
                                        WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        $_SESSION['verification_success'] = 'El. pašto adresas sėkmingai patvirtintas!';
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['login_error'] = 'Šis tokenas neegzistuoja arba jau yra panaudotas!';
        header("Location: index.php");
        exit();
    }
} else {
    http_response_code(404);
    exit();
}

?>