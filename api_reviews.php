<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$ride_id    = intval($data['ride_id']    ?? 0);
$request_id = intval($data['request_id'] ?? 0);
$rating     = intval($data['rating']     ?? 0);
$comment    = trim($data['comment']      ?? '');
$reviewer_id = $_SESSION['user_id'];

// Validate rating range
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please select a rating between 1 and 5 stars.']);
    exit;
}

// If ride_id not sent, look it up from request_id
if (empty($ride_id) && !empty($request_id)) {
    $reqQ = $conn->prepare("SELECT ride_id FROM ride_requests WHERE request_id = ? AND passenger_id = ?");
    $reqQ->bind_param("ii", $request_id, $reviewer_id);
    $reqQ->execute();
    $reqRow = $reqQ->get_result()->fetch_assoc();
    if ($reqRow) {
        $ride_id = intval($reqRow['ride_id']);
    }
}

if (!$ride_id) {
    echo json_encode(['success' => false, 'message' => 'Ride not found.']);
    exit;
}

// Verify the reviewer is actually a passenger of this ride
$passengerCheck = $conn->prepare("SELECT request_id FROM ride_requests WHERE ride_id = ? AND passenger_id = ? AND status IN ('accepted','completed')");
$passengerCheck->bind_param("ii", $ride_id, $reviewer_id);
$passengerCheck->execute();

if ($passengerCheck->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You are not a passenger of this ride.']);
    exit;
}

// Prevent duplicate review
$dupCheck = $conn->prepare("SELECT id FROM reviews WHERE ride_id = ? AND reviewer_id = ?");
$dupCheck->bind_param("ii", $ride_id, $reviewer_id);
$dupCheck->execute();
if ($dupCheck->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already rated this ride.']);
    exit;
}

// Find the driver to rate
$findDriver = $conn->prepare("SELECT driver_id FROM rides WHERE ride_id = ?");
$findDriver->bind_param("i", $ride_id);
$findDriver->execute();
$driverRow = $findDriver->get_result()->fetch_assoc();

if (!$driverRow) {
    echo json_encode(['success' => false, 'message' => 'Ride not found.']);
    exit;
}

$reviewee_id = intval($driverRow['driver_id']);

// Save the review
$stmt = $conn->prepare("INSERT INTO reviews (ride_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iiiis", $ride_id, $reviewer_id, $reviewee_id, $rating, $comment);

if ($stmt->execute()) {
    // Recalculate driver's average rating and update users table
    $avgQ = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM reviews WHERE reviewee_id = ?");
    $avgQ->bind_param("i", $reviewee_id);
    $avgQ->execute();
    $avgRow = $avgQ->get_result()->fetch_assoc();

    $newAvg = round(floatval($avgRow['avg_rating']), 2);
    $count  = intval($avgRow['cnt']);

    $updUser = $conn->prepare("UPDATE users SET rating = ?, rating_count = ? WHERE user_id = ?");
    $updUser->bind_param("dii", $newAvg, $count, $reviewee_id);
    $updUser->execute();

    echo json_encode([
        'success'    => true,
        'message'    => 'Rating submitted! Driver rating updated to ' . number_format($newAvg, 1) . ' ⭐',
        'new_rating' => $newAvg
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error saving review. Please try again.']);
}
?>
