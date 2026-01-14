<?php
// create_admin.php
require_once 'config.php';
require_once 'db_connect.php';

$admin_email = 'admin@sharemyride.com';
// Changed to a stronger password to avoid browser security warnings
$admin_pass = 'ShareRide$2026!'; 
$hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
$admin_name = 'Super Admin';

// Check if admin exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Update existing admin
    $update = $conn->prepare("UPDATE users SET password = ?, role = 'admin', name = ? WHERE email = ?");
    $update->bind_param("sss", $hashed_pass, $admin_name, $admin_email);
    if ($update->execute()) {
        echo "<h1>Admin Account Updated</h1>";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    // Create new admin
    $insert = $conn->prepare("INSERT INTO users (name, email, password, role, is_verified) VALUES (?, ?, ?, 'admin', 1)");
    $insert->bind_param("sss", $admin_name, $admin_email, $hashed_pass);
    if ($insert->execute()) {
        echo "<h1>Admin Account Created</h1>";
    } else {
        echo "Error: " . $conn->error;
    }
}

echo "<div style='font-family: Arial; padding: 20px; border: 1px solid #ccc; max-width: 400px; background: #f9f9f9;'>";
echo "<h3>Credentials Updated!</h3>";
echo "<p>Use these exact credentials to login:</p>";
echo "<p>Email: <b>$admin_email</b></p>";
echo "<p>Password: <b style='color: green; font-size: 1.2em;'>$admin_pass</b></p>";
echo "<p><a href='login.php' style='display:inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Login Now</a></p>";
echo "</div>";
?>
