<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'sharemyride');
$res = $conn->query("DESCRIBE ride_requests");
if (!$res) die("Table ride_requests not found\n");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

$res = $conn->query("DESCRIBE users");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
