<?php

$host = "localhost";
$user = "";
$password = "";
$database = "";

$server_base64_private_key = "";
$server_base64_public_key = "";

$HEADERS = "From: KTUIVS <ktuivs@gmail.com>";
$PROJECT_NAME = "projektas";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>