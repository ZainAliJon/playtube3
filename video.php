<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Player with Recommendations</title>
    <style>
        #videoPlayer {
            width: 80%;
            margin: 0 auto;
            display: block;
        }
        #recommendations {
            display: none;
            text-align: center;
        }
        .recommendation {
            display: inline-block;
            margin: 10px;
        }
        .recommendation img {
            width: 120px;
            height: 80px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- Video Player -->
    <video id="videoPlayer" controls>
        <source src="http://localhost/playtube/upload/videos/2024/10/TFOOzHRSTGQxuDXCU4r4_07_6d4136b9c48d2d4571f243d84335bea5_video.mov" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <!-- Recommendations Section -->
    <div id="recommendations">
        <h3>Recommended Videos</h3>
        <?php
            // Example of hard-coded recommended videos. Replace with dynamic PHP code if needed.
            $recommendedVideos = [
                ['title' => 'Video 1', 'thumbnail' => 'http://localhost/playtube/upload/photos/2024/10/qkJy6lPMGFkFouhMYvMy_07_a0dddb7a97f65da90c71d42347781e09_image.jpg', 'src' => 'http://localhost/playtube/upload/videos/2024/10/TFOOzHRSTGQxuDXCU4r4_07_6d4136b9c48d2d4571f243d84335bea5_video.mov'],
                ['title' => 'Video 2', 'thumbnail' => 'http://localhost/playtube/upload/photos/2024/10/qkJy6lPMGFkFouhMYvMy_07_a0dddb7a97f65da90c71d42347781e09_image.jpg', 'src' => 'http://localhost/playtube/upload/videos/2024/10/TFOOzHRSTGQxuDXCU4r4_07_6d4136b9c48d2d4571f243d84335bea5_video.mov'],
                ['title' => 'Video 3', 'thumbnail' => 'http://localhost/playtube/upload/photos/2024/10/qkJy6lPMGFkFouhMYvMy_07_a0dddb7a97f65da90c71d42347781e09_image.jpg', 'src' => 'http://localhost/playtube/upload/videos/2024/10/TFOOzHRSTGQxuDXCU4r4_07_6d4136b9c48d2d4571f243d84335bea5_video.mov'],
            ];
            
            foreach ($recommendedVideos as $video) {
                echo '<div class="recommendation" onclick="playVideo(\'' . $video['src'] . '\')">';
                echo '<img src="' . $video['thumbnail'] . '" alt="' . $video['title'] . '">';
                echo '<p>' . $video['title'] . '</p>';
                echo '</div>';
            }
        ?>
    </div>

    <script>
        const videoPlayer = document.getElementById('videoPlayer');
        const recommendations = document.getElementById('recommendations');

        // Function to play a recommended video
        function playVideo(videoSrc) {
            videoPlayer.src = videoSrc;
            videoPlayer.play();
            recommendations.style.display = 'none';
        }

        // Show recommendations when video ends
        videoPlayer.addEventListener('ended', function() {
            recommendations.style.display = 'block';
        });

        // Optional: show recommendations if video is paused
        videoPlayer.addEventListener('pause', function() {
            if (videoPlayer.currentTime !== videoPlayer.duration) { // Show only if user manually pauses
                recommendations.style.display = 'block';
            }
        });
    </script>

</body>
</html>
