<?php
$servername = getenv('DB_HOST') ?: "127.0.0.1";
$username   = getenv('DB_USER') ?: "root";
$password   = getenv('DB_PASS') ?: "";
$dbname     = getenv('DB_NAME') ?: "sharemyride";
$dbport     = getenv('DB_PORT') ?: 3306;

// Create connection (using port for Aiven compatibility)
$conn = new mysqli($servername, $username, $password, $dbname, $dbport);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        "status"  => "error",
        "message" => "DB Connection failed: " . $conn->connect_error
    ]));
}

// Skip manual DB selection if using Aiven (already selected in mysqli constructor)
// $conn->select_db($dbname);

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Start Session for global access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
