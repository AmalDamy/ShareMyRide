<?php
require_once 'config.php';
require_once 'db_connect.php';

// Driver John's ID is 2 (from previous populate script and database.sql)
$driver_id_to_remove = 2;

$stmt = $conn->prepare("DELETE FROM rides WHERE driver_id = ?");
$stmt->bind_param("i", $driver_id_to_remove);

if ($stmt->execute()) {
    echo "<h1>Successfully removed rides for Driver John.</h1>";
    echo "<p>Rows deleted: " . $stmt->affected_rows . "</p>";
    echo "<p><a href='find_ride.php'>Go back to Find Ride</a></p>";
} else {
    echo "<h1>Error:</h1> " . $stmt->error;
}

$conn->close();
?>
