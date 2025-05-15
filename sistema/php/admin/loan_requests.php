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

if(!isset($_SESSION['loan_requests_tab_state'])){
    $_SESSION['loan_requests_tab_state'] = "submitted";
}

if (isset($_POST['request_approve'])) {
    approveRequest($_POST['request_id']);
}

if (isset($_POST['request_reject'])) {
    rejectRequest($_POST['request_id']);
}

if (isset($_POST['request_feedback'])) {
    addFeedback($_POST['request_id'], $_POST['feedback']);
}

if (isset($_POST['register_loan'])) {
    registerLoan($_POST['user_id'], $_POST['inventory_id'], $_POST['start_date'], $_POST['end_date']);
}

if (isset($_POST['register_return'])) {
    registerReturn($_POST['request_id'], $_POST['inventory_id']);
}

$result_submitted = display_loan_requests_submitted();
$result_corrected = display_loan_requests_corrected();
$result_needs_correction = display_loan_requests_needs_correction();
$result_approved = display_loan_requests_approved();
$result_rejected = display_loan_requests_rejected();
$result_finished = display_loan_requests_finished();
$collapse_count = 0;
$input_count = 0;
$date_input_count1 = 0;
$date_input_count2 = 0;
$expanded_check_submitted = true;
$expanded_check_corrected = true;
$expanded_check_needs_correction = true;
$expanded_check_approved = true;
$expanded_check_rejected = true;
$expanded_check_finished = true;

?>

<?php include '../../includes/header_admin.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panaudos Prašymai</title>
    <link rel="stylesheet" href="../../css/mdb.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <script defer src="../../js/bootstrap.bundle.min.js"></script>
    <script defer src="../../js/mdb.umd.min.js"></script>
    <script defer src="../../js/header.js"></script>
    <script defer src="../../js/search_accordion_multiple_tabs.js"></script>
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

        <div class="modal fade" id="confirmation-modal" tabindex="-1" aria-labelledby="confirmation-modal" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmation-modal-label"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="inventory" class="form-label">Pavadinimas</label>
                            <input type="text" class="form-control" id="inventory" name="name" required disabled/>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="location" class="form-label">Vieta</label>
                            <input type="text" class="form-control" id="location" name="location" required disabled/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="serial_number" class="form-label">Serijinis numeris</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number" required disabled/>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="inventory_number" class="form-label">Inventoriaus numeris</label>
                            <input type="text" class="form-control" id="inventory_number" name="inventory_number" required disabled/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-3">
                            <label for="description" class="form-label">Aprašymas</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required disabled></textarea>
                        </div>
                    </div>
                    <label class="text-danger" id="fee"></label>
                    <hr>
                    <div class="confirmation_question"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmation_btn">Taip</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ne</button>
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
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['loan_requests_tab_state'] === "submitted") ? "active" : "" ?> border-primary border-2" 
                    id="submitted-tab" data-bs-toggle="tab" data-bs-target="#submitted-tab-pane" type="button" role="tab" aria-controls="submitted-tab-pane" 
                    aria-selected="true" onclick="saveState('loan_requests_tab_state', 'submitted')">Naujai Pateikti</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['loan_requests_tab_state'] === "corrected") ? "active" : "" ?> border-primary border-2" 
                    id="corrected-tab" data-bs-toggle="tab" data-bs-target="#corrected-tab-pane" type="button" role="tab" aria-controls="corrected-tab-pane" 
                    aria-selected="false" onclick="saveState('loan_requests_tab_state', 'corrected')">Pakoreguoti</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['loan_requests_tab_state'] === "needs_correction") ? "active" : "" ?> border-primary border-2" 
                    id="needs_correction-tab" data-bs-toggle="tab" data-bs-target="#needs_correction-tab-pane" type="button" role="tab" aria-controls="needs_correction-tab-pane" 
                    aria-selected="false" onclick="saveState('loan_requests_tab_state', 'needs_correction')">Reikalingas Pataisymas</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['loan_requests_tab_state'] === "approved") ? "active" : "" ?> border-primary border-2" 
                    id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved-tab-pane" type="button" role="tab" aria-controls="approved-tab-pane" 
                    aria-selected="false" onclick="saveState('loan_requests_tab_state', 'approved')">Patvirtinti</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['loan_requests_tab_state'] === "rejected") ? "active" : "" ?> border-primary border-2" 
                    id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected-tab-pane" type="button" role="tab" aria-controls="rejected-tab-pane" 
                    aria-selected="false" onclick="saveState('loan_requests_tab_state', 'rejected')">Atmesti</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($_SESSION['loan_requests_tab_state'] === "finished") ? "active" : "" ?> border-primary border-2" 
                    id="finished-tab" data-bs-toggle="tab" data-bs-target="#finished-tab-pane" type="button" role="tab" aria-controls="finished-tab-pane" 
                    aria-selected="false" onclick="saveState('loan_requests_tab_state', 'finished')">Užbaigti</button>
                </li>
            </ul>
            <div class="tab-content border border-2 rounded-bottom border-primary" id="myTabContent">
                <div class="tab-pane fade <?php echo ($_SESSION['loan_requests_tab_state'] === "submitted") ? "show active" : "" ?>" id="submitted-tab-pane" 
                    role="tabpanel" aria-labelledby="submitted-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <h3 class="d-flex justify-content-center">Naujai pateikti prašymai</h3>
    
                            <div class="accordion" id="accordion_submited">
                            <?php
                            while($row = mysqli_fetch_assoc($result_submitted)){
                            ?>
                                <div class="accordion-item" data-id="<?= $row['id'] ?>">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?php if(!$expanded_check_submitted){echo 'collapsed';}?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $collapse_count ?>" 
                                        aria-expanded="<?= $expanded_check_submitted ?>" aria-controls="collapse<?= $collapse_count ?>">
                                        <?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') . " : " . htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?></button> 
                                    </h2>
                                    <div id="collapse<?= $collapse_count++ ?>" class="accordion-collapse collapse 
                                    <?php if($expanded_check_submitted){echo 'show';}?>" data-bs-parent="#accordion_submited">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Vardas Pavardė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Akademinė grupė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') ?>" disabled>
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
                                                            <input type="text" class="form-control" id="date_start<?= $date_input_count1++ ?>" 
                                                            value="<?= htmlspecialchars($row['start_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                        <div class="col-1 d-flex justify-content-center align-items-center">
                                                            <i class="bi bi-dash"></i>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" class="form-control" id="date_end<?= $date_input_count2++ ?>" 
                                                            value="<?= htmlspecialchars($row['end_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Inventoriaus pavadinimas</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Papildomi komentarai</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" style="color: #776F6F;" rows="3" 
                                                    disabled><?= htmlspecialchars($row['additional_comments'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Pastabos</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" 
                                                    rows="3"><?= htmlspecialchars($row['feedback'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col d-flex justify-content-end">
                                                    <button type="button" class="btn btn-success mx-1" onclick="approve()">PATVIRTINTI</button>
                                                    <button type="button" class="btn btn-danger mx-1" onclick="reject()">ATMESTI</button>
                                                    <button type="button" class="btn btn-warning ms-1" onclick="addFeedback()">PATEIKTI PASTABĄ</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                $expanded_check_submitted = false;
                            }
                            $_SESSION['success_message'] = "";
                            $_SESSION['error_message'] = "";
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo ($_SESSION['loan_requests_tab_state'] === "corrected") ? "show active" : "" ?>" id="corrected-tab-pane" 
                    role="tabpanel" aria-labelledby="corrected-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <h3 class="d-flex justify-content-center">Pakoreguoti prašymai</h3>
    
                            <div class="accordion" id="accordion_corrected">
                            <?php
                            while($row = mysqli_fetch_assoc($result_corrected)){
                            ?>
                                <div class="accordion-item" data-id="<?= $row['id'] ?>">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?php if(!$expanded_check_corrected){echo 'collapsed';}?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $collapse_count ?>" 
                                        aria-expanded="<?= $expanded_check_corrected ?>" aria-controls="collapse<?= $collapse_count ?>">
                                        <?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') . " : " . htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?></button> 
                                    </h2>
                                    <div id="collapse<?= $collapse_count++ ?>" class="accordion-collapse collapse 
                                    <?php if($expanded_check_corrected){echo 'show';}?>" data-bs-parent="#accordion_corrected">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Vardas Pavardė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Akademinė grupė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_group'] , ENT_QUOTES, 'UTF-8')?>" disabled>
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
                                                            <input type="text" class="form-control" id="date_start<?= $date_input_count1++ ?>" 
                                                            value="<?= htmlspecialchars($row['start_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                        <div class="col-1 d-flex justify-content-center align-items-center">
                                                            <i class="bi bi-dash"></i>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" class="form-control" id="date_end<?= $date_input_count2++ ?>" 
                                                            value="<?= htmlspecialchars($row['end_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Inventoriaus pavadinimas</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Papildomi komentarai</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" style="color: #776F6F;" rows="3" 
                                                    disabled><?= htmlspecialchars($row['additional_comments'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Pastabos</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" 
                                                    rows="3"><?= htmlspecialchars($row['feedback'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col d-flex justify-content-end">
                                                    <button type="button" class="btn btn-success mx-1" onclick="approve()">PATVIRTINTI</button>
                                                    <button type="button" class="btn btn-danger mx-1" onclick="reject()">ATMESTI</button>
                                                    <button type="button" class="btn btn-warning ms-1" onclick="addFeedback()">PATEIKTI PASTABĄ</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                $expanded_check_corrected = false;
                            }
                            $_SESSION['success_message'] = "";
                            $_SESSION['error_message'] = "";
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo ($_SESSION['loan_requests_tab_state'] === "needs_correction") ? "show active" : "" ?>" id="needs_correction-tab-pane" 
                    role="tabpanel" aria-labelledby="needs_correction-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <h3 class="d-flex justify-content-center">Prašymai, kuriems reikalingas pataisymas</h3>
    
                            <div class="accordion" id="accordion_needs_correction">
                            <?php
                            while($row = mysqli_fetch_assoc($result_needs_correction)){
                            ?>
                                <div class="accordion-item" data-id="<?= $row['id'] ?>">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?php if(!$expanded_check_needs_correction){echo 'collapsed';}?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $collapse_count ?>" 
                                        aria-expanded="<?= $expanded_check_needs_correction ?>" aria-controls="collapse<?= $collapse_count ?>">
                                        <?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') . " : " . htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?></button> 
                                    </h2>
                                    <div id="collapse<?= $collapse_count++ ?>" class="accordion-collapse collapse 
                                    <?php if($expanded_check_needs_correction){echo 'show';}?>" data-bs-parent="#accordion_needs_correction">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Vardas Pavardė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Akademinė grupė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') ?>" disabled>
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
                                                            <input type="text" class="form-control" id="date_start<?= $date_input_count1++ ?>" 
                                                            value="<?= htmlspecialchars($row['start_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                        <div class="col-1 d-flex justify-content-center align-items-center">
                                                            <i class="bi bi-dash"></i>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" class="form-control" id="date_end<?= $date_input_count2++ ?>" 
                                                            value="<?= htmlspecialchars($row['end_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Inventoriaus pavadinimas</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Papildomi komentarai</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" style="color: #776F6F;" rows="3" 
                                                    disabled><?= htmlspecialchars($row['additional_comments'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Pastabos</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" 
                                                    rows="3"><?= htmlspecialchars($row['feedback'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col d-flex justify-content-end">
                                                    <button type="button" class="btn btn-success mx-1" onclick="approve()">PATVIRTINTI</button>
                                                    <button type="button" class="btn btn-danger mx-1" onclick="reject()">ATMESTI</button>
                                                    <button type="button" class="btn btn-warning ms-1" onclick="addFeedback()">PATEIKTI PASTABĄ</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                $expanded_check_needs_correction = false;
                            }
                            $_SESSION['success_message'] = "";
                            $_SESSION['error_message'] = "";
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo ($_SESSION['loan_requests_tab_state'] === "approved") ? "show active" : "" ?>" id="approved-tab-pane" 
                    role="tabpanel" aria-labelledby="approved-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <h3 class="d-flex justify-content-center">Patvirtinti prašymai</h3>
    
                            <div class="accordion" id="accordion_accepted">
                            <?php
                            while($row = mysqli_fetch_assoc($result_approved)){
                            ?>
                                <div class="accordion-item" data-id="<?= $row['id'] ?>" data-user_id="<?= $row['fk_user_id'] ?>" data-inventory_id="<?= $row['fk_inventory_id'] ?>">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?php if(!$expanded_check_approved){echo 'collapsed';}?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $collapse_count ?>" 
                                        aria-expanded="<?= $expanded_check_approved ?>" aria-controls="collapse<?= $collapse_count ?>">
                                        <?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') . " : " . htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?></button> 
                                    </h2>
                                    <div id="collapse<?= $collapse_count++ ?>" class="accordion-collapse collapse 
                                    <?php if($expanded_check_approved){echo 'show';}?>" data-bs-parent="#accordion_accepted">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Vardas Pavardė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Akademinė grupė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') ?>" disabled>
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
                                                            <input type="text" class="form-control" name="date_start" id="date_start<?= $date_input_count1++ ?>" 
                                                            value="<?= htmlspecialchars($row['start_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                        <div class="col-1 d-flex justify-content-center align-items-center">
                                                            <i class="bi bi-dash"></i>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" class="form-control" name="date_end" id="date_end<?= $date_input_count2++ ?>" 
                                                            value="<?= htmlspecialchars($row['end_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Inventoriaus pavadinimas</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Papildomi komentarai</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" style="color: #776F6F;" rows="3" 
                                                    disabled><?= htmlspecialchars($row['additional_comments'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Pastabos</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" 
                                                    rows="3" disabled><?= htmlspecialchars($row['feedback'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col d-flex justify-content-end">
                                                <?php
                                                    if($row['inventory_status'] === "Available"){
                                                ?>
                                                    <button type="button" class="btn btn-success mx-1" onclick="registerLoan()">UŽFIKSUOTI ATSIĖMIMĄ</button>
                                                <?php
                                                    } else {
                                                ?>
                                                    <button type="button" class="btn btn-danger mx-1" onclick="registerReturn()">UŽFIKSUOTI GRĄŽINIMĄ</button>
                                                <?php
                                                    }
                                                ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                $expanded_check_approved = false;
                            }
                            $_SESSION['success_message'] = "";
                            $_SESSION['error_message'] = "";
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo ($_SESSION['loan_requests_tab_state'] === "rejected") ? "show active" : "" ?>" id="rejected-tab-pane" 
                    role="tabpanel" aria-labelledby="rejected-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <h3 class="d-flex justify-content-center">Atmesti prašymai</h3>
    
                            <div class="accordion" id="accordion_rejected">
                            <?php
                            while($row = mysqli_fetch_assoc($result_rejected)){
                            ?>
                                <div class="accordion-item" data-id="<?= $row['id'] ?>">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?php if(!$expanded_check_rejected){echo 'collapsed';}?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $collapse_count ?>" 
                                        aria-expanded="<?= $expanded_check_rejected ?>" aria-controls="collapse<?= $collapse_count ?>">
                                        <?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') . " : " . htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?></button> 
                                    </h2>
                                    <div id="collapse<?= $collapse_count++ ?>" class="accordion-collapse collapse 
                                    <?php if($expanded_check_rejected){echo 'show';}?>" data-bs-parent="#accordion_rejected">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Vardas Pavardė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Akademinė grupė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') ?>" disabled>
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
                                                            <input type="text" class="form-control" id="date_start<?= $date_input_count1++ ?>" 
                                                            value="<?= htmlspecialchars($row['start_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                        <div class="col-1 d-flex justify-content-center align-items-center">
                                                            <i class="bi bi-dash"></i>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" class="form-control" id="date_end<?= $date_input_count2++ ?>" 
                                                            value="<?= htmlspecialchars($row['end_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Inventoriaus pavadinimas</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Papildomi komentarai</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" style="color: #776F6F;" rows="3" 
                                                    disabled><?= htmlspecialchars($row['additional_comments'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Pastabos</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" 
                                                    rows="3" disabled><?= htmlspecialchars($row['feedback'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                $expanded_check_rejected = false;
                            }
                            $_SESSION['success_message'] = "";
                            $_SESSION['error_message'] = "";
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo ($_SESSION['loan_requests_tab_state'] === "finished") ? "show active" : "" ?>" id="finished-tab-pane" 
                    role="tabpanel" aria-labelledby="finished-tab" tabindex="0">
                    <div class="row mt-3 d-flex justify-content-center">
                        <div class="col-12 mb-3">
                            <h3 class="d-flex justify-content-center">Užbaigti prašymai</h3>
    
                            <div class="accordion" id="accordion_done">
                            <?php
                            while($row = mysqli_fetch_assoc($result_finished)){
                            ?>
                                <div class="accordion-item" data-id="<?= $row['id'] ?>">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?php if(!$expanded_check_finished){echo 'collapsed';}?>" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $collapse_count ?>" 
                                        aria-expanded="<?= $expanded_check_finished ?>" aria-controls="collapse<?= $collapse_count ?>">
                                        <?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') . " : " . htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?></button> 
                                    </h2>
                                    <div id="collapse<?= $collapse_count++ ?>" class="accordion-collapse collapse 
                                    <?php if($expanded_check_finished){echo 'show';}?>" data-bs-parent="#accordion_done">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Vardas Pavardė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                                <div class="col-3 mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Akademinė grupė</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['student_group'], ENT_QUOTES, 'UTF-8') ?>" disabled>
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
                                                            <input type="text" class="form-control" id="date_start<?= $date_input_count1++ ?>" 
                                                            value="<?= htmlspecialchars($row['start_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                        <div class="col-1 d-flex justify-content-center align-items-center">
                                                            <i class="bi bi-dash"></i>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" class="form-control" id="date_end<?= $date_input_count2++ ?>" 
                                                            value="<?= htmlspecialchars($row['end_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="text<?= $input_count ?>" class="form-label">Inventoriaus pavadinimas</label>
                                                    <input type="text" class="form-control" id="text<?= $input_count++ ?>" 
                                                    value="<?= htmlspecialchars($row['inventory_name'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Papildomi komentarai</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" style="color: #776F6F;" rows="3" 
                                                    disabled><?= htmlspecialchars($row['additional_comments'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3">
                                                    <label for="textArea<?= $input_count ?>" class="form-label">Pastabos</label>
                                                    <textarea class="form-control" id="textArea<?= $input_count++ ?>" 
                                                    rows="3" disabled><?= htmlspecialchars($row['feedback'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                $expanded_check_finished = false;
                            }
                            $_SESSION['success_message'] = "";
                            $_SESSION['error_message'] = "";
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function approve() {
            const accordion_item = event.target.closest(".accordion-item");

            form = document.createElement("form");
            form.method = "POST";
            form.action = "loan_requests.php";

            input_approve = document.createElement("input");
            input_approve.type = "hidden";
            input_approve.name = "request_approve";
            input_approve.value = "";

            input_id = document.createElement("input");
            input_id.type = "hidden";
            input_id.name = "request_id";
            input_id.value = accordion_item.dataset.id;

            form.appendChild(input_approve);
            form.appendChild(input_id);

            document.body.appendChild(form);
            form.submit();
        }

        function reject() {
            const accordion_item = event.target.closest(".accordion-item");

            form = document.createElement("form");
            form.method = "POST";
            form.action = "loan_requests.php";

            input_reject = document.createElement("input");
            input_reject.type = "hidden";
            input_reject.name = "request_reject";
            input_reject.value = "";

            input_id = document.createElement("input");
            input_id.type = "hidden";
            input_id.name = "request_id";
            input_id.value = accordion_item.dataset.id;

            form.appendChild(input_reject);
            form.appendChild(input_id);

            document.body.appendChild(form);
            form.submit();
        }

        function addFeedback() {
            const accordion_item = event.target.closest(".accordion-item");
            const inputs = accordion_item.getElementsByTagName("input");
            const textAreas = accordion_item.getElementsByTagName("textarea");

            form = document.createElement("form");
            form.method = "POST";
            form.action = "loan_requests.php";

            input_request_feedback = document.createElement("input");
            input_request_feedback.type = "hidden";
            input_request_feedback.name = "request_feedback";
            input_request_feedback.value = "";

            input_id = document.createElement("input");
            input_id.type = "hidden";
            input_id.name = "request_id";
            input_id.value = accordion_item.dataset.id;

            input_feedback = document.createElement("input");
            input_feedback.type = "hidden";
            input_feedback.name = "feedback";
            input_feedback.value = textAreas[1].value;

            form.appendChild(input_request_feedback);
            form.appendChild(input_id);
            form.appendChild(input_feedback);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <script>
        function registerLoan() {
            let accordion_item;
            let data = {};

            accordion_item = event.target.closest(".accordion-item");
            const inputs = accordion_item.getElementsByTagName("input");
            data = {
                start_date: inputs[2].value,
                end_date: inputs[3].value,
                user_id: accordion_item.dataset.user_id,
                inventory_id: accordion_item.dataset.inventory_id
            };

            fetch(`../../config/functions.php?action=get_inventory&inventory_id=${data.inventory_id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('inventory').value = data.name;
                    document.getElementById('location').value = data.location_name;
                    document.getElementById('serial_number').value = data.serial_number;
                    document.getElementById('inventory_number').value = data.inventory_number;
                    document.getElementById('description').value = data.description;
                    document.getElementById('fee').innerText = "";
                })
                .catch(error => {
                    document.getElementById('inventory').value = "Informacija nerasta";
                    document.getElementById('location').value = "Informacija nerasta";
                    document.getElementById('serial_number').value = "Informacija nerasta";
                    document.getElementById('inventory_number').value = "Informacija nerasta";
                    document.getElementById('description').value = "Informacija nerasta";
                    document.getElementById('fee').innerText = "";
                });

            showLoanConfirmationModal(data).then((confirmed) => {
                if (confirmed) {
                    form = document.createElement("form");
                    form.method = "POST";
                    form.action = "loan_requests.php";

                    input_register_loan = document.createElement("input");
                    input_register_loan.type = "hidden";
                    input_register_loan.name = "register_loan";
                    input_register_loan.value = "";

                    input_user_id = document.createElement("input");
                    input_user_id.type = "hidden";
                    input_user_id.name = "user_id";
                    input_user_id.value = data.user_id;

                    start_date = document.createElement("input");
                    start_date.type = "hidden";
                    start_date.name = "start_date";
                    start_date.value = data.start_date;

                    end_date = document.createElement("input");
                    end_date.type = "hidden";
                    end_date.name = "end_date";
                    end_date.value = data.end_date;

                    input_inventory_id = document.createElement("input");
                    input_inventory_id.type = "hidden";
                    input_inventory_id.name = "inventory_id";
                    input_inventory_id.value = data.inventory_id;

                    form.appendChild(input_register_loan);
                    form.appendChild(input_user_id);
                    form.appendChild(start_date);
                    form.appendChild(end_date);
                    form.appendChild(input_inventory_id);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function registerReturn() {
            let accordion_item;
            let data = {};

            accordion_item = event.target.closest(".accordion-item");
            data = {
                request_id: accordion_item.dataset.id,
                user_id: accordion_item.dataset.user_id,
                inventory_id: accordion_item.dataset.inventory_id
            };

            fetch(`../../config/functions.php?action=get_inventory&inventory_id=${data.inventory_id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('inventory').value = data.name;
                    document.getElementById('location').value = data.location_name;
                    document.getElementById('serial_number').value = data.serial_number;
                    document.getElementById('inventory_number').value = data.inventory_number;
                    document.getElementById('description').value = data.description;
                    if(data.fee > 0){
                        document.getElementById('fee').innerText = 'Mokėtina suma dėl grąžinimo pasibaigus terminui: ' + data.fee + ' €';
                    }
                })
                .catch(error => {
                    document.getElementById('inventory').value = "Informacija nerasta";
                    document.getElementById('location').value = "Informacija nerasta";
                    document.getElementById('serial_number').value = "Informacija nerasta";
                    document.getElementById('inventory_number').value = "Informacija nerasta";
                    document.getElementById('description').value = "Informacija nerasta";
                    document.getElementById('fee').innerText = "";
                });

            showReturnConfirmationModal(data).then((confirmed) => {
                if (confirmed) {
                    form = document.createElement("form");
                    form.method = "POST";
                    form.action = "loan_requests.php";

                    input_register_return = document.createElement("input");
                    input_register_return.type = "hidden";
                    input_register_return.name = "register_return";
                    input_register_return.value = "";

                    input_id = document.createElement("input");
                    input_id.type = "hidden";
                    input_id.name = "request_id";
                    input_id.value = data.request_id;

                    input_user_id = document.createElement("input");
                    input_user_id.type = "hidden";
                    input_user_id.name = "user_id";
                    input_user_id.value = data.user_id;

                    input_inventory_id = document.createElement("input");
                    input_inventory_id.type = "hidden";
                    input_inventory_id.name = "inventory_id";
                    input_inventory_id.value = data.inventory_id;

                    form.appendChild(input_register_return);
                    form.appendChild(input_id);
                    form.appendChild(input_user_id);
                    form.appendChild(input_inventory_id);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function showLoanConfirmationModal() {
            return new Promise((resolve) => {
                var confirmation_modal = document.getElementById('confirmation-modal');
                confirmation_modal.querySelector('h5').textContent = "Duomenų atitikimo ir atsiėmimo užfiksavimo patvirtinimas";
                confirmation_modal.querySelector('.confirmation_question').textContent = "Ar visi duomenys atitinka ir norite užfiksuoti atsiėmimą?";

                const modal = new bootstrap.Modal(confirmation_modal);
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

        function showReturnConfirmationModal() {
            return new Promise((resolve) => {
                var confirmation_modal = document.getElementById('confirmation-modal');
                confirmation_modal.querySelector('h5').textContent = "Duomenų atitikimo ir grąžinimo užfiksavimo patvirtinimas";
                confirmation_modal.querySelector('.confirmation_question').textContent = "Ar visi duomenys atitinka ir norite užfiksuoti grąžinimą?";

                const modal = new bootstrap.Modal(confirmation_modal);
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