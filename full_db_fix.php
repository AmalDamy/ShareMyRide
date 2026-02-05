<?php
require_once 'db_connect.php';

echo "<h2>Database Repair Tool</h2>";

// 1. Users Table
echo "Checking 'users' table... ";
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    google_id VARCHAR(255) UNIQUE DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    profile_pic VARCHAR(255) DEFAULT 'default_user.png',
    rating DECIMAL(3, 2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// Users Columns Checks
$cols = [
    "google_id" => "VARCHAR(255) UNIQUE DEFAULT NULL",
    "rating" => "DECIMAL(3, 2) DEFAULT 0.00",
    "rating_count" => "INT DEFAULT 0",
    "profile_pic" => "VARCHAR(255) DEFAULT 'default_user.png'"
];
foreach ($cols as $col => $def) {
    echo " - Checking column '$col' in 'users'... ";
    try {
        $conn->query("SELECT $col FROM users LIMIT 1");
        echo " <span style='color:green'>Exists</span><br>";
    } catch (Exception $e) {
        echo " <span style='color:orange'>Missing. Adding...</span> ";
        $conn->query("ALTER TABLE users ADD COLUMN $col $def");
        echo "Done.<br>";
    }
}


// 2. Rides Table
echo "<br>Checking 'rides' table... ";
$sql = "CREATE TABLE IF NOT EXISTS rides (
    ride_id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    from_location VARCHAR(255) NOT NULL,
    to_location VARCHAR(255) NOT NULL,
    ride_date DATE NOT NULL,
    ride_time TIME NOT NULL,
    end_date DATE,
    ride_type ENUM('daily', 'long') DEFAULT 'daily',
    seats_available INT NOT NULL,
    price_per_seat DECIMAL(10, 2) NOT NULL,
    total_cost DECIMAL(10, 2),
    vehicle_type VARCHAR(50) NOT NULL,
    vehicle_number VARCHAR(20),
    vehicle_model VARCHAR(100),
    details TEXT,
    intermediate_stops TEXT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// Rides Column Checks
$cols = [
    "end_date" => "DATE",
    "ride_type" => "ENUM('daily', 'long') DEFAULT 'daily'",
    "total_cost" => "DECIMAL(10, 2)",
    "intermediate_stops" => "TEXT",
    "status" => "ENUM('active', 'completed', 'cancelled') DEFAULT 'active'"
];
foreach ($cols as $col => $def) {
    echo " - Checking column '$col' in 'rides'... ";
    try {
        $check = $conn->query("SELECT $col FROM rides LIMIT 1");
        if($check) echo " <span style='color:green'>Exists</span><br>";
        else throw new Exception("Missing");
    } catch (Exception $e) {
        echo " <span style='color:orange'>Missing. Adding...</span> ";
        $conn->query("ALTER TABLE rides ADD COLUMN $col $def");
        echo "Done.<br>";
    }
}


// 3. Ride Requests Table
echo "<br>Checking 'ride_requests' table... ";
$sql = "CREATE TABLE IF NOT EXISTS ride_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    passenger_id INT NOT NULL,
    seats_requested INT DEFAULT 1,
    status ENUM('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending',
    proof_image VARCHAR(255) DEFAULT NULL,
    id_type VARCHAR(50) DEFAULT NULL,
    id_number VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES users(user_id) ON DELETE CASCADE
) " . (version_compare($conn->server_info, '5.6.5', '<') ? "ENGINE=InnoDB" : "");
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}


// Ride Requests Column Check
$cols = [
    "proof_image" => "VARCHAR(255) DEFAULT NULL",
    "id_type" => "VARCHAR(50) DEFAULT NULL",
    "id_number" => "VARCHAR(100) DEFAULT NULL",
    "pickup_loc" => "VARCHAR(100) DEFAULT NULL",
    "drop_loc" => "VARCHAR(100) DEFAULT NULL",
    "final_price" => "DECIMAL(10, 2) DEFAULT 0.00"
];
foreach ($cols as $col => $def) {
    echo " - Checking column '$col' in 'ride_requests'... ";
    try {
        $check = $conn->query("SELECT $col FROM ride_requests LIMIT 1");
        if($check) echo " <span style='color:green'>Exists</span><br>";
        else throw new Exception("Missing");
    } catch (Exception $e) {
        echo " <span style='color:orange'>Missing. Adding...</span> ";
        $conn->query("ALTER TABLE ride_requests ADD COLUMN $col $def");
        echo "Done.<br>";
    }
}
echo "<br>";


// 4. Notifications Table
echo "<br>Checking 'notifications' table... ";
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100),
    message TEXT,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}


// 5. Reviews Table
echo "<br>Checking 'reviews' table... ";
$sql = "CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(ride_id),
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id),
    FOREIGN KEY (reviewee_id) REFERENCES users(user_id)
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// 6. Password Resets Table
echo "<br>Checking 'password_resets' table... ";
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at INT NOT NULL,
    INDEX (email),
    INDEX (token)
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// 7. Messages Table (Chat)
echo "<br>Checking 'messages' table... ";
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    ride_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (ride_id) REFERENCES rides(ride_id) ON DELETE SET NULL
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// 8. Contact Messages Table
echo "<br>Checking 'contact_messages' table... ";
$sql = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    subject VARCHAR(200),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// 9. Admin Logs Table
echo "<br>Checking 'admin_logs' table... ";
$sql = "CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// 10. Vehicles Table
echo "<br>Checking 'vehicles' table... ";
$sql = "CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    make VARCHAR(50),      -- Toyota, Honda
    model VARCHAR(50),     -- Camry, Civic
    color VARCHAR(30),
    license_plate VARCHAR(20) UNIQUE,
    type VARCHAR(20),      -- Car, Bike, SUV
    capacity INT DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// 11. Payments Table
echo "<br>Checking 'payments' table... ";
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    payer_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50), -- Card, UPI, Cash
    transaction_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(ride_id),
    FOREIGN KEY (payer_id) REFERENCES users(user_id)
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// 12. Saved Locations Table
echo "<br>Checking 'saved_locations' table... ";
$sql = "CREATE TABLE IF NOT EXISTS saved_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50),      -- Home, Work, Gym
    address VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

// 13. KYC/Verification Documents
echo "<br>Checking 'kyc_documents' table... ";
$sql = "CREATE TABLE IF NOT EXISTS kyc_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type ENUM('license', 'student_id', 'aadhar') NOT NULL,
    document_number VARCHAR(100),
    file_path VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo " <span style='color:green'>OK (Created/Exists)</span><br>";
} else {
    echo " <span style='color:red'>Error: " . $conn->error . "</span><br>";
}

echo "<br><h2>Database Fix Complete!</h2>";
?>
