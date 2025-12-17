<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// GET: Fetch Rides
if ($method === 'GET') {
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    $date = $_GET['date'] ?? '';
    $type = $_GET['type'] ?? 'daily'; // Default to daily rides

    $sql = "SELECT r.*, u.name as driver_name, u.rating, u.profile_pic 
            FROM rides r 
            JOIN users u ON r.driver_id = u.user_id 
            WHERE r.status = 'active'";
    
    // Filter by Type
    $sql .= " AND r.ride_type = '$type'";

    $params = [];
    $types = "";

    if (!empty($from)) {
        $sql .= " AND r.from_location LIKE ?";
        $params[] = "%$from%";
        $types .= "s";
    }
    if (!empty($to)) {
        $sql .= " AND r.to_location LIKE ?";
        $params[] = "%$to%";
        $types .= "s";
    }
    if (!empty($date)) {
        $sql .= " AND r.ride_date = ?";
        $params[] = $date;
        $types .= "s";
    }
    
    // Check for specific ride_id (Bypass type filter if ID provided)
    $ride_id = $_GET['ride_id'] ?? '';
    if (!empty($ride_id)) {
        // Reset SQL for ID lookup to find ANY trip type
        $sql = "SELECT r.*, u.name as driver_name, u.rating, u.profile_pic 
                FROM rides r 
                JOIN users u ON r.driver_id = u.user_id 
                WHERE r.ride_id = ?";
        $params = [$ride_id];
        $types = "i";
    } else {
        $sql .= " ORDER BY r.ride_date ASC, r.ride_time ASC";
    }

    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rides = [];
    while ($row = $result->fetch_assoc()) {
        $rides[] = $row;
    }
    
    // If ride_id was requested, return a single object or error if not found
    if (!empty($ride_id)) {
        if (count($rides) > 0) {
            echo JSON_encode(['success' => true, 'ride' => $rides[0]]);
        } else {
            echo JSON_encode(['success' => false, 'message' => 'Ride not found']);
        }
    } else {
        echo JSON_encode(['success' => true, 'rides' => $rides]);
    }
    
    exit;

// POST: Create Ride
} elseif ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo JSON_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $driver_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    $type = $data['type'] ?? 'daily'; // daily or long
    $from = $data['from'] ?? '';
    $to = $data['to'] ?? '';
    $date = $data['date'] ?? '';
    $time = $data['time'] ?? '08:00'; // Default time for long trips if not set
    $seats = $data['seats'] ?? 1;
    $price = $data['price'] ?? 0;
    $vehicle = $data['vehicle'] ?? '';
    $details = $data['details'] ?? '';
    
    // New fields for Long Trip
    $end_date = $data['end_date'] ?? NULL;
    $total_cost = $data['total_cost'] ?? 0;
    
    if (empty($from) || empty($to) || empty($date)) {
        echo JSON_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO rides (driver_id, from_location, to_location, ride_date, ride_time, end_date, ride_type, seats_available, price_per_seat, total_cost, vehicle_type, details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssidsss", $driver_id, $from, $to, $date, $time, $end_date, $type, $seats, $price, $total_cost, $vehicle, $details);

    if ($stmt->execute()) {
        echo JSON_encode(['success' => true, 'message' => ucfirst($type) . ' trip published successfully', 'ride_id' => $stmt->insert_id]);
    } else {
        echo JSON_encode(['success' => false, 'message' => 'Error publishing ride: ' . $conn->error]);
    }

} else {
    echo JSON_encode(['success' => false, 'message' => 'Invalid Method']);
}
?>
