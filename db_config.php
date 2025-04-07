<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "sql208.byetcluster.com";
$username = "if0_38669623";
$password = "Bt2YckwivJ7xedp";
$database = "if0_38669623_Kolijachat";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    } else {
        echo "Connection successful!";
        }
        ?>