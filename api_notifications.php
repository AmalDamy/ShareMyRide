<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch unread notifications
    // Limit to last 20?
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifs = [];
    while($row = $result->fetch_assoc()) {
        $notifs[] = $row;
    }
    
    echo json_encode(['success' => true, 'notifications' => $notifs]);

} elseif ($method === 'POST') {
    // Mark as read
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'mark_read') {
        $notif_id = $input['id'] ?? 0;
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'mark_all_read') {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
}
?>
