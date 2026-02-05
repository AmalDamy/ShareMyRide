<?php
require_once 'db_connect.php';

$email = 'amaldamy2028@mca.ajce.in';
$new_rating = 4.8;

// Force update rating for demo
$stmt = $conn->prepare("UPDATE users SET rating = ? WHERE email = ?");
$stmt->bind_param("ds", $new_rating, $email);

if ($stmt->execute()) {
    echo "<h1>Rating Updated for $email</h1>";
    echo "<p>New Rating: <b>$new_rating</b></p>";
    echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
} else {
    echo "Error: " . $conn->error;
}
?>
