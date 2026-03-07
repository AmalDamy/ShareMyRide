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

    // Parse Input (JSON or FormData)
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $data = $input;
    } else {
        $data = $_POST;
    }

    $action = $data['action'] ?? 'create'; // create, accept, reject

    if ($action === 'create') {
        // Passenger requesting a ride
        $ride_id = $data['ride_id'] ?? 0;
        $seats = $data['seats_requested'] ?? 1;
        $id_type = $data['id_type'] ?? null;
        $id_number = $data['id_number'] ?? null;
        $passenger_id = $_SESSION['user_id'];
        $phone = trim($data['phone'] ?? '');

        // DEBUG: Log the phone capture
        file_put_contents('debug_phone.txt', "User $passenger_id sent phone: '$phone' at " . date('H:i:s') . "\n", FILE_APPEND);

        // Update user phone if provided
        if (!empty($phone) && strlen($phone) >= 10) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            $conn->query("UPDATE users SET phone = '$phone' WHERE user_id = $passenger_id"); 
            $_SESSION['phone'] = $phone;
        }

        // Check if already requested
        $check = $conn->prepare("SELECT request_id, final_price FROM ride_requests WHERE ride_id = ? AND passenger_id = ?");
        $check->bind_param("ii", $ride_id, $passenger_id);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            echo JSON_encode(['success' => false, 'message' => 'You have already requested this ride.', 'request_id' => $row['request_id'], 'amount' => $row['final_price'], 'already_requested' => true]);
            exit;
        }

        // Check if user is the driver
        $driverCheck = $conn->query("SELECT driver_id FROM rides WHERE ride_id = $ride_id");
        if($driverCheck && $driverCheck->num_rows > 0) {
            $dRow = $driverCheck->fetch_assoc();
            if($dRow['driver_id'] == $passenger_id) {
                echo JSON_encode(['success' => false, 'message' => 'You cannot request your own ride']);
                exit;
            }
        }

        // Handle Proof Image Upload
        $proof_path = null;
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/proofs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileInfo = pathinfo($_FILES['proof_image']['name']);
            $ext = strtolower($fileInfo['extension']);
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (in_array($ext, $allowed)) {
                $uniqueName = 'proof_' . $ride_id . '_' . $passenger_id . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $uniqueName;
                
                if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $targetFile)) {
                    $proof_path = $targetFile;
                }
            }
        }
        
        // New Fields
        $pickup_loc = $data['pickup_loc'] ?? null;
        $drop_loc = $data['drop_loc'] ?? null;
        $final_price = $data['final_price'] ?? 0;

        $stmt = $conn->prepare("INSERT INTO ride_requests (ride_id, passenger_id, seats_requested, proof_image, id_type, id_number, pickup_loc, drop_loc, final_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisssssd", $ride_id, $passenger_id, $seats, $proof_path, $id_type, $id_number, $pickup_loc, $drop_loc, $final_price);

        if ($stmt->execute()) {
            
            // Notify Driver
            // Get driver ID and Ride Info
            $getRide = $conn->query("SELECT r.driver_id, r.to_location, u.name as passenger_name 
                                    FROM rides r 
                                    JOIN users u ON u.user_id = $passenger_id 
                                    WHERE r.ride_id = $ride_id");
            $rideRow = $getRide->fetch_assoc();
            
            $driver_id = $rideRow['driver_id'];
            $pass_name = $rideRow['passenger_name'];
            $dest = $rideRow['to_location'];
            
            $title = "New Request: $pass_name";
            $msg = "Requested $seats seat(s) for ride to $dest";
            $type = 'info';
            $link = 'dashboard.php#incoming';
            
            $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
            $nStmt->bind_param("issss", $driver_id, $title, $msg, $type, $link);
            $nStmt->execute();

            echo JSON_encode(['success' => true, 'message' => 'Request sent successfully', 'request_id' => $stmt->insert_id, 'amount' => $final_price]);
        } else {
            echo JSON_encode(['success' => false, 'message' => 'Error sending request']);
        }

    } elseif ($action === 'accept' || $action === 'reject') {
        $request_id = $data['request_id'] ?? 0;
        $driver_id = $_SESSION['user_id'];
        
        // Verify driver owns the ride
        $check = $conn->prepare("
            SELECT rq.ride_id, rq.passenger_id, rq.seats_requested, r.driver_id, r.seats_available, r.to_location 
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
        
        if ($action === 'accept' && $row['seats_available'] < $row['seats_requested']) {
            echo JSON_encode(['success' => false, 'message' => 'Not enough seats available']);
            exit;
        }

        // Update Request Status
        $upd = $conn->prepare("UPDATE ride_requests SET status = ? WHERE request_id = ?");
        $upd->bind_param("si", $new_status, $request_id);
        
        if ($upd->execute()) {
            
            $pass_id = $row['passenger_id'];
            $dest = $row['to_location'];
            $link = 'dashboard.php#bookings';

            // If accepted, decrease seats
            if ($action === 'accept') {
                $new_seats = $row['seats_available'] - $row['seats_requested'];
                $conn->query("UPDATE rides SET seats_available = $new_seats WHERE ride_id = " . $row['ride_id']);
                
                // Notify Passenger
                $title = 'Ride Accepted';
                $msg = "Your ride to $dest has been accepted!";
                $type = 'success';
                
                $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
                $nStmt->bind_param("issss", $pass_id, $title, $msg, $type, $link);
                $nStmt->execute();

            } elseif ($action === 'reject') {
                // Notify Passenger
                $title = 'Ride Rejected';
                $msg = "Your ride request to $dest was rejected.";
                $type = 'warning';
                
                $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
                $nStmt->bind_param("issss", $pass_id, $title, $msg, $type, $link);
                $nStmt->execute();
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
    } elseif ($action === 'cancel_passenger') {
        // Passenger cancelling their request
        $request_id = $data['request_id'] ?? 0;
        $passenger_id = $_SESSION['user_id'];
        
        // Get request details (including seats and current status)
        $stmt = $conn->prepare("SELECT ride_id, seats_requested, status FROM ride_requests WHERE request_id = ? AND passenger_id = ?");
        $stmt->bind_param("ii", $request_id, $passenger_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows === 0) {
            echo JSON_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        $row = $res->fetch_assoc();
        $ride_id = $row['ride_id'];
        $seats = $row['seats_requested'];
        $current_status = $row['status'];
        
        if ($current_status === 'cancelled' || $current_status === 'completed' || $current_status === 'rejected') {
            echo JSON_encode(['success' => false, 'message' => 'Cannot cancel this request now']);
            exit;
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update status to cancelled
            $upd = $conn->prepare("UPDATE ride_requests SET status = 'cancelled' WHERE request_id = ?");
            $upd->bind_param("i", $request_id);
            $upd->execute();
            
            // If it was accepted, we must give seats back to the driver
            if ($current_status === 'accepted') {
                $conn->query("UPDATE rides SET seats_available = seats_available + $seats WHERE ride_id = $ride_id");
            }

            // Notify Driver
            $getDriver = $conn->query("SELECT r.driver_id, r.to_location, u.name as passenger_name 
                                    FROM rides r 
                                    JOIN users u ON u.user_id = $passenger_id 
                                    WHERE r.ride_id = $ride_id");
            if ($getDriver && $getDriver->num_rows > 0) {
                $dRow = $getDriver->fetch_assoc();
                $driver_id = $dRow['driver_id'];
                $pass_name = $dRow['passenger_name'];
                $dest = $dRow['to_location'];
                
                $title = "Request Cancelled";
                $msg = "$pass_name cancelled their request for ride to $dest";
                $type = 'warning';
                $link = 'dashboard.php#incoming';
                
                $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
                $nStmt->bind_param("issss", $driver_id, $title, $msg, $type, $link);
                $nStmt->execute();
            }
            
            $conn->commit();
            echo JSON_encode(['success' => true, 'message' => 'Request cancelled successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            echo JSON_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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
        $filter = $_GET['filter'] ?? 'pending';
        
        $statusClause = "AND rq.status = 'pending'";
        if ($filter === 'history') {
             $statusClause = "AND rq.status IN ('accepted', 'rejected', 'completed')";
        }

        $sql = "SELECT rq.*, r.from_location, r.to_location, r.ride_date, r.ride_time, 
                       u.name as passenger_name, u.email as passenger_email, u.phone as passenger_phone, u.profile_pic, u.rating,
                       (SELECT COUNT(*) FROM payments pay WHERE pay.request_id = rq.request_id AND pay.status = 'paid') as is_paid
                FROM ride_requests rq 
                JOIN rides r ON rq.ride_id = r.ride_id 
                JOIN users u ON rq.passenger_id = u.user_id 
                WHERE r.driver_id = ? $statusClause
                ORDER BY rq.created_at DESC";
    } else {
        // Requests BY this user (Passenger)
        // Check if rated: JOIN with reviews table
        // We also fetch r.status as ride_status. If the ride is globally completed, the user should be able to rate.
        $sql = "SELECT rq.*, r.from_location, r.to_location, r.ride_date, r.ride_time, r.price_per_seat, r.status as ride_status,
                       u.name as driver_name, r.driver_id,
                       (SELECT COUNT(*) FROM reviews rev WHERE rev.ride_id = rq.ride_id AND rev.reviewer_id = rq.passenger_id) as has_rated,
                       (SELECT COUNT(*) FROM payments pay WHERE pay.request_id = rq.request_id AND pay.status = 'paid') as is_paid
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
