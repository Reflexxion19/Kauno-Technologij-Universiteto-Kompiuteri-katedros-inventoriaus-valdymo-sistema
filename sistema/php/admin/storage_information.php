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

$storage_id = -1;
$row = array();

if (isset($_GET['storage_id'])) {
    $storage_id = $_GET['storage_id'];
    $row = getStorageById($storage_id);
}

if (isset($_POST['update_storage'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $lock_name = $_POST['lock_name'];
    $lock_public_key = $_POST['lock_public_key'];
    $lock_address = $_POST['lock_address'];

    updateStorage($name, $description, $lock_name, $lock_public_key, $lock_address, $storage_id);
}

if (isset($_POST['delete_storage'])) {
    deleteStorage($storage_id, $row['name']);
}

$path = "../../images/qr_codes/";

?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talpyklos Informacija</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="../../js/header.js"></script>
</head>
<body>
    <div class="container-md min-vh-100">
    <?php 
    if($_SESSION['error_message'] != ""){
    ?>
        <div class="mt-3 alert alert-danger" role="alert">
        <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php
    }
    ?>
        <div class="row <?php echo ($_SESSION['error_message'] === "") ? "mt-5" : ""; $_SESSION['error_message'] = ""; ?>">
            <form id="form" method="post">
                <div class="row">
                    <div class="col mb-3">
                        <label for="storage" class="form-label">Talpyklos pavadinimas</label>
                        <input type="text" class="form-control" id="storage" name="name" placeholder="Pvz.: Arduino UNO R3" value="<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>" required disabled>
                    </div>
                    <div class="col mb-3">
                        <label for="lock_name" class="form-label">Elektroninio užrakto pavadinimas</label>
                        <input type="text" class="form-control" id="lock_name" name="lock_name" placeholder="Pvz.: Mk1" value="<?= htmlspecialchars($row['device_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="lock_public_key" class="form-label">Elektroninio užrakto viešasis raktas</label>
                        <input type="text" class="form-control" id="lock_public_key" name="lock_public_key" value="<?= htmlspecialchars($row['public_key'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="lock_address" class="form-label">Elektroninio užrakto adresas</label>
                        <input type="text" class="form-control" id="lock_address" name="lock_address" value="<?= htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                    </div>
                </div>
                <div class="row">
                    <div class="col mb-3">
                        <label for="description" class="form-label">Aprašymas</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required disabled><?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col d-flex justify-content-end">
                        <button type="button" class="btn btn-warning mx-1" name="edit_storage" onclick="enableFields(true)">Redaguoti</button>
                        <button type="submit" class="btn btn-success mx-1" name="update_storage" style="display: none;">Atnaujinti</button>
                        <button type="button" class="btn btn-warning mx-1" name="cancel_storage" style="display: none;" onclick="enableFields(false)">Atšaukti</button>
                        <button type="submit" class="btn btn-danger ms-1" name="delete_storage"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        function enableFields(option) {
            inputs = document.getElementsByTagName("input");
            textArea = document.getElementById("description");

            for (i = 0; i < inputs.length; i++) {
                inputs[i].disabled = !option
            }

            textArea.disabled = !option;

            if(option) {
                document.getElementsByName("edit_storage")[0].style.display = "none";
                document.getElementsByName("update_storage")[0].style.display = "unset";
                document.getElementsByName("cancel_storage")[0].style.display = "unset";
            } else {
                document.getElementsByName("edit_storage")[0].style.display = "unset";
                document.getElementsByName("update_storage")[0].style.display = "none";
                document.getElementsByName("cancel_storage")[0].style.display = "none";
            }

            if(!option) {
                location.reload(); 
            }
        }
    </script>
</body>
</html>

<?php include '../../includes/footer_admin.php'; ?>