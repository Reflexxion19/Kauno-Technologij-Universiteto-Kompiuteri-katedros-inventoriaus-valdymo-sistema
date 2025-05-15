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

if (isset($_POST['user_id']) && isset($_POST['user_role'])){
    changeRole($_POST['user_id'], $_POST['user_role']);
}

$keypair = [
    'public_key' => "",
    'private_key' => ""
];

if (isset($_POST['user_id'])){
    $keypair = adminPrivatePublicKeys($_POST['user_id']);
}

$result = display_users();

?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naudotojai</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="../../js/mdb.umd.min.js"></script>
    <script defer src="../../js/header.js"></script>
    <script defer src="../../js/search_w_role.js"></script>
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
        <div class="modal fade" id="card-data-modal" tabindex="-1" aria-labelledby="card-data-modal" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="card-data-modal-label">Sugeneruoti kortelės duomenys</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal_body" style="word-wrap: break-word; overflow-wrap: break-word; white-space: pre-wrap;">
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmation_btn">Uždaryti</button>
                </div>
                </div>
            </div>
        </div>

        <div class="row <?php echo ($_SESSION['success_message'] === "" && $_SESSION['error_message'] === "") ? "mt-5" : "" ?> mb-3 d-flex justify-content-end">
            <div class="col-12">
                <div class="input-group">
                    <div class="form-outline" data-mdb-input-init>
                        <input type="search" id="search-box" class="form-control" onkeyup="search()"/>
                        <label class="form-label" for="search-box">Ieškoti</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <table class="table" id="table">
                    <thead>
                        <tr>
                            <th scope="col"><b>Naudotojas</b></th>
                            <th scope="col"><b>Rolė</b></th>
                        </tr>
                    </thead>
                    <tbody class="table-group-divider">
                    <?php
                    while($row = mysqli_fetch_assoc($result)){
                    ?>
                        <tr data-id="<?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div class="row">
                                    <div class="col-6">
                                        <select class="form-select" aria-label="Role select" name="role_select">
                    <?php
                        if($row['role'] == 'admin'){
                    ?>
                                            <option selected value="admin">Administratorius</option>
                                            <option value="employee">Darbuotojas</option>
                                            <option value="student">Studentas</option>
                    <?php
                        } elseif($row['role'] == 'employee'){
                    ?>
                                            <option value="admin">Administratorius</option>
                                            <option selected value="employee">Darbuotojas</option>
                                            <option value="student">Studentas</option>
                    <?php
                        } elseif($row['role'] == 'student'){
                    ?>
                                            <option value="admin">Administratorius</option>
                                            <option value="employee">Darbuotojas</option>
                                            <option selected value="student">Studentas</option>
                    <?php
                        }
                    ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-success" name="change_role" onclick="changeRole()">Keisti rolę</button>
                                    <?php
                                        if($row['role'] == 'admin'){
                                    ?>
                                        <button type="button" class="btn btn-danger" name="change_role" data-id="<?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>" data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>" onclick="generateKecardData()">Generuoti koretelės duomenis</button>
                                    <?php
                                        }
                                    ?>
                                    </div>
                                </div>
                            </td>
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
    <script>
        function changeRole() {
            const row = event.target.closest("tr");
            const cells = row.getElementsByTagName("td");

            form = document.createElement("form");
            form.method = "POST";
            form.action = "users.php";

            input_id = document.createElement("input");
            input_id.type = "hidden";
            input_id.name = "user_id";
            input_id.value = row.dataset.id;

            input_role = document.createElement("input");
            input_role.type = "hidden";
            input_role.name = "user_role";
            input_role.value = cells[1].getElementsByTagName("select")[0].value;

            form.appendChild(input_id);
            form.appendChild(input_role);

            document.body.appendChild(form);
            form.submit();
        }

        function generateKecardData() {
            let button;
            let data = {};

            button = event.target;
            data = {
                request_id: button.dataset.id,
                user_name: button.dataset.name
            };

            fetch('../../config/functions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_admin_card_data&user_id=' + data.request_id + '&user_name=' + data.user_name
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('modal_body').innerText = "Sugeneruoti kortelės duomenys: " + data['data'];
                showModal();
            })
            .catch(error => {
                console.error('Error:', error);
                showModal();
            });
        }

        function showModal() {
            return new Promise((resolve) => {
                var card_data_modal = document.getElementById('card-data-modal');

                const modal = new bootstrap.Modal(card_data_modal);
                modal.show();

                const confirm_button = document.getElementById('confirmation_btn');

                confirm_button.addEventListener('click', function() {
                    modal.hide();
                    resolve(true);
                });
            });
        }
    </script>
</body>
</html>

<?php include '../../includes/footer_admin.php'; ?>