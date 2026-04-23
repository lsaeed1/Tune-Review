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

// ==========================================
// CAPTURE SONG ID FROM URL
// ==========================================
$song_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid ID provided, redirect home
if($song_id <= 0){
    header("location: index.php");
    exit;
}

// ==========================================
// QUERY 1: FETCH SONG DETAILS
// ==========================================
$song = null;
$sql_song = "SELECT id, title, artist, genre, cover_image_url FROM songs WHERE id = ?";

if($stmt = mysqli_prepare($link, $sql_song)){
    mysqli_stmt_bind_param($stmt, "i", $song_id);

    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $song = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

// If song not found, redirect home
if(!$song){
    header("location: index.php");
    exit;
}

// ==========================================
// QUERY 2: FETCH ALL REVIEWS FOR THIS SONG
// ==========================================
$reviews = [];
$avg_rating = 0;
$total_reviews = 0;

$sql_reviews = "
    SELECT r.rating, r.feedback, r.created_at, u.username
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.song_id = ?
    ORDER BY r.created_at DESC
";

if($stmt = mysqli_prepare($link, $sql_reviews)){
    mysqli_stmt_bind_param($stmt, "i", $song_id);

    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $reviews[] = $row;
        }
        $total_reviews = count($reviews);
        if($total_reviews > 0){
            $sum = array_sum(array_column($reviews, 'rating'));
            $avg_rating = round($sum / $total_reviews, 1);
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TuneReview — <?php echo htmlspecialchars($song['title']); ?></title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ===== SONG HERO ===== */
        .song-hero {
            background: #1a1030;
            padding: 48px 40px;
            display: flex;
            align-items: center;
            gap: 36px;
        }

        .song-hero-cover {
            width: 180px;
            height: 180px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 8px 30px rgba(0,0,0,0.5);
            flex-shrink: 0;
        }

        .song-hero-cover-placeholder {
            width: 180px;
            height: 180px;
            border-radius: 12px;
            background: #2e2050;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            flex-shrink: 0;
        }

        .song-hero-info {
            color: white;
        }

        .song-hero-genre {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #9b7fd4;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .song-hero-title {
            font-size: 32px;
            font-weight: bold;
            margin: 0 0 6px;
            line-height: 1.2;
        }

        .song-hero-artist {
            font-size: 16px;
            color: rgba(255,255,255,0.65);
            margin-bottom: 20px;
        }

        .song-hero-stats {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-stars {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-star {
            font-size: 20px;
            color: #ccc;
        }

        .stat-star.filled {
            color: #f5c518;
        }

        .stat-score {
            font-size: 22px;
            font-weight: bold;
            color: white;
            margin-left: 6px;
        }

        .stat-count {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
        }

        .song-hero-actions {
            margin-top: 22px;
        }

        .btn-review-this {
            background: #7a5fbb;
            color: white;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-review-this:hover {
            background: #6a4faa;
            color: white;
        }

        /* ===== REVIEWS SECTION ===== */
        .reviews-section {
            max-width: 820px;
            margin: 40px auto;
            padding: 0 40px;
        }

        .reviews-section h2 {
            font-size: 20px;
            color: #1a1a2e;
            margin-bottom: 24px;
            border-left: 4px solid #7a5fbb;
            padding-left: 12px;
        }

        /* ===== REVIEW CARD ===== */
        .review-card {
            background: white;
            border-radius: 14px;
            padding: 22px 26px;
            margin-bottom: 18px;
            box-shadow: 0 4px 16px rgba(60, 40, 100, 0.1);
        }

        .review-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .review-username {
            font-weight: bold;
            color: #1a1a2e;
            font-size: 14px;
        }

        .review-date {
            font-size: 12px;
            color: #aaa;
            margin-top: 2px;
        }

        .review-stars {
            display: flex;
            gap: 2px;
        }

        .review-star {
            font-size: 16px;
            color: #ddd;
        }

        .review-star.filled {
            color: #f5c518;
        }

        .review-feedback {
            font-size: 14px;
            color: #444;
            line-height: 1.65;
        }

        /* ===== EMPTY STATE ===== */
        .no-reviews {
            background: white;
            border-radius: 14px;
            padding: 48px 26px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(60, 40, 100, 0.1);
        }

        .no-reviews .icon {
            font-size: 42px;
            margin-bottom: 12px;
        }

        .no-reviews p {
            color: #888;
            font-size: 14px;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <a href="index.php" class="logo">
        <img src="tune_review_logo_2.png" alt="Tune Review Logo" style="height:60px; vertical-align:middle;">
    </a>
    <div>
        <a href="index.php">Home</a>
        <a href="index.php#songs">Albums</a>
        <a href="addSong.php" class="btn-nav" style="background: #9b7fd4;">Add a Song</a>
        <a href="review.php" class="btn-nav">Write a Review</a>
        <a href="logout.php" style="color:#e74c3c;">Logout</a>
    </div>
</div>

<!-- Song Hero -->
<div class="song-hero">
    <?php if(!empty($song['cover_image_url'])): ?>
        <img src="<?php echo htmlspecialchars($song['cover_image_url']); ?>"
             alt="<?php echo htmlspecialchars($song['title']); ?>"
             class="song-hero-cover">
    <?php else: ?>
        <div class="song-hero-cover-placeholder">🎵</div>
    <?php endif; ?>

    <div class="song-hero-info">
        <div class="song-hero-genre"><?php echo htmlspecialchars($song['genre']); ?></div>
        <h1 class="song-hero-title"><?php echo htmlspecialchars($song['title']); ?></h1>
        <div class="song-hero-artist"><?php echo htmlspecialchars($song['artist']); ?></div>

        <!-- Average rating display -->
        <div class="song-hero-stats">
            <div class="stat-stars">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <span class="stat-star <?php echo ($avg_rating >= $i) ? 'filled' : ''; ?>">★</span>
                <?php endfor; ?>
                <span class="stat-score"><?php echo $total_reviews > 0 ? $avg_rating : '—'; ?></span>
            </div>
            <span class="stat-count">
                <?php echo $total_reviews; ?> <?php echo $total_reviews === 1 ? 'review' : 'reviews'; ?>
            </span>
        </div>

        <div class="song-hero-actions">
            <a href="review.php?song_id=<?php echo $song['id']; ?>&song_title=<?php echo urlencode($song['title']); ?>&artist=<?php echo urlencode($song['artist']); ?>"
               class="btn-review-this">✍️ Write a Review</a>
        </div>
    </div>
</div>

<!-- Community Reviews -->
<div class="reviews-section">
    <h2>Community Reviews</h2>

    <?php if($total_reviews > 0): ?>
        <?php foreach($reviews as $review): ?>
        <div class="review-card">
            <div class="review-card-header">
                <div>
                    <div class="review-username">@<?php echo htmlspecialchars($review['username']); ?></div>
                    <div class="review-date">
                        <?php echo isset($review['created_at'])
                            ? date("F j, Y", strtotime($review['created_at']))
                            : ''; ?>
                    </div>
                </div>
                <div class="review-stars">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <span class="review-star <?php echo ($review['rating'] >= $i) ? 'filled' : ''; ?>">★</span>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="review-feedback">
                <?php echo nl2br(htmlspecialchars($review['feedback'])); ?>
            </div>
        </div>
        <?php endforeach; ?>

    <?php else: ?>
        <div class="no-reviews">
            <div class="icon">🎧</div>
            <p>No reviews yet. Be the first to share your thoughts!</p>
            <a href="review.php?song_id=<?php echo $song['id']; ?>&song_title=<?php echo urlencode($song['title']); ?>&artist=<?php echo urlencode($song['artist']); ?>"
               class="btn-primary" style="display:inline-block; width:auto; padding:10px 24px; text-decoration:none;">
                Write the First Review
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer>
    &copy; 2024 <span>TuneReview</span> — All rights reserved.
</footer>

</body>
</html>