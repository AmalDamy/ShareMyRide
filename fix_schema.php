<?php
require_once 'db_connect.php';

// Allow NULL for end_date and vehicle_type and details and total_cost
$sql1 = "ALTER TABLE rides MODIFY end_date DATE NULL";
$sql2 = "ALTER TABLE rides MODIFY total_cost DECIMAL(10,2) DEFAULT 0.00";
$sql3 = "ALTER TABLE rides MODIFY vehicle_type VARCHAR(50) NULL";
$sql4 = "ALTER TABLE rides MODIFY details TEXT NULL";

if($conn->query($sql1)) echo "end_date modified.<br>"; else echo "Error modifying end_date: " . $conn->error . "<br>";
if($conn->query($sql2)) echo "total_cost modified.<br>"; else echo "Error modifying total_cost: " . $conn->error . "<br>";
if($conn->query($sql3)) echo "vehicle_type modified.<br>"; else echo "Error modifying vehicle_type: " . $conn->error . "<br>";
if($conn->query($sql4)) echo "details modified.<br>"; else echo "Error modifying details: " . $conn->error . "<br>";

echo "Done.";
?>
