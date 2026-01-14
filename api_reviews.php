<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo JSON_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $ride_id = $data['ride_id'] ?? 0;
    $rating = $data['rating'] ?? 5;
    $comment = $data['comment'] ?? '';
    $reviewer_id = $_SESSION['user_id'];
    
    // Find who to rate (The driver of the ride)
    // Assuming reviewer is passenger
    $findDriver = $conn->query("SELECT driver_id FROM rides WHERE ride_id = $ride_id");
    
    if ($findDriver->num_rows === 0) {
        // Maybe reviewer is driver rating passenger? 
        // For simplicity, let's assume Passenger -> Driver rating mostly
        echo JSON_encode(['success' => false, 'message' => 'Ride not found']);
        exit;
    }

    $row = $findDriver->fetch_assoc();
    $reviewee_id = $row['driver_id']; 
    
    // Save Review
    $stmt = $conn->prepare("INSERT INTO reviews (ride_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $ride_id, $reviewer_id, $reviewee_id, $rating, $comment);
    
    if ($stmt->execute()) {
        
        // Update Driver's Average Rating
        $avgQ = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE reviewee_id = $reviewee_id");
        $avgRow = $avgQ->fetch_assoc();
        $newAvg = $avgRow['avg_rating'];
        $count = $avgRow['count'];
        
        $conn->query("UPDATE users SET rating = $newAvg, rating_count = $count WHERE user_id = $reviewee_id");

        echo JSON_encode(['success' => true, 'message' => 'Review submitted']);
    } else {
        echo JSON_encode(['success' => false, 'message' => 'Error submitting review']);
    }

} else {
    echo JSON_encode(['success' => false, 'message' => 'Invalid Method']);
}
?>
