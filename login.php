<?php
require_once 'config.php';
require_once 'db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
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
    <title>Join ShareMyRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --white: #ffffff;
            --gray: #f3f4f6;
            --text: #1f2937;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: #f6f5f7;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            background-image: linear-gradient(135deg, #e0f2fe 0%, #f0fdf4 100%);
        }

        .container {
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.1), 0 10px 10px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            width: 850px;
            max-width: 100%;
            min-height: 550px;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        .sign-in-container {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .sign-up-container {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }

        /* Animation State */
        .container.right-panel-active .sign-in-container {
            transform: translateX(100%);
            opacity: 0;
        }

        .container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: show 0.6s;
        }

        @keyframes show {
            0%, 49.99% { opacity: 0; z-index: 1; }
            50%, 100% { opacity: 1; z-index: 5; }
        }

        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }

        .container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .overlay {
            background: linear-gradient(to right, #10b981, #059669);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 0 0;
            color: #ffffff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .container.right-panel-active .overlay {
            transform: translateX(50%);
        }

        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-left {
            transform: translateX(-20%);
        }

        .container.right-panel-active .overlay-left {
            transform: translateX(0);
        }

        .overlay-right {
            right: 0;
            transform: translateX(0);
        }

        .container.right-panel-active .overlay-right {
            transform: translateX(20%);
        }

        /* Form Styling */
        form {
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 50px;
            height: 100%;
            text-align: center;
        }

        h1 {
            font-weight: 700;
            margin: 0;
            font-size: 2rem;
            color: var(--text);
        }

        p {
            font-size: 14px;
            font-weight: 100;
            line-height: 20px;
            letter-spacing: 0.5px;
            margin: 20px 0 30px;
            color: #6b7280;
        }

        span {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 10px;
        }

        a {
            color: #333;
            font-size: 14px;
            text-decoration: none;
            margin: 15px 0;
        }

        button {
            border-radius: 20px;
            border: 1px solid var(--primary);
            background-color: var(--primary);
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in;
            cursor: pointer;
            margin-top: 10px;
        }

        button:active {
            transform: scale(0.95);
        }

        button:focus {
            outline: none;
        }

        button.ghost {
            background-color: transparent;
            border-color: #ffffff;
        }

        input {
            background-color: #eee;
            border: none;
            padding: 12px 15px;
            margin: 8px 0;
            width: 100%;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
        }

        input:focus {
            background-color: #e2e8f0;
        }

        /* Responsive Fixes */
        @media (max-width: 768px) {
            body {
                height: auto;
                min-height: 100vh;
                padding: 20px 0;
            }
            .container {
                width: 95%;
                min-height: auto;
                display: flex;
                flex-direction: column;
            }
            .form-container {
                position: relative;
                width: 100% !important;
                height: auto;
                transform: none !important;
            }
            .sign-in-container {
                display: block;
                padding: 40px 0;
            }
            .sign-up-container {
                display: none; /* We will use JS to toggle or just show sign-in first */
                padding: 40px 0;
                opacity: 1 !important;
                z-index: 2;
            }
            .container.right-panel-active .sign-in-container {
                display: none;
            }
            .container.right-panel-active .sign-up-container {
                display: block;
            }
            .overlay-container {
                display: none;
            }
            
            /* Mobile Toggle */
            .mobile-auth-toggle {
                display: block !important;
                margin-top: 20px;
                color: var(--primary);
                font-weight: 600;
                cursor: pointer;
            }
            
            form {
                padding: 0 20px;
            }
        }
        
        .mobile-auth-toggle {
            display: none;
        }
        
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: var(--primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 1000;
        }
        
        .back-link:hover {
            color: var(--primary-dark);
        }
        
        @media (max-width: 768px) {
            .container {
                width: 100vw;
                height: 100vh;
                border-radius: 0;
            }
            .sign-in-container, .sign-up-container {
                width: 100%;
            }
            .overlay-container {
                display: none;
            }
        }
        
        .overlay-panel p { color: #fff; }
        .overlay-panel h1 { color: #fff; }
        
        /* Alert Box */
         .alert-box {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: none;
            width: 100%;
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
    </style>
</head>
<body>

    <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Home</a>

    <div class="container" id="container">
        
        <!-- Sign Up Form -->
        <div class="form-container sign-up-container">
            <form id="signupForm" onsubmit="handleAuth(event, 'signup')">
                <h1>Create Account</h1>
                
                <div id="google_btn_signup" style="margin-bottom: 15px; height: 44px;"></div>
                
                <span class="my-2">or use your email for registration</span>
                
                <div id="signup-alert" class="alert-box alert-error"></div>

                <input type="text" id="signupName" placeholder="Name" oninput="validateSignupLive('signupName')" required />
                <input type="email" id="signupEmail" placeholder="Email" oninput="validateSignupLive('signupEmail')" required />
                <input type="password" id="signupPass" placeholder="Password" oninput="validateSignupLive('signupPass')" required />
                
                <button type="submit">Sign Up</button>
                
                <div class="mobile-auth-toggle">
                    Already have an account? <span onclick="toggleMobile('signIn')">Sign In</span>
                </div>
            </form>
        </div>

        <!-- Sign In Form -->
        <div class="form-container sign-in-container">
            <form id="loginForm" onsubmit="handleAuth(event, 'login')" autocomplete="off">
                <h1 style="margin-bottom: 1rem;">Sign in</h1>
                
                <div id="google_btn_login" style="margin-bottom: 15px; height: 44px;"></div>
                
                <script src="https://accounts.google.com/gsi/client" async defer></script>

                <span style="font-size: 0.8rem; margin: 15px 0;">or use your account</span>
                
                <div id="login-alert" class="alert-box alert-error"></div>
                
                <input type="hidden" id="selected-role" value="user"> 

                <input type="email" id="loginEmail" placeholder="Email" required />
                <input type="password" id="loginPass" placeholder="Password" required />
                <a href="forgot_password.php" style="color: var(--primary); font-weight: 500;">Forgot your password?</a>
                
                <button type="submit">Sign In</button>

                <p style="margin-top: 2rem; display: none;" class="mobile-toggle">New here? <a href="#" onclick="toggleMobile('signUp')">Sign Up</a></p>
            </form>
        </div>

        <!-- Overlay -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start your journey with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        // Sliding Animation
        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });

        // Mobile Responsive Logic for desktop-like behavior on mobile
        function toggleMobile(mode) {
            if (mode === 'signUp') {
                container.classList.add("right-panel-active");
            } else {
                container.classList.remove("right-panel-active");
            }
        }

        // Live Validation and UI Helpers below...

        // LIVE VALIDATION HELPERS
        function validateSignupLive(fieldId) {
            const name = document.getElementById('signupName').value.trim();
            const email = document.getElementById('signupEmail').value.trim();
            const pass = document.getElementById('signupPass').value.trim();
            const alertBox = document.getElementById('signup-alert');
            
            alertBox.style.display = 'none';
            alertBox.classList.remove('alert-success');
            alertBox.classList.add('alert-error');

            if (fieldId === 'signupName' && name.length > 0) {
                if (!/^[a-zA-Z\s\.\-']+$/.test(name)) {
                    alertBox.innerText = "Name can only contain letters, spaces, dots, and hyphens";
                    alertBox.style.display = 'block';
                    return false;
                }
                if (name.length < 3) {
                    alertBox.innerText = "Name must be at least 3 characters long";
                    alertBox.style.display = 'block';
                    return false;
                }
            }

            if (fieldId === 'signupEmail' && email.length > 0) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alertBox.innerText = "Please enter a valid email address";
                    alertBox.style.display = 'block';
                    return false;
                }
            }

            if (fieldId === 'signupPass' && pass.length > 0) {
                if (pass.length < 6) {
                    alertBox.innerText = "Password must be at least 6 characters long";
                    alertBox.style.display = 'block';
                    return false;
                }
            }
            return true;
        }

        function validateLoginLive(fieldId) {
            const email = document.getElementById('loginEmail').value.trim();
            const alertBox = document.getElementById('login-alert');
            
            alertBox.style.display = 'none';
            alertBox.classList.remove('alert-success');
            alertBox.classList.add('alert-error');

            if (fieldId === 'loginEmail' && email.length > 0) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alertBox.innerText = "Please enter a valid email address";
                    alertBox.style.display = 'block';
                    return false;
                }
            }
            return true;
        }

        // AUTH LOGIC (AUTHENTIC)
        async function handleAuth(e, action) {
            e.preventDefault();
            
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            
            // Final validation before submit
            let isValid = true;
            if (action === 'signup') {
                isValid = validateSignupLive('signupName') && 
                          validateSignupLive('signupEmail') && 
                          validateSignupLive('signupPass');
            } else {
                isValid = validateLoginLive('loginEmail');
            }

            if (!isValid) return;

            const originalText = btn.innerText;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', action);

            try {
                if (action === 'signup') {
                    formData.append('name', document.getElementById('signupName').value);
                    formData.append('email', document.getElementById('signupEmail').value);
                    formData.append('password', document.getElementById('signupPass').value);
                    formData.append('role', 'user'); 
                } else {
                    formData.append('email', document.getElementById('loginEmail').value);
                    formData.append('password', document.getElementById('loginPass').value);
                    formData.append('role', 'user'); 
                }

                const response = await fetch('auth.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                const alertBox = document.getElementById(action + '-alert');
                
                if (result.success) {
                    alertBox.className = 'alert-box alert-success';
                    alertBox.innerText = result.message;
                    alertBox.style.display = 'block';
                    
                    if (action === 'login') {
                        setTimeout(() => window.location.href = result.redirect, 1000);
                    } else {
                        setTimeout(() => {
                            container.classList.remove("right-panel-active"); // Switch to login
                            toggleMobile('signIn');
                            alertBox.style.display = 'none';
                        }, 2000);
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
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }

        // Google Sign-In Callback
        function handleCredentialResponse(response) {
            console.log("Encoded JWT ID token: " + response.credential);
            
            const gBtns = document.querySelectorAll('.g_id_signin');
            gBtns.forEach(btn => btn.style.opacity = '0.5');

            const formData = new FormData();
            formData.append('credential', response.credential);
            formData.append('role', 'user'); 
            formData.append('action', 'google_auth'); 

            fetch('google_callback.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const alertBox = document.getElementById('login-alert'); // Default to login alert
                
                if (data.success) {
                    alertBox.className = 'alert-box alert-success';
                    alertBox.innerHTML = '<i class="fas fa-check-circle"></i> ' + (data.message || 'Login successful!');
                    alertBox.style.display = 'block';
                    // Also show in signup if that was active
                    const signupAlert = document.getElementById('signup-alert');
                    if(container.classList.contains('right-panel-active')) {
                         signupAlert.className = 'alert-box alert-success';
                         signupAlert.innerHTML = '<i class="fas fa-check-circle"></i> ' + (data.message || 'Login successful!');
                         signupAlert.style.display = 'block';
                    }
                    
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    alertBox.className = 'alert-box alert-error';
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Google Login failed.');
                    alertBox.style.display = 'block';
                    gBtns.forEach(btn => btn.style.opacity = '1');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('An error occurred during Google Sign-In.');
                gBtns.forEach(btn => btn.style.opacity = '1');
            });
        }

        // Initialize Google Button
        window.onload = function() {
            // Re-run original onload logic
            const params = new URLSearchParams(window.location.search);
            if (params.get('mode') === 'signup') {
                container.classList.add("right-panel-active");
            }
            // Clear inputs for fresh login
            if(document.getElementById('loginEmail')) document.getElementById('loginEmail').value = '';
            if(document.getElementById('loginPass')) document.getElementById('loginPass').value = '';
            if(document.getElementById('signupName')) document.getElementById('signupName').value = '';
            if(document.getElementById('signupEmail')) document.getElementById('signupEmail').value = '';
            if(document.getElementById('signupPass')) document.getElementById('signupPass').value = '';

            // Initialize GSI
            try {
                google.accounts.id.initialize({
                    client_id: "<?php echo GOOGLE_CLIENT_ID; ?>",
                    callback: handleCredentialResponse
                });
                
                // Render Login Button
                const loginParent = document.getElementById("google_btn_login");
                if(loginParent) {
                    google.accounts.id.renderButton(
                        loginParent,
                        { theme: "outline", size: "large", width: 280, text: "sign_in_with" } 
                    );
                }

                // Render Signup Button
                const signupParent = document.getElementById("google_btn_signup");
                if(signupParent) {
                    google.accounts.id.renderButton(
                        signupParent,
                        { theme: "outline", size: "large", width: 280, text: "signup_with" } 
                    );
                }
            } catch (e) {
                console.error("Google Sign In Error:", e);
            }
        };
    </script>
</body>
</html>
