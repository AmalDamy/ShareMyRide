<?php
$servername = "127.0.0.1";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password
$dbname = "sharemyride";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($dbname);

// Start Session for global access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
