<?php
require_once 'config.php';
require_once 'db_connect.php';

$email = 'amaldamy2028@mca.ajce.in';
// A generic avatar for demonstration
$new_pic = 'https://ui-avatars.com/api/?name=Amal+Damy&background=0D8ABC&color=fff&size=200';

$stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE email = ? AND (profile_pic IS NULL OR profile_pic = 'default_user.png' OR profile_pic = '')");
$stmt->bind_param("ss", $new_pic, $email);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo "<h1>Profile Picture Updated!</h1>";
    echo "<p>We've set a temporary profile picture for <b>$email</b>.</p>";
    echo "<p>Please <a href='dashboard.php'>Refresh your Dashboard</a> to see it.</p>";
} else {
    echo "<h1>No changes made.</h1>";
    echo "<p>Maybe the user doesn't exist or already has a picture set.</p>";
}
?>
