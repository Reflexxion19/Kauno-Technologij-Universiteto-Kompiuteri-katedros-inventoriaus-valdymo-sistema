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
require_once '../config/config.php';
require_once '../config/functions.php';

// Tikrinama ar vartotojas paspaudė registracijos mygtuką
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $academic_group = $_POST['academic_group'];
    $email = $_POST['email_register'];
    $password = $_POST['password_register'];
    $repeated_password = $_POST['repeated_password_register'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $email_parts = preg_split('/[.@]/', $email);
    $verification_token = md5(uniqid(rand(), true));

    $full_name = $name . " " . $surname;
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    $name_pattern = '/^[a-zA-ZąčęėįšųūžĄČĘĖĮŠŲŪŽ]+$/';

    // Tikrinama ar vardas ir pavardė sudaryti tik iš raidžių
    if (preg_match($name_pattern, $name) && preg_match($name_pattern, $surname)) {
        // Tikrinama ar slaptažodis sudarytas iš 8 simbolių ir turi bent vieną didžiąjąją raidę, mažąjąją raidę, skaičių ir specialų simbolį
        if (preg_match($pattern, $password)) {
            // Tikinama ar slaptažodžiai sutampa
            if ($password === $repeated_password) {
                // Tikrinama ar el. pašto adresas yra išduotas KTU bei ar tai yra darbuotojo arba studento el. pašto adresas
                if (count($email_parts) === 4) {
                    if ($email_parts[2] === 'ktu' && $email_parts[3] === 'lt') {
                        $stmt = mysqli_prepare($conn, "SELECT email 
                                                        FROM users 
                                                        WHERE email = ?");
                        mysqli_stmt_bind_param($stmt, "s", $email);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        // Tikrinama ar toks el. pašto adresas jau yra
                        if (mysqli_num_rows($result) > 0) {
                            $_SESSION['register_error'] = 'Paskyra su tokiu el. pašto adresu jau yra!';
                            $_SESSION['active_form'] ='register';
                        } else {
                            $stmt = mysqli_prepare($conn, "INSERT INTO users (`name`, email, `password`, `role`, verification_token)
                                                            VALUES (?, ?, ?, 'employee', ?)");
                            mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $hashed_password, $verification_token);
                            mysqli_stmt_execute($stmt);
                            $affected_rows = mysqli_stmt_affected_rows($stmt);

                            if ($affected_rows > 0) {
                                if(sendEmailVerificationMail($email, $verification_token)){
                                    $_SESSION['registration_success'] = 'Paskyra sėkmingai sukurta! Elektroniniu paštu išsiųstas patvirtinimo laiškas!';
                                    $_SESSION['active_form'] ='login';
                                } else {
                                    $_SESSION['register_error'] = 'Nepavyko išsiųsti el. pašto patvirtinimo laiško! Bandykite dar kartą arba susisiekite su sistemos administratoriumi!';
                                    $_SESSION['active_form'] ='register';
                                }
                            }
                        }
                    } elseif ($email_parts[2] === 'ktu' && $email_parts[3] === 'edu') {
                        if (strlen($academic_group) !== 0){
                            $stmt = mysqli_prepare($conn, "SELECT email 
                                                            FROM users 
                                                            WHERE email = ?");
                            mysqli_stmt_bind_param($stmt, "s", $email);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);

                            // Tikrinama ar toks el. pašto adresas jau yra
                            if (mysqli_num_rows($result) > 0) {
                                $_SESSION['register_error'] = 'Paskyra su tokiu el. pašto adresu jau yra!';
                                $_SESSION['active_form'] ='register';
                            } else {
                                $stmt = mysqli_prepare($conn, "INSERT INTO users (`name`, email, `password`, `role`, academic_group, verification_token)
                                                                VALUES (?, ?, ?, 'student', ?, ?)");
                                mysqli_stmt_bind_param($stmt, "sssss", $full_name, $email, $hashed_password, $academic_group, $verification_token);
                                mysqli_stmt_execute($stmt);
                                $affected_rows = mysqli_stmt_affected_rows($stmt);

                                if ($affected_rows > 0) {
                                    if(sendEmailVerificationMail($email, $verification_token)){
                                        $_SESSION['registration_success'] = 'Paskyra sėkmingai sukurta! Elektroniniu paštu išsiųstas patvirtinimo laiškas!';
                                        $_SESSION['active_form'] ='login';
                                    } else {
                                        $_SESSION['register_error'] = 'Nepavyko išsiųsti el. pašto patvirtinimo laiško! Bandykite dar kartą arba susisiekite su sistemos administratoriumi!';
                                        $_SESSION['active_form'] ='register';
                                    }
                                }
                            }
                        } else {
                            $_SESSION['register_error'] = 'Akademinės grupės kodas negali būti tuščias!';
                            $_SESSION['active_form'] ='register';
                        }
                    } else {
                        $_SESSION['register_error'] = 'Priimtini tik KTU išduoti el. pašto adresai!';
                        $_SESSION['active_form'] ='register';
                    }
                } else {
                    $_SESSION['register_error'] = 'El. pašto adresas turi atitikti KTU išduoto ilgojo el. pašto adreso formatą!';
                    $_SESSION['active_form'] ='register';
                }
            } else {
                $_SESSION['register_error'] = 'Slaptažodžiai nesutampa!';
                $_SESSION['active_form'] ='register';
            }
        } else {
            $_SESSION['register_error'] = 'Slaptažodis turi būti sudarytas bent iš 8 simbolių ir turėti 
            bent vieną didžiąją raidę, mažąją raidę, skaičių ir specialų simbolį!';
            $_SESSION['active_form'] ='register';
        }
    } else {
        $_SESSION['register_error'] = 'Vardas ir pavardė turi būti sudaryti tik iš raidžių!';
        $_SESSION['active_form'] ='register';
    }

    header("Location: ../index.php");
    exit();
}

// Tikrinama ar vartotojas paspaudė prisijungimo mygtuką
if (isset($_POST['login'])) {
    $email = $_POST['email_login'];
    $password = $_POST['password_login'];

    $stmt = mysqli_prepare($conn, "SELECT * 
                                    FROM users 
                                    WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Tikrinama ar el. pašto adresas yra duomenų bazėje
    if(mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        // Tikrinama ar slaptažodis atitinka duomenų bazėje esantį slaptažodį
        if ($user['verification_token'] === NULL) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['academic_group'] = $user['academic_group'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
    
                // Inicijuojami reikalingi sesijos kintamieji
                $_SESSION['success_message'] = "";
                $_SESSION['error_message'] = "";
    
                // Tikrinama ar vartotojas turi admin, employee ar student rolę
                if ($user['role'] === 'admin') {
                    header("Location: ../php/admin/inventory.php");
                } elseif ($user['role'] === 'employee') {
                    header("Location: ../php/employee/loans.php");
                } elseif ($user['role'] === 'student') {
                    header("Location: ../php/student/student_loans.php");
                }
                exit();
            }
        } else {
            $_SESSION['login_error'] = 'Jūs dar nepatvirtinote savo el. pašto adreso! Patvirtinkite el. pašto adresą, kad galėtumėte prisijungti!';
            $_SESSION['active_form'] = 'login';
        }
    } else {
        $_SESSION['login_error'] = 'Neteisingas el. pašto adresas arba slaptažodis!';
        $_SESSION['active_form'] = 'login';
    }

    header("Location: ../index.php");
    exit();
}

?>