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

$inventory_id = -1;
$row_inventory = array();
$result_locations = array();

if (isset($_GET['inventory_id'])) {
    $inventory_id = $_GET['inventory_id'];
    $row_inventory = getInventoryById($inventory_id);
    $result_locations = getLocations();
}

if (isset($_POST['update_inventory'])) {
    $name = $_POST['name'];
    $location = (int)$_POST['location'];
    $serial_number = $_POST['serial_number'];
    $inventory_number = $_POST['inventory_number'];
    $description = $_POST['description'];

    updateInventory($name, $location, $serial_number, $inventory_number, $description, $inventory_id);
}

if (isset($_POST['delete_inventory'])) {
    deleteInventory($inventory_id, $row_inventory['name'], $row_inventory['serial_number'], $row_inventory['inventory_number']);
}

$path = "../../images/qr_codes/";

?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventoriaus Informacija</title>
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
            <form id="form" method="post">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="inventory" class="form-label">Pavadinimas</label>
                        <input type="text" class="form-control" id="inventory" name="name" placeholder="Pvz.: Arduino UNO R3" value="<?= htmlspecialchars($row_inventory['name'], ENT_QUOTES, 'UTF-8') ?>" required disabled>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="location_select" class="form-label">Vieta</label>
                        <select class="form-select" id="location_select" name="location" aria-label="Location select" required disabled>
                        <?php
                        while($row_locations = mysqli_fetch_assoc($result_locations)){
                        ?>
                            <option <?php echo (htmlspecialchars($row_inventory['fk_inventory_location_id'], ENT_QUOTES, 'UTF-8') === htmlspecialchars($row_locations['id'], ENT_QUOTES, 'UTF-8')) ? "selected" : "" ?> 
                                value="<?= htmlspecialchars($row_locations['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row_locations['name'], ENT_QUOTES, 'UTF-8')?></option>
                        <?php
                        }
                        ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="serial_number" class="form-label">Serijinis numeris</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" placeholder="Pvz.: 6489878" value="<?= htmlspecialchars($row_inventory['serial_number'], ENT_QUOTES, 'UTF-8') ?>" required disabled>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="inventory_number" class="form-label">Inventoriaus numeris</label>
                        <input type="text" class="form-control" id="inventory_number" name="inventory_number" placeholder="Pvz.: 1232165" value="<?= htmlspecialchars($row_inventory['inventory_number'], ENT_QUOTES, 'UTF-8') ?>" required disabled>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="description" class="form-label">Aprašymas</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required disabled><?= htmlspecialchars($row_inventory['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col d-flex justify-content-end">
                        <button type="button" class="btn btn-warning mx-1" name="edit_inventory" onclick="enableFields(true)">Redaguoti</button>
                        <button type="submit" class="btn btn-success mx-1" name="update_inventory" style="display: none;">Atnaujinti</button>
                        <button type="button" class="btn btn-warning mx-1" name="cancel_inventory" style="display: none;" onclick="enableFields(false)">Atšaukti</button>
                        <button type="submit" class="btn btn-danger ms-1" name="delete_inventory"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        function enableFields(option) {
            form = document.getElementById("form");
            inputs = form.getElementsByTagName("input");
            select = document.getElementById("location_select");
            textArea = document.getElementById("description");

            for (i = 0; i < inputs.length; i++) {
                inputs[i].disabled = !option;
            }

            select.disabled = !option;
            textArea.disabled = !option;

            if(option) {
                document.getElementsByName("edit_inventory")[0].style.display = "none";
                document.getElementsByName("update_inventory")[0].style.display = "unset";
                document.getElementsByName("cancel_inventory")[0].style.display = "unset";
            } else {
                document.getElementsByName("edit_inventory")[0].style.display = "unset";
                document.getElementsByName("update_inventory")[0].style.display = "none";
                document.getElementsByName("cancel_inventory")[0].style.display = "none";
            }

            if(!option) {
                location.reload(); 
            }
        }
    </script>
</body>
</html>

<?php include '../../includes/footer_admin.php'; ?>