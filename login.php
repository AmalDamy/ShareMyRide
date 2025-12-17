<?php
require_once 'db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ShareMyRide</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-teal: #10b981;
            --dark-teal: #047857;
            --bg-color: #f3f4f6;
            --text-dark: #1f2937;
            --text-gray: #6b7280;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            display: flex;
            width: 90%;
            max-width: 1100px;
            height: 650px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* LEFT SIDE - Illustration */
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--dark-teal), var(--primary-teal));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .illustration-content {
            position: relative;
            z-index: 10;
        }

        .illustration-content h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .illustration-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .hero-img {
            max-width: 80%;
            border-radius: 12px;
            box-shadow: 0 10px 15px rgba(0,0,0,0.2);
            /* Placeholder for actual illustration */
            background: rgba(255,255,255,0.2);
            height: 200px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        /* RIGHT SIDE - Form */
        .login-right {
            flex: 1;
            padding: 3rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .form-header p {
            color: var(--text-gray);
        }

        .input-group {
            margin-bottom: 1.25rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 0.9rem;
            background: var(--primary-teal);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: var(--dark-teal);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .toggle-link {
            color: var(--primary-teal);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .hidden {
            display: none;
        }

        .alert-box {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: none;
        }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                height: auto;
                margin: 2rem 0;
            }
            .login-left {
                padding: 2rem;
                height: 200px;
            }
            .hero-img {
                display: none;
            }
            .login-right {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        
        <!-- Left Side -->
        <div class="login-left">
            <div class="illustration-content">
                <h1>ShareMyRide</h1>
                <p>Join the community of students and travelers sharing rides, saving costs, and making friends.</p>
                <div class="hero-img">
                    <i class="fas fa-car-side"></i>
                </div>
            </div>
        </div>

        <!-- Right Side -->
        <div class="login-right">
            
            <!-- Login Form -->
            <div id="login-form">
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Enter your details to access your account.</p>
                </div>
                
                <div id="login-alert" class="alert-box alert-error"></div>

                <form onsubmit="handleAuth(event, 'login')">
                    <div class="input-group">
                        <label>Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="loginEmail" class="form-input" placeholder="student@college.edu" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="loginPass" class="form-input" placeholder="••••••••" required>
                            <i class="fas fa-eye" style="left: auto; right: 1rem; cursor: pointer;" onclick="togglePass('loginPass')"></i>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-gray); font-size: 0.9rem;">
                            <input type="checkbox"> Remember me
                        </label>
                        <a href="#" class="toggle-link" style="font-size: 0.9rem;">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn-primary">Sign In</button>
                    
                    <div style="text-align: center; margin-top: 1.5rem; color: var(--text-gray);">
                        Don't have an account? <span class="toggle-link" onclick="switchView('signup')">Sign Up</span>
                    </div>
                </form>
            </div>

            <!-- Signup Form -->
            <div id="signup-form" class="hidden">
                <div class="form-header">
                    <h2>Create Account</h2>
                    <p>Start your journey with ShareMyRide today.</p>
                </div>

                <div id="signup-alert" class="alert-box alert-error"></div>

                <form onsubmit="handleAuth(event, 'signup')">
                    <div class="input-group">
                        <label>Full Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="signupName" class="form-input" placeholder="John Doe" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="signupEmail" class="form-input" placeholder="student@college.edu" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="signupPass" class="form-input" placeholder="Create a strong password" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Create Account</button>
                    
                    <div style="text-align: center; margin-top: 1.5rem; color: var(--text-gray);">
                        Already have an account? <span class="toggle-link" onclick="switchView('login')">Sign In</span>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        function switchView(view) {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const alerts = document.querySelectorAll('.alert-box');
            
            alerts.forEach(a => a.style.display = 'none'); // Clear alerts

            if (view === 'signup') {
                loginForm.classList.add('hidden');
                signupForm.classList.remove('hidden');
            } else {
                signupForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
            }
        }

        function togglePass(id) {
            const input = document.getElementById(id);
            if (input.type === 'password') input.type = 'text';
            else input.type = 'password';
        }

        async function handleAuth(e, action) {
            e.preventDefault();
            
            const btn = e.target.querySelector('button');
            const originalText = btn.innerText;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', action);

            if (action === 'signup') {
                formData.append('name', document.getElementById('signupName').value);
                formData.append('email', document.getElementById('signupEmail').value);
                formData.append('password', document.getElementById('signupPass').value);
            } else {
                formData.append('email', document.getElementById('loginEmail').value);
                formData.append('password', document.getElementById('loginPass').value);
                formData.append('role', 'user'); // Default to user login
            }

            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                const alertBox = document.getElementById(action + '-alert');
                
                if (result.success) {
                    if (action === 'login') {
                        window.location.href = result.redirect;
                    } else {
                        // Signup success
                        alertBox.className = 'alert-box alert-success';
                        alertBox.innerText = result.message;
                        alertBox.style.display = 'block';
                        setTimeout(() => switchView('login'), 2000);
                    }
                } else {
                    alertBox.className = 'alert-box alert-error';
                    alertBox.innerText = result.message;
                    alertBox.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
