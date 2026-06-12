<?php
include 'php/connection.php';
$res = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE date='2026-06-12'");
$row = $res->fetch_assoc();
echo "Count 2026-06-12: " . $row['c'] . "\n";
?>
