<?php
require_once 'db_connect.php';
require_once 'send_email.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// 1. Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Security: Don't reveal if email exists or not, just say sent
    // But for this project debugging, we might want to be explicit? 
    // Let's stick to standard practice but be helpful since it's dev.
    echo json_encode(['success' => false, 'message' => 'Email not found in our records.']);
    exit;
}

// 2. Generate Token
$token = bin2hex(random_bytes(32));
$expiry = time() + (15 * 60); // 15 mins

// 3. Store in DB
// First delete any old tokens for this email
$del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
$del->bind_param("s", $email);
$del->execute();

$ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
$ins->bind_param("ssi", $email, $token, $expiry);

if ($ins->execute()) {
    // 4. Send Email
    $resetLink = "http://localhost/sharemyride/preview_reset_password.php?token=" . $token;
    $emailResult = sendResetEmail($email, $resetLink);
    
    if ($emailResult['success']) {
        echo json_encode(['success' => true, 'message' => 'Reset link sent to your email.']);
    } else {
        // Fallback for simulation if SMTP fails
        echo json_encode([
            'success' => false, 
            'message' => 'could not send email (SMTP Error).',
            'reset_link' => $resetLink // Simulation Link
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>
