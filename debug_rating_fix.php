<?php
// debug_rating_fix.php
require 'db_connect.php';

echo "<h1>Rating Consistency Check & Fix</h1>";

$users = $conn->query("SELECT user_id, name, rating, rating_count FROM users");

echo "<table border=1 cellpadding=5>
    <tr>
        <th>User ID</th>
        <th>Name</th>
        <th>Stored Rating (Count)</th>
        <th>Calculated Avg (Count)</th>
        <th>Status</th>
    </tr>";

while($user = $users->fetch_assoc()) {
    $uid = $user['user_id'];
    
    // Calc real average
    $avgQ = $conn->query("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE reviewee_id = $uid");
    $avgData = $avgQ->fetch_assoc();
    
    // Handle NULL average (no reviews)
    $realAvgRaw = $avgData['avg'] ?? 0;
    $realAvg = number_format((float)$realAvgRaw, 2);
    $realCount = $avgData['cnt'] ?? 0;
    
    $storedRating = number_format((float)$user['rating'], 2);
    $storedCount = $user['rating_count'];
    
    // Compare
    $ratingDiff = abs((float)$storedRating - (float)$realAvg);
    $countDiff = abs($storedCount - $realCount);
    
    $status = "OK";
    if ($ratingDiff > 0.01 || $countDiff > 0) {
        $status = "<span style='color:red'>MISMATCH</span>";
        
        // Auto-fix
        $updateSt = $conn->prepare("UPDATE users SET rating = ?, rating_count = ? WHERE user_id = ?");
        $updateSt->bind_param("dii", $realAvgRaw, $realCount, $uid);
        if ($updateSt->execute()) {
            $status .= " -> FIXED";
        } else {
            $status .= " -> FIX FAILED: " . $conn->error;
        }
    }
    
    echo "<tr>
        <td>{$uid}</td>
        <td>" . htmlspecialchars($user['name']) . "</td>
        <td>{$storedRating} ({$storedCount})</td>
        <td>{$realAvg} ({$realCount})</td>
        <td>{$status}</td>
    </tr>";
}
echo "</table>";
?>
