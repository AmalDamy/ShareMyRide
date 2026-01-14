<?php
session_start();

// Set dummy session data to simulate a logged-in user
$_SESSION['user_id'] = 1; // Assuming ID 1 exists, or it might fail on some DB lookups if not exists, but UI will load.
$_SESSION['username'] = 'Test User';
$_SESSION['email'] = 'test@example.com';
$_SESSION['role'] = 'user';
$_SESSION['profile_pic'] = null;

// Redirect to dashboard
header("Location: dashboard.php");
exit;
?>
