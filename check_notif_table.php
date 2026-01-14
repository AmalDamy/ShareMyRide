<?php
require_once 'db_connect.php';
$table = 'notifications';
$result = $conn->query("SHOW COLUMNS FROM $table");
if (!$result) {
    // If table doesn't exist, create it
    echo "Table '$table' does not exist. Creating...<br>";
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(20) DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )";
    if ($conn->query($sql)) {
        echo "Table created.<br>";
    } else {
        echo "Error creating table: " . $conn->error;
    }
} else {
    echo "<h2>Columns in '$table':</h2><ul>";
    while($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ul>";
}
?>
