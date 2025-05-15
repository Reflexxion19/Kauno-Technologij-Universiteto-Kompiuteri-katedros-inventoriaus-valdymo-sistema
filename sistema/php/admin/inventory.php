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

if (!isset($_SESSION['inventory_tab_state'])) {
    $_SESSION['inventory_tab_state'] = "inventory";
}

if (isset($_POST['delete_inventory'])) {
    deleteInventory($_POST['delete_inventory'], $_POST['name'], $_POST['serial_number'], $_POST['inventory_number']);
}

if (isset($_POST['delete_storage'])) {
    deleteStorage($_POST['delete_storage'], $_POST['name']);
}

$result_inventory = displayInventory();
$result_storage = displayStorage();
$path = "../../images/qr_codes/";
    
?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventorius</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="../../js/mdb.umd.min.js"></script>
    <script defer src="../../js/header.js"></script>
    <script defer src="../../js/search_table_2.js"></script>
    <script defer src="../../js/state.js"></script>
</head>
<body>
    <div class="container-md min-vh-100">
    <?php 
    if($_SESSION['success_message'] != ""){
    ?>
        <div class="mt-3 alert alert-success" role="alert">
            <?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php
    }
    ?>

    <?php 
    if($_SESSION['error_message'] != ""){
    ?>
        <div class="mt-3 alert alert-danger" role="alert">
        <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php
    }
    ?>
        <div class="modal fade" id="remove-confirmation-modal" tabindex="-1" aria-labelledby="remove-confirmation-modal" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="remove-confirmation-modal-label"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmation_btn">Taip</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ne</button>
                </div>
                </div>
            </div>
        </div>

        <div class="row <?php echo ($_SESSION['success_message'] === "" && $_SESSION['error_message'] === "") ? "mt-5" : "" ?> mb-3 d-flex justify-content-end">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['inventory_tab_state'] === "inventory") ? "active" : "" ?> border-primary border-2" 
                    id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory-tab-pane" type="button" role="tab" aria-controls="inventory-tab-pane" 
                    aria-selected="true" onclick="saveState('inventory_tab_state', 'inventory')">Įrenginiai</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['inventory_tab_state'] === "storage") ? "active" : "" ?> border-primary border-2" 
                    id="storage-tab" data-bs-toggle="tab" data-bs-target="#storage-tab-pane" type="button" role="tab" aria-controls="storage-tab-pane" 
                    aria-selected="false" onclick="saveState('inventory_tab_state', 'storage')">Talpyklos</button>
                </li>
            </ul>
            <div class="tab-content border border-2 rounded-bottom border-primary" id="myTabContent">
                <div class="tab-pane fade <?php echo ($_SESSION['inventory_tab_state'] === "inventory") ? "show active" : "" ?>" id="inventory-tab-pane" 
                    role="tabpanel" aria-labelledby="inventory-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <h3 class="d-flex justify-content-center">Įrenginiai</h3>
    
                            <div class="col-12 mt-3">
                                <div class="input-group">
                                    <div class="form-outline" data-mdb-input-init>
                                        <input type="search" id="search-box" class="form-control" onkeyup="search()"/>
                                        <label class="form-label" for="search-box">Ieškoti</label>
                                    </div>
                                    <button type="button" class="btn btn-success ms-1" onClick="document.location.href='add_inventory.php'">PRIDĖTI ĮRENGINĮ</button>
                                </div>
                            </div>

                            <div class="col">
                                <table class="table" id="table-inventory">
                                    <thead>
                                        <tr>
                                            <th scope="col"><b>Pavadinimas</b></th>
                                            <th scope="col"><b>Serijinis Numeris</b></th>
                                            <th scope="col"><b>Inventoriaus Numeris</b></th>
                                            <th scope="col"><b>Statusas</b></th>
                                            <th scope="col" class="col-1"><b>Lipdukas</b></th>
                                            <th scope="col" class="col-1"><b>Veiksmai</b></th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-group-divider">
                                    <?php
                                    while($row = mysqli_fetch_assoc($result_inventory)){
                                    ?>
                                        <tr style="cursor: pointer;" data-id="<?= $row['id'] ?>" onclick="redirect(event)">
                                            <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['serial_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['inventory_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <?php
                                        if(checkInventoryAvailability($row['id'])){
                                    ?>
                                            <td>LAISVAS</td>
                                    <?php
                                        } else {
                                    ?>
                                            <td>PASISKOLINTAS</td>
                                    <?php
                                        }
                                    ?>
                                            <td><a class="d-flex justify-content-center" id="download" href="<?= $path . $row['sticker_path'] ?>" 
                                            download="<?= $row['sticker_path'] ?>"><?php echo '<img style="width: 50%;" src="' . $path . $row['sticker_path'] . '" />' ?></a></td>
                                            <td><button type="button" class="btn btn-danger" id="btn"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    <?php
                                    }
                                    $_SESSION['success_message'] = "";
                                    $_SESSION['error_message'] = "";
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo ($_SESSION['inventory_tab_state'] === "storage") ? "show active" : "" ?>" id="storage-tab-pane" 
                    role="tabpanel" aria-labelledby="storage-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <h3 class="d-flex justify-content-center">Talpyklos</h3>
    
                            <div class="col-12 mt-3">
                                <div class="input-group">
                                    <div class="form-outline" data-mdb-input-init>
                                        <input type="search" id="search-box-storage" class="form-control" onkeyup="searchStorage()"/>
                                        <label class="form-label" for="search-box-storage">Ieškoti</label>
                                    </div>
                                    <button type="button" class="btn btn-success ms-1" onClick="document.location.href='add_storage.php'">PRIDĖTI TALPYKLĄ</button>
                                </div>
                            </div>

                            <div class="col">
                                <table class="table" id="table-storage">
                                    <thead>
                                        <tr>
                                            <th scope="col"><b>Pavadinimas</b></th>
                                            <th scope="col" class="col-1"><b>Lipdukas</b></th>
                                            <th scope="col" class="col-1"><b>Veiksmai</b></th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-group-divider">
                                    <?php
                                    while($row = mysqli_fetch_assoc($result_storage)){
                                    ?>
                                        <tr style="cursor: pointer;" data-id="<?= $row['id'] ?>" onclick="redirect(event)">
                                            <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><a class="d-flex justify-content-center" id="download_storage" href="<?= $path . $row['sticker_path'] ?>" 
                                            download="<?= $row['sticker_path'] ?>"><?php echo '<img style="width: 50%;" src="' . $path . $row['sticker_path'] . '" />' ?></a></td>
                                            <td><button type="button" class="btn btn-danger" id="btn-storage"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    <?php
                                    }
                                    $_SESSION['success_message'] = "";
                                    $_SESSION['error_message'] = "";
                                    ?>
                                    </tbody>
                                </table>
                            </div>  
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function redirect(event) {
            let row;
            let row_data = {};

            if (event.target.id === "download" || event.target.id === "download_storage" || 
                event.target.closest("#download") || event.target.closest("#download_storage")) {
                return;
            } else if (event.target.closest("#btn")) {
                row = event.currentTarget;
                row_data = {
                    id: row.dataset.id,
                    name: row.getElementsByTagName('td')[0].textContent,
                    serial_number: row.getElementsByTagName('td')[1].textContent,
                    inventory_number: row.getElementsByTagName('td')[2].textContent
                };

                showRemoveInventoryModal(row_data).then((confirmed) => {
                    if (confirmed) {
                        const form = document.createElement("form");
                        form.method = "POST";
                        form.action = "inventory.php";

                        const input_delete = document.createElement('input');
                        input_delete.type = "hidden";
                        input_delete.name = "delete_inventory";
                        input_delete.value = row_data.id;

                        const input_name = document.createElement('input');
                        input_name.type = "hidden";
                        input_name.name = "name";
                        input_name.value = row_data.name;

                        const input_serial_number = document.createElement('input');
                        input_serial_number.type = "hidden";
                        input_serial_number.name = "serial_number";
                        input_serial_number.value = row_data.serial_number;

                        const input_inventory_number = document.createElement('input');
                        input_inventory_number.type = "hidden";
                        input_inventory_number.name = "inventory_number";
                        input_inventory_number.value = row_data.inventory_number;

                        form.appendChild(input_delete);
                        form.appendChild(input_name);
                        form.appendChild(input_serial_number);
                        form.appendChild(input_inventory_number);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
                return;
            } else if (event.target.closest("#btn-storage")) {
                row = event.currentTarget;
                row_data = {
                    id: row.dataset.id,
                    name: row.getElementsByTagName('td')[0].textContent
                };

                showRemoveStorageModal(row_data).then((confirmed) => {
                    if (confirmed) {
                // if(confirm("Ar tikrai norite ištrinti šį talpyklos įrašą?")) {
                        const form = document.createElement("form");
                        form.method = "POST";
                        form.action = "inventory.php";

                        const input_delete = document.createElement('input');
                        input_delete.type = "hidden";
                        input_delete.name = "delete_storage";
                        input_delete.value = row_data.id;

                        const input_name = document.createElement('input');
                        input_name.type = "hidden";
                        input_name.name = "name";
                        input_name.value = row_data.name;

                        form.appendChild(input_delete);
                        form.appendChild(input_name);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
                return;
            }

            if (event.target.closest("#inventory-tab-pane")) {
                window.location.href = 'inventory_information.php?inventory_id=' + event.currentTarget.dataset.id;
            } else if(event.target.closest("#storage-tab-pane")) {
                window.location.href = 'storage_information.php?storage_id=' + event.currentTarget.dataset.id;
            }
        }

        function showRemoveInventoryModal() {
            return new Promise((resolve) => {
                var storage_remove_modal = document.getElementById('remove-confirmation-modal');
                storage_remove_modal.querySelector('h5').textContent = "Inventoriaus įrašo ištrynimo patvirtinimas";
                storage_remove_modal.querySelector('.modal-body').textContent = "Ar tikrai norite ištrinti šį inventoriaus įrašą?";

                const modal = new bootstrap.Modal(storage_remove_modal);
                modal.show();

                const confirm_button = document.getElementById('confirmation_btn');

                confirm_button.addEventListener('click', function() {
                    modal.hide();
                    resolve(true);
                });

                const cancel_button = document.querySelector('.btn-secondary');
                cancel_button.addEventListener('click', function() {
                    modal.hide();
                    resolve(false);
                });
            });
        }

        function showRemoveStorageModal() {
            return new Promise((resolve) => {
                var storage_remove_modal = document.getElementById('remove-confirmation-modal');
                storage_remove_modal.querySelector('h5').textContent = "Talpyklos įrašo ištrynimo patvirtinimas";
                storage_remove_modal.querySelector('.modal-body').textContent = "Ar tikrai norite ištrinti šį talpyklos įrašą?";

                const modal = new bootstrap.Modal(storage_remove_modal);
                modal.show();

                const confirm_button = document.getElementById('confirmation_btn');

                confirm_button.addEventListener('click', function() {
                    modal.hide();
                    resolve(true);
                });

                const cancel_button = document.querySelector('.btn-secondary');
                cancel_button.addEventListener('click', function() {
                    modal.hide();
                    resolve(false);
                });
            });
        }
    </script>
</body>
</html>

<?php include '../../includes/footer_admin.php'; ?>