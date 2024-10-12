<?php
header('Content-Type: application/json');

// Check if the URL parameter is set
if (isset($_GET['url'])) {
    $url = $_GET['url'];

    // Validate the URL format
    if (preg_match('/^https:\/\/e-ai.dailycodefix.com\/v\/([a-zA-Z0-9]+)/', $url, $matches)) {
        $videoId = $matches[1];

        // Generate the oEmbed response
        $response = [
            'version' => '1.0',
            'type' => 'video',
            'provider_name' => 'Your Site Name',
            'provider_url' => 'https://e-ai.dailycodefix.com/script',
            'width' => 640,
            'height' => 360,
            'html' => '<iframe width="640" height="360" src="http://localhost/playtube/v/wjazNO" frameborder="0" allowfullscreen></iframe>',
            'thumbnail_url' => 'http://localhost/path/to/thumbnail/' . htmlspecialchars($videoId) . '.jpg', // Adjust as necessary
        ];
        echo json_encode($response);
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
