<?php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$user_id = $_SESSION['user_id'];
echo "Checking requests for User ID: $user_id<br><br>";

$sql = "SELECT rq.request_id, rq.status as request_status, r.ride_id, r.status as ride_status, r.from_location, r.to_location 
        FROM ride_requests rq 
        JOIN rides r ON rq.ride_id = r.ride_id 
        WHERE rq.passenger_id = $user_id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Request ID</th><th>Ride ID</th><th>From</th><th>To</th><th>Request Status</th><th>Ride Status</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['request_id'] . "</td>";
        echo "<td>" . $row['ride_id'] . "</td>";
        echo "<td>" . $row['from_location'] . "</td>";
        echo "<td>" . $row['to_location'] . "</td>";
        echo "<td>" . $row['request_status'] . "</td>";
        echo "<td>" . $row['ride_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No ride requests found.";
}
?>
