<?php
$conn = new mysqli("127.0.0.1", "root", "", "sharemyride");
if ($conn->connect_error) die("<h2 style='color:red'>❌ " . $conn->connect_error . "</h2>");
$conn->set_charset("utf8mb4");

$log = [];
function ins($conn, $sql, $label, &$log) {
    if ($conn->query($sql)) {
        $log[] = ["ok", "✅ $label — " . $conn->affected_rows . " row(s) inserted"];
    } else {
        $log[] = ["err", "❌ $label — " . $conn->error];
    }
}

// ── bcrypt of "password" ──────────────────────────────────────────
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// ════════════════════════════════════════════════════════════════
// 1. USERS  (skip if already exist)
// ════════════════════════════════════════════════════════════════
$cnt = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
if ($cnt < 4) {
    $conn->query("DELETE FROM users WHERE email IN (
        'admin@sharemyride.com','john@example.com','alice@example.com',
        'bob@example.com','priya@example.com','ravi@example.com')");

    ins($conn, "INSERT INTO users
        (name, email, password, role, is_verified, rating, rating_count, phone, profile_pic) VALUES
        ('Admin User',    'admin@sharemyride.com', '$hash', 'admin', 1, 5.00, 10, '9876543210', 'default_user.png'),
        ('Driver John',   'john@example.com',      '$hash', 'user',  1, 4.50,  8, '9876501234', 'default_user.png'),
        ('Student Alice', 'alice@example.com',      '$hash', 'user',  0, 4.20,  3, '9876505678', 'default_user.png'),
        ('Traveller Bob', 'bob@example.com',         '$hash', 'user',  1, 4.80,  5, '9876509999', 'default_user.png'),
        ('Priya Nair',    'priya@example.com',       '$hash', 'user',  1, 4.60,  6, '9876511111', 'default_user.png'),
        ('Ravi Kumar',    'ravi@example.com',         '$hash', 'user',  1, 4.30,  4, '9876522222', 'default_user.png')
    ", "users", $log);
} else {
    $log[] = ["info", "ℹ️ Users already exist ($cnt found) — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 2. RIDES
// ════════════════════════════════════════════════════════════════
$rideCount = $conn->query("SELECT COUNT(*) as c FROM rides")->fetch_assoc()['c'];
if ($rideCount < 3) {
    // Get driver IDs
    $john  = $conn->query("SELECT user_id FROM users WHERE email='john@example.com'")->fetch_assoc()['user_id'] ?? 2;
    $bob   = $conn->query("SELECT user_id FROM users WHERE email='bob@example.com'")->fetch_assoc()['user_id']  ?? 4;
    $ravi  = $conn->query("SELECT user_id FROM users WHERE email='ravi@example.com'")->fetch_assoc()['user_id'] ?? 6;

    ins($conn, "INSERT INTO rides
        (driver_id, from_location, to_location, ride_date, ride_time,
         seats_available, price_per_seat, vehicle_type, vehicle_model, vehicle_number, details, status)
    VALUES
        ($john, 'Kottayam',   'Kochi',       DATE_ADD(CURDATE(),INTERVAL 1 DAY), '08:00:00', 3, 150.00, 'Car', 'Toyota Glanza',   'KL-05-AB-1234', 'AC car, music system, comfortable seats. Daily commuter.', 'active'),
        ($john, 'Kottayam',   'Trivandrum',  DATE_ADD(CURDATE(),INTERVAL 2 DAY), '07:30:00', 2, 250.00, 'Car', 'Toyota Glanza',   'KL-05-AB-1234', 'Long trip, stops at Kollam if needed.', 'active'),
        ($bob,  'Kochi',      'Kozhikode',   DATE_ADD(CURDATE(),INTERVAL 1 DAY), '09:00:00', 4, 200.00, 'SUV', 'Mahindra XUV500', 'KL-07-BC-5678', 'Spacious SUV, AC, safe driver. Regular weekly trip.', 'active'),
        ($john, 'Mundakkayam','Kottayam',    DATE_ADD(CURDATE(),INTERVAL 3 DAY), '07:00:00', 3, 100.00, 'Car', 'Toyota Glanza',   'KL-05-AB-1234', 'Morning commute, punctual pickup.', 'active'),
        ($ravi, 'Ernakulam',  'Thrissur',    DATE_ADD(CURDATE(),INTERVAL 1 DAY), '10:00:00', 3, 120.00, 'Car', 'Hyundai i20',     'KL-09-CD-3456', 'Office commute, silent ride preferred.', 'active'),
        ($ravi, 'Trivandrum', 'Kochi',       DATE_ADD(CURDATE(),INTERVAL 4 DAY), '06:30:00', 2, 280.00, 'Car', 'Hyundai i20',     'KL-09-CD-3456', 'Early morning, highway route, fast.', 'active'),
        ($bob,  'Kottayam',   'Munnar',      DATE_ADD(CURDATE(),INTERVAL 5 DAY), '08:30:00', 3, 350.00, 'SUV', 'Mahindra XUV500', 'KL-07-BC-5678', 'Weekend trip to Munnar. Scenic route, tourist stops possible.', 'active'),
        ($john, 'Kochi',      'Kottayam',    DATE_ADD(CURDATE(),-INTERVAL 3 DAY),'17:00:00', 3, 140.00, 'Car', 'Toyota Glanza',   'KL-05-AB-1234', 'Evening return trip.', 'completed')
    ", "rides", $log);
} else {
    $log[] = ["info", "ℹ️ Rides already exist ($rideCount found) — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 3. RIDE REQUESTS
// ════════════════════════════════════════════════════════════════
$reqCount = $conn->query("SELECT COUNT(*) as c FROM ride_requests")->fetch_assoc()['c'];
if ($reqCount < 2) {
    $alice = $conn->query("SELECT user_id FROM users WHERE email='alice@example.com'")->fetch_assoc()['user_id'] ?? 3;
    $bob   = $conn->query("SELECT user_id FROM users WHERE email='bob@example.com'")->fetch_assoc()['user_id']  ?? 4;
    $priya = $conn->query("SELECT user_id FROM users WHERE email='priya@example.com'")->fetch_assoc()['user_id'] ?? 5;

    // Get first ride_id
    $r1 = $conn->query("SELECT ride_id FROM rides ORDER BY ride_id ASC LIMIT 1")->fetch_assoc()['ride_id'] ?? 1;
    $r2 = $r1 + 1;
    $r3 = $r1 + 2;
    $r8 = $r1 + 7; // completed ride

    ins($conn, "INSERT INTO ride_requests
        (ride_id, passenger_id, seats_requested, proof_image, id_type, id_number, pickup_loc, drop_loc, final_price, payment_method, status)
    VALUES
        ($r1, $alice, 1, 'uploads/proofs/sample_proof_1.jpg', 'Aadhar', 'XXXX-XXXX-1234', 'Kottayam Bus Stand', 'Kochi MG Road', 150.00, 'pay_later', 'accepted'),
        ($r1, $priya, 1, 'uploads/proofs/sample_proof_2.jpg', 'Aadhar', 'XXXX-XXXX-5678', 'Kottayam Town',      'Kochi Edapally',160.00, 'pay_now',   'pending'),
        ($r2, $bob,   1, 'uploads/proofs/sample_proof_3.jpg', 'PAN',    'ABCDE1234F',     'Kottayam Railway',   'Trivandrum Central', 250.00, 'pay_later', 'pending'),
        ($r3, $alice, 2, 'uploads/proofs/sample_proof_4.jpg', 'Aadhar', 'XXXX-XXXX-9999', 'Kochi Airport',      'Kozhikode Town', 400.00, 'pay_now',   'rejected'),
        ($r8, $alice, 1, 'uploads/proofs/sample_proof_5.jpg', 'Aadhar', 'XXXX-XXXX-7777', 'Kochi Central',      'Kottayam KSRTC',140.00, 'pay_later', 'completed')
    ", "ride_requests", $log);
} else {
    $log[] = ["info", "ℹ️ Ride requests already exist ($reqCount found) — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 4. PAYMENTS
// ════════════════════════════════════════════════════════════════
$payCount = $conn->query("SELECT COUNT(*) as c FROM payments")->fetch_assoc()['c'];
if ($payCount < 1) {
    $req1 = $conn->query("SELECT request_id, ride_id, passenger_id FROM ride_requests WHERE status='accepted' LIMIT 1")->fetch_assoc();
    $req2 = $conn->query("SELECT request_id, ride_id, passenger_id FROM ride_requests WHERE status='completed' LIMIT 1")->fetch_assoc();

    if ($req1) {
        ins($conn, "INSERT INTO payments
            (request_id, passenger_id, ride_id, amount, currency, razorpay_order_id, razorpay_payment_id, status)
        VALUES
            ({$req1['request_id']}, {$req1['passenger_id']}, {$req1['ride_id']}, 150.00, 'INR', 'order_test_SMR001', 'pay_test_001', 'paid')
        ", "payments (paid)", $log);
    }
    if ($req2) {
        ins($conn, "INSERT INTO payments
            (request_id, passenger_id, ride_id, amount, currency, razorpay_order_id, razorpay_payment_id, status)
        VALUES
            ({$req2['request_id']}, {$req2['passenger_id']}, {$req2['ride_id']}, 140.00, 'INR', 'order_test_SMR002', 'pay_test_002', 'paid')
        ", "payments (completed ride)", $log);
    }
} else {
    $log[] = ["info", "ℹ️ Payments already exist ($payCount found) — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 5. NOTIFICATIONS
// ════════════════════════════════════════════════════════════════
$notifCount = $conn->query("SELECT COUNT(*) as c FROM notifications")->fetch_assoc()['c'];
if ($notifCount < 3) {
    $john  = $conn->query("SELECT user_id FROM users WHERE email='john@example.com'")->fetch_assoc()['user_id']  ?? 2;
    $alice = $conn->query("SELECT user_id FROM users WHERE email='alice@example.com'")->fetch_assoc()['user_id'] ?? 3;
    $bob   = $conn->query("SELECT user_id FROM users WHERE email='bob@example.com'")->fetch_assoc()['user_id']   ?? 4;

    ins($conn, "INSERT INTO notifications (user_id, title, message, type, is_read, link) VALUES
        ($john,  'New Ride Request',    'Alice has requested 1 seat for your Kottayam → Kochi ride.', 'info',    0, 'dashboard.php#incoming'),
        ($john,  'Payment Received',    'Alice paid ₹150.00 for the ride to Kochi.', 'success', 0, 'dashboard.php#incoming'),
        ($alice, 'Ride Accepted! 🎉',  'Your ride to Kochi has been accepted by Driver John.', 'success', 0, 'dashboard.php#bookings'),
        ($alice, 'Welcome to ShareMyRide!', 'Start finding or offering rides to save money and reduce emissions.', 'info', 1, 'find_ride.php'),
        ($bob,   'Request Pending',     'Your ride request to Trivandrum is pending driver approval.', 'info',    0, 'dashboard.php#bookings'),
        ($john,  'New Request: Priya',  'Priya requested 1 seat for your Kottayam → Kochi ride.', 'info',       0, 'dashboard.php#incoming')
    ", "notifications", $log);
} else {
    $log[] = ["info", "ℹ️ Notifications already exist ($notifCount found) — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 6. REVIEWS
// ════════════════════════════════════════════════════════════════
$revCount = $conn->query("SELECT COUNT(*) as c FROM reviews")->fetch_assoc()['c'];
if ($revCount < 1) {
    $john  = $conn->query("SELECT user_id FROM users WHERE email='john@example.com'")->fetch_assoc()['user_id']  ?? 2;
    $alice = $conn->query("SELECT user_id FROM users WHERE email='alice@example.com'")->fetch_assoc()['user_id'] ?? 3;
    $bob   = $conn->query("SELECT user_id FROM users WHERE email='bob@example.com'")->fetch_assoc()['user_id']   ?? 4;
    $priya = $conn->query("SELECT user_id FROM users WHERE email='priya@example.com'")->fetch_assoc()['user_id'] ?? 5;

    // Get the completed ride
    $compRide = $conn->query("SELECT ride_id FROM rides WHERE status='completed' LIMIT 1")->fetch_assoc();
    $crid = $compRide['ride_id'] ?? 1;

    ins($conn, "INSERT INTO reviews (ride_id, reviewer_id, reviewee_id, rating, comment) VALUES
        ($crid, $alice, $john, 5, 'Excellent driver! Very punctual, clean car, smooth ride. Highly recommended.'),
        ($crid, $bob,   $john, 4, 'Good ride, comfortable car. Driver was friendly and took the best route.'),
        ($crid, $priya, $john, 5, 'Very safe and professional driver. Will definitely book again!')
    ", "reviews", $log);

    // Update driver rating
    $avgR = $conn->query("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE reviewee_id=$john")->fetch_assoc();
    $conn->query("UPDATE users SET rating=" . round($avgR['avg'], 2) . ", rating_count=" . $avgR['cnt'] . " WHERE user_id=$john");
    $log[] = ["ok", "✅ Driver John rating updated to " . round($avgR['avg'], 2) . " ⭐"];
} else {
    $log[] = ["info", "ℹ️ Reviews already exist ($revCount found) — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 7. PASSWORD RESETS (sample expired token)
// ════════════════════════════════════════════════════════════════
$prCount = $conn->query("SELECT COUNT(*) as c FROM password_resets")->fetch_assoc()['c'];
if ($prCount < 1) {
    ins($conn, "INSERT INTO password_resets (email, token, expires_at) VALUES
        ('alice@example.com', 'sample_expired_token_abc123', " . (time() - 3600) . ")
    ", "password_resets (sample expired token)", $log);
} else {
    $log[] = ["info", "ℹ️ Password resets table already has data — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 8. CONTACT MESSAGES
// ════════════════════════════════════════════════════════════════
$cmCount = $conn->query("SELECT COUNT(*) as c FROM contact_messages")->fetch_assoc()['c'];
if ($cmCount < 1) {
    ins($conn, "INSERT INTO contact_messages (user_name, user_email, subject, content, status) VALUES
        ('Alice Thomas', 'alice@example.com', 'Question about booking', 'How do I cancel a ride request?', 'resolved'),
        ('Ravi Kumar',   'ravi@example.com',  'Payment Issue',          'My payment was deducted but booking is still pending.', 'open'),
        ('Priya Nair',   'priya@example.com', 'Feature Request',        'Please add a chat feature between driver and passenger.', 'open')
    ", "contact_messages", $log);
} else {
    $log[] = ["info", "ℹ️ Contact messages already exist — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 9. ADMIN LOGS
// ════════════════════════════════════════════════════════════════
$alCount = $conn->query("SELECT COUNT(*) as c FROM admin_logs")->fetch_assoc()['c'];
if ($alCount < 1) {
    ins($conn, "INSERT INTO admin_logs (admin_username, action_type, action_desc) VALUES
        ('admin@sharemyride.com', 'user_verified',  'Verified Driver John (john@example.com)'),
        ('admin@sharemyride.com', 'ride_cancelled', 'Cancelled ride #3 due to driver complaint'),
        ('admin@sharemyride.com', 'user_banned',    'Temporary warning issued to user ravi@example.com')
    ", "admin_logs", $log);
} else {
    $log[] = ["info", "ℹ️ Admin logs already exist — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 10. FUEL LOGS
// ════════════════════════════════════════════════════════════════
$flCount = $conn->query("SELECT COUNT(*) as c FROM fuel_logs")->fetch_assoc()['c'];
if ($flCount < 1) {
    $john  = $conn->query("SELECT user_id FROM users WHERE email='john@example.com'")->fetch_assoc()['user_id']  ?? 2;
    $alice = $conn->query("SELECT user_id FROM users WHERE email='alice@example.com'")->fetch_assoc()['user_id'] ?? 3;

    ins($conn, "INSERT INTO fuel_logs (user_id, distance, fuel_efficiency, fuel_price, estimated_cost) VALUES
        ($john,  65.00, 15.00, 102.00, 442.00),
        ($john, 120.00, 14.50, 102.00, 844.14),
        ($alice, 65.00, 18.00, 102.00, 368.33)
    ", "fuel_logs", $log);
} else {
    $log[] = ["info", "ℹ️ Fuel logs already exist — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 11. LOCATION TRACKING
// ════════════════════════════════════════════════════════════════
$ltCount = $conn->query("SELECT COUNT(*) as c FROM location_tracking")->fetch_assoc()['c'];
if ($ltCount < 1) {
    $john   = $conn->query("SELECT user_id FROM users WHERE email='john@example.com'")->fetch_assoc()['user_id'] ?? 2;
    $actRide = $conn->query("SELECT ride_id FROM rides WHERE status='active' LIMIT 1")->fetch_assoc();
    if ($actRide) {
        $rid = $actRide['ride_id'];
        ins($conn, "INSERT INTO location_tracking (ride_id, driver_id, latitude, longitude) VALUES
            ($rid, $john, 9.59130, 76.52210)
        ", "location_tracking", $log);
    }
} else {
    $log[] = ["info", "ℹ️ Location tracking already has data — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 12. SAVED LOCATIONS
// ════════════════════════════════════════════════════════════════
$slCount = $conn->query("SELECT COUNT(*) as c FROM saved_locations")->fetch_assoc()['c'];
if ($slCount < 1) {
    $john  = $conn->query("SELECT user_id FROM users WHERE email='john@example.com'")->fetch_assoc()['user_id']  ?? 2;
    $alice = $conn->query("SELECT user_id FROM users WHERE email='alice@example.com'")->fetch_assoc()['user_id'] ?? 3;

    ins($conn, "INSERT INTO saved_locations (user_id, location_name, address_text) VALUES
        ($john,  'Home',   'Kottayam, Kerala'),
        ($john,  'Office', 'Infopark, Kakkanad, Kochi'),
        ($alice, 'College','MG University, Kottayam'),
        ($alice, 'Home',   'Kanjirappally, Kerala')
    ", "saved_locations", $log);
} else {
    $log[] = ["info", "ℹ️ Saved locations already exist — skipped"];
}

// ════════════════════════════════════════════════════════════════
// 13. IDENTITY DOCUMENTS
// ════════════════════════════════════════════════════════════════
$idCount = $conn->query("SELECT COUNT(*) as c FROM identity_documents")->fetch_assoc()['c'];
if ($idCount < 1) {
    $john  = $conn->query("SELECT user_id FROM users WHERE email='john@example.com'")->fetch_assoc()['user_id']  ?? 2;
    $alice = $conn->query("SELECT user_id FROM users WHERE email='alice@example.com'")->fetch_assoc()['user_id'] ?? 3;

    ins($conn, "INSERT INTO identity_documents (user_id, doc_type, doc_file_path, verify_status) VALUES
        ($john,  'Aadhar', 'uploads/proofs/john_aadhar.jpg',  'verified'),
        ($alice, 'Aadhar', 'uploads/proofs/alice_aadhar.jpg', 'pending')
    ", "identity_documents", $log);
} else {
    $log[] = ["info", "ℹ️ Identity documents already exist — skipped"];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShareMyRide — Populate Data</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #0d0d1a; color: #e0e0f0; padding: 40px 20px; }
.box { max-width: 820px; margin: 0 auto; }
h1 { color: #7c6fff; margin-bottom: 6px; }
.sub { color: #888; font-size: 14px; margin-bottom: 24px; }
.card { background: #1a1a2e; border-radius: 12px; padding: 22px 26px; margin-bottom: 18px; border-left: 4px solid #7c6fff; }
.card.err { border-left-color: #f87171; }
h3 { color: #a0a0ff; font-size: 15px; margin-bottom: 12px; }
li { padding: 5px 0; font-size: 14px; list-style: none; }
.ok   { color: #4ade80; }
.err  { color: #f87171; }
.info { color: #60a5fa; }
.btn  { display:inline-block; margin-top:10px; margin-right:10px; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:600; color:#fff; }
.b1 { background: linear-gradient(135deg,#7c6fff,#6c63ff); }
.b2 { background: linear-gradient(135deg,#34d399,#059669); }
.b3 { background: linear-gradient(135deg,#f59e0b,#d97706); }
</style>
</head>
<body>
<div class="box">
    <h1>🗄️ ShareMyRide — Data Population</h1>
    <p class="sub">All tables populated with realistic sample data.</p>

    <?php
    $oks   = array_filter($log, fn($l) => $l[0] === 'ok');
    $errs  = array_filter($log, fn($l) => $l[0] === 'err');
    $infos = array_filter($log, fn($l) => $l[0] === 'info');
    ?>

    <div class="card">
        <h3>Results (<?= count($log) ?> operations)</h3>
        <ul>
        <?php foreach ($log as [$type, $msg]): ?>
            <li class="<?= $type === 'ok' ? 'ok' : ($type === 'err' ? 'err' : 'info') ?>">
                <?= htmlspecialchars($msg) ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>

    <div style="margin-top:8px">
        <a href="view_database.php"  class="btn b1">👁️ View Database</a>
        <a href="dashboard.php"      class="btn b2">📊 Dashboard</a>
        <a href="login.php"          class="btn b3">🚀 Login</a>
    </div>
</div>
</body>
</html>
