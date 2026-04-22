<?php
// Initialize the session
session_start();

// If not logged in, redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Initialize variables
$search_artist = $search_track = "";
$api_error = $db_error = "";
$preview_data = null;

// ==========================================
// ACTION 2: SAVE CONFIRMED SONG TO DATABASE
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_add'])) {

    $title = trim($_POST['title']);
    $artist = trim($_POST['artist']);
    $genre = isset($_POST['genre']) ? trim($_POST['genre']) : "";
    $cover_url = trim($_POST['cover_url']);

    // Insert into database
    $sql = "INSERT INTO songs (title, artist, genre, cover_image_url) VALUES (?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssss", $param_title, $param_artist, $param_genre, $param_cover);

        $param_title = $title;
        $param_artist = $artist;
        $param_genre = $genre;
        $param_cover = $cover_url;

        if (mysqli_stmt_execute($stmt)) {
            // Grab the ID of the song we just inserted
            $new_song_id = mysqli_insert_id($link);

            // Redirect straight to the review page with this song pre-selected
            header("location: review.php?song_id=" . $new_song_id . "&song_title=" . urlencode($title) . "&artist=" . urlencode($artist));
            exit;
        } else {
            $db_error = "Oops! Something went wrong saving to the database.";
        }
        mysqli_stmt_close($stmt);
    }
}

// ==========================================
// ACTION 1: SEARCH LAST.FM API
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search_api'])) {

    $search_artist = trim($_GET['artist']);
    $search_track = trim($_GET['track']);

    if (!empty($search_artist) && !empty($search_track)) {

        // Build the API URL
        $api_key = LASTFM_API_KEY;
        $url_artist = urlencode($search_artist);
        $url_track = urlencode($search_track);
        $url = "http://ws.audioscrobbler.com/2.0/?method=track.getInfo&api_key={$api_key}&artist={$url_artist}&track={$url_track}&format=json";

        // Fetch the data (the @ suppresses PHP warnings if the API is unreachable)
        $response = @file_get_contents($url);

        if ($response === FALSE) {
            $api_error = "Could not connect to Last.fm. Please try again later.";
        } else {
            $data = json_decode($response, true);

            // Check if Last.fm returned an error (like "Track not found")
            if (isset($data['error'])) {
                $api_error = "Song not found. Please double-check your spelling!";
            } else {
                // Success! Extract the data we need
                $preview_data = [
                    'title' => $data['track']['name'],
                    'artist' => $data['track']['artist']['name'],
                    'genre' => '',
                    'cover_image' => ''
                ];

                // Grab the 'extralarge' image for the cover
                if (isset($data['track']['album']['image'][3]['#text'])) {
                    $preview_data['cover_image'] = $data['track']['album']['image'][3]['#text'];
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TuneReview - Add Song</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .preview-card {
            text-align: center;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            border: 1px solid #ddd;
        }

        .preview-card img {
            max-width: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }

        .preview-card h3 {
            margin: 0 0 5px 0;
            color: #1a1a2e;
        }

        .preview-card p {
            margin: 0 0 15px 0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <a href="index.php" class="logo">TUNE REVIEW</a>
        <div>
            <a href="index.php">Home</a>
            <a href="index.php#songs">Albums</a>
            <a href="addSong.php" class="btn-nav" style="background:#6a4faa;">Write a Review</a>
            <a href="logout.php" style="color:#e74c3c;">Logout</a>
        </div>
    </div>

    <div class="wrapper" style="width: 500px;">
        <h2>Find a Track</h2>

        <?php if (!empty($api_error)) echo "<div class='alert-danger'>$api_error</div>"; ?>
        <?php if (!empty($db_error)) echo "<div class='alert-danger'>$db_error</div>"; ?>

        <form action="addSong.php" method="GET">
            <label>Artist Name</label>
            <input type="text" name="artist" value="<?php echo htmlspecialchars($search_artist); ?>" required>

            <label>Song Title</label>
            <input type="text" name="track" value="<?php echo htmlspecialchars($search_track); ?>" required>

            <input type="hidden" name="search_api" value="1">
            <input type="submit" class="btn-primary" value="Search Last.fm">
        </form>

        <?php if ($preview_data): ?>
            <div class="preview-card">
                <?php if ($preview_data['cover_image']): ?>
                    <img src="<?php echo htmlspecialchars($preview_data['cover_image']); ?>" alt="Album Cover">
                <?php else: ?>
                    <div style="width:200px; height:200px; background:#ddd; margin:0 auto 15px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#888;">No Image Found</div>
                <?php endif; ?>

                <h3><?php echo htmlspecialchars($preview_data['title']); ?></h3>
                <p><?php echo htmlspecialchars($preview_data['artist']); ?></p>

                <form action="addSong.php" method="POST">
                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($preview_data['title']); ?>">
                    <input type="hidden" name="artist" value="<?php echo htmlspecialchars($preview_data['artist']); ?>">
                    <input type="hidden" name="cover_url" value="<?php echo htmlspecialchars($preview_data['cover_image']); ?>">
                    <input type="hidden" name="confirm_add" value="1">

                    <input type="submit" class="btn-primary" style="background:#2ecc71;" value="Confirm & Review this Song">
                </form>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>