<?php
require 'db_connect.php';
$r = $conn->query('SELECT * FROM rides WHERE ride_id=2');
print_r($r->fetch_assoc());
?>
