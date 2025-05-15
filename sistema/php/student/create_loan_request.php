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

if(isset($_POST['submit'])){
    $inventory_id = $_POST['inventory'];
    $start_date = $_POST['start-date'];
    $end_date = $_POST['end-date'];
    $comments = $_POST['additional-comments'];

    createApplication($inventory_id, $start_date, $end_date, $comments);
}

$result = displayInventory();

?>

<?php include '../../includes/header_student.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sukurti Panaudos Prašymą</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <link rel='stylesheet' href='https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/ui-lightness/jquery-ui.css'> 
    <link rel="stylesheet" href="../../css/dropdown_search.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="../../js/mdb.umd.min.js"></script>
    <script defer src="../../js/header.js"></script>
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
            <form method="post">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label for="full-name" class="form-label">Vardas Pavardė</label>
                        <input type="text" class="form-control" id="full-name" name="full-name" value="<?= $_SESSION['name'] ?>" disabled/>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <label for="academic-group" class="form-label">Akademinė grupė</label>
                        <input type="text" class="form-control" id="academic-group" name="academic-group" value="<?= $_SESSION['academic_group'] ?>" disabled/>
                    </div>
                    <div class="col-lg-6 col-12 mb-3">
                        <div class="row">
                            <div class="col d-flex">
                                <label class="form-label" for="start-date">Pradžios data</label>
                            </div>
                            <div class="col-1 d-flex">
                            </div>
                            <div class="col d-flex">
                                <label class="form-label" for="end-date">Pabaigos data</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <input type="text" id="start-date" class="form-control" name="start-date" min="2025-01-01" required/>
                            </div>
                            <div class="col-1 d-flex justify-content-center align-items-center">
                                <i class="bi bi-dash"></i>
                            </div>
                            <div class="col">
                                <input type="text" id="end-date" class="form-control" name="end-date" min="2025-01-01" required/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="inventory" class="form-label">Inventoriaus vienetas</label>
                        <select class="form-select" id="inventory" name="inventory" required>
                            <option value="">Pasirinkite inventorių</option>
                        <?php
                        while($row = mysqli_fetch_assoc($result)){
                        ?>
                            <option value="<?= htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php
                        }
                        $_SESSION['success_message'] = "";
                        $_SESSION['error_message'] = "";
                        ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3">
                        <label for="textArea" class="form-label">Papildomi komentarai</label>
                        <textarea class="form-control" id="textArea" name="additional-comments" rows="3"></textarea>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col d-flex justify-content-end">
                        <label class="form-check-label me-2" for="check">Sutinku, jog negrąžinus įrenginio per numatytą laiką turėsiu sumokėti numatytą sumą (0,1 € / dieną)</label>
                        <input class="form-check-input" type="checkbox" id="check" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col d-flex justify-content-end">
                        <button type="submit" class="btn btn-success mx-1" name="submit">PATEIKTI</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script> 
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="../../js/dselect.js"></script>
    <script>
        var select_box_element = document.querySelector('#inventory');

        dselect(select_box_element, {
            search: true
        });

        document.addEventListener('DOMContentLoaded', function() {
            var select_box_element = document.querySelector('#inventory');
            
            dselect(select_box_element, {
                search: true
            });

            select_box_element.style.display = 'flex';
            select_box_element.style.opacity = '0';
            select_box_element.style.position = 'absolute';
            select_box_element.style.height = select_box_element.height;
            select_box_element.style.width = '20%';
        });
    </script>
    <script>
        $(document).ready(function() { 
            $("#start-date").datepicker({
                changeMonth: true, 
                changeYear: true
            });

            $("#end-date").datepicker({
                changeMonth: true, 
                changeYear: true
            });

            $("#start-date").change(function() { 
                startDate = $(this).datepicker('getDate'); 
                $("#end-date").datepicker("option", "minDate", startDate); 
            });

            $("#end-date").change(function() { 
                endDate = $(this).datepicker('getDate'); 
                $("#start-date").datepicker("option", "maxDate", endDate); 
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
        })
    </script>
</body>
</html>

<?php include '../../includes/footer_student.php'; ?>