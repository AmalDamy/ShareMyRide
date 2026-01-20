<?php
require_once 'config.php';

// Connect manually to ensure we can run this standalone
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = 'admin@sharemyride.com';
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Check if admin exists first
$check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    if ($stmt->execute()) {
        echo "Admin password updated successfully.<br>";
        echo "Email: " . $email . "<br>";
        echo "New Password: " . $new_password . "<br>";
    } else {
        echo "Error updating password: " . $conn->error;
    }
} else {
    // Create admin if not exists
    $name = 'Admin';
    $role = 'admin';
    $verified = 1;
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, is_verified) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $name, $email, $hashed_password, $role, $verified);
    if ($stmt->execute()) {
        echo "Admin user created successfully.<br>";
        echo "Email: " . $email . "<br>";
        echo "Password: " . $new_password . "<br>";
    } else {
        echo "Error creating admin: " . $conn->error;
    }
}

$conn->close();
?>
