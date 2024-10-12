<?php
header('Content-Type: application/json');
$servername = "localhost";
$username = "u144950326_e_ai";
$password = "Devquire@512514";
$dbname = "u144950326_e_ai";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check if the URL parameter is set
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    // Validate the URL format
    if (preg_match('/^https:\/\/e-ai.dailycodefix.com\/v\/([a-zA-Z0-9]+)/', $url, $matches)) {
       
// Assuming $matches[1] contains the video ID from a previous match operation
$videoId = rtrim($matches[1], '/');
// Prepare the SQL statement to prevent SQL injection
$sql = "SELECT * FROM videos WHERE short_id = '".mysqli_real_escape_string($conn, $videoId)."'";
$result = mysqli_query($conn, $sql);
if ($result) { // Check if the query was successful
    if (mysqli_num_rows($result) > 0) {
        // Output data of each row
        while ($video = mysqli_fetch_assoc($result)) {
            // Assuming the thumbnail path is stored in the database
            $thumbnail = "https://e-ai.dailycodefix.com/" . htmlspecialchars($video['thumbnail'], ENT_QUOTES, 'UTF-8');
            // Additional processing can be done here if needed
        }
    } else {
        // Handle case where no videos are found
        echo "No video found with that ID.";
    }
} else {
    // Handle SQL query error
    echo "Error executing query: " . mysqli_error($conn);
}


        // Generate the oEmbed response
        $response = [
    'version' => '1.0',
    'type' => 'video',
    'provider_name' => 'E.AI',
    'provider_url' => 'https://e-ai.dailycodefix.com',
    'title' => $video['title'] ?? 'E.AI Video',
    'width' => 640,
    'height' => 360,
    'html' => str_replace('\\', '', '<iframe width="640" height="360" src="https://e-ai.dailycodefix.com/video.php?video_id=' . htmlspecialchars($videoId) . '" frameborder="0" allowfullscreen></iframe>'),
    'thumbnail_url' => $thumbnail,
];

        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        exit;
    } else {
        // Invalid URL format
        http_response_code(404);
        echo json_encode(['error' => 'Invalid URL format']);
        exit;
    }
} else {
    // No URL parameter provided
    http_response_code(400);
    echo json_encode(['error' => 'No URL parameter provided']);
    exit;
}
