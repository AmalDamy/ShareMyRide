<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Handle Requests
if ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo JSON_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'create'; // create, accept, reject

    if ($action === 'create') {
        // Passenger requesting a ride
        $ride_id = $data['ride_id'] ?? 0;
        $seats = $data['seats_requested'] ?? 1;
        $passenger_id = $_SESSION['user_id'];

        // Check if already requested
        $check = $conn->prepare("SELECT request_id FROM ride_requests WHERE ride_id = ? AND passenger_id = ?");
        $check->bind_param("ii", $ride_id, $passenger_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo JSON_encode(['success' => false, 'message' => 'You have already requested this ride']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO ride_requests (ride_id, passenger_id, seats_requested) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $ride_id, $passenger_id, $seats);

        if ($stmt->execute()) {
            
            // Notify Driver (Insert into notifications table)
            // Get driver ID
            $getDriver = $conn->query("SELECT driver_id FROM rides WHERE ride_id = $ride_id");
            $driverRow = $getDriver->fetch_assoc();
            $driver_id = $driverRow['driver_id'];
            
            $msg = "New ride request from a user!";
            $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($driver_id, 'New Ride Request', '$msg', 'info')");

            echo JSON_encode(['success' => true, 'message' => 'Request sent successfully']);
        } else {
            echo JSON_encode(['success' => false, 'message' => 'Error sending request']);
        }

    } elseif ($action === 'accept' || $action === 'reject') {
        $request_id = $data['request_id'] ?? 0;
        $driver_id = $_SESSION['user_id'];
        
        // Verify driver owns the ride
        $check = $conn->prepare("
            SELECT rq.ride_id, rq.seats_requested, r.driver_id, r.seats_available 
            FROM ride_requests rq 
            JOIN rides r ON rq.ride_id = r.ride_id 
            WHERE rq.request_id = ?
        ");
        $check->bind_param("i", $request_id);
        $check->execute();
        $res = $check->get_result();
        
        if ($res->num_rows === 0) {
            echo JSON_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }

        $row = $res->fetch_assoc();
        
        if ($row['driver_id'] != $driver_id) {
            echo JSON_encode(['success' => false, 'message' => 'Unauthorized action']);
            exit;
        }
        
        $new_status = ($action === 'accept') ? 'accepted' : 'rejected';
        
        // If accepting, check seats
        if ($action === 'accept' && $row['seats_available'] < $row['seats_requested']) {
            echo JSON_encode(['success' => false, 'message' => 'Not enough seats available']);
            exit;
        }

        // Update Request Status
        $upd = $conn->prepare("UPDATE ride_requests SET status = ? WHERE request_id = ?");
        $upd->bind_param("si", $new_status, $request_id);
        
        if ($upd->execute()) {
            
            // If accepted, decrease seats
            if ($action === 'accept') {
                $new_seats = $row['seats_available'] - $row['seats_requested'];
                $conn->query("UPDATE rides SET seats_available = $new_seats WHERE ride_id = " . $row['ride_id']);
                
                // Notify Passenger
                $pass_id = $conn->query("SELECT passenger_id FROM ride_requests WHERE request_id = $request_id")->fetch_assoc()['passenger_id'];
                $msg = "Your ride request has been accepted!";
                $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($pass_id, 'Ride Accepted', '$msg', 'success')");
            }

            echo JSON_encode(['success' => true, 'message' => 'Request ' . $new_status]);
        } else {
            echo JSON_encode(['success' => false, 'message' => 'Database error']);
        }

    } elseif ($action === 'complete_passenger') {
        // Passenger confirming they arrived
        $request_id = $data['request_id'] ?? 0;
        $passenger_id = $_SESSION['user_id'];
        
        // Verify ownership
        $stmt = $conn->prepare("UPDATE ride_requests SET status = 'completed' WHERE request_id = ? AND passenger_id = ? AND status = 'accepted'");
        $stmt->bind_param("ii", $request_id, $passenger_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo JSON_encode(['success' => true, 'message' => 'Ride marked as completed']);
        } else {
             echo JSON_encode(['success' => false, 'message' => 'Could not complete ride. Check if it is accepted.']);
        }
    }

} elseif ($method === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo JSON_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $type = $_GET['type'] ?? 'incoming'; // incoming (for driver) or outgoing (my requests)

    if ($type === 'incoming') {
        // Requests FOR this user (Driver)
        $sql = "SELECT rq.*, r.from_location, r.to_location, r.ride_date, r.ride_time, 
                       u.name as passenger_name, u.email as passenger_email, u.profile_pic, u.rating 
                FROM ride_requests rq 
                JOIN rides r ON rq.ride_id = r.ride_id 
                JOIN users u ON rq.passenger_id = u.user_id 
                WHERE r.driver_id = ? AND rq.status = 'pending'
                ORDER BY rq.created_at DESC";
    } else {
        // Requests BY this user (Passenger)
        // Check if rated: JOIN with reviews table
        $sql = "SELECT rq.*, r.from_location, r.to_location, r.ride_date, r.ride_time, r.price_per_seat,
                       u.name as driver_name,
                       (SELECT COUNT(*) FROM reviews rev WHERE rev.ride_id = rq.ride_id AND rev.reviewer_id = rq.passenger_id) as has_rated
                FROM ride_requests rq 
                JOIN rides r ON rq.ride_id = r.ride_id 
                JOIN users u ON r.driver_id = u.user_id 
                WHERE rq.passenger_id = ?
                ORDER BY rq.created_at DESC";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }

    echo JSON_encode(['success' => true, 'requests' => $requests]);
}
?>
