<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "e-ai";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define the "active" time window (5 minutes)
$active_time_limit = date("Y-m-d H:i:s", strtotime('-5 minutes'));

// Query to get the count of users active within the last 5 minutes
$sql = "SELECT COUNT(*) as live_user_count FROM user_sessions WHERE last_activity > '$active_time_limit'";
$result = $conn->query($sql);

$live_user_count = 0;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $live_user_count = $row['live_user_count'];
}

echo json_encode(['live_user_count' => $live_user_count]);

$conn->close();
?>
