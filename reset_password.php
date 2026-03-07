<?php
require_once 'db_connect.php';

$token = $_GET['token'] ?? '';
$isValid = false;
$email = '';

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > ?");
    $now = time();
    $stmt->bind_param("si", $token, $now);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $email = $row['email'];
        $isValid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ShareMyRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --text: #1f2937;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body {
            background-image: linear-gradient(135deg, #e0f2fe 0%, #f0fdf4 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h1 { font-size: 1.8rem; margin-bottom: 10px; color: var(--text); }
        p { color: #6b7280; font-size: 0.9rem; margin-bottom: 25px; }
        input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            outline: none;
            background: #f9fafb;
        }
        button {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover { background: var(--primary-dark); }
        .alert {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            display: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($isValid): ?>
            <h1>New Password</h1>
            <p>Set a new password for your account <strong><?php echo htmlspecialchars($email); ?></strong></p>
            
            <div id="alertBox" class="alert"></div>

            <form onsubmit="handleSubmit(event)">
                <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="password" id="newPass" placeholder="Enter new password" required minlength="6">
                <input type="password" id="confirmPass" placeholder="Confirm new password" required minlength="6">
                <button type="submit" id="submitBtn">Update Password</button>
            </form>
        <?php else: ?>
            <h1 style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Invalid Link</h1>
            <p>This password reset link is invalid or has expired.</p>
            <a href="forgot_password.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">Request a new link</a>
        <?php endif; ?>
    </div>

    <script>
        async function handleSubmit(e) {
            e.preventDefault();
            const token = document.getElementById('token').value;
            const newPass = document.getElementById('newPass').value;
            const confirmPass = document.getElementById('confirmPass').value;
            const btn = document.getElementById('submitBtn');
            const alertBox = document.getElementById('alertBox');

            if (newPass !== confirmPass) {
                alertBox.className = 'alert alert-error';
                alertBox.innerText = 'Passwords do not match.';
                alertBox.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            alertBox.style.display = 'none';

            try {
                const response = await fetch('api_reset_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token: token, password: newPass })
                });
                const data = await response.json();

                if (data.success) {
                    alertBox.className = 'alert alert-success';
                    alertBox.innerText = data.message;
                    alertBox.style.display = 'block';
                    setTimeout(() => window.location.href = 'login.php', 2000);
                } else {
                    alertBox.className = 'alert alert-error';
                    alertBox.innerText = data.message;
                    alertBox.style.display = 'block';
                }
            } catch (error) {
                alertBox.className = 'alert alert-error';
                alertBox.innerText = 'Something went wrong. Please try again.';
                alertBox.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.innerText = 'Update Password';
            }
        }
    </script>
</body>
</html>
