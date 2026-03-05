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

// Ensure the payments table exists so any query to it doesn't fail
$conn->query("
    CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        passenger_id INT NOT NULL,
        ride_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(5) DEFAULT 'INR',
        razorpay_order_id VARCHAR(100),
        razorpay_payment_id VARCHAR(100),
        razorpay_signature VARCHAR(255),
        status ENUM('pending','paid','failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (request_id),
        INDEX (passenger_id)
    )
");

// Start Session for global access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
