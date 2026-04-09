<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "biokpi_db";

$conn = new mysqli($host, $user, $pass, $db);

// FIX TIMEZONE: Force Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');
$conn->query("SET time_zone = '+07:00'");

if ($conn->connect_error) {
    // Attempt to create DB if not exists
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "CREATE DATABASE IF NOT EXISTS $db";
    if ($conn->query($sql) === TRUE) {
        $conn->select_db($db);
    } else {
        die("Error creating database: " . $conn->error);
    }
}
?>
