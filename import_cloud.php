<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes

// Aiven Connection Details
$host = "mysql-2b2e12f3-sharemyride-0025.b.aivencloud.com";
$port = 27962;
$user = "avnadmin";
$pass = "AVNS_I73W0twlhU7zWCofOT7";
$db   = "defaultdb";

echo "<h2>🚀 Aiven Database Importer</h2>";
echo "Connecting to Aiven...<br>";

// Connect using MySQLi
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("<b style='color:red'>Connection Failed:</b> " . $conn->connect_error);
}

echo "<b style='color:green'>Connected Successfully!</b><br><br>";

// Check if SQL file exists
$sqlFile = 'database.sql';
if (!file_exists($sqlFile)) {
    die("<b style='color:red'>Error:</b> database.sql not found in this folder.");
}

echo "Reading database.sql...<br>";
$sql = file_get_contents($sqlFile);

// Split by semicolon to run multiple queries, but handle comments and strings
$queries = explode(";\n", $sql);
$count = 0;
$success = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        $count++;
        if ($conn->query($query)) {
            $success++;
        } else {
            echo "<small style='color:orange'>Skipped/Error on query $count: " . substr($conn->error, 0, 100) . "...</small><br>";
        }
    }
}

echo "<br><hr>";
echo "<h3>✅ Import Finished!</h3>";
echo "Processed: $count queries<br>";
echo "Successful: $success queries<br>";
echo "<br><a href='index.php'>Go to Website</a>";

$conn->close();
?>
