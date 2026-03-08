<?php
// ================================================================
// ShareMyRide — MASTER DATABASE SETUP
// Scanned from ALL PHP files to match exact column names used.
// Run ONCE at: http://localhost/sharemyride/setup_database.php
// ================================================================

$servername = "127.0.0.1";
$username   = "root";
$password   = "";
$dbname     = "sharemyride";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("<h2 style='color:red'>❌ MySQL Connection Failed: " . $conn->connect_error . "<br>Make sure XAMPP MySQL is running!</h2>");
}

$errors  = [];
$success = [];

function run($conn, $sql, $label, &$success, &$errors) {
    if ($conn->query($sql) === TRUE) {
        $success[] = "✅ $label";
    } else {
        $errors[] = "❌ $label — " . $conn->error;
    }
}

// Safe column add: checks if column exists before ALTER
function addColumnIfMissing($conn, $table, $column, $definition, &$success, &$errors) {
    $check = $conn->query("SELECT COUNT(*) as cnt 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = '$table' 
        AND COLUMN_NAME = '$column'");
    $row = $check->fetch_assoc();
    if ($row['cnt'] == 0) {
        run($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition",
            "Added column: $table.$column", $success, $errors);
    } else {
        $success[] = "ℹ️ Column $table.$column already exists — skipped";
    }
}

// ── Create & Select Database ────────────────────────────────────
run($conn, "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "Database '$dbname'", $success, $errors);
$conn->select_db($dbname);
$conn->set_charset("utf8mb4");

// ================================================================
// TABLE 1: users
// Used in: auth.php, api_payment.php, api_requests.php, dashboard.php
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `users` (
    `user_id`              INT AUTO_INCREMENT PRIMARY KEY,
    `name`                 VARCHAR(100) NOT NULL,
    `email`                VARCHAR(100) UNIQUE NOT NULL,
    `google_id`            VARCHAR(255) UNIQUE DEFAULT NULL,
    `password`             VARCHAR(255) NOT NULL DEFAULT '',
    `role`                 ENUM('user','admin') DEFAULT 'user',
    `profile_pic`          VARCHAR(255) DEFAULT 'default_user.png',
    `phone`                VARCHAR(20) DEFAULT NULL,
    `rating`               DECIMAL(3,2) DEFAULT 0.00,
    `rating_count`         INT DEFAULT 0,
    `is_verified`          BOOLEAN DEFAULT FALSE,
    `razorpay_customer_id` VARCHAR(100) DEFAULT NULL,
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB", "Table: users", $success, $errors);

// ================================================================
// TABLE 2: rides
// Used in: api_rides.php — columns: from_location, to_location,
//          ride_date, ride_time, end_date, ride_type,
//          seats_available, price_per_seat, total_cost,
//          vehicle_type, details, intermediate_stops, status
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `rides` (
    `ride_id`            INT AUTO_INCREMENT PRIMARY KEY,
    `driver_id`          INT NOT NULL,
    `from_location`      VARCHAR(255) NOT NULL,
    `to_location`        VARCHAR(255) NOT NULL,
    `ride_date`          DATE NOT NULL,
    `ride_time`          TIME NOT NULL,
    `end_date`           DATE DEFAULT NULL,
    `ride_type`          ENUM('daily','long') DEFAULT 'daily',
    `seats_available`    INT NOT NULL DEFAULT 1,
    `price_per_seat`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_cost`         DECIMAL(10,2) DEFAULT NULL,
    `vehicle_type`       VARCHAR(50) NOT NULL DEFAULT 'Car',
    `vehicle_number`     VARCHAR(20) DEFAULT NULL,
    `vehicle_model`      VARCHAR(100) DEFAULT NULL,
    `details`            TEXT DEFAULT NULL,
    `intermediate_stops` TEXT DEFAULT NULL,
    `status`             ENUM('active','completed','cancelled') DEFAULT 'active',
    `created_at`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`driver_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    INDEX `idx_from`   (`from_location`(50)),
    INDEX `idx_to`     (`to_location`(50)),
    INDEX `idx_date`   (`ride_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB", "Table: rides", $success, $errors);

// ================================================================
// TABLE 3: ride_requests
// Used in: api_requests.php — columns:
//   proof_image, id_type, id_number, pickup_loc, drop_loc, final_price
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `ride_requests` (
    `request_id`      INT AUTO_INCREMENT PRIMARY KEY,
    `ride_id`         INT NOT NULL,
    `passenger_id`    INT NOT NULL,
    `seats_requested` INT DEFAULT 1,
    `proof_image`     VARCHAR(255) DEFAULT NULL,
    `id_type`         VARCHAR(50) DEFAULT NULL,
    `id_number`       VARCHAR(100) DEFAULT NULL,
    `pickup_loc`      VARCHAR(255) DEFAULT NULL,
    `drop_loc`        VARCHAR(255) DEFAULT NULL,
    `final_price`     DECIMAL(10,2) DEFAULT 0.00,
    `payment_method`  ENUM('pay_now','pay_later') DEFAULT 'pay_later',
    `status`          ENUM('pending','accepted','rejected','cancelled','completed') DEFAULT 'pending',
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ride_id`)      REFERENCES `rides`(`ride_id`)  ON DELETE CASCADE,
    FOREIGN KEY (`passenger_id`) REFERENCES `users`(`user_id`)  ON DELETE CASCADE,
    INDEX `idx_ride_id`      (`ride_id`),
    INDEX `idx_passenger_id` (`passenger_id`)
) ENGINE=InnoDB", "Table: ride_requests", $success, $errors);

// ================================================================
// TABLE 4: payments
// Used in: api_payment.php — exact columns used
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `payments` (
    `id`                   INT AUTO_INCREMENT PRIMARY KEY,
    `request_id`           INT NOT NULL,
    `passenger_id`         INT NOT NULL,
    `ride_id`              INT NOT NULL,
    `amount`               DECIMAL(10,2) NOT NULL,
    `currency`             VARCHAR(5) DEFAULT 'INR',
    `razorpay_order_id`    VARCHAR(100) DEFAULT NULL,
    `razorpay_payment_id`  VARCHAR(100) DEFAULT NULL,
    `razorpay_signature`   VARCHAR(255) DEFAULT NULL,
    `status`               ENUM('pending','paid','failed') DEFAULT 'pending',
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`request_id`),
    INDEX (`passenger_id`)
) ENGINE=InnoDB", "Table: payments", $success, $errors);

// ================================================================
// TABLE 5: notifications
// Used in: api_requests.php, api_payment.php — columns:
//   user_id, title, message, type, is_read, link
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT NOT NULL,
    `title`      VARCHAR(100) DEFAULT NULL,
    `message`    TEXT DEFAULT NULL,
    `type`       ENUM('info','success','warning','error') DEFAULT 'info',
    `is_read`    BOOLEAN DEFAULT FALSE,
    `link`       VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    INDEX (`user_id`)
) ENGINE=InnoDB", "Table: notifications", $success, $errors);

// ================================================================
// TABLE 6: reviews
// Used in: api_reviews.php — columns:
//   ride_id, reviewer_id, reviewee_id, rating, comment
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `reviews` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `ride_id`     INT NOT NULL,
    `reviewer_id` INT NOT NULL,
    `reviewee_id` INT NOT NULL,
    `rating`      INT CHECK (`rating` >= 1 AND `rating` <= 5),
    `comment`     TEXT DEFAULT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ride_id`)     REFERENCES `rides`(`ride_id`),
    FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`user_id`),
    FOREIGN KEY (`reviewee_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB", "Table: reviews", $success, $errors);

// ================================================================
// TABLE 7: password_resets
// Used in: api_forgot_password.php, api_reset_password.php
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `email`      VARCHAR(100) NOT NULL,
    `token`      VARCHAR(255) NOT NULL,
    `expires_at` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB", "Table: password_resets", $success, $errors);

// ================================================================
// TABLE 8: location_tracking
// Used in: live_tracking.php
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `location_tracking` (
    `track_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `ride_id`    INT NOT NULL,
    `driver_id`  INT NOT NULL,
    `latitude`   DECIMAL(10,8) NOT NULL,
    `longitude`  DECIMAL(11,8) NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`ride_id`)   REFERENCES `rides`(`ride_id`)  ON DELETE CASCADE,
    FOREIGN KEY (`driver_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB", "Table: location_tracking", $success, $errors);

// ================================================================
// TABLE 9: contact_messages
// Used in: contact.php
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `msg_id`     INT AUTO_INCREMENT PRIMARY KEY,
    `user_name`  VARCHAR(100) NOT NULL,
    `user_email` VARCHAR(100) NOT NULL,
    `subject`    VARCHAR(255) NOT NULL,
    `content`    TEXT NOT NULL,
    `status`     ENUM('open','resolved') DEFAULT 'open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB", "Table: contact_messages", $success, $errors);

// ================================================================
// TABLE 10: admin_logs
// Used in: admin_dashboard.php, api_admin.php
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `admin_logs` (
    `log_id`         INT AUTO_INCREMENT PRIMARY KEY,
    `admin_username` VARCHAR(100) NOT NULL,
    `action_type`    VARCHAR(100) NOT NULL,
    `action_desc`    TEXT NOT NULL,
    `action_time`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB", "Table: admin_logs", $success, $errors);

// ================================================================
// TABLE 11: saved_locations
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `saved_locations` (
    `loc_id`        INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT NOT NULL,
    `location_name` VARCHAR(100) NOT NULL,
    `address_text`  VARCHAR(255) NOT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB", "Table: saved_locations", $success, $errors);

// ================================================================
// TABLE 12: fuel_logs (Fuel Calculator)
// Used in: fuel_calculator.php
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `fuel_logs` (
    `calc_id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT NOT NULL,
    `distance`        DECIMAL(8,2) NOT NULL,
    `fuel_efficiency` DECIMAL(5,2) NOT NULL,
    `fuel_price`      DECIMAL(6,2) NOT NULL,
    `estimated_cost`  DECIMAL(10,2) NOT NULL,
    `calculated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB", "Table: fuel_logs", $success, $errors);

// ================================================================
// TABLE 13: identity_documents (KYC)
// ================================================================
run($conn, "
CREATE TABLE IF NOT EXISTS `identity_documents` (
    `doc_id`        INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT NOT NULL,
    `doc_type`      VARCHAR(50) NOT NULL DEFAULT 'Aadhar',
    `doc_file_path` VARCHAR(255) NOT NULL,
    `verify_status` ENUM('pending','verified','rejected') DEFAULT 'pending',
    `uploaded_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB", "Table: identity_documents", $success, $errors);

// ================================================================
// SAMPLE DATA — only if users table is empty
// ================================================================
$chk = $conn->query("SELECT COUNT(*) as cnt FROM users");
$cnt = $chk->fetch_assoc()['cnt'];

if ($cnt == 0) {
    // bcrypt hash of "password"
    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    run($conn, "
    INSERT INTO `users` (`name`,`email`,`password`,`role`,`is_verified`,`rating`,`rating_count`,`phone`) VALUES
    ('Admin User',    'admin@sharemyride.com', '$hash', 'admin', 1, 5.00, 10, '9876543210'),
    ('Driver John',   'john@example.com',      '$hash', 'user',  1, 4.50,  8, '9876501234'),
    ('Student Alice', 'alice@example.com',      '$hash', 'user',  0, 4.00,  3, '9876505678'),
    ('Traveller Bob', 'bob@example.com',         '$hash', 'user',  1, 4.80,  5, '9876509999')
    ", "Sample Users (password: 'password')", $success, $errors);

    // Rides for Driver John (user_id=2) and Bob (user_id=4)
    run($conn, "
    INSERT INTO `rides`
        (`driver_id`,`from_location`,`to_location`,`ride_date`,`ride_time`,
         `seats_available`,`price_per_seat`,`vehicle_type`,`vehicle_model`,`details`,`status`)
    VALUES
    (2,'Kottayam','Kochi',       DATE_ADD(CURDATE(),INTERVAL 1 DAY),'08:00:00',3,150.00,'Car','Toyota Glanza','AC car, comfortable seats','active'),
    (2,'Kottayam','Trivandrum',  DATE_ADD(CURDATE(),INTERVAL 2 DAY),'07:30:00',2,250.00,'Car','Toyota Glanza','Long trip, stops at Kollam','active'),
    (4,'Kochi',   'Kozhikode',   DATE_ADD(CURDATE(),INTERVAL 1 DAY),'09:00:00',4,200.00,'SUV','Mahindra XUV500','Spacious SUV with AC','active'),
    (2,'Mundakkayam','Kottayam', DATE_ADD(CURDATE(),INTERVAL 3 DAY),'07:00:00',3,100.00,'Car','Toyota Glanza','Morning commute','active')
    ", "Sample Rides", $success, $errors);

    // A sample notification for john
    run($conn, "
    INSERT INTO `notifications` (`user_id`,`title`,`message`,`type`,`is_read`)
    VALUES (2,'Welcome to ShareMyRide!','Your account is ready. Start offering or finding rides.','success',0)
    ", "Sample Notification", $success, $errors);

} else {
    $success[] = "ℹ️ Users already exist ($cnt found) — skipping sample data.";
}

// ================================================================
// Safely add missing columns to existing tables
// Uses INFORMATION_SCHEMA check — will NOT crash if column exists
// ================================================================
addColumnIfMissing($conn, 'users', 'phone',                 "VARCHAR(20) DEFAULT NULL AFTER `profile_pic`",          $success, $errors);
addColumnIfMissing($conn, 'users', 'rating_count',          "INT DEFAULT 0 AFTER `rating`",                          $success, $errors);
addColumnIfMissing($conn, 'users', 'razorpay_customer_id',  "VARCHAR(100) DEFAULT NULL AFTER `is_verified`",          $success, $errors);
addColumnIfMissing($conn, 'rides', 'end_date',              "DATE DEFAULT NULL AFTER `ride_time`",                    $success, $errors);
addColumnIfMissing($conn, 'rides', 'ride_type',             "ENUM('daily','long') DEFAULT 'daily' AFTER `end_date`",  $success, $errors);
addColumnIfMissing($conn, 'rides', 'total_cost',            "DECIMAL(10,2) DEFAULT NULL AFTER `price_per_seat`",      $success, $errors);
addColumnIfMissing($conn, 'rides', 'intermediate_stops',    "TEXT DEFAULT NULL AFTER `details`",                      $success, $errors);
addColumnIfMissing($conn, 'ride_requests', 'proof_image',   "VARCHAR(255) DEFAULT NULL AFTER `seats_requested`",      $success, $errors);
addColumnIfMissing($conn, 'ride_requests', 'id_type',       "VARCHAR(50) DEFAULT NULL AFTER `proof_image`",           $success, $errors);
addColumnIfMissing($conn, 'ride_requests', 'id_number',     "VARCHAR(100) DEFAULT NULL AFTER `id_type`",              $success, $errors);
addColumnIfMissing($conn, 'ride_requests', 'pickup_loc',    "VARCHAR(255) DEFAULT NULL AFTER `id_number`",            $success, $errors);
addColumnIfMissing($conn, 'ride_requests', 'drop_loc',      "VARCHAR(255) DEFAULT NULL AFTER `pickup_loc`",           $success, $errors);
addColumnIfMissing($conn, 'ride_requests', 'final_price',   "DECIMAL(10,2) DEFAULT 0.00 AFTER `drop_loc`",            $success, $errors);
addColumnIfMissing($conn, 'ride_requests', 'payment_method',"ENUM('pay_now','pay_later') DEFAULT 'pay_later' AFTER `final_price`", $success, $errors);


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShareMyRide — Database Setup</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #0d0d1a;
    color: #e0e0f0;
    min-height: 100vh;
    padding: 40px 20px;
}
.container { max-width: 820px; margin: 0 auto; }
h1 { color: #7c6fff; font-size: 28px; margin-bottom: 8px; }
p.sub { color: #888; margin-bottom: 30px; font-size: 14px; }
.card {
    background: #1a1a2e;
    border-radius: 12px;
    padding: 22px 26px;
    margin-bottom: 20px;
    border-left: 4px solid #7c6fff;
}
.card.error { border-left-color: #f87171; }
.card.creds { border-left-color: #34d399; }
h3 { font-size: 16px; margin-bottom: 14px; color: #a0a0ff; }
li { padding: 5px 0; font-size: 14px; list-style: none; }
.ok  { color: #4ade80; }
.err { color: #f87171; }
.info { color: #60a5fa; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 8px 12px; text-align: left; font-size: 14px; }
th { color: #a0a0ff; border-bottom: 1px solid #2a2a4a; }
td { color: #ccc; }
.btn {
    display: inline-block;
    margin-top: 8px;
    margin-right: 10px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #7c6fff, #6c63ff);
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: opacity .2s;
}
.btn:hover { opacity: .85; }
.btn.sec { background: linear-gradient(135deg, #34d399, #059669); }
.stat { font-size: 13px; color: #888; margin-bottom: 5px; }
</style>
</head>
<body>
<div class="container">
    <h1>🚗 ShareMyRide — Database Setup Complete</h1>
    <p class="sub">All tables created and connected to your PHP project.</p>

    <div class="card">
        <h3>✅ Successful (<?= count($success) ?> operations)</h3>
        <ul>
            <?php foreach ($success as $s):
                $cls = strpos($s, 'ℹ️') !== false ? 'info' : 'ok'; ?>
                <li class="<?= $cls ?>"><?= htmlspecialchars($s) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="card error">
        <h3>❌ Errors (<?= count($errors) ?>)</h3>
        <ul>
            <?php foreach ($errors as $e): ?>
                <li class="err"><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card creds">
        <h3>🔑 Test Login Credentials (Password: <code>password</code>)</h3>
        <table>
            <tr><th>Role</th><th>Name</th><th>Email</th></tr>
            <tr><td>Admin</td><td>Admin User</td><td>admin@sharemyride.com</td></tr>
            <tr><td>Driver</td><td>Driver John</td><td>john@example.com</td></tr>
            <tr><td>Passenger</td><td>Student Alice</td><td>alice@example.com</td></tr>
            <tr><td>Passenger</td><td>Traveller Bob</td><td>bob@example.com</td></tr>
        </table>
    </div>

    <a href="/sharemyride/login.php"  class="btn">🚀 Go to Login</a>
    <a href="/sharemyride/dashboard.php" class="btn sec">📊 Dashboard</a>
    <a href="http://localhost/phpmyadmin/index.php?db=sharemyride" class="btn" style="background:linear-gradient(135deg,#f59e0b,#d97706)">🗄️ phpMyAdmin</a>
</div>
</body>
</html>
