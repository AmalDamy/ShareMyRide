<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', ''); // Updated to empty for XAMPP default, user can change if needed
define('DB_NAME', 'sharemyride');

// Google Configuration
define('GOOGLE_CLIENT_ID', '873781652172-bhui7j2gn3cju8nv8nlvunte90nvjjho.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', ''); // Not required for GSI Popup flow
define('GOOGLE_REDIRECT_URI', 'http://localhost/sharemyride/google_callback.php'); // Standard XAMPP path

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
