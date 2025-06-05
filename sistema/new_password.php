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

if (!isset($_SESSION['password_reset_error'])){
    $_SESSION['password_reset_error'] = "";
}

if (isset($_POST['reset']) && isset($_POST['password']) && isset($_POST['repeated_password']) && $_SESSION['id'] != null) {
    $password = $_POST['password'];
    $repeated_password = $_POST['repeated_password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if($password === $repeated_password) {
        $stmt = mysqli_prepare($conn, "UPDATE users
                                        SET `password` = ?, password_reset_token = NULL, reset_token_expiration = NULL
                                        WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_SESSION['id']);
        mysqli_stmt_execute($stmt);

        unset($_SESSION['id']);
        $_SESSION['verification_success'] = 'Slaptažodis sėkmingai atstatytas!';
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['password_reset_error'] = 'Slaptažodžiai nesutampa!';
    }
} elseif (isset($_GET['token']) && preg_match('/^[a-f0-9]{32}$/', $_GET['token'])) {
    $token = $_GET['token'];

    $stmt = mysqli_prepare($conn, "SELECT id
                                    FROM users
                                    WHERE password_reset_token = ? AND reset_token_expiration > NOW()");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        $_SESSION['login_error'] = 'Šis tokenas neegzistuoja arba jau yra panaudotas!';
        header("Location: index.php");
        exit();
    } else {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['id'] = $user['id'];
    }
} else {
    http_response_code(404);
    exit();
}

function showError($error) {
    unset($_SESSION['password_reset_error']);
    return !empty($error) ? "<p class='error-message'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>" : '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTU IVS</title>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

    <div class="container">
        <div class="form-box active" id="password-reset">
            <form id="password-reset-form" method="post">
                <h2>Naujas slaptažodis</h2>
                <?= showError($_SESSION['password_reset_error']); ?>
                <input type="password" name="password" placeholder="Naujas slaptažodis"
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[#?!@$%^&*\-\[\]]).{8,}" id="password" required>
                <input type="password" name="repeated_password" placeholder="Pakartokite slaptažodį" id="repeated_password" required>
                <button type="submit" name="reset">Pateikti</button>
            </form>
        </div>
    </div>
    <script>
        const input_password = document.getElementById('password');
        const input_repeated_password = document.getElementById('repeated_password');

        input_password.addEventListener('change', (ev) => {
          input_repeated_password.setAttribute('pattern', ev.target.value);
        });
    </script>
</body>
</html>