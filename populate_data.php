<?php
require_once 'config.php';
require_once 'db_connect.php';

echo "<h1>Populating Sample Rides...</h1>";

// 1. Rides for "Driver John" (ID 2)
$driver_id_john = 2; 

// 2. Rides for "Me" (ID 1 - The user logged in via bypass)
$driver_id_me = 1;

// Rides to insert
$rides = [
    // John's Rides
    [
        'driver_id' => $driver_id_john,
        'from_location' => 'Kottayam',
        'to_location' => 'Kochi',
        'ride_date' => date('Y-m-d', strtotime('+1 day')), 
        'ride_time' => '08:30:00',
        'seats_available' => 3,
        'price_per_seat' => 150.00,
        'vehicle_type' => 'Car (Sedan)',
        'details' => 'Leaving from Gandhi Square. AC Car.'
    ],
    [
        'driver_id' => $driver_id_john,
        'from_location' => 'Trivandrum',
        'to_location' => 'Kollam',
        'ride_date' => date('Y-m-d', strtotime('+2 days')),
        'ride_time' => '10:00:00',
        'seats_available' => 2,
        'price_per_seat' => 200.00,
        'vehicle_type' => 'Bike',
        'details' => 'Helmet provided.'
    ],
    // My Rides (To verify I can see my own rides now)
    [
        'driver_id' => $driver_id_me,
        'from_location' => 'Pala',
        'to_location' => 'Ernakulam',
        'ride_date' => date('Y-m-d', strtotime('+3 days')),
        'ride_time' => '07:00:00',
        'seats_available' => 4,
        'price_per_seat' => 100.00,
        'vehicle_type' => 'Hatchback',
        'details' => 'Early morning drive.'
    ]
];

foreach ($rides as $ride) {
    $stmt = $conn->prepare("INSERT INTO rides (driver_id, from_location, to_location, ride_date, ride_time, seats_available, price_per_seat, vehicle_type, details) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssidss", $ride['driver_id'], $ride['from_location'], $ride['to_location'], $ride['ride_date'], $ride['ride_time'], $ride['seats_available'], $ride['price_per_seat'], $ride['vehicle_type'], $ride['details']);
    
    if ($stmt->execute()) {
        echo "<p>Inserted ride: {$ride['from_location']} -> {$ride['to_location']} (Driver ID: {$ride['driver_id']})</p>";
    } else {
        echo "<p style='color:red'>Error inserting ride: " . $stmt->error . "</p>";
    }
}

echo "<p>Done! Go to <a href='find_ride.php'>Find Ride</a> to see them.</p>";
?>
