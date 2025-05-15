<?php

session_save_path("/tmp");
session_start();

if(isset($_POST['inventory_tab_state'])) {
    $_SESSION['inventory_tab_state'] = $_POST['inventory_tab_state'];
}

if(isset($_POST['loan_inventory_tab_state'])) {
    $_SESSION['loan_inventory_tab_state'] = $_POST['loan_inventory_tab_state'];
}

if(isset($_POST['return_inventory_tab_state'])) {
    $_SESSION['return_inventory_tab_state'] = $_POST['return_inventory_tab_state'];
}

if(isset($_POST['loan_requests_tab_state'])) {
    $_SESSION['loan_requests_tab_state'] = $_POST['loan_requests_tab_state'];
}

if (isset($_POST['analysis_tab_state'])) {
    $_SESSION['analysis_tab_state'] = $_POST['analysis_tab_state'];
}

?>