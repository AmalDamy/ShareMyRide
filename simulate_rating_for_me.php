<?php
require 'db_connect.php';

// Force create a review for User 4 (Amal) from User 5 (Sharon)
// Associated with Ride 1 (Driven by Amal)

$ride_id = 1;
$reviewer_id = 5; // Sharon
$reviewee_id = 4; // Amal
$rating = 4.5;
$comment = "Great driver, very punctual!";

// Check if already exists to avoid duplicates
$check = $conn->query("SELECT review_id FROM reviews WHERE ride_id=$ride_id AND reviewer_id=$reviewer_id");
if($check->num_rows > 0) {
    echo "Review already exists for this ride/reviewer combination.<br>";
} else {
    $stmt = $conn->prepare("INSERT INTO reviews (ride_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $ride_id, $reviewer_id, $reviewee_id, $rating, $comment);
    if($stmt->execute()) {
        echo "Successfully inserted fake review for User $reviewee_id.<br>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Now update the user stats
$avgQ = $conn->query("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE reviewee_id = $reviewee_id");
$avg = $avgQ->fetch_assoc();
$newRating = $avg['avg'];
$count = $avg['cnt'];

$conn->query("UPDATE users SET rating = $newRating, rating_count = $count WHERE user_id = $reviewee_id");

echo "Updated User $reviewee_id: New Rating = $newRating (from $count reviews).";
?>
