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

if($_SESSION['role'] != 'student'){
    header("Location: ../../index.php");
    exit();
}

if(isset($_POST['start_date']) && isset($_POST['end_date']) && isset($_POST['inventory']) && isset($_POST['comments'])){
    $application_id = $_POST['application_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $inventory_id = $_POST['inventory'];
    $comments = $_POST['comments'];

    updateRequest($application_id, $start_date, $end_date, $inventory_id, $comments);
} elseif(isset($_POST['application_id'])){
    cancelRequest($_POST['application_id']);
}

$result_inventory = displayInventory();
$inventory_array = [];
$j = 0;
while($row_inventory = mysqli_fetch_assoc($result_inventory)){
    $inventory_array[$j++] = ['id' => htmlspecialchars($row_inventory['id'], ENT_QUOTES, 'UTF-8'), 'name' => htmlspecialchars($row_inventory['name'], ENT_QUOTES, 'UTF-8')];
}
$inventory_count = count($inventory_array);
$result_requests = display_student_loan_requests();
$collapse_count = 0;
$input_count = 0;
$date_input_count1 = 0;
$date_input_count2 = 0;
$select_count = 0;
$expanded_check = true;

?>

<?php include '../../includes/header_student.php'; ?>

<!DOCTYPE html>
<html lang="lt" class="notranslate" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panaudos Prašymai</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <link rel='stylesheet' href='https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/ui-lightness/jquery-ui.css'> 
    <link rel="stylesheet" href="../../css/dropdown_search.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="../../js/mdb.umd.min.js"></script>
    <script defer src="../../js/header.js"></script>
    <script defer src="../../js/search_accordion.js"></script>
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
            <div class="col-12">
                <div class="input-group">
                    <div class="form-outline" data-mdb-input-init>
                        <input type="search" id="search-box" class="form-control" onkeyup="search()"/>
                        <label class="form-label" for="search-box">Ieškoti</label>
                    </div>
                    <button type="button" class="btn btn-success mx-1" onClick="document.location.href='create_loan_request.php'">SUKURTI PRAŠYMĄ</button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="accordion" id="accordion">
            <?php
            while($row = mysqli_fetch_assoc($result_requests)){
            ?>
                <div class="accordion-item" data-id="<?= $row['id'] ?>">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?php if(!$expanded_check){echo 'collapsed';}?>" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $collapse_count ?>" 
                        aria-expanded="<?= $expanded_check ?>" aria-controls="collapse<?= $collapse_count ?>">
                        <?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') . " : " . htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?></button> 
                    </h2>
                    <div id="collapse<?= $collapse_count++ ?>" class="accordion-collapse collapse 
                    <?php if($expanded_check){echo 'show';}?>" data-bs-parent="#accordion">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="col-3 mb-3">
                                    <label for="text<?= $input_count ?>" class="form-label">Vardas Pavardė</label>
                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" value="<?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?>" disabled/>
                                </div>
                                <div class="col-3 mb-3">
                                    <label for="text<?= $input_count ?>" class="form-label">Akademinė grupė</label>
                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" value="<?= htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') ?>" disabled/>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="row">
                                        <div class="col d-flex">
                                            <label class="form-label" for="date_start<?= $date_input_count1 ?>">Pradžios data</label>
                                        </div>
                                        <div class="col-1 d-flex">
                                        </div>
                                        <div class="col d-flex">
                                            <label class="form-label" for="date_end<?= $date_input_count2 ?>">Pabaigos data</label>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <input type="text" class="form-control start-date" id="date_start<?= $date_input_count1++ ?>" min="2025-01-01" value="<?= htmlspecialchars($row['start_date'], ENT_QUOTES, 'UTF-8') ?>"/>
                                        </div>
                                        <div class="col-1 d-flex justify-content-center align-items-center">
                                            <i class="bi bi-dash"></i>
                                        </div>
                                        <div class="col">
                                            <input type="text" class="form-control end-date" id="date_end<?= $date_input_count2++ ?>" min="2025-01-01" value="<?= htmlspecialchars($row['end_date'], ENT_QUOTES, 'UTF-8') ?>"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3">
                                    <label for="select<?= $select_count ?>" class="form-label">Inventoriaus vienetas</label>
                                    <select class="form-select inventory" id="select<?= $select_count++ ?>" name="inventory">
                                        <option value="">Pasirinkite inventorių</option>
                                 <?php
                                    $result_inventory = displayInventory();
                                    foreach($inventory_array as $inventory){
                                    ?>
                                        <option <?php echo (htmlspecialchars($row['fk_inventory_id'], ENT_QUOTES, 'UTF-8') === $inventory['id']) ? "selected" : "" ?> 
                                        value="<?= $inventory['id'] ?>"><?= $inventory['name'] ?></option>
                                    <?php
                                    }
                                    ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3">
                                    <label for="textArea<?= $input_count ?>" class="form-label">Papildomi komentarai</label>
                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" rows="3" 
                                    ><?= htmlspecialchars($row['additional_comments'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3">
                                    <label for="textArea<?= $input_count ?>" class="form-label">Pastabos</label>
                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" style="color: #776F6F;" rows="3" 
                                    disabled><?= htmlspecialchars($row['feedback'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col d-flex justify-content-end">
                                    <button type="button" class="btn btn-warning mx-1" onclick="update()">ATNAUJINTI</button>
                                    <button type="button" class="btn btn-danger mx-1" onclick="remove()">ATŠAUKTI</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                $expanded_check = false;
            }
            $_SESSION['success_message'] = "";
            $_SESSION['error_message'] = "";
            ?>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script> 
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="../../js/dselect.js"></script>
    <script>
        var select_box_element = document.querySelectorAll('.inventory');

        select_box_element.forEach(element => {
            dselect(element, {
                search: true
            });
        });
    </script>
    <script>
        var date_input_count = <?php echo json_encode($date_input_count1); ?>;

        $(document).ready(function() { 
            for (i = 0; i < date_input_count; i++) {
                $('#date_start' + i).datepicker({
                    changeMonth: true, 
                    changeYear: true
                });

                $("#date_end" + i).datepicker({
                    changeMonth: true, 
                    changeYear: true
                });

                $('#date_start' + i).change(function() { 
                    start_date = $(this).datepicker('getDate'); 
                    $("#date_end" + i).datepicker("option", "minDate", start_date); 
                });

                $("#date_end" + i).change(function() { 
                    end_date = $(this).datepicker('getDate'); 
                    $('#date_start' + i).datepicker("option", "maxDate", end_date); 
                });

                $.datepicker.regional['lt'] = {
                    closeText: 'Uždaryti',
                    prevText: '&#x3c;Atgal',
                    nextText: 'Pirmyn&#x3e;',
                    currentText: 'Šiandien',
                    monthNames: ['sausis', 'vasaris', 'kovas', 'balandis', 'gegužė', 'birželis', 'liepa', 'rugpjūtis', 'rugsėjis', 'spalis', 'lapkritis', 'gruodis'],
                    monthNamesShort: ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
                    dayNames: ['sekmadienis', 'pirmadienis', 'antradienis', 'trečiadienis', 'ketvirtadienis', 'penktadienis', 'šeštadienis'],
                    dayNamesShort: ['s', 'pr', 'an', 'tr', 'kt', 'pn', 'š'],
                    dayNamesMin: ['s', 'pr', 'an', 'tr', 'kt', 'pn', 'š'],
                    weekHeader: 'Savaitė',
                    dateFormat: 'yy-mm-dd',
                    firstDay: 0,
                    isRTL: false,
                    showMonthAfterYear: true,
                    yearSuffix: ''
                };

                $.datepicker.setDefaults($.datepicker.regional['lt']);
            }
        }) 
    </script>
    <script>
        function update() {
            const accordion_item = event.target.closest(".accordion-item");

            form = document.createElement("form");
            form.method = "POST";
            form.action = "student_loan_requests.php";

            input_application_id = document.createElement("input");
            input_application_id.type = "hidden";
            input_application_id.name = "application_id";
            input_application_id.value = accordion_item.dataset.id;

            input_start_date = document.createElement("input");
            input_start_date.type = "hidden";
            input_start_date.name = "start_date";
            input_start_date.value = accordion_item.querySelector('.start-date').value;

            input_end_date = document.createElement("input");
            input_end_date.type = "hidden";
            input_end_date.name = "end_date";
            input_end_date.value = accordion_item.querySelector('.end-date').value;

            input_inventory = document.createElement("input");
            input_inventory.type = "hidden";
            input_inventory.name = "inventory";
            input_inventory.value = accordion_item.querySelector('.inventory').value;

            input_comments = document.createElement("input");
            input_comments.type = "hidden";
            input_comments.name = "comments";
            input_comments.value = accordion_item.querySelector('textarea').value;

            form.appendChild(input_application_id);
            form.appendChild(input_start_date);
            form.appendChild(input_end_date);
            form.appendChild(input_inventory);
            form.appendChild(input_comments);

            document.body.appendChild(form);
            form.submit();
        }

        function remove() {
            const accordion_item = event.target.closest(".accordion-item");

            form = document.createElement("form");
            form.method = "POST";
            form.action = "student_loan_requests.php";

            input_id = document.createElement("input");
            input_id.type = "hidden";
            input_id.name = "application_id";
            input_id.value = accordion_item.dataset.id;

            form.appendChild(input_id);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>

<?php include '../../includes/footer_student.php'; ?>