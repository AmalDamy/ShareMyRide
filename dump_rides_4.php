<?php
require 'db_connect.php';
$r = $conn->query('SELECT * FROM rides WHERE driver_id=4');
echo "Count: " . $r->num_rows . "\n";
while($row=$r->fetch_assoc()){
    print_r($row);
}
?>
