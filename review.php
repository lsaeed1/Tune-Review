<?php
// Initialize the session
session_start();
 
// If not logged in, redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
 
// Include config file
require_once "config.php";
 
// Get song details passed from homepage (if any)
$song_id    = isset($_GET['song_id'])    ? (int)$_GET['song_id']              : 0;
$song_title = isset($_GET['song_title']) ? trim($_GET['song_title'])           : '';
$artist     = isset($_GET['artist'])     ? trim($_GET['artist'])               : '';
 
// All available songs for the dropdown
$songs = [
    // Alternative - Blood Orange
    ["id"=>1,  "title"=>"Negro Swan",          "artist"=>"Blood Orange"],
    ["id"=>2,  "title"=>"Freetown Sound",      "artist"=>"Blood Orange"],
    ["id"=>3,  "title"=>"Cupid Deluxe",        "artist"=>"Blood Orange"],
    // Metal - Deftones
    ["id"=>4,  "title"=>"White Pony",          "artist"=>"Deftones"],
    ["id"=>5,  "title"=>"Around the Fur",      "artist"=>"Deftones"],
    ["id"=>6,  "title"=>"Diamond Eyes",        "artist"=>"Deftones"],
    // Popiano - Tyla
    ["id"=>7,  "title"=>"Tyla",                "artist"=>"Tyla"],
    ["id"=>8,  "title"=>"Water",               "artist"=>"Tyla"],
    ["id"=>9,  "title"=>"Jump",                "artist"=>"Tyla"],
    // R&B - Brent Faiyaz
    ["id"=>10, "title"=>"Wasteland",           "artist"=>"Brent Faiyaz"],
    ["id"=>11, "title"=>"Sonder Son",          "artist"=>"Brent Faiyaz"],
    ["id"=>12, "title"=>"Lost",                "artist"=>"Brent Faiyaz"],
    // Hip-Hop - Kendrick Lamar
    ["id"=>13, "title"=>"GNX",                "artist"=>"Kendrick Lamar"],
    ["id"=>14, "title"=>"Mr. Morale",          "artist"=>"Kendrick Lamar"],
    ["id"=>15, "title"=>"To Pimp a Butterfly", "artist"=>"Kendrick Lamar"],
    // Pop - Beyonce
    ["id"=>16, "title"=>"Cowboy Carter",       "artist"=>"Beyonce"],
    ["id"=>17, "title"=>"Renaissance",         "artist"=>"Beyonce"],
    ["id"=>18, "title"=>"Lemonade",            "artist"=>"Beyonce"],
];
 
// Define variables and initialize with empty values
$feedback = "";
$feedback_err = $song_err = $rating_err = "";
$rating = 0;
$selected_song_id = $song_id;
$success = false;
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Get selected song
    $selected_song_id = (int)$_POST['song_id'];
    if($selected_song_id == 0){
        $song_err = "Please select a song to review.";
    }
 
    // Validate rating
    $rating = (int)$_POST['rating'];
    if($rating < 1 || $rating > 5){
        $rating_err = "Please select a rating.";
    }
 
    // Validate feedback
    if(empty(trim($_POST["feedback"]))){
        $feedback_err = "Please write your feedback.";
    } else{
        $feedback = trim($_POST["feedback"]);
    }
 
    // If no errors, save to database
    if(empty($song_err) && empty($rating_err) && empty($feedback_err)){
 
        $sql = "INSERT INTO reviews (song_id, user_id, rating, feedback) VALUES (?, ?, ?, ?)";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "iiis", $param_song_id, $param_user_id, $param_rating, $param_feedback);
            $param_song_id  = $selected_song_id;
            $param_user_id  = $_SESSION["id"];
            $param_rating   = $rating;
            $param_feedback = $feedback;
 
            if(mysqli_stmt_execute($stmt)){
                $success = true;
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
 
            mysqli_stmt_close($stmt);
        }
    }
 
    mysqli_close($link);
}
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
    <a href="index.php" class="logo">TUNE REVIEW</a>
    <div>
        <a href="index.php">Home</a>
        <a href="index.php#songs">Albums</a>
        <a href="addSong.php" class="btn-nav" style="background: #9b7fd4;">Add a Song</a>
        <a href="review.php" class="btn-nav">Write a Review</a>
        <a href="logout.php" style="color:#e74c3c;">Logout</a>
    </div>
</div>
 
<div class="wrapper">
 
<?php if($success): ?>
 
    <!-- Success message -->
    <div class="success-box">
        <div class="icon">🎵</div>
        <h3>Review Submitted!</h3>
        <p>Thank you! Your review has been submitted successfully.</p>
        <a href="index.php" class="btn-primary" style="display:inline-block; width:auto; padding: 10px 24px; text-decoration:none;">Back to Homepage</a>
    </div>
 
<?php else: ?>
 
    <h2>Review Submission Form</h2>
 
    <form action="review.php" method="post">
 
        <!-- Show pre-selected song (from homepage click) or show dropdown -->
        <?php if($song_id > 0 && $song_title != ''): ?>
            <div class="song-tag">
                <span>🎵 <?php echo htmlspecialchars($song_title); ?> — <?php echo htmlspecialchars($artist); ?></span>
                <a href="review.php">Change</a>
            </div>
            <input type="hidden" name="song_id" value="<?php echo $song_id; ?>">
 
        <?php else: ?>
            <label>Select a Song</label>
            <select name="song_id">
                <option value="0">-- Choose a song --</option>
                <?php foreach($songs as $s): ?>
                <option value="<?php echo $s['id']; ?>" <?php echo ($selected_song_id == $s['id']) ? 'selected' : ''; ?>>
                    <?php echo $s['title']; ?> — <?php echo $s['artist']; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <span class="help-block"><?php echo $song_err; ?></span>
        <?php endif; ?>
 
        <label>Overall Rating</label>
        <div class="stars-wrap">
            <?php for($i = 1; $i <= 5; $i++): ?>
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
 
<!-- Footer -->
<footer>
    &copy; 2024 <span>TuneReview</span> — All rights reserved.
</footer>
 
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