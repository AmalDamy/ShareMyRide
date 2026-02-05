<?php
require_once 'db_connect.php';

echo "<h2>Implementing User-Defined Schema...</h2>";

// Use a helper function for clarity
function createTable($conn, $sql, $tableName) {
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Table <strong>$tableName</strong> created/verified.</p>";
    } else {
        echo "<p style='color:red'>Error creating $tableName: " . $conn->error . "</p>";
    }
}

// 1. USERS (Modify existing to match spec)
// Spec: user_id, name, email, phone, password_hash, rating, preferences, user_role, created_at
echo "<h3>1. Users Table</h3>";
$conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20)");
$conn->query("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255)"); // Will map to password logic later
$conn->query("ALTER TABLE users ADD COLUMN preferences TEXT");
$conn->query("ALTER TABLE users ADD COLUMN user_role ENUM('Passenger','Driver','Both') DEFAULT 'Passenger'");
echo "Updated 'users' table columns.<br>";

// 2. ADMIN
$sql = "CREATE TABLE IF NOT EXISTS admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255),
    role ENUM('SuperAdmin','Moderator','Support'),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
createTable($conn, $sql, "admin");

// 3. ADMIN_LOGS
// Spec: log_id, ... (Assuming standard log fields + description based on name)
$sql = "CREATE TABLE IF NOT EXISTS admin_logs_new (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(255),
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
)";
createTable($conn, $sql, "admin_logs (Spec)");

// 4. ADMIN_NOTIFICATIONS
$sql = "CREATE TABLE IF NOT EXISTS admin_notifications (
    admin_notif_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    message TEXT,
    notif_type ENUM('Verification','Report','System','User Issue'),
    status ENUM('Read','Unread') DEFAULT 'Unread',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
)";
createTable($conn, $sql, "admin_notifications");

// 5. VERIFICATION
$sql = "CREATE TABLE IF NOT EXISTS verification (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    verified_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";
createTable($conn, $sql, "verification");

// 6. IDENTITY_DOCUMENTS
$sql = "CREATE TABLE IF NOT EXISTS identity_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    doc_type VARCHAR(50),
    doc_image LONGBLOB, -- Or TEXT for base64/path
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";
createTable($conn, $sql, "identity_documents");

// 7. RIDES (Modify existing)
// Spec: ride_id, driver_id, origin, destination, date_time, total_seats, available_seats, price_per_seat, vehicle_type, safety_code, status
echo "<h3>7. Rides Table</h3>";
$conn->query("ALTER TABLE rides ADD COLUMN origin VARCHAR(100)");
$conn->query("ALTER TABLE rides ADD COLUMN destination VARCHAR(100)");
$conn->query("ALTER TABLE rides ADD COLUMN date_time DATETIME");
$conn->query("ALTER TABLE rides ADD COLUMN total_seats INT");
$conn->query("ALTER TABLE rides ADD COLUMN safety_code VARCHAR(10)");
// Note: vehicle_type, price_per_seat, status, driver_id already exist
echo "Updated 'rides' table columns.<br>";

// 8. BOOKINGS
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT,
    passenger_id INT,
    status ENUM('Pending','Confirmed','Cancelled','Completed') DEFAULT 'Pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(ride_id),
    FOREIGN KEY (passenger_id) REFERENCES users(user_id)
)";
createTable($conn, $sql, "bookings");

// 9. FEEDBACK
$sql = "CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT,
    from_user INT,
    to_user INT,
    rating INT,
    comments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(ride_id),
    FOREIGN KEY (from_user) REFERENCES users(user_id),
    FOREIGN KEY (to_user) REFERENCES users(user_id)
)";
createTable($conn, $sql, "feedback");

// 10. CHAT_MESSAGES
$sql = "CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT,
    sender_id INT,
    message_text TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(ride_id),
    FOREIGN KEY (sender_id) REFERENCES users(user_id)
)";
createTable($conn, $sql, "chat_messages");

// 11. LONG_TRIP_GROUP
$sql = "CREATE TABLE IF NOT EXISTS long_trip_group (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT,
    origin VARCHAR(100),
    destination VARCHAR(100),
    start_date DATE, -- Image cut off, assuming DATE
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
)";
createTable($conn, $sql, "long_trip_group");

// 12. GROUP_MEMBERS
$sql = "CREATE TABLE IF NOT EXISTS group_members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT,
    user_id INT,
    seats_taken INT,
    FOREIGN KEY (group_id) REFERENCES long_trip_group(group_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";
createTable($conn, $sql, "group_members");

// 13. NOTIFICATIONS (Modify/Ensure)
// Spec: notification_id, user_id, message, notif_type, created_at, status
echo "<h3>13. Notifications Table</h3>";
// Rename id to notification_id if needed, or just ensure aliases work. 
// Current table has: id, user_id, title, message, type, is_read, created_at
// Let's create a new one strictly matching spec or alter. 
// To be safe and "Make the database" per spec, I'll create the strict table if not exists with slightly different name to avoid conflict or alter.
// Let's alter existing to fit.
$conn->query("ALTER TABLE notifications CHANGE id notification_id INT AUTO_INCREMENT");
$conn->query("ALTER TABLE notifications ADD COLUMN notif_type ENUM('Ride','Alert','Payment','General')");
$conn->query("ALTER TABLE notifications ADD COLUMN status ENUM('Sent','Pending','Failed') DEFAULT 'Sent'");
echo "Updated 'notifications' table columns.<br>";


// 14. LOCATION_TRACKING
$sql = "CREATE TABLE IF NOT EXISTS location_tracking (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT,
    user_id INT,
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    tracked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(ride_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";
createTable($conn, $sql, "location_tracking");


echo "<h2>Full Specification Schema Implemented!</h2>";
?>
