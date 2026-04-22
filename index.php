<?php
// Initialize the session
session_start();

// If not logged in, redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Song list
$songs = [
    // Alternative - Blood Orange
    ["id" => 1,  "title" => "Negro Swan",         "artist" => "Blood Orange",  "genre" => "Alternative", "img" => "https://upload.wikimedia.org/wikipedia/en/thumb/d/d0/Negro_Swan.jpg/250px-Negro_Swan.jpg"],
    ["id" => 2,  "title" => "Freetown Sound",     "artist" => "Blood Orange",  "genre" => "Alternative", "img" => "https://upload.wikimedia.org/wikipedia/en/2/2d/Freetown_Sound_Cover.jpg"],
    ["id" => 3,  "title" => "Cupid Deluxe",       "artist" => "Blood Orange",  "genre" => "Alternative", "img" => "https://upload.wikimedia.org/wikipedia/en/5/5d/Blood_Orange_Cupid_Deluxe_album_cover.jpg"],

    // Metal - Deftones
    ["id" => 4,  "title" => "White Pony",         "artist" => "Deftones",      "genre" => "Metal",       "img" => "https://upload.wikimedia.org/wikipedia/en/1/16/Deftones_-_White_Pony-greycoverart.jpg"],
    ["id" => 5,  "title" => "Around the Fur",     "artist" => "Deftones",      "genre" => "Metal",       "img" => "https://upload.wikimedia.org/wikipedia/en/thumb/2/21/Deftones_-_Around_the_Fur.jpg/250px-Deftones_-_Around_the_Fur.jpg"],
    ["id" => 6,  "title" => "Diamond Eyes",       "artist" => "Deftones",      "genre" => "Metal",       "img" => "https://upload.wikimedia.org/wikipedia/en/8/8b/Deftones_-_Diamond_Eyes.jpg"],
    // Popiano - Tyla
    ["id" => 7,  "title" => "Tyla",               "artist" => "Tyla",          "genre" => "Popiano",     "img" => "https://upload.wikimedia.org/wikipedia/en/1/1e/Tyla_album.jpg"],
    ["id" => 8,  "title" => "Water",              "artist" => "Tyla",          "genre" => "Popiano",     "img" => "https://i.ebayimg.com/images/g/jGcAAOSwp6JoG9r7/s-l400.jpg"],
    ["id" => 9,  "title" => "Jump",               "artist" => "Tyla",          "genre" => "Popiano",     "img" => "https://is1-ssl.mzstatic.com/image/thumb/Music126/v4/1e/c9/5b/1ec95bbc-a56f-cbef-070c-ddec9f4fdd3c/196871296205.jpg/1200x630bb.jpg"],

    // R&B - Brent Faiyaz
    ["id" => 10, "title" => "Wasteland",          "artist" => "Brent Faiyaz",  "genre" => "R&B",         "img" => "https://upload.wikimedia.org/wikipedia/en/5/51/Brent_Faiyaz_-_Wasteland.jpeg"],
    ["id" => 11, "title" => "Sonder Son",         "artist" => "Brent Faiyaz",  "genre" => "R&B",         "img" => "https://upload.wikimedia.org/wikipedia/en/1/1f/Brent_Faiyaz_-_Sonder_Son.jpeg"],
    ["id" => 12, "title" => "Lost",               "artist" => "Brent Faiyaz",  "genre" => "R&B",         "img" => "https://media.pitchfork.com/photos/5be0ab5a9b412c347e38741b/master/pass/brent.jpg"],

    // Hip-Hop - Kendrick Lamar
    ["id" => 13, "title" => "GNX",               "artist" => "Kendrick Lamar", "genre" => "Hip-Hop",     "img" => "https://upload.wikimedia.org/wikipedia/en/9/93/Kendrick_Lamar_-_GNX.png"],
    ["id" => 14, "title" => "Mr. Morale",         "artist" => "Kendrick Lamar", "genre" => "Hip-Hop",     "img" => "https://upload.wikimedia.org/wikipedia/en/e/e1/Kendrick_Lamar_-_Mr._Morale_%26_the_Big_Steppers.png"],
    ["id" => 15, "title" => "To Pimp a Butterfly", "artist" => "Kendrick Lamar", "genre" => "Hip-Hop",     "img" => "https://upload.wikimedia.org/wikipedia/en/f/f6/Kendrick_Lamar_-_To_Pimp_a_Butterfly.png"],

    // Pop - Beyonce
    ["id" => 16, "title" => "Cowboy Carter",      "artist" => "Beyonce",        "genre" => "Pop",         "img" => "https://upload.wikimedia.org/wikipedia/en/a/aa/Beyonc%C3%A9_-_Cowboy_Carter.png"],
    ["id" => 17, "title" => "Renaissance",        "artist" => "Beyonce",        "genre" => "Pop",         "img" => "https://upload.wikimedia.org/wikipedia/en/a/ad/Beyonc%C3%A9_-_Renaissance.png"],
    ["id" => 18, "title" => "Lemonade",           "artist" => "Beyonce",        "genre" => "Pop",         "img" => "https://upload.wikimedia.org/wikipedia/en/thumb/5/53/Beyonce_-_Lemonade_%28Official_Album_Cover%29.png/250px-Beyonce_-_Lemonade_%28Official_Album_Cover%29.png"],
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TuneReview - Home</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Navbar -->
    <div class="navbar">
        <a href="index.php" class="logo">TUNE REVIEW</a>
        <div>
            <a href="index.php">Home</a>

            <a href="myReviews.php" class="btn-nav" style="background:#9b7fd4;">My Reviews</a>

            <a href="addSong.php" class="btn-nav">Write a Review</a>
            <a href="logout.php" style="color:#e74c3c;">Logout</a>
        </div>
    </div>

    <!-- Hero section -->
    <div class="hero">
        <h1>Homepage</h1>
        <p>Discover, rate, and discuss your favorite music with expert and community reviews.</p>
        <div class="hero-btns">
            <a href="#songs" class="btn-hero">Explore Now</a>
            <a href="#songs" class="btn-hero-outline">Browse Songs</a>
        </div>
    </div>

    <!-- Song Browsing -->
    <div class="section" id="songs">
        <div class="section-header">
            <h2>Song Browsing</h2>
            <a href="#">View All</a>
        </div>

        <?php
        // Group songs by genre
        $genres = [];
        foreach ($songs as $song) {
            $genres[$song['genre']][] = $song;
        }

        // Display each genre row
        foreach ($genres as $genre => $genre_songs):
        ?>
            <div class="genre-row">
                <h3 class="genre-label"><?php echo $genre; ?></h3>
                <div class="song-grid">
                    <?php foreach ($genre_songs as $song): ?>
                        <a href="review.php?song_id=<?php echo $song['id']; ?>&song_title=<?php echo urlencode($song['title']); ?>&artist=<?php echo urlencode($song['artist']); ?>" class="song-card">
                            <img src="<?php echo $song['img']; ?>" alt="<?php echo $song['title']; ?>">
                            <div class="song-card-overlay"></div>
                            <div class="song-card-info">
                                <div class="song-card-genre"><?php echo $song['genre']; ?></div>
                                <div class="song-card-title"><?php echo $song['title']; ?></div>
                                <div class="song-card-artist"><?php echo $song['artist']; ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

    <!-- Write a Review banner -->
    <div class="cta-banner">
        <div>
            <h3>Share Your Thoughts on Music</h3>
            <p>Write a review for any song and help the community discover great music.</p>
        </div>
        <a href="addSong.php" class="btn-cta">Write a Review</a>
    </div>

    <!-- Footer -->
    <footer>
        &copy; 2024 <span>TuneReview</span> — All rights reserved.
    </footer>

</body>

</html>