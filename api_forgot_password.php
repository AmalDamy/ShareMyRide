<?php
ob_start();
error_reporting(0);
require_once 'db_connect.php';
require_once 'send_email.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    // 1. Check if user exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows === 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Email not found in our records.']);
        exit;
    }

    // 2. Generate Token
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (15 * 60); // 15 mins

    // 3. Store in DB
    $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $del->bind_param("s", $email);
    $del->execute();

    $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $ins->bind_param("ssi", $email, $token, $expiry);

    if ($ins->execute()) {
        // 4. Send Email
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        // Get the directory of the current script
        $currentDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($currentDir === '/') $currentDir = '';
        
        $resetLink = "$protocol://$host$currentDir/reset_password.php?token=" . $token;
        $emailResult = sendResetEmail($email, $resetLink);
        
        ob_clean();
        if ($emailResult['success']) {
            echo json_encode(['success' => true, 'message' => 'Reset link sent to your email.', 'reset_link' => $resetLink]);
        } else {
            // Fallback for simulation
            $ipHint = " (Tip: Use 192.168.47.191 if testing from another device)";
            echo json_encode([
                'success' => false, 
                'message' => 'Could not send email (SMTP Error).' . $ipHint,
                'reset_link' => $resetLink
            ]);
        }
    } else {
        throw new Exception("Database error: " . $conn->error);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>
