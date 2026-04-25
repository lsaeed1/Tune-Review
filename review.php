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

// Resolve a reliable songs.id for reviews by matching title/artist first,
// then falling back to creating the song if it does not exist yet.
function resolve_song_id($link, $incoming_song_id, $song_title, $artist)
{
    $song_title = trim($song_title);
    $artist = trim($artist);

    if ($song_title === '') {
        return 0;
    }

    // 1) Prefer exact title/artist match so IDs stay consistent with song metadata.
    try {
        $sql = "SELECT id FROM songs WHERE title = ? AND artist = ? LIMIT 1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $song_title, $artist);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                if ($row && isset($row['id'])) {
                    return (int)$row['id'];
                }
            } else {
                mysqli_stmt_close($stmt);
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Continue to fallback options.
    }

    // 2) If caller passed a valid existing ID, allow that.
    if ($incoming_song_id > 0) {
        try {
            $sql = "SELECT id FROM songs WHERE id = ? LIMIT 1";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $incoming_song_id);
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    $row = mysqli_fetch_assoc($result);
                    mysqli_stmt_close($stmt);
                    if ($row && isset($row['id'])) {
                        return (int)$row['id'];
                    }
                } else {
                    mysqli_stmt_close($stmt);
                }
            }
        } catch (mysqli_sql_exception $e) {
            // Continue to create fallback.
        }
    }

    // 3) Create the song row (schema-safe fallbacks).
    try {
        $sql = "INSERT INTO songs (title, artist, cover_image_url) VALUES (?, ?, '')";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $song_title, $artist);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return (int)mysqli_insert_id($link);
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        // Continue to next fallback.
    }

    try {
        $sql = "INSERT INTO songs (title, artist, cover_url) VALUES (?, ?, '')";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $song_title, $artist);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return (int)mysqli_insert_id($link);
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        // Continue to next fallback.
    }

    try {
        $sql = "INSERT INTO songs (title, artist) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $song_title, $artist);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return (int)mysqli_insert_id($link);
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $e) {
        // Fall through.
    }

    return 0;
}

// Get song details passed from homepage (if any)
$song_id    = isset($_GET['song_id'])    ? (int)$_GET['song_id']              : 0;
$song_title = isset($_GET['song_title']) ? trim($_GET['song_title'])           : '';
$artist     = isset($_GET['artist'])     ? trim($_GET['artist'])               : '';

// Define variables and initialize with empty values
$feedback = "";
$feedback_err = $rating_err = "";
$rating = 0;
$selected_song_id = $song_id;
$success = false;

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $song_title = isset($_POST['song_title']) ? trim($_POST['song_title']) : $song_title;
    $artist = isset($_POST['artist']) ? trim($_POST['artist']) : $artist;

    // Normalize incoming song reference to a reliable songs.id.
    $incoming_song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : 0;
    $selected_song_id = resolve_song_id($link, $incoming_song_id, $song_title, $artist);
    if ($selected_song_id <= 0 || $song_title === '') {
        header("location: addSong.php");
        exit;
    }

    // Validate rating
    $rating = (int)$_POST['rating'];
    if ($rating < 1 || $rating > 5) {
        $rating_err = "Please select a rating.";
    }

    // Validate feedback
    if (empty(trim($_POST["feedback"]))) {
        $feedback_err = "Please write your feedback.";
    } else {
        $feedback = trim($_POST["feedback"]);
    }

    // If no errors, save to database
    if (empty($rating_err) && empty($feedback_err)) {

        $sql = "INSERT INTO reviews (song_id, user_id, rating, feedback) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iiis", $param_song_id, $param_user_id, $param_rating, $param_feedback);
            $param_song_id  = $selected_song_id;
            $param_user_id  = $_SESSION["id"];
            $param_rating   = $rating;
            $param_feedback = $feedback;

            if (mysqli_stmt_execute($stmt)) {
                $success = true;
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
} else {
    // Direct access without song metadata should go to the add-song flow.
    if ($song_title === '') {
        header("location: addSong.php");
        exit;
    }

    // Normalize to a valid songs.id for any review entered from this page.
    $selected_song_id = resolve_song_id($link, $selected_song_id, $song_title, $artist);
    if ($selected_song_id <= 0) {
        header("location: addSong.php");
        exit;
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TuneReview - Write a Review</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Navbar -->
    <div class="navbar">
        <a href="index.php" class="logo">
            <img src="tune_review_logo_2.png" alt="Tune Review Logo">
        </a>
        <div>
            <a href="myReviews.php" class="btn-nav nav-my-reviews">My Reviews</a>
            <a href="logout.php" class="nav-logout">Logout</a>
        </div>
    </div>

    <div class="wrapper">

        <?php if ($success): ?>

            <!-- Success message -->
            <div class="success-box">
                <div class="icon">🎵</div>
                <h3>Review Submitted!</h3>
                <p>Thank you! Your review has been submitted successfully.</p>
                <a href="myReviews.php" class="btn-primary" style="display:inline-block; width:auto; padding: 10px 24px; text-decoration:none;">Go to My Reviews</a>
            </div>
        <?php else: ?>

            <h2>Review Submission Form</h2>

            <form action="review.php" method="post">

                <div class="song-tag">
                    <span>🎵 <?php echo htmlspecialchars($song_title); ?> — <?php echo htmlspecialchars($artist); ?></span>
                    <a href="addSong.php">Change</a>
                </div>
                <input type="hidden" name="song_id" value="<?php echo $selected_song_id; ?>">
                <input type="hidden" name="song_title" value="<?php echo htmlspecialchars($song_title); ?>">
                <input type="hidden" name="artist" value="<?php echo htmlspecialchars($artist); ?>">

                <label>Overall Rating</label>
                <div class="stars-wrap">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="star-btn <?php echo ($rating >= $i) ? 'filled' : ''; ?>" data-val="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)">★</button>
                    <?php endfor; ?>
                    <span class="rating-count" id="ratingCount">
                        <?php echo $rating > 0 ? "($rating/5 stars)" : "(click to rate)"; ?>
                    </span>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="<?php echo $rating; ?>">
                <span class="help-block"><?php echo $rating_err; ?></span>

                <label>Your Feedback</label>
                <textarea name="feedback" placeholder="Please type your detailed comments here..."><?php echo $feedback; ?></textarea>
                <span class="help-block"><?php echo $feedback_err; ?></span>

                <input type="submit" class="btn-primary" value="Submit Review">

            </form>

        <?php endif; ?>

    </div>

    <script>
        function setRating(val) {
            document.getElementById('ratingInput').value = val;
            document.getElementById('ratingCount').textContent = '(' + val + '/5 stars)';
            document.querySelectorAll('.star-btn').forEach(function(btn, i) {
                btn.classList.toggle('filled', i < val);
            });
        }
    </script>

</body>

</html>