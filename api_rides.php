<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// GET: Fetch Rides
if ($method === 'GET') {
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    $date = $_GET['date'] ?? '';
    $type = $_GET['type'] ?? ''; // Default to ALL types (empty)
    $status_filter = $_GET['status'] ?? 'active';

    // AUTO-CLEANUP: Mark past rides as completed
    $now_date = date('Y-m-d');
    $now_time = date('H:i:s');
    
    // Update rides that are past their scheduled time
    $conn->query("UPDATE rides SET status = 'completed' 
                 WHERE status = 'active' 
                 AND (ride_date < '$now_date' OR (ride_date = '$now_date' AND ride_time < '$now_time'))");
    
    // Also mark accepted requests for those rides as completed
    $conn->query("UPDATE ride_requests SET status = 'completed' 
                 WHERE status = 'accepted' 
                 AND ride_id IN (SELECT ride_id FROM rides WHERE status = 'completed')");

    $current_user_id = $_SESSION['user_id'] ?? 0;
    $sql = "SELECT r.*, u.name as driver_name, u.rating, u.profile_pic, 
            (SELECT COUNT(*) FROM ride_requests WHERE ride_id = r.ride_id AND passenger_id = $current_user_id AND status != 'cancelled') as is_requested
            FROM rides r 
            JOIN users u ON r.driver_id = u.user_id 
            WHERE 1=1";
    
    // Default to active unless 'all' is specified or specific status
    if ($status_filter !== 'all') {
        // If status is comma separated (e.g. 'completed,cancelled')
        if (strpos($status_filter, ',') !== false) {
            $statuses = explode(',', $status_filter);
            $status_list = "'" . implode("','", array_map('trim', $statuses)) . "'";
            $sql .= " AND r.status IN ($status_list)";
        } else {
            $sql .= " AND r.status = '$status_filter'";
        }
    }
    
    // Filter by Type (only if specified)
    if(!empty($type)) {
        $sql .= " AND r.ride_type = '$type'";
    }

    // IMPORTANT: Exclude my own rides from general search (Find Ride page)
    // Only exclude if I'm logged in AND I haven't specifically asked for a driver (e.g. 'me')
    $driver_filter = $_GET['driver_id'] ?? '';
    /* 
    // Commented out for testing/demo purposes so user can see their own rides in the list
    if (empty($driver_filter) && isset($_SESSION['user_id'])) {
         $sql .= " AND r.driver_id != " . $_SESSION['user_id'];
    }
    */

    $params = [];
    $types = "";

    if (!empty($from)) {
        $sql .= " AND (r.from_location LIKE ? OR r.intermediate_stops LIKE ?)";
        $params[] = "%$from%";
        $params[] = "%$from%";
        $types .= "ss";
    }
    if (!empty($to)) {
        $sql .= " AND (r.to_location LIKE ? OR r.intermediate_stops LIKE ?)";
        $params[] = "%$to%";
        $params[] = "%$to%";
        $types .= "ss";
    }
    if (!empty($date)) {
        $sql .= " AND r.ride_date = ?";
        $params[] = $date;
        $types .= "s";
    }

    // Filter by Driver (My Rides)
    $driver_filter = $_GET['driver_id'] ?? '';
    // Flag to skip exclusion if we are explicitly asking for our own rides
    $is_fetching_mine = false; 

    if (!empty($driver_filter)) {
        if ($driver_filter === 'me' && isset($_SESSION['user_id'])) {
            $sql .= " AND r.driver_id = ?";
            $params[] = $_SESSION['user_id'];
            $types .= "i";
            $is_fetching_mine = true;
        } elseif (is_numeric($driver_filter)) {
            $sql .= " AND r.driver_id = ?";
            $params[] = $driver_filter;
            $types .= "i";
        }
    }

    // Exclude my own rides unless 'show_self' is requested
    $show_self = $_GET['show_self'] ?? '0';
    if (!$is_fetching_mine && isset($_SESSION['user_id']) && $show_self !== '1') {
        $sql .= " AND r.driver_id != ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
    }

    // HIDE FULL RIDES: If not fetching my own rides or a specific ID, hide those with 0 seats
    if (!$is_fetching_mine && empty($ride_id)) {
        $sql .= " AND r.seats_available > 0";
    }
    
    // Check for specific ride_id (Bypass type filter if ID provided)
    $ride_id = $_GET['ride_id'] ?? '';
    if (!empty($ride_id)) {
        // Reset SQL for ID lookup to find ANY trip type
        $sql = "SELECT r.*, u.name as driver_name, u.rating, u.profile_pic,
                (SELECT COUNT(*) FROM ride_requests WHERE ride_id = r.ride_id AND passenger_id = $current_user_id AND status != 'cancelled') as is_requested
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

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create';

    // SERVER-SIDE VALIDATION
    if (in_array($action, ['create', 'update'])) {
        $price = floatval($input['price'] ?? 0);
        $seats = intval($input['seats'] ?? 0);
        $total_cost = floatval($input['total_cost'] ?? 0);

        if ($price <= 0) {
            echo JSON_encode(['success' => false, 'message' => 'Price per seat must be greater than 0']);
            exit;
        }
        if ($seats <= 0) {
            echo JSON_encode(['success' => false, 'message' => 'Total seats must be at least 1']);
            exit;
        }
        if (($input['type'] ?? '') === 'long' && $total_cost <= 0) {
            echo JSON_encode(['success' => false, 'message' => 'Total trip cost must be provided']);
            exit;
        }
    }

    // ACTION: COMPLETE RIDE
    if ($action === 'complete') {
        $ride_id = $input['ride_id'] ?? 0;
        $driver_id = $_SESSION['user_id'];
        
        // Validate Ownership
        $check = $conn->prepare("SELECT ride_id FROM rides WHERE ride_id = ? AND driver_id = ?");
        $check->bind_param("ii", $ride_id, $driver_id);
        $check->execute();
        
        if($check->get_result()->num_rows === 0) {
            echo json_encode(['success'=>false, 'message'=>'Unauthorized or Ride not found']);
            exit;
        }
        
        // Update Ride Status
        $upd = $conn->prepare("UPDATE rides SET status = 'completed' WHERE ride_id = ?");
        $upd->bind_param("i", $ride_id);
        
        if($upd->execute()) {
            // Also complete all accepted requests
            $conn->query("UPDATE ride_requests SET status = 'completed' WHERE ride_id = $ride_id AND status = 'accepted'");
            echo json_encode(['success'=>true, 'message'=>'Ride completed']);
        } else {
            echo json_encode(['success'=>false, 'message'=>'Update failed']);
        }

    // ACTION: DELETE RIDE
    } elseif ($action === 'delete') {
        $ride_id = $input['ride_id'] ?? 0;
        $driver_id = $_SESSION['user_id'];
        
        // Validate Ownership
        $check = $conn->prepare("SELECT ride_id FROM rides WHERE ride_id = ? AND driver_id = ?");
        $check->bind_param("ii", $ride_id, $driver_id);
        $check->execute();
        
        if($check->get_result()->num_rows === 0) {
            echo json_encode(['success'=>false, 'message'=>'Unauthorized or Ride not found']);
            exit;
        }
        
        // Soft delete (Cancel)
        $upd = $conn->prepare("UPDATE rides SET status = 'cancelled' WHERE ride_id = ?");
        $upd->bind_param("i", $ride_id);
        
        if($upd->execute()) {
            // Cancel pending requests
            $conn->query("UPDATE ride_requests SET status = 'rejected' WHERE ride_id = $ride_id AND status = 'pending'");
            echo json_encode(['success'=>true, 'message'=>'Ride deleted']);
        } else {
            echo json_encode(['success'=>false, 'message'=>'Delete failed']);
        }

    // ACTION: UPDATE RIDE
    } elseif ($action === 'update') {
        $ride_id = $input['ride_id'] ?? 0;
        $driver_id = $_SESSION['user_id'];

        // Validate Ownership
        $check = $conn->prepare("SELECT ride_id FROM rides WHERE ride_id = ? AND driver_id = ?");
        $check->bind_param("ii", $ride_id, $driver_id);
        $check->execute();
        
        if($check->get_result()->num_rows === 0) {
            echo json_encode(['success'=>false, 'message'=>'Unauthorized or Ride not found']);
            exit;
        }
        
        // Update fields
        $from = $input['from'] ?? '';
        $to = $input['to'] ?? '';
        $date = $input['date'] ?? '';
        $time = $input['time'] ?? '';
        $seats = $input['seats'] ?? 1;
        $price = $input['price'] ?? 0;
        $vehicle = $input['vehicle'] ?? '';
        $details = $input['details'] ?? '';
        $stops = $input['stops'] ?? '';
        
        // Optional: Block update if there are accepted requests (to avoid conflict)
        // For now, we allow it but it might be good to restrict fundamental changes if bookings exist.
        
        $sql = "UPDATE rides SET from_location=?, to_location=?, ride_date=?, ride_time=?, seats_available=?, price_per_seat=?, vehicle_type=?, details=?, intermediate_stops=? WHERE ride_id=?";
        $upd = $conn->prepare($sql);
        $upd->bind_param("ssssidsssi", $from, $to, $date, $time, $seats, $price, $vehicle, $details, $stops, $ride_id);
        
        if($upd->execute()) {
             echo json_encode(['success'=>true, 'message'=>'Ride updated successfully']);
        } else {
             echo json_encode(['success'=>false, 'message'=>'Update failed: '.$conn->error]);
        }

    // ACTION: CREATE RIDE (Default)
    } else {
        $driver_id = $_SESSION['user_id'];
        
        $type = $input['type'] ?? 'daily'; // daily or long
        $from = $input['from'] ?? '';
        $to = $input['to'] ?? '';
        $date = $input['date'] ?? '';
        $time = $input['time'] ?? '08:00'; 
        $seats = $input['seats'] ?? 1;
        $price = $input['price'] ?? 0;
        $vehicle = $input['vehicle'] ?? '';
        $details = $input['details'] ?? '';
        $stops = $input['stops'] ?? '';
        
        // New fields for Long Trip
        $end_date = $input['end_date'] ?? NULL;
        $total_cost = $input['total_cost'] ?? 0;
        
        if (empty($from) || empty($to) || empty($date)) {
            echo JSON_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO rides (driver_id, from_location, to_location, ride_date, ride_time, end_date, ride_type, seats_available, price_per_seat, total_cost, vehicle_type, details, intermediate_stops) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssidssss", $driver_id, $from, $to, $date, $time, $end_date, $type, $seats, $price, $total_cost, $vehicle, $details, $stops);

        if ($stmt->execute()) {
            echo JSON_encode(['success' => true, 'message' => ucfirst($type) . ' trip published successfully', 'ride_id' => $stmt->insert_id]);
        } else {
            echo JSON_encode(['success' => false, 'message' => 'Error publishing ride: ' . $conn->error]);
        }
    }

} else {
    echo JSON_encode(['success' => false, 'message' => 'Invalid Method']);
}
?>
