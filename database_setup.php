<?php
require_once 'db_connect.php';

// Users Table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    rating DECIMAL(3,2) DEFAULT 5.00,
    rating_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Rides Table
$sql = "CREATE TABLE IF NOT EXISTS rides (
    ride_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    driver_id INT(11) NOT NULL,
    from_location VARCHAR(100) NOT NULL,
    to_location VARCHAR(100) NOT NULL,
    ride_date DATE NOT NULL,
    ride_time TIME NOT NULL,
    ride_type ENUM('daily', 'long') DEFAULT 'daily',
    seats_available INT(11) NOT NULL,
    price_per_seat DECIMAL(10,2) NOT NULL,
    vehicle_type VARCHAR(50),
    details TEXT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date DATE NULL,
    total_cost DECIMAL(10,2) DEFAULT 0
)";
$conn->query($sql);

// Ride Requests Table
$sql = "CREATE TABLE IF NOT EXISTS ride_requests (
    request_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ride_id INT(11) NOT NULL,
    passenger_id INT(11) NOT NULL,
    seats_requested INT(11) NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Reviews Table
$sql = "CREATE TABLE IF NOT EXISTS reviews (
    review_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ride_id INT(11) NOT NULL,
    reviewer_id INT(11) NOT NULL,
    reviewee_id INT(11) NOT NULL,
    rating INT(1) NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table reviews created successfully\n";
} else {
    echo "Error creating reviews table: " . $conn->error . "\n";
}

// Add columns to users if missing
$conn->query("ALTER TABLE users ADD COLUMN rating DECIMAL(3,2) DEFAULT 5.00");
$conn->query("ALTER TABLE users ADD COLUMN rating_count INT DEFAULT 0");

echo "Database setup completed.";
?>
