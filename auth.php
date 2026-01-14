<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'signup') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        echo JSON_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo JSON_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'user';
        
        echo JSON_encode(['success' => true, 'message' => 'Account created successfully']);
    } else {
        echo JSON_encode(['success' => false, 'message' => 'Error creating account']);
    }

} elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user'; // 'user' or 'admin'

    if (empty($email) || empty($password)) {
        echo JSON_encode(['success' => false, 'message' => 'Email and Password required']);
        exit;
    }

    $stmt = $conn->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify Password
        if (password_verify($password, $user['password'])) {
            
            // Verify Role if attempting admin login
            if ($role === 'admin' && $user['role'] !== 'admin') {
                echo JSON_encode(['success' => false, 'message' => 'Access Denied: Not an Admin']);
                exit;
            }

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            echo JSON_encode(['success' => true, 'redirect' => ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'), 'message' => 'Login successful. Redirecting...']);
        } else {
            echo JSON_encode(['success' => false, 'message' => 'Invalid Password']);
        }
    } else {
        echo JSON_encode(['success' => false, 'message' => 'User not found']);
    }

} elseif ($action === 'logout') {
    session_destroy();
    echo JSON_encode(['success' => true, 'redirect' => 'login.php']);

} else {
    echo JSON_encode(['success' => false, 'message' => 'Invalid Action']);
}
?>
