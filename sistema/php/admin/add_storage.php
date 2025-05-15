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
require_once '../../config/functions.php';

if(!isset($_SESSION['email'])) {
    header("Location: ../../index.php");
    exit();
}

if($_SESSION['role'] != 'admin'){
    header("Location: ../../index.php");
    exit();
}

if (isset($_POST['add_storage'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $lock_name = $_POST['lock_name'];
    $lock_public_key = $_POST['lock_public_key'];
    $lock_address = $_POST['lock_address'];

    addStorage($name, $description, $lock_name, $lock_public_key, $lock_address);
}

?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pridėti Inventoriaus Talpyklą</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="../../js/header.js"></script>
</head>
<body>
    <div class="container-md min-vh-100">
    <?php 
    if($_SESSION['error_message'] !== ""){
    ?>
        <div class="mt-3 alert alert-danger" role="alert">
        <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php
    }
    ?>
        <div class="row <?php echo ($_SESSION['error_message'] === "") ? "mt-5" : ""; $_SESSION['error_message'] = ""; ?>">
            <form method="post">
                <div class="row">
                    <div class="col mb-3">
                        <label for="storage" class="form-label">Talpyklos pavadinimas</label>
                        <input type="text" class="form-control" id="storage" name="name" placeholder="Pvz.: 208-1" required>
                    </div>
                    <div class="col mb-3">
                        <label for="lock_name" class="form-label">Elektroninio užrakto pavadinimas</label>
                        <input type="text" class="form-control" id="lock_name" name="lock_name" placeholder="Pvz.: Mk1">
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="lock_public_key" class="form-label">Elektroninio užrakto viešasis raktas</label>
                        <input type="text" class="form-control" id="lock_public_key" name="lock_public_key">
                    </div>
                    <div class="col-6 mb-3">
                        <label for="lock_address" class="form-label">Elektroninio užrakto adresas</label>
                        <input type="text" class="form-control" id="lock_address" name="lock_address">
                    </div>
                </div>
                <div class="row">
                    <div class="col mb-3">
                        <label for="description" class="form-label">Aprašymas</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col d-flex justify-content-end">
                        <button type="submit" class="btn btn-success" name="add_storage">Pridėti talpyklą</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        const input_storage_name = document.getElementById('storage');
        const textarea_description = document.getElementById('description');

        input_storage_name.setCustomValidity('Įveskite duomenis!');
        textarea_description.setCustomValidity('Įveskite duomenis!');

        input_storage_name.addEventListener('input', function() {
            this.setCustomValidity('');
            if (!this.validity.valid) {
                this.setCustomValidity('Įveskite duomenis!');
            }
        });

        textarea_description.addEventListener('input', function() {
            this.setCustomValidity('');
            if (!this.validity.valid) {
                this.setCustomValidity('Įveskite duomenis!');
            }
        });
    </script>
</body>
</html>

<?php include '../../includes/footer_admin.php'; ?>