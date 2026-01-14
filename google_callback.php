<?php
require_once 'config.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}


try {
    // 1. Get the ID Token from the POST request
    file_put_contents('debug_log.txt', "Callback hit at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    if ($conn->connect_error) {
        throw new Exception("DB Connection Fatal Error: " . $conn->connect_error);
    }

    if (!isset($_POST['credential'])) {
        throw new Exception("No credential provided.");
    }
    file_put_contents('debug_log.txt', "Credential received\n", FILE_APPEND);

    $id_token = $_POST['credential'];
    $input_role = isset($_POST['role']) ? $_POST['role'] : 'user';

    // 2. Verify ID Token via Google API (using cURL)
    // This verifies that the token was actually issued by Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Disable SSL verification for local XAMPP dev only. 
    // In production, you MUST have valid CA certs.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === FALSE || $http_code !== 200) {
        throw new Exception("Token verification failed: " . $curl_error . " (HTTP $http_code)");
    }

    $payload = json_decode($response, true);

    if (isset($payload['error_description'])) {
        throw new Exception("Google Error: " . $payload['error_description']);
    }

    // 3. Security Check: Validate Audience (Client ID)
    if ($payload['aud'] !== GOOGLE_CLIENT_ID) {
        throw new Exception("Invalid Client ID. Token not intended for this app.");
    }

    // 4. Extract User Info
    $google_id = $payload['sub']; // Unique ID from Google
    $email = $payload['email'];
    $name = $payload['name'];
    $picture = $payload['picture']; // Might be missing, so check existence if used

    file_put_contents('debug_log.txt', "User Info: $email, $name, $google_id\n", FILE_APPEND);

    // 5. Database Logic
    file_put_contents('debug_log.txt', "Step 5: Starting DB checks\n", FILE_APPEND);

    // Check if user exists by google_id
    $stmt = $conn->prepare("SELECT user_id, name, email, role, google_id FROM users WHERE google_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $google_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();

    file_put_contents('debug_log.txt', "Rows found via Google ID: " . $result->num_rows . "\n", FILE_APPEND);

    if ($result->num_rows === 1) {
        // A. User exists with this Google ID -> Log them in
        $user = $result->fetch_assoc();
        $stmt->close(); // Close explicitly
        loginUser($user);
        
    } else {
        // B. No user with this Google ID, check by Email (Linking accounts)
        $stmt->close();
        
        file_put_contents('debug_log.txt', "Checking by email: $email\n", FILE_APPEND);

        $stmt = $conn->prepare("SELECT user_id, name, email, role, google_id FROM users WHERE email = ?");
        if (!$stmt) {
             throw new Exception("Prepare (email) failed: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result_email = $stmt->get_result();
        
        file_put_contents('debug_log.txt', "Rows found via Email: " . $result_email->num_rows . "\n", FILE_APPEND);

        if ($result_email->num_rows === 1) {
            // User exists by email but hasn't linked Google yet -> Link it now
            file_put_contents('debug_log.txt', "User exists via Email. Linking account.\n", FILE_APPEND);
            $user = $result_email->fetch_assoc();
            $stmt->close(); // Close before new prepare
            
            $update_stmt = $conn->prepare("UPDATE users SET google_id = ? WHERE user_id = ?");
            $update_stmt->bind_param("si", $google_id, $user['user_id']);
            if (!$update_stmt->execute()) {
                 throw new Exception("Link Update failed: " . $update_stmt->error);
            }
            $update_stmt->close();
            
            loginUser($user);
            
        } else {
            $stmt->close(); // Close before new prepare

            // C. New User -> Create Account (Auto-Signup)
            file_put_contents('debug_log.txt', "Creating new user\n", FILE_APPEND);
            
            $random_password = bin2hex(random_bytes(16)); // Secure random password
            $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
            
            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role, google_id, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
            $insert_stmt->bind_param("sssss", $name, $email, $hashed_password, $input_role, $google_id);
            
            if ($insert_stmt->execute()) {
                $new_user_id = $insert_stmt->insert_id;
                $insert_stmt->close();
                
                // Construct user array for session
                $new_user = [
                    'user_id' => $new_user_id,
                    'name' => $name,
                    'email' => $email, // Ensure email is passed
                    'role' => $input_role
                ];
                loginUser($new_user);
            } else {
                throw new Exception("Registration failed: " . $conn->error);
            }
        }
    }

    $conn->close();

} catch (Exception $e) {
    file_put_contents('debug_log.txt', "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function loginUser($user) {
    // Check headers sent to avoid warning output breaking JSON
    if (headers_sent()) {
       file_put_contents('debug_log.txt', "Headers already sent, cannot JSON encode properly.\n", FILE_APPEND);
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['name']; // Use current name in DB
    $_SESSION['email'] = $user['email'];   // Create session for email
    $_SESSION['role'] = $user['role'];
    
    file_put_contents('debug_log.txt', "Login Success: " . $_SESSION['email'] . "\n", FILE_APPEND);

    echo json_encode([
        'success' => true, 
        'redirect' => ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'),
        'message' => 'Login successful.'
    ]);
    exit;
}

?>
