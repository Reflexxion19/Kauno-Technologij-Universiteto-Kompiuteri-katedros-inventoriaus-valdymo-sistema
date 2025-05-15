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

if (isset($_POST['add_inventory'])) {
    $name = $_POST['name'];
    $location = (int)$_POST['location'];
    $serial_number = $_POST['serial_number'];
    $inventory_number = $_POST['inventory_number'];
    $description = $_POST['description'];

    addInventory($name, $location, $serial_number, $inventory_number, $description);
}

$result = getLocations();

?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pridėti Inventorių</title>
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
                    <div class="col-6 mb-3">
                        <label for="inventory" class="form-label">Pavadinimas</label>
                        <input type="text" class="form-control" id="inventory" name="name" placeholder="Pvz.: Arduino UNO R3" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="location_select" class="form-label">Vieta</label>
                        <select class="form-select" id="location_select" name="location" aria-label="Location select" required>
                            <option value="">--Pasirinkti vietą--</option>
                        <?php
                        while($row = mysqli_fetch_assoc($result)){
                        ?>
                            <option value="<?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php
                        }
                        ?>
                        </select>

                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="serial_number" class="form-label">Serijinis numeris</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" placeholder="Pvz.: 6489878" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label for="inventory_number" class="form-label">Inventoriaus numeris</label>
                        <input type="text" class="form-control" id="inventory_number" name="inventory_number" placeholder="Pvz.: 1232165" required>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="description" class="form-label">Aprašymas</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col d-flex justify-content-end">
                        <button type="submit" class="btn btn-success" name="add_inventory">Pridėti įrenginį</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        const input_inventory_name = document.getElementById('inventory');
        const input_location_select = document.getElementById('location_select');
        const input_serial_number = document.getElementById('serial_number');
        const input_inventory_number = document.getElementById('inventory_number');
        const textarea_description = document.getElementById('description');

        input_inventory_name.setCustomValidity('Įveskite duomenis!');
        input_location_select.setCustomValidity('Įveskite duomenis!');
        input_serial_number.setCustomValidity('Įveskite duomenis!');
        input_inventory_number.setCustomValidity('Įveskite duomenis!');
        textarea_description.setCustomValidity('Įveskite duomenis!');

        input_inventory_name.addEventListener('input', function() {
            this.setCustomValidity('');
            if (!this.validity.valid) {
                this.setCustomValidity('Įveskite duomenis!');
            }
        });

        input_location_select.addEventListener('input', function() {
            this.setCustomValidity('');
            if (!this.validity.valid) {
                this.setCustomValidity('Įveskite duomenis!');
            }
        });

        input_serial_number.addEventListener('input', function() {
            this.setCustomValidity('');
            if (!this.validity.valid) {
                this.setCustomValidity('Įveskite duomenis!');
            }
        });

        input_inventory_number.addEventListener('input', function() {
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