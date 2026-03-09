<?php
// api_admin.php - Dedicated API for Admin actions
require_once 'db_connect.php';

header('Content-Type: application/json');

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// helper to get data from input
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}

// --- GET Actions ---
if ($method === 'GET') {

    if ($action === 'stats') {
        // Fetch Real Stats
        $stats = [];
        
        // Total Users
        $res = $conn->query("SELECT COUNT(*) as c FROM users WHERE role != 'admin'");
        $stats['total_users'] = $res->fetch_assoc()['c'];

        // Active Rides
        $res = $conn->query("SELECT COUNT(*) as c FROM rides WHERE status = 'active'");
        $stats['active_rides'] = $res->fetch_assoc()['c'];

        // Pending Verifications (Mock logic: users created recently or flag)
        // Let's assume 'is_verified = 0' means pending
        $res = $conn->query("SELECT COUNT(*) as c FROM users WHERE is_verified = 0");
        $stats['pending_verifications'] = $res->fetch_assoc()['c'];

        // Revenue (Based on actual bookings: count of accepted/completed requests * ride price)
        // We join rides and ride_requests to calculate this
        $res = $conn->query("
            SELECT SUM(r.price_per_seat * rq.seats_requested) as actual_revenue 
            FROM ride_requests rq
            JOIN rides r ON rq.ride_id = r.ride_id
            WHERE rq.status IN ('accepted', 'completed')
        ");
        $revenue = $res->fetch_assoc()['actual_revenue'] ?? 0;
        $stats['total_revenue'] = '₹' . number_format($revenue);

        echo json_encode(['success' => true, 'stats' => $stats]);

    } elseif ($action === 'users') {
        // List Users
        $sql = "SELECT user_id, name, email, role, is_verified, created_at, profile_pic FROM users WHERE role != 'admin' ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        $users = [];
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'users' => $users]);

    } elseif ($action === 'messages') {
        // List Contact Messages
        $result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
        $messages = [];
        if ($result) {
            while($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
        echo json_encode(['success' => true, 'messages' => $messages]);
    }
    
    exit;
}

// --- POST Actions ---
if ($method === 'POST') {
    $input = getJsonInput();
    
    // Delete Ride
    if ($action === 'delete_ride') {
        $ride_id = $input['ride_id'] ?? 0;
        if(!$ride_id) { echo json_encode(['success'=>false]); exit; }

        $stmt = $conn->prepare("DELETE FROM rides WHERE ride_id = ?");
        $stmt->bind_param("i", $ride_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Ride deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
    }

    // Delete User
    elseif ($action === 'delete_user') {
        $user_id = $input['user_id'] ?? 0;
        if(!$user_id) { echo json_encode(['success'=>false]); exit; }

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
    }

    // Verify User
    elseif ($action === 'verify_user') {
        $user_id = $input['user_id'] ?? 0;
        if(!$user_id) { echo json_encode(['success'=>false]); exit; }

        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User verified']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    }

    // Update Message Status
    elseif ($action === 'update_message_status') {
        $msg_id = $input['id'] ?? 0;
        $status = $input['status'] ?? '';
        if(!$msg_id || !in_array($status, ['new','read','resolved'])) {
            echo json_encode(['success'=>false, 'message'=>'Invalid data']); exit;
        }
        $stmt = $conn->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $msg_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    }

    // Delete Message
    elseif ($action === 'delete_message') {
        $msg_id = $input['id'] ?? 0;
        if(!$msg_id) { echo json_encode(['success'=>false]); exit; }
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->bind_param("i", $msg_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Message deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
    }

    exit;
}
?>
