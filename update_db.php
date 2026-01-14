<?php
require_once 'config.php';
require_once 'db_connect.php';

// Check if google_id column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");

if ($checkColumn->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE DEFAULT NULL AFTER email";
    
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added google_id column to users table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "google_id column already exists.\n";
}

$conn->close();
?>
