<?php
require 'db_connect.php';
$r = $conn->query('DESCRIBE reviews');
while($row = $r->fetch_assoc()){
    print_r($row);
}
?>
