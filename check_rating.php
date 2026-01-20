<?php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$user_id = $_SESSION['user_id'];

echo "<h1>Debug Rating for User ID: $user_id</h1>";

// 1. Check User Table
$stmt = $conn->prepare("SELECT name, email, rating, rating_count FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

echo "<h2>User Table Data</h2>";
echo "<pre>";
print_r($user);
echo "</pre>";

// 2. Check Reviews Received (Where I am the reviewee)
echo "<h2>Reviews Received (as Driver/Target)</h2>";
$revQ = $conn->query("SELECT * FROM reviews WHERE reviewee_id = $user_id");
if ($revQ->num_rows > 0) {
    while($row = $revQ->fetch_assoc()) {
        echo "Review ID: " . $row['review_id'] . " | Rating: " . $row['rating'] . " | Comment: " . $row['comment'] . "<br>";
    }
} else {
    echo "No reviews received yet.<br>";
}

// 3. Check Reviews Given (Where I am the reviewer)
echo "<h2>Reviews Given (by me)</h2>";
$givenQ = $conn->query("SELECT * FROM reviews WHERE reviewer_id = $user_id");
if ($givenQ->num_rows > 0) {
    while($row = $givenQ->fetch_assoc()) {
        echo "Review ID: " . $row['review_id'] . " | To User: " . $row['reviewee_id'] . " | Rating: " . $row['rating'] . "<br>";
    }
} else {
    echo "Has not written any reviews.<br>";
}

echo "<br><br><b>Explanation:</b> Your dashboard rating reflects the average of 'Reviews Received'. If you just submitted a rating for someone else, THAT person's rating updated, not yours.";
?>
