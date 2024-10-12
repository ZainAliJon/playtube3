<?php
// oEmbed endpoint
header('Content-Type: application/json');

// Allowed parameters
$allowedParams = ['url', 'maxwidth', 'maxheight', 'format'];

// Parse query parameters
$params = [];
foreach ($allowedParams as $param) {
    if (isset($_GET[$param])) {
        $params[$param] = $_GET[$param];
    }
}

// Check if 'url' parameter is provided
if (!isset($params['url'])) {
    echo json_encode(['error' => 'Missing "url" parameter']);
    exit;
}

// Custom video URL regex to capture the video ID
$customVideoRegex = '/^(https?:\/\/localhost\/playtube\/script\/v\/([a-zA-Z0-9_-]+))$/';
$videoId = null;

// Check if the URL is a valid custom video URL
if (preg_match($customVideoRegex, $params['url'], $matches)) {
    $videoId = $matches[2]; // Capture the video ID from the URL
}

// If no valid video ID was found
if (!$videoId) {
    echo json_encode(['error' => 'Invalid video URL']);
    exit;
}

// Prepare oEmbed response
$response = [
    'version' => '1.0',
    'type' => 'video',
    'provider_name' => 'Your Custom Video Service',
    'provider_url' => 'https://e-ai.dailycodefix.com/script',
    'html' => '<iframe width="560" height="315" src="https://e-ai.dailycodefix.com/script/embed/' . $videoId . '" frameborder="0" allowfullscreen></iframe>',
    'width' => 560,
    'height' => 315,
    'title' => 'Custom Video',
    'author_name' => 'Your Custom Video Service',
    'author_url' => 'https://e-ai.dailycodefix.com/script',
];

// Add maxwidth/maxheight to response if provided
if (isset($params['maxwidth'])) {
    $response['width'] = min(560, (int)$params['maxwidth']);
}
if (isset($params['maxheight'])) {
    $response['height'] = min(315, (int)$params['maxheight']);
}

// Return the response as JSON
echo json_encode($response);
?>
