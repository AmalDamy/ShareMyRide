<?php
// setup_db.php
// Script to run database.sql and initialize the database tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Connect without DB name first to create it if needed
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read SQL file
$sqlFile = 'database.sql';
if (!file_exists($sqlFile)) {
    die("Error: database.sql file not found.");
}

$sql = file_get_contents($sqlFile);

// Execute multi_query
if ($conn->multi_query($sql)) {
    echo "<h1>Database Setup Successful!</h1>";
    echo "<p>Tables created. Mock data inserted.</p>";
    echo "<p>You can now <a href='login.php'>Login</a> or go to the <a href='index.php'>Home Page</a>.</p>";
    
    // Process results to clear buffer
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
} else {
    echo "<h1>Error setting up database:</h1>";
    echo "<p>" . $conn->error . "</p>";
}

$conn->close();
?>
