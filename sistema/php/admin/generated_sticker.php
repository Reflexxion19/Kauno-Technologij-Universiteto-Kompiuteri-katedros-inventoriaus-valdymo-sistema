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

if(!isset($_SESSION['email'])) {
    header("Location: ../../index.php");
    exit();
}

if($_SESSION['role'] != 'admin'){
    header("Location: ../../index.php");
    exit();
}

$path = "../../images/qr_codes/";
    
?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugeneruotas Kodas</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="../../js/header.js"></script>
</head>
<body>
    <div class="container-md min-vh-100">
        <div class="row mt-5 mb-3 d-flex justify-content-center">
            <div class="col-4">
                <?php echo '<img style="width: 100%;" src="' . $path . $_SESSION['generated_sticker'] . '" />' ?>
            </div>
        </div>

        <div class="row d-flex justify-content-center">
            <div class="col-4 d-flex justify-content-center">
                <a class="btn btn-success" id="download" href="<?= $path . $_SESSION['generated_sticker'] ?>" download="hello">Parsisiųsti</a>
            </div>
        </div>
    </div>
    <?php
        $_SESSION['generated_sticker'] = "";
    ?>
</body>
</html>

<?php include '../../includes/footer_admin.php'; ?>