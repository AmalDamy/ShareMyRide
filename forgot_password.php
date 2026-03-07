<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ShareMyRide</title>
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
        .back-link {
            display: block;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }
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
        <h1>Reset Password</h1>
        <p>Enter your email address and we'll send you a link to reset your password.</p>
        
        <div id="alertBox" class="alert"></div>

        <form onsubmit="handleSubmit(event)">
            <input type="email" id="email" placeholder="Enter your email" required>
            <button type="submit" id="submitBtn">Send Reset Link</button>
        </form>

        <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Login</a>
    </div>

    <script>
        async function handleSubmit(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const btn = document.getElementById('submitBtn');
            const alertBox = document.getElementById('alertBox');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            alertBox.style.display = 'none';

            try {
                const response = await fetch('api_forgot_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: email })
                });
                
                const rawText = await response.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (pe) {
                    console.error('Non-JSON response:', rawText);
                    throw new Error('Server error occurred.');
                }

                if (data.success) {
                    alertBox.className = 'alert alert-success';
                    alertBox.innerHTML = `<strong>Success!</strong> ${data.message}`;
                    if (data.reset_link) {
                        alertBox.innerHTML += `<br><br><strong>Direct Link:</strong><br><a href="${data.reset_link}" style="word-break: break-all;">${data.reset_link}</a>`;
                    }
                    alertBox.style.display = 'block';
                } else {
                    alertBox.className = 'alert alert-error';
                    alertBox.innerText = data.message;
                    if (data.reset_link) {
                         alertBox.innerHTML += `<br><br><strong>Demo Mode Link:</strong><br><a href="${data.reset_link}" style="word-break: break-all;">${data.reset_link}</a>`;
                    }
                    alertBox.style.display = 'block';
                }
            } catch (error) {
                alertBox.className = 'alert alert-error';
                alertBox.innerText = error.message || 'Something went wrong. Please try again.';
                alertBox.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.innerText = 'Send Reset Link';
            }
        }
    </script>
</body>
</html>
