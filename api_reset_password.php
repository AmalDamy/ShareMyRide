<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';
$password = $data['password'] ?? '';

if (!$token || !$password) {
    echo json_encode(['success' => false, 'message' => 'Missing token or password.']);
    exit;
}

// 1. Validate Token
$stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or used reset token.']);
    exit;
}

$resetRequest = $result->fetch_assoc();
if (time() > $resetRequest['expires_at']) {
    echo json_encode(['success' => false, 'message' => 'Token has expired.']);
    exit;
}

$email = $resetRequest['email'];

// 2. Update Password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$update->bind_param("ss", $hashedPassword, $email);

if ($update->execute()) {
    // 3. Delete Token
    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $del->bind_param("s", $email);
    $del->execute();
    
    echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
}

$conn->close();
?>
