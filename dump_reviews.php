<?php
require 'db_connect.php';
$r = $conn->query('SELECT * FROM reviews');
echo "Total Reviews: " . $r->num_rows . "\n";
while($row = $r->fetch_assoc()){
    print_r($row);
}
?>
