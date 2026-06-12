<?php
include 'php/connection.php';
$sql = "SELECT a.*, e.position, e.type FROM attendance a 
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN employees e ON u.employee_id = e.id LIMIT 1";
$res = $conn->query($sql);
if (!$res) {
    echo "Error: " . $conn->error;
} else {
    echo "Success";
}
?>
