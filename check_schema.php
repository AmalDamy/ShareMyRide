<?php
require_once 'db_connect.php';

$table = 'rides';
$result = $conn->query("SHOW COLUMNS FROM $table");

echo "<h2>Columns in '$table':</h2><ul>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
}
echo "</ul>";
?>
