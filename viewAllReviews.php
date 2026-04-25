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

$search_song = isset($_GET['song']) ? trim($_GET['song']) : "";

// Same curated homepage catalog (id -> title/artist) used as primary review lookup.
$catalog_songs = [
    ["id" => 1,  "title" => "Negro Swan", "artist" => "Blood Orange"],
    ["id" => 2,  "title" => "Freetown Sound", "artist" => "Blood Orange"],
    ["id" => 3,  "title" => "Cupid Deluxe", "artist" => "Blood Orange"],
    ["id" => 4,  "title" => "White Pony", "artist" => "Deftones"],
    ["id" => 5,  "title" => "Around the Fur", "artist" => "Deftones"],
    ["id" => 6,  "title" => "Diamond Eyes", "artist" => "Deftones"],
    ["id" => 7,  "title" => "Tyla", "artist" => "Tyla"],
    ["id" => 8,  "title" => "Water", "artist" => "Tyla"],
    ["id" => 9,  "title" => "Jump", "artist" => "Tyla"],
    ["id" => 10, "title" => "Wasteland", "artist" => "Brent Faiyaz"],
    ["id" => 11, "title" => "Sonder Son", "artist" => "Brent Faiyaz"],
    ["id" => 12, "title" => "Lost", "artist" => "Brent Faiyaz"],
    ["id" => 13, "title" => "GNX", "artist" => "Kendrick Lamar"],
    ["id" => 14, "title" => "Mr. Morale", "artist" => "Kendrick Lamar"],
    ["id" => 15, "title" => "To Pimp a Butterfly", "artist" => "Kendrick Lamar"],
    ["id" => 16, "title" => "Cowboy Carter", "artist" => "Beyonce"],
    ["id" => 17, "title" => "Renaissance", "artist" => "Beyonce"],
    ["id" => 18, "title" => "Lemonade", "artist" => "Beyonce"]
];

$catalog_lookup = [];
foreach ($catalog_songs as $song) {
    $catalog_lookup[(int)$song['id']] = [
        'title' => $song['title'],
        'artist' => $song['artist']
    ];
}

// Build a song lookup using flexible column mapping so schema differences do not break this page.
$db_song_lookup = [];
$sql_songs = "SELECT * FROM songs";
if ($result = mysqli_query($link, $sql_songs)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        if ($id <= 0) {
            continue;
        }

        $db_song_lookup[$id] = [
            'title' => isset($row['title']) ? $row['title'] : (isset($row['song_title']) ? $row['song_title'] : 'Unknown Title'),
            'artist' => isset($row['artist']) ? $row['artist'] : (isset($row['artist_name']) ? $row['artist_name'] : 'Unknown Artist')
        ];
    }
}

$all_reviews = [];
$visible_reviews = [];

// Fetch all reviews across all users.
$sql_reviews = "
    SELECT r.id, r.song_id, r.rating, r.feedback, u.username
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.id DESC
";

if ($stmt = mysqli_prepare($link, $sql_reviews)) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $song_id = isset($row['song_id']) ? (int)$row['song_id'] : 0;

            if (isset($catalog_lookup[$song_id])) {
                $song_title = $catalog_lookup[$song_id]['title'];
                $song_artist = $catalog_lookup[$song_id]['artist'];
            } elseif (isset($db_song_lookup[$song_id])) {
                $song_title = $db_song_lookup[$song_id]['title'];
                $song_artist = $db_song_lookup[$song_id]['artist'];
            } else {
                $song_title = 'Unknown Title';
                $song_artist = 'Unknown Artist';
            }

            $row['song_title'] = $song_title;
            $row['song_artist'] = $song_artist;
            $all_reviews[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

if ($search_song === "") {
    $visible_reviews = $all_reviews;
} else {
    $needle = mb_strtolower($search_song, 'UTF-8');
    foreach ($all_reviews as $review) {
        $haystack_title = mb_strtolower($review['song_title'], 'UTF-8');
        if (strpos($haystack_title, $needle) !== false) {
            $visible_reviews[] = $review;
        }
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TuneReview - All Reviews</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="navbar">
        <a href="index.php" class="logo">
            <img src="tune_review_logo_2.png" alt="Tune Review Logo">
        </a>
        <div>
            <a href="myReviews.php" class="btn-nav nav-my-reviews">My Reviews</a>
            <a href="logout.php" class="nav-logout">Logout</a>
        </div>
    </div>

    <div class="section" style="max-width:900px; margin:0 auto; padding-top:40px;">
        <h2>All Community Reviews</h2>
        <p>Search by song title to see what everyone on the platform has said.</p>

        <form action="viewAllReviews.php" method="get" style="display:flex; gap:10px; margin:20px 0 26px;">
            <input type="text" name="song" placeholder="Search for a song title..." value="<?php echo htmlspecialchars($search_song); ?>" style="flex:1; margin:0;">
            <button type="submit" class="btn-primary" style="width:auto; padding:10px 20px; margin:0;">Search</button>
            <?php if ($search_song !== ""): ?>
                <a href="viewAllReviews.php" class="btn-default" style="width:auto; padding:10px 18px; margin:0; text-decoration:none; display:inline-block;">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($search_song !== "" && empty($visible_reviews)): ?>
            <div class="alert-danger" style="margin-top:10px;">
                No one has reviewed "<?php echo htmlspecialchars($search_song); ?>" yet.
            </div>
        <?php endif; ?>

        <?php if (empty($visible_reviews) && $search_song === ""): ?>
            <div style="text-align:center; padding:40px; background:white; border-radius:12px; box-shadow: 0 10px 40px rgba(60, 40, 100, 0.2);">
                <h3 style="color:#1a1a2e;">No reviews have been posted yet.</h3>
                <a href="addSong.php" class="btn-primary" style="display:inline-block; width:auto; padding:10px 24px; margin-top:10px; text-decoration:none;">Write the First Review</a>
            </div>
        <?php else: ?>

            <?php foreach ($visible_reviews as $review): ?>
                <div style="background:white; padding:20px; border-radius:12px; margin-top:16px; box-shadow:0 4px 10px rgba(60, 40, 100, 0.1);">
                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                        <div>
                            <h3 style="margin:0 0 4px; font-size:18px; color:#1a1a2e;"><?php echo htmlspecialchars($review['song_title']); ?></h3>
                            <p style="margin:0 0 8px; color:#666; font-size:14px;"><?php echo htmlspecialchars($review['song_artist']); ?></p>
                            <p style="margin:0; font-size:13px; color:#7a5fbb;">Reviewed by @<?php echo htmlspecialchars($review['username']); ?></p>
                        </div>
                        <div style="color:#1a1a2e; font-size:18px; white-space:nowrap;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo ($i <= (int)$review['rating']) ? '★' : '<span style="color:#ccc;">★</span>'; ?>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <p style="margin:12px 0 0; font-size:14px; line-height:1.55; color:#333;"><?php echo nl2br(htmlspecialchars($review['feedback'])); ?></p>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

</body>

</html>