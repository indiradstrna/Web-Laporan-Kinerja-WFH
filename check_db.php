<?php
include 'php/connection.php';
$today = date('Y-m-d');
echo "Today: $today\n";
$res = $conn->query("SELECT * FROM attendance ORDER BY date DESC LIMIT 5");
if ($res) {
    while($r = $res->fetch_assoc()) {
        print_r($r);
    }
} else {
    echo $conn->error;
}
?>
