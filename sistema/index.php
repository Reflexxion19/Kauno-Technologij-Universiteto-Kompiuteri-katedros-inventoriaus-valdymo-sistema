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

if(isset($_SESSION['email']) && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    header("Location: php/admin/inventory.php");
    exit();
}

if(isset($_SESSION['email']) && isset($_SESSION['role']) && $_SESSION['role'] == 'employee') {
    header("Location: php/employee/loans.php");
    exit();
}

if(isset($_SESSION['email']) && isset($_SESSION['role']) && $_SESSION['role'] == 'student') {
    header("Location: php/student/student_loans.php");
    exit();
}

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$activeForm = $_SESSION['active_form'] ?? 'login';
$registration_success = $_SESSION['registration_success'] ?? '';
$verification_success = $_SESSION['verification_success'] ?? '';

session_unset();

function showError($error) {
    return !empty($error) ? "<p class='error-message'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>" : '';
}

function showSuccess($success) {
    return !empty($success) ? "<p class='success-message'>" . htmlspecialchars($success, ENT_QUOTES, 'UTF-8') . "</p>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
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
        <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
            <form id = login_form action="php_login_register/login_register.php" method="post">
                <h2>Prisijungti</h2>
                <?= showError($errors['login']); ?>
                <?= showSuccess($registration_success); ?>
                <?= showSuccess($verification_success); ?>
                <input type="email" name="email_login" placeholder="El. paštas" id="email_login" required>
                <input type="password" name="password_login" placeholder="Slaptažodis" id="password_login" required>
                <button type="submit" name="login">Prisijungti</button>
                <p>Pamiršote slaptažodį? <a href="password_reset.php">Atstatykite</a></p>
                <p>Neturite paskyros? <a href="#" onclick="showForm('register-form')">Prisiregistruokite</a></p>
            </form>
        </div>

        <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
            <form id = registration_form action="php_login_register/login_register.php" method="post">
                <h2>Prisiregistruoti</h2>
                <?= showError($errors['register']); ?>
                <input type="text" name="name" placeholder="Vardas" id="name" pattern="^[a-zA-ZąčęėįšųūžĄČĘĖĮŠŲŪŽ]+$" required>
                <input type="text" name="surname" placeholder="Pavardė" id="surname" pattern="^[a-zA-ZąčęėįšųūžĄČĘĖĮŠŲŪŽ]+$" required>
                <input type="text" name="academic_group" placeholder="Akademinė grupė" id="academic_group" pattern=".{3,}$">
                <input type="email" name="email_register" placeholder="El. paštas" id="email_register" pattern="[a-z]+\.+[a-z]+@ktu+\.(edu|lt)$" required>
                <input type="password" name="password_register" placeholder="Slaptažodis"
                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[#?!@$%^&*\-\[\]]).{8,}" id="password_register" required>
                <input type="password" name="repeated_password_register" placeholder="Pakartokite slaptažodį" id="repeated_password" required>
                <button type="submit" name="register">Prisiregistruoti</button>
                <p>Jau turite paskyrą? <a href="#" onclick="showForm('login-form')">Prisijungti</a></p>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script src="js/validation.js"></script>
</body>
</html>