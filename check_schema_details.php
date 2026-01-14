<?php
require_once 'db_connect.php';

$table = 'notifications';
$result = $conn->query("SHOW COLUMNS FROM $table");

echo "<h2>Columns in '$table':</h2><table border=1><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
}
echo "</table>";
?>
