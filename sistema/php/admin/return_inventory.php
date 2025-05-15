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

if (!isset($_SESSION['return_inventory_tab_state'])) {
    $_SESSION['return_inventory_tab_state'] = "open_storage";
}

if(isset($_POST['unlock_storage'])){
    $unlock_storage_id_code = $_POST['unlock_storage_id_code'];

    unlockStorage($unlock_storage_id_code);
}

if(isset($_POST['return'])){
    $return_id_code = $_POST['return_id_code'];
    $user_id = $_SESSION['user_id'];

    returnInventory($return_id_code, $user_id);
}

$tab1 = "storage-tab";
$tab2 = "return-tab";
$reader1 = "reader-storage";
$reader2 = "reader-ret";
$input1 = "identification_code_storage";
$input2 = "identification_code_return";

?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grąžinti Inventorių</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="../../js/header.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
    <script defer src="../../js/qr_barcode_reader.js"></script>
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
        <div class="row <?php echo ($_SESSION['success_message'] === "" && $_SESSION['error_message'] === "") ? "mt-5" : "" ?> mb-3 d-flex justify-content-end">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['return_inventory_tab_state'] === "open_storage") ? "active" : "" ?> border-primary border-2" 
                    id="storage-tab" data-bs-toggle="tab" data-bs-target="#storage-tab-pane" type="button" role="tab" aria-controls="storage-tab-pane" 
                    aria-selected="true" onclick="saveState('return_inventory_tab_state', 'open_storage')">Atrakinti Talpyklą</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['return_inventory_tab_state'] === "return_inventory") ? "active" : "" ?> border-primary border-2" 
                    id="return-tab" data-bs-toggle="tab" data-bs-target="#return-tab-pane" type="button" role="tab" aria-controls="return-tab-pane" 
                    aria-selected="false" onclick="saveState('return_inventory_tab_state', 'return_inventory')">Grąžinti Inventorių</button>
                </li>
            </ul>
            <div class="tab-content border border-2 rounded-bottom border-primary" id="myTabContent">
                <div class="tab-pane fade <?php echo ($_SESSION['return_inventory_tab_state'] === "open_storage") ? "show active" : "" ?>" id="storage-tab-pane" role="tabpanel" aria-labelledby="storage-tab" tabindex="0">
                    <div class="row my-5 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <form method="post">
                                <h3 class="d-flex justify-content-center">Atrakinti Talpyklą</h3>

                                <div class="my-3" id="reader-storage"></div>

                                <label for="identification_code_storage" class="form-label">Identifikacinis kodas</label>
                                <input type="text" class="form-control mb-3" id="identification_code_storage" placeholder="Pvz.: 321654898798756654" name="unlock_storage_id_code"></input>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success" name="unlock_storage">Atrakinti</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo ($_SESSION['return_inventory_tab_state'] === "return_inventory") ? "show active" : "" ?>" id="return-tab-pane" role="tabpanel" aria-labelledby="return-tab" tabindex="0">
                    <div class="row my-5 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <form method="post">
                                <h3 class="d-flex justify-content-center">Grąžinti Inventorių</h3>

                                <div class="my-3" id="reader-ret"></div>

                                <label for="identification_code_return" class="form-label">Identifikacinis kodas</label>
                                <input type="text" class="form-control mb-3" id="identification_code_return" placeholder="Pvz.: 321654898798756654" name="return_id_code">

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success" name="return">Grąžinti</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
            $_SESSION['success_message'] = "";
            $_SESSION['error_message'] = "";
        ?>
    </div>
    <script>
        const tab1 = <?php echo json_encode($tab1); ?>;
        const tab2 = <?php echo json_encode($tab2); ?>;
        const reader1 = <?php echo json_encode($reader1); ?>;
        const reader2 = <?php echo json_encode($reader2); ?>;
        const input1 = <?php echo json_encode($input1); ?>;
        const input2 = <?php echo json_encode($input2); ?>;
    </script>
</body>
</html>

<?php include '../../includes/footer_admin.php'; ?>