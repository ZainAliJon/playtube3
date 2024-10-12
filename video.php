<?php
// Database connection parameters
$host = 'localhost';
$db = 'u144950326_e_ai';
$user = 'u144950326_e_ai';
$pass = 'Devquire@512514';

// Create a connection
$conn = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get the video ID from the query string and sanitize it
$video_id = isset($_GET['video_id']) ? mysqli_real_escape_string($conn, $_GET['video_id']) : '';
$video_id = str_replace('\\', '', $video_id);

// Initialize default values
$thumbnail = '';
$url = '';
$category_id = '';

if (!empty($video_id)) {
    // Prepare the SQL statement
    $sql = "SELECT * FROM videos WHERE short_id = '$video_id'";
    
    // Execute the query
    $result = mysqli_query($conn, $sql);

    // Check if there are results
    if (mysqli_num_rows($result) > 0) {
        // Fetch video data
        while ($video = mysqli_fetch_assoc($result)) {
            $thumbnail = "https://e-ai.dailycodefix.com/" . $video['thumbnail'];
            $url = "https://e-ai.dailycodefix.com/" . $video['video_location'];
            $category_id = $video['category_id'];
        }
    } else {
        echo "No video found with the provided ID.";
    }
} else {
    echo "No video ID provided.";
}

// Fetch related videos
$sql_related = "SELECT * FROM videos WHERE category_id = '$category_id' AND short_id != '$video_id' LIMIT 3";
$result_related = mysqli_query($conn, $sql_related);
$related_videos = [];
if (mysqli_num_rows($result_related) > 0) {
    while ($related_video = mysqli_fetch_assoc($result_related)) {
        $related_videos[] = $related_video;
    }
}

// Close the connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Video Player</title>

    <!-- Plyr CSS -->
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            position: relative; /* Ensure positioning context for absolute elements */
        }
        #videoContainer {
            position: relative; /* Position context for absolutely positioned elements */
            width: 80%;
            margin: 0 auto;
        }
        .plyr {
            margin-bottom: 0; /* Remove margin below player */
        }
        #logoOverlay {
            position: absolute; /* Position the logo absolutely */
            top: 10px; /* Distance from the top */
            left: 10px; /* Distance from the left */
            z-index: 3; /* Ensure it's above other content */
        }
        #recommendationsContainer {
            position: absolute; /* Position over the video */
            bottom: 0; /* Align to the bottom */
            left: 0; /* Align to the left */
            right: 0; /* Align to the right */
            background: rgba(0, 0, 0, 0.7); /* Semi-transparent background */
            padding: 10px; /* Padding for content */
            display: none; /* Hidden by default */
        }
        .recommendations {
            display: flex; /* Aligns items in a single line */
            justify-content: space-between; /* Distributes space between items */
        }
        .recommendationVideo {
            flex: 1; /* Allows each video to take equal width */
            margin-right: 10px; /* Space between videos */
            min-width: 0; /* Prevent overflow */
        }
        .recommendationVideo img {
            width: 100%;
            border-radius: 8px;
        }
        .recommendationVideo:last-child {
            margin-right: 0; /* Remove margin for the last item */
        }
    </style>
</head>
<body>

    <div id="videoContainer">
        <!-- Logo Overlay -->
        <div id="logoOverlay">
            <a href="https://e-ai.dailycodefix.com" target="_blank">
                <img src="https://e-ai.dailycodefix.com/themes/default/img/logo-light.png" alt="Logo" style="width: 100px; cursor: pointer;">
            </a>
        </div>

        <!-- Plyr Video Player -->
        <video controls crossorigin id="player" preload="metadata">
            <source src="<?php echo htmlspecialchars($url); ?>" type="video/mp4" />
            Your browser does not support the video tag.
        </video>

        <!-- Recommendations Container -->
        <div id="recommendationsContainer">
            <h3 style="color: #fff; margin-bottom: 10px;">Recommended Videos</h3>
            <div class="recommendations">
                <?php if (!empty($related_videos)): ?>
                    <?php foreach ($related_videos as $related_video): ?>
                        <div class="recommendationVideo">
                            <a href="video.php?video_id=<?php echo htmlspecialchars($related_video['short_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <img src="<?php echo htmlspecialchars($related_video['thumbnail'], ENT_QUOTES, 'UTF-8'); ?>" alt="Video Thumbnail">
                            </a>
                            <p class="text-center" style="color: #fff;"><?php echo htmlspecialchars($related_video['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #fff;">No related videos found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Plyr JS -->
    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    
    <script>
        // Initialize the Plyr player
        const player = new Plyr('#player');

        // Event listener for when the video ends
        player.on('ended', () => {
            // Show recommendations when the main video ends
            document.getElementById('recommendationsContainer').style.display = 'block';
        });

        // Event listener for when the video is paused
        player.on('pause', () => {
            // Show recommendations only if the video is manually paused
            if (player.currentTime !== player.duration) {
                document.getElementById('recommendationsContainer').style.display = 'block';
            }
        });

        // Event listener for when the video is playing
        player.on('play', () => {
            // Hide recommendations when the main video is playing
            document.getElementById('recommendationsContainer').style.display = 'none';
        });
    </script>

</body>
</html>
