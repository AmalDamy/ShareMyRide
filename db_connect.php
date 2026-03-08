<?php
$servername = "127.0.0.1";
$username   = "root";  // Default XAMPP username
$password   = "";      // Default XAMPP password (blank)
$dbname     = "sharemyride";

// Create connection (without selecting DB first)
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        "status"  => "error",
        "message" => "DB Connection failed: " . $conn->connect_error
    ]));
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

// Select the database
$conn->select_db($dbname);

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Start Session for global access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
