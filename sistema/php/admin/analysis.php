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

if (!isset($_SESSION['analysis_tab_state'])) {
    $_SESSION['analysis_tab_state'] = "loans_by_month";
}

$current_date_parsed = explode("-", date("Y-m-d"));
$current_year = (int)$current_date_parsed[0];

$loans_by_month_year = $current_year;
if(isset($_GET['loans_by_month_year'])){
    $loans_by_month_year = (int)$_GET['loans_by_month_year'];
}
$loans_by_month = calculate_year_loans_by_month($loans_by_month_year);

$returned_not_returned_in_time_loans_year = $current_year;
if(isset($_GET['returned_not_returned_in_time_loans_year'])){
    $returned_not_returned_in_time_loans_year = (int)$_GET['returned_not_returned_in_time_loans_year'];
}
$returned_not_returned_in_time_loans = calculate_year_returned_and_not_returned_in_time_loans($returned_not_returned_in_time_loans_year);

$loanYears = loan_years();

?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analizė</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="../../js/header.js"></script>
    <script defer src="../../js/state.js"></script>
</head>
<body>
    
    <div class="container-md min-vh-100">
        <div class="row mt-5 mb-1">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['analysis_tab_state'] === "loans_by_month") ? "active" : "" ?> border-primary border-2" 
                    id="loans_by_month-tab" data-bs-toggle="tab" data-bs-target="#loans_by_month-tab-pane" type="button" role="tab" aria-controls="loans_by_month-tab-pane" 
                    aria-selected="true" onclick="saveState('analysis_tab_state', 'loans_by_month')">Kiekvieno mėnesio panaudos</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['analysis_tab_state'] === "returned_not_returned_in_time_loans") ? "active" : "" ?> border-primary border-2" 
                    id="returned_not_returned_in_time_loans-tab" data-bs-toggle="tab" data-bs-target="#returned_not_returned_in_time_loans-tab-pane" type="button" role="tab" aria-controls="returned_not_returned_in_time_loans-tab-pane" 
                    aria-selected="false" onclick="saveState('analysis_tab_state', 'returned_not_returned_in_time_loans')">Grąžintas/negrąžintas inventorius</button>
                </li>
            </ul>
            <div class="tab-content border border-2 rounded-bottom border-primary" id="myTabContent">
                <div class="tab-pane fade <?php echo ($_SESSION['analysis_tab_state'] === "loans_by_month") ? "show active" : "" ?>" id="loans_by_month-tab-pane" 
                    role="tabpanel" aria-labelledby="loans_by_month-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <select class="form-select mb-2" id="loans_by_month_year_select" aria-label="Year select" onchange="loans_by_month_year_select()">
                            <?php 
                                for($i = $loanYears[0]; $i <= $loanYears[1]; $i++){
                            ?>
                                <option <?php if($i === $loans_by_month_year){echo 'selected';} ?> value="<?= $i ?>"><?= $i ?></option>
                            <?php
                                }
                            ?>
                            </select>

                            <div class="d-flex justify-content-center w-100" style="height:75vh">
                                <canvas id="loans_by_month"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo ($_SESSION['analysis_tab_state'] === "returned_not_returned_in_time_loans") ? "show active" : "" ?>" id="returned_not_returned_in_time_loans-tab-pane" 
                    role="tabpanel" aria-labelledby="returned_not_returned_in_time_loans-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <select class="form-select mb-2" id="returned_not_returned_in_time_loans_year_select" aria-label="Year select" onchange="returned_not_returned_in_time_loans_year_select()">
                            <?php 
                                for($i = $loanYears[0]; $i <= $loanYears[1]; $i++){
                            ?>
                                <option <?php if($i === $returned_not_returned_in_time_loans_year){echo 'selected';} ?> value="<?= $i ?>"><?= $i ?></option>
                            <?php
                                }
                            ?>
                            </select>

                            <div class="d-flex justify-content-center w-100" style="height:75vh">
                                <canvas id="returned_not_returned_in_time_loans"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loans_by_month_year_select(){
            const select = document.getElementById('loans_by_month_year_select');
            const loans_by_month_year = select.value;
            window.location.href = `analysis.php?loans_by_month_year=${loans_by_month_year}`;
        }

        function returned_not_returned_in_time_loans_year_select(){
            const select = document.getElementById('returned_not_returned_in_time_loans_year_select');
            const returned_not_returned_in_time_loans = select.value;
            window.location.href = `analysis.php?returned_not_returned_in_time_loans_year=${returned_not_returned_in_time_loans}`;
        }
    </script>
    <script>
        const loans_by_month_year = <?php echo json_encode($loans_by_month_year); ?>;
        const loans_by_month = <?php echo json_encode($loans_by_month); ?>;

        const returned_not_returned_in_time_loans_year = <?php echo json_encode($returned_not_returned_in_time_loans_year); ?>;
        const returned_not_returned_in_time_loans = <?php echo json_encode($returned_not_returned_in_time_loans); ?>;
    </script>
    <script type="module" src="charts/loans_by_month.js"></script>
    <script type="module" src="charts/returned_not_returned_in_time_loans.js"></script>
</body>
</html>

<?php include '../../includes/footer_admin.php'; ?>