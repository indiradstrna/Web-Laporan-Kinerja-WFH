<?php
include 'php/connection.php';
$my_id = 83; // User 93 from my check_db output (user_id 83)
$date = date('Y-m-d');
echo "Date: $date\n";
$sql = "SELECT a.*, e.position, e.type FROM attendance a 
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN employees e ON u.employee_id = e.id
        WHERE a.user_id = '$my_id' AND a.date = '$date'";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    print_r($res->fetch_assoc());
} else {
    echo "No attendance found for $my_id on $date\n";
}
?>
