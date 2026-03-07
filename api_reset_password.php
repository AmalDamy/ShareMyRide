<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';
$password = $data['password'] ?? '';

if (empty($token) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Missing data.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit;
}

// 1. Verify token
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > ?");
$now = time();
$stmt->bind_param("si", $token, $now);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token.']);
    exit;
}

$row = $res->fetch_assoc();
$email = $row['email'];

// 2. Hash new password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// 3. Update User Password
$upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$upd->bind_param("ss", $hashed, $email);

if ($upd->execute()) {
    // 4. Delete token
    $conn->query("DELETE FROM password_resets WHERE email = '$email'");
    
    echo json_encode(['success' => true, 'message' => 'Password updated successfully! Redirecting to login...']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>
