<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>LOCATION CHECK</h1>";
echo "<p>Currently running file: " . __FILE__ . "</p>";
echo "<p>If this path starts with <code>C:\\xampp 1\\htdocs</code>, you are in the right place.</p>";

$conn = new mysqli("127.0.0.1", "root", "", "sharemyride");
if ($conn->connect_error) {
    echo "<h2 style='color:red'>Database Error: " . $conn->connect_error . "</h2>";
} else {
    echo "<h2 style='color:green'>Database Connected Successfully!</h2>";
}
?>
