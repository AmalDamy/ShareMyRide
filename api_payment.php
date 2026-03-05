<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ─── Razorpay Credentials (Test Mode) ───────────────────────────────────────
define('RZP_KEY_ID',     'rzp_test_SMQoEwEByn3ZrX');
define('RZP_KEY_SECRET', 'UfTqm48a8Hh4CQHYwyGMQALO');

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// ─── ACTION: Create Order ────────────────────────────────────────────────────
if ($action === 'create_order') {
    $request_id  = intval($input['request_id'] ?? 0);
    $passenger_id = $_SESSION['user_id'];

    if (!$request_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
        exit;
    }

    // Fetch request + ride details to get amount
    $stmt = $conn->prepare("
        SELECT rq.request_id, rq.ride_id, rq.passenger_id, rq.seats_requested,
               rq.final_price, rq.status,
               r.price_per_seat, r.from_location, r.to_location
        FROM ride_requests rq
        JOIN rides r ON rq.ride_id = r.ride_id
        WHERE rq.request_id = ? AND rq.passenger_id = ?
    ");
    $stmt->bind_param("ii", $request_id, $passenger_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    if (!in_array($row['status'], ['pending', 'accepted'])) {
        echo json_encode(['success' => false, 'message' => 'Ride cannot be paid in its current status']);
        exit;
    }

    // Check if already paid
    $payCheck = $conn->prepare("SELECT id FROM payments WHERE request_id = ? AND status = 'paid'");
    $payCheck->bind_param("i", $request_id);
    $payCheck->execute();
    if ($payCheck->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This ride has already been paid']);
        exit;
    }

    // Amount in paise (Razorpay expects smallest currency unit)
    $amount_inr  = floatval($row['final_price']) > 0 ? floatval($row['final_price'])
                   : (floatval($row['price_per_seat']) * intval($row['seats_requested']));
    $amount_paise = intval(round($amount_inr * 100));

    if ($amount_paise < 100) { // Minimum ₹1
        echo json_encode(['success' => false, 'message' => 'Amount too small for payment']);
        exit;
    }

    // Create Razorpay Order via REST API
    $orderData = [
        'amount'   => $amount_paise,
        'currency' => 'INR',
        'receipt'  => 'smr_req_' . $request_id . '_' . time(),
        'notes'    => [
            'request_id' => $request_id,
            'passenger'  => $_SESSION['username'] ?? '',
            'route'      => $row['from_location'] . ' → ' . $row['to_location'],
        ]
    ];

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($orderData),
        CURLOPT_USERPWD        => RZP_KEY_ID . ':' . RZP_KEY_SECRET,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        $errMsg = 'cURL error: ' . $curlErr;
        echo json_encode(['success' => false, 'message' => $errMsg]);
        exit;
    }

    if ($httpCode !== 200) {
        $errBody = json_decode($response, true);
        $errMsg  = isset($errBody['error']['description']) ? $errBody['error']['description'] : 'Razorpay API error';
        echo json_encode(['success' => false, 'message' => $errMsg]);
        exit;
    }

    $order = json_decode($response, true);

    // Store pending payment record
    $conn->query("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id INT NOT NULL,
            passenger_id INT NOT NULL,
            ride_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(5) DEFAULT 'INR',
            razorpay_order_id VARCHAR(100),
            razorpay_payment_id VARCHAR(100),
            razorpay_signature VARCHAR(255),
            status ENUM('pending','paid','failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (request_id),
            INDEX (passenger_id)
        )
    ");

    // Upsert pending payment row
    $orderId = $order['id'];
    $conn->query("DELETE FROM payments WHERE request_id = $request_id AND status = 'pending'");
    $ins = $conn->prepare("INSERT INTO payments (request_id, passenger_id, ride_id, amount, razorpay_order_id) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param("iiids", $request_id, $passenger_id, $row['ride_id'], $amount_inr, $orderId);
    $ins->execute();

    echo json_encode([
        'success'      => true,
        'order_id'     => $orderId,
        'amount'       => $amount_paise,
        'currency'     => 'INR',
        'amount_inr'   => $amount_inr,
        'key_id'       => RZP_KEY_ID,
        'request_id'   => $request_id,
        'name'         => $_SESSION['username'] ?? 'Passenger',
        'description'  => 'Ride: ' . $row['from_location'] . ' → ' . $row['to_location'],
    ]);
    exit;
}

// ─── ACTION: Verify Payment ──────────────────────────────────────────────────
if ($action === 'verify_payment') {
    $rzp_order_id   = $input['razorpay_order_id']   ?? '';
    $rzp_payment_id = $input['razorpay_payment_id'] ?? '';
    $rzp_signature  = $input['razorpay_signature']  ?? '';
    $request_id     = intval($input['request_id']   ?? 0);
    $passenger_id   = $_SESSION['user_id'];

    if (!$rzp_order_id || !$rzp_payment_id || !$rzp_signature) {
        echo json_encode(['success' => false, 'message' => 'Missing payment details']);
        exit;
    }

    // Verify HMAC signature
    $payload  = $rzp_order_id . '|' . $rzp_payment_id;
    $expected = hash_hmac('sha256', $payload, RZP_KEY_SECRET);

    if (!hash_equals($expected, $rzp_signature)) {
        // Update payment status to failed
        $conn->query("UPDATE payments SET status='failed', razorpay_payment_id='$rzp_payment_id' WHERE razorpay_order_id='$rzp_order_id'");
        echo json_encode(['success' => false, 'message' => 'Payment verification failed. Please contact support.']);
        exit;
    }

    // Signature valid — mark as paid
    $upd = $conn->prepare("
        UPDATE payments 
        SET status = 'paid',
            razorpay_payment_id = ?,
            razorpay_signature  = ?
        WHERE razorpay_order_id = ? AND passenger_id = ?
    ");
    $upd->bind_param("sssi", $rzp_payment_id, $rzp_signature, $rzp_order_id, $passenger_id);
    $upd->execute();

    if ($upd->affected_rows < 1) {
        echo json_encode(['success' => false, 'message' => 'Payment record not found']);
        exit;
    }

    // Fetch driver_id for notification
    $info = $conn->query("
        SELECT rq.ride_id, r.driver_id, r.to_location, p.amount
        FROM payments p
        JOIN ride_requests rq ON p.request_id = rq.request_id
        JOIN rides r ON rq.ride_id = r.ride_id
        WHERE p.razorpay_order_id = '$rzp_order_id'
        LIMIT 1
    ")->fetch_assoc();

    if ($info) {
        $driver_id = $info['driver_id'];
        $dest      = $info['to_location'];
        $amount    = number_format($info['amount'], 2);
        $pass_name = $_SESSION['username'] ?? 'Passenger';

        $title = "Payment Received: ₹$amount";
        $msg   = "$pass_name has paid ₹$amount for the ride to $dest.";
        $type  = 'success';
        $link  = 'dashboard.php#incoming';

        $nStmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        $nStmt->bind_param("issss", $driver_id, $title, $msg, $type, $link);
        $nStmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Payment successful! Your booking is confirmed.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
