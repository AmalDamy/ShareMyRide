<?php
require_once 'db_connect.php';

echo "<h2>Populating Database with Mock Data...</h2>";

// Helper function
function runInsert($conn, $sql) {
    if ($conn->query($sql) === TRUE) {
        // Success (silence is golden for clean output, or dot for progress)
        echo ".";
    } else {
        echo "<br>Error: " . $conn->error;
    }
}

// 1. ADMIN
echo "<br>Populating Admin...";
runInsert($conn, "INSERT INTO admin (name, email, phone, role) VALUES ('Super Admin', 'admin@sharemyride.com', '9876543210', 'SuperAdmin')");
runInsert($conn, "INSERT INTO admin (name, email, phone, role) VALUES ('Moderator Mike', 'mike@sharemyride.com', '9876543211', 'Moderator')");

// 2. ADMIN_LOGS
echo "<br>Populating Admin Logs...";
runInsert($conn, "INSERT INTO admin_logs_new (admin_id, action, description) VALUES (1, 'Login', 'Admin logged in successfully')");
runInsert($conn, "INSERT INTO admin_logs_new (admin_id, action, description) VALUES (1, 'Settings Change', 'Updated system preferences')");

// 3. ADMIN_NOTIFICATIONS
echo "<br>Populating Admin Notifications...";
runInsert($conn, "INSERT INTO admin_notifications (admin_id, message, notif_type) VALUES (1, 'New user verification pending', 'Verification')");
runInsert($conn, "INSERT INTO admin_notifications (admin_id, message, notif_type) VALUES (1, 'Server load high', 'System')");

// 4. USERS (Update existing or insert new if needed)
echo "<br>Populating Users (Extras)...";
// Ensure we have some users with extended fields
runInsert($conn, "UPDATE users SET phone='1234567890', user_role='Driver' WHERE user_id=1"); 
runInsert($conn, "UPDATE users SET phone='0987654321', user_role='Passenger' WHERE user_id=2");

// 5. VERIFICATION
echo "<br>Populating Verification...";
runInsert($conn, "INSERT INTO verification (user_id, status, verified_at) VALUES (1, 'Approved', NOW())");
runInsert($conn, "INSERT INTO verification (user_id, status) VALUES (2, 'Pending')");

// 6. IDENTITY_DOCUMENTS
echo "<br>Populating Identity Docs...";
runInsert($conn, "INSERT INTO identity_documents (user_id, doc_type) VALUES (1, 'Driving License')");
runInsert($conn, "INSERT INTO identity_documents (user_id, doc_type) VALUES (2, 'Student ID card')");

// 7. RIDES (Update existing to have new fields filled)
echo "<br>Populating Rides...";
// Update existing active rides
runInsert($conn, "UPDATE rides SET origin=from_location, destination=to_location, date_time=CONCAT(ride_date, ' ', ride_time), total_seats=seats_available+1, safety_code='1234' WHERE ride_id > 0");

// 8. BOOKINGS
echo "<br>Populating Bookings...";
// Get a ride id and user id
$resRide = $conn->query("SELECT ride_id FROM rides LIMIT 1");
$rideId = $resRide->fetch_assoc()['ride_id'] ?? 1;
runInsert($conn, "INSERT INTO bookings (ride_id, passenger_id, status) VALUES ($rideId, 2, 'Confirmed')");
runInsert($conn, "INSERT INTO bookings (ride_id, passenger_id, status) VALUES ($rideId, 1, 'Pending')");

// 9. FEEDBACK
echo "<br>Populating Feedback...";
runInsert($conn, "INSERT INTO feedback (ride_id, from_user, to_user, rating, comments) VALUES ($rideId, 2, 1, 5, 'Great ride, very safe drive!')");
runInsert($conn, "INSERT INTO feedback (ride_id, from_user, to_user, rating, comments) VALUES ($rideId, 1, 2, 4, 'Nice passenger')");

// 10. CHAT_MESSAGES
echo "<br>Populating Chat Messages...";
runInsert($conn, "INSERT INTO chat_messages (ride_id, sender_id, message_text) VALUES ($rideId, 2, 'Where is the pickup point?')");
runInsert($conn, "INSERT INTO chat_messages (ride_id, sender_id, message_text) VALUES ($rideId, 1, 'Near the main gate.')");

// 11. LONG_TRIP_GROUP
echo "<br>Populating Long Trip Groups...";
runInsert($conn, "INSERT INTO long_trip_group (created_by, origin, destination, start_date) VALUES (1, 'Kochi', 'Bangalore', '2025-12-25')");

// 12. GROUP_MEMBERS
echo "<br>Populating Group Members...";
runInsert($conn, "INSERT INTO group_members (group_id, user_id, seats_taken) VALUES (1, 1, 1)");
runInsert($conn, "INSERT INTO group_members (group_id, user_id, seats_taken) VALUES (1, 2, 2)");

// 13. NOTIFICATIONS (Already active in app, but let's add specific types)
echo "<br>Populating Notifications...";
runInsert($conn, "INSERT INTO notifications (user_id, message, notif_type, status) VALUES (1, 'Your ride has been booked', 'Ride', 'Sent')");

// 14. LOCATION_TRACKING
echo "<br>Populating Location Tracking...";
runInsert($conn, "INSERT INTO location_tracking (ride_id, user_id, latitude, longitude) VALUES ($rideId, 1, 9.931233, 76.267304)");

echo "<br><h2>Data Population Complete!</h2>";
?>
