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

$reset_email_error = $_SESSION['reset_email_error'] ?? '';
$reset_email_success = $_SESSION['reset_email_success'] ?? '';
session_unset();

function showError($error) {
    return !empty($error) ? "<p class='error-message'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>" : '';
}

function showSuccess($success) {
    return !empty($success) ? "<p class='success-message'>" . htmlspecialchars($success, ENT_QUOTES, 'UTF-8') . "</p>" : '';
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
            <form id="password-reset-form" action="pw_reset.php" method="post">
                <h2>Atstatyti slaptažodį</h2>
                <?= showError($reset_email_error); ?>
                <?= showSuccess($reset_email_success); ?>
                <input type="email" name="email" placeholder="El. paštas" id="email" required>
                <button type="submit" name="reset">Atstatyti</button>
            </form>
        </div>
    </div>
</body>
</html>