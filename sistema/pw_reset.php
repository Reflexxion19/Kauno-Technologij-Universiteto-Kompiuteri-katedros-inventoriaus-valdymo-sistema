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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    $stmt = mysqli_prepare($conn, "SELECT id
                                    FROM users
                                    WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $id = $user['id'];

        $token = md5(uniqid(rand(), true));

        date_default_timezone_set("Europe/Vilnius");
        $expiration = date("Y-m-d H:i:s", strtotime("+1 hour"));

        mysqli_begin_transaction($conn);

        try{

            $stmt = mysqli_prepare($conn, "UPDATE users
                                            SET password_reset_token = ?, reset_token_expiration = ?
                                            WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $token, $expiration, $id);
            mysqli_stmt_execute($stmt);

            if(sendPasswordResetMail($email, $token)) {
                $_SESSION['verification_success'] = 'Nurodytu el. paštu išsiųstas paskyros atstatymo laiškas!';
                mysqli_commit($conn);

                header("Location: index.php");
                exit();
            } else {
                $_SESSION['reset_email_error'] = 'Nepavyko išsiųsti slaptažodžio atstatymo laiško nurodytu el. paštu!';
                header("Location: password_reset.php");
                exit();
            }
        } catch(Exception $e){
            $_SESSION['reset_email_error'] = 'Nepavyko išsiųsti slaptažodžio atstatymo laiško nurodytu el. paštu!';
            mysqli_rollback($conn);

            header("Location: password_reset.php");
            exit();
        }
    } else {
        $_SESSION['reset_email_error'] = 'Paskyra su tokiu el. pašto adresu nerasta!';
        header("Location: password_reset.php");
        exit();
    }
}

?>