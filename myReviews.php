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

$delete_success = "";
$delete_error = "";
$edit_success = "";
$edit_error = "";
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;

// Handle review deletion (only for the logged-in user's own reviews)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_review'])) {
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;

    if ($review_id > 0) {
        $delete_sql = "DELETE FROM reviews WHERE id = ? AND user_id = ?";
        if ($delete_stmt = mysqli_prepare($link, $delete_sql)) {
            mysqli_stmt_bind_param($delete_stmt, "ii", $param_review_id, $param_user_id);
            $param_review_id = $review_id;
            $param_user_id = $_SESSION["id"];

            if (mysqli_stmt_execute($delete_stmt)) {
                if (mysqli_stmt_affected_rows($delete_stmt) > 0) {
                    $delete_success = "Review deleted successfully.";
                } else {
                    $delete_error = "Review could not be deleted.";
                }
            } else {
                $delete_error = "Something went wrong deleting the review.";
            }
            mysqli_stmt_close($delete_stmt);
        }
    } else {
        $delete_error = "Invalid review selected.";
    }
}

// Handle review update (only for the logged-in user's own reviews)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_review'])) {
    $edit_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    $new_rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $new_feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : "";

    if ($edit_id <= 0) {
        $edit_error = "Invalid review selected.";
    } elseif ($new_rating < 1 || $new_rating > 5) {
        $edit_error = "Please select a rating between 1 and 5.";
    } elseif ($new_feedback === "") {
        $edit_error = "Feedback cannot be empty.";
    } else {
        $update_sql = "UPDATE reviews SET rating = ?, feedback = ? WHERE id = ? AND user_id = ?";
        if ($update_stmt = mysqli_prepare($link, $update_sql)) {
            mysqli_stmt_bind_param($update_stmt, "isii", $param_rating, $param_feedback, $param_review_id, $param_user_id);
            $param_rating = $new_rating;
            $param_feedback = $new_feedback;
            $param_review_id = $edit_id;
            $param_user_id = $_SESSION["id"];

            if (mysqli_stmt_execute($update_stmt)) {
                if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                    $edit_success = "Review updated successfully.";
                    $edit_id = 0;
                } else {
                    // Could be unchanged values or invalid ownership/id.
                    $exists_sql = "SELECT id FROM reviews WHERE id = ? AND user_id = ?";
                    if ($exists_stmt = mysqli_prepare($link, $exists_sql)) {
                        mysqli_stmt_bind_param($exists_stmt, "ii", $param_review_id_check, $param_user_id_check);
                        $param_review_id_check = $param_review_id;
                        $param_user_id_check = $param_user_id;
                        mysqli_stmt_execute($exists_stmt);
                        mysqli_stmt_store_result($exists_stmt);
                        if (mysqli_stmt_num_rows($exists_stmt) > 0) {
                            $edit_success = "No changes were made.";
                            $edit_id = 0;
                        } else {
                            $edit_error = "Review could not be updated.";
                        }
                        mysqli_stmt_close($exists_stmt);
                    } else {
                        $edit_error = "Review could not be updated.";
                    }
                }
            } else {
                $edit_error = "Something went wrong updating the review.";
            }
            mysqli_stmt_close($update_stmt);
        }
    }
}

// 1. The Hardcoded Song Library (needed to look up images/titles)
$songs = [
    ["id" => 1,  "title" => "Negro Swan",         "artist" => "Blood Orange",  "img" => "https://upload.wikimedia.org/wikipedia/en/thumb/d/d0/Negro_Swan.jpg/250px-Negro_Swan.jpg"],
    ["id" => 2,  "title" => "Freetown Sound",     "artist" => "Blood Orange",  "img" => "https://upload.wikimedia.org/wikipedia/en/2/2d/Freetown_Sound_Cover.jpg"],
    ["id" => 3,  "title" => "Cupid Deluxe",       "artist" => "Blood Orange",  "img" => "https://upload.wikimedia.org/wikipedia/en/5/5d/Blood_Orange_Cupid_Deluxe_album_cover.jpg"],
    ["id" => 4,  "title" => "White Pony",         "artist" => "Deftones",      "img" => "https://upload.wikimedia.org/wikipedia/en/1/16/Deftones_-_White_Pony-greycoverart.jpg"],
    ["id" => 5,  "title" => "Around the Fur",     "artist" => "Deftones",      "img" => "https://upload.wikimedia.org/wikipedia/en/thumb/2/21/Deftones_-_Around_the_Fur.jpg/250px-Deftones_-_Around_the_Fur.jpg"],
    ["id" => 6,  "title" => "Diamond Eyes",       "artist" => "Deftones",      "img" => "https://upload.wikimedia.org/wikipedia/en/8/8b/Deftones_-_Diamond_Eyes.jpg"],
    ["id" => 7,  "title" => "Tyla",               "artist" => "Tyla",          "img" => "https://upload.wikimedia.org/wikipedia/en/1/1e/Tyla_album.jpg"],
    ["id" => 8,  "title" => "Water",              "artist" => "Tyla",          "img" => "https://i.ebayimg.com/images/g/jGcAAOSwp6JoG9r7/s-l400.jpg"],
    ["id" => 9,  "title" => "Jump",               "artist" => "Tyla",          "img" => "https://is1-ssl.mzstatic.com/image/thumb/Music126/v4/1e/c9/5b/1ec95bbc-a56f-cbef-070c-ddec9f4fdd3c/196871296205.jpg/1200x630bb.jpg"],
    ["id" => 10, "title" => "Wasteland",          "artist" => "Brent Faiyaz",  "img" => "https://upload.wikimedia.org/wikipedia/en/5/51/Brent_Faiyaz_-_Wasteland.jpeg"],
    ["id" => 11, "title" => "Sonder Son",         "artist" => "Brent Faiyaz",  "img" => "https://upload.wikimedia.org/wikipedia/en/1/1f/Brent_Faiyaz_-_Sonder_Son.jpeg"],
    ["id" => 12, "title" => "Lost",               "artist" => "Brent Faiyaz",  "img" => "https://media.pitchfork.com/photos/5be0ab5a9b412c347e38741b/master/pass/brent.jpg"],
    ["id" => 13, "title" => "GNX",               "artist" => "Kendrick Lamar", "img" => "https://upload.wikimedia.org/wikipedia/en/9/93/Kendrick_Lamar_-_GNX.png"],
    ["id" => 14, "title" => "Mr. Morale",         "artist" => "Kendrick Lamar", "img" => "https://upload.wikimedia.org/wikipedia/en/e/e1/Kendrick_Lamar_-_Mr._Morale_%26_the_Big_Steppers.png"],
    ["id" => 15, "title" => "To Pimp a Butterfly", "artist" => "Kendrick Lamar", "img" => "https://upload.wikimedia.org/wikipedia/en/f/f6/Kendrick_Lamar_-_To_Pimp_a_Butterfly.png"],
    ["id" => 16, "title" => "Cowboy Carter",      "artist" => "Beyonce",        "img" => "https://upload.wikimedia.org/wikipedia/en/a/aa/Beyonc%C3%A9_-_Cowboy_Carter.png"],
    ["id" => 17, "title" => "Renaissance",        "artist" => "Beyonce",        "img" => "https://upload.wikimedia.org/wikipedia/en/a/ad/Beyonc%C3%A9_-_Renaissance.png"],
    ["id" => 18, "title" => "Lemonade",           "artist" => "Beyonce",        "img" => "https://upload.wikimedia.org/wikipedia/en/thumb/5/53/Beyonce_-_Lemonade_%28Official_Album_Cover%29.png/250px-Beyonce_-_Lemonade_%28Official_Album_Cover%29.png"]
];

// Create a quick lookup dictionary: $song_lookup[1] will give us Negro Swan's details
$song_lookup = [];
foreach ($songs as $s) {
    $song_lookup[$s['id']] = $s;
}

$my_reviews = [];

// 2. Fetch only the reviews written by the currently logged-in user
$sql = "SELECT id, song_id, rating, feedback FROM reviews WHERE user_id = ? ORDER BY id DESC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $param_user_id);
    $param_user_id = $_SESSION["id"];

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $my_reviews[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TuneReview - My Reviews</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="navbar">
        <a href="index.php" class="logo">TUNE REVIEW</a>
        <div>
            <a href="index.php">Home</a>
            <a href="addSong.php" class="btn-nav">Browse Songs</a>
            <a href="logout.php" style="color:#e74c3c;">Logout</a>
        </div>
    </div>

    <div class="section" style="max-width: 800px; margin: 0 auto; padding-top: 40px;">
        <h2>My Reviews</h2>
        <p>A collection of all the thoughts and ratings you've shared.</p>

        <?php if (!empty($delete_success)): ?>
            <div class="alert-success" style="margin-top:15px;"><?php echo htmlspecialchars($delete_success); ?></div>
        <?php endif; ?>

        <?php if (!empty($delete_error)): ?>
            <div class="alert-danger" style="margin-top:15px;"><?php echo htmlspecialchars($delete_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($edit_success)): ?>
            <div class="alert-success" style="margin-top:15px;"><?php echo htmlspecialchars($edit_success); ?></div>
        <?php endif; ?>

        <?php if (!empty($edit_error)): ?>
            <div class="alert-danger" style="margin-top:15px;"><?php echo htmlspecialchars($edit_error); ?></div>
        <?php endif; ?>

        <?php if (empty($my_reviews)): ?>
            <div style="text-align:center; padding:40px; background:white; border-radius:12px; margin-top:20px; box-shadow: 0 10px 40px rgba(60, 40, 100, 0.2);">
                <h3 style="color:#1a1a2e;">You haven't written any reviews yet!</h3>
                <a href="index.php#songs" class="btn-primary" style="display:inline-block; width:auto; padding: 10px 24px; margin-top:10px; text-decoration:none;">Discover Music</a>
            </div>
        <?php else: ?>

            <?php foreach ($my_reviews as $review):
                // Lookup the song details using the song_id from the database
                $song_id = $review['song_id'];
                $song_details = isset($song_lookup[$song_id]) ? $song_lookup[$song_id] : null;

                // If the song exists in our array, display the review card
                if ($song_details):
            ?>
                    <div style="background:white; padding:20px; border-radius:12px; margin-top:20px; display:flex; gap:20px; box-shadow:0 4px 10px rgba(60, 40, 100, 0.1);">
                        <img src="<?php echo htmlspecialchars($song_details['img']); ?>" alt="Cover" style="width:120px; height:120px; border-radius:8px; object-fit:cover;">
                        <div style="flex:1;">
                            <h3 style="margin:0; font-size:18px; color:#1a1a2e;"><?php echo htmlspecialchars($song_details['title']); ?></h3>
                            <p style="margin:4px 0 10px 0; color:#666; font-size:14px;"><?php echo htmlspecialchars($song_details['artist']); ?></p>

                            <?php if ($edit_id === (int)$review['id']): ?>
                                <form action="myReviews.php" method="post" style="margin-top:8px;">
                                    <label style="display:block; margin-bottom:6px; color:#1a1a2e;">Rating</label>
                                    <select name="rating" style="max-width:180px; margin-bottom:10px;">
                                        <?php for ($r = 1; $r <= 5; $r++): ?>
                                            <option value="<?php echo $r; ?>" <?php echo ((int)$review['rating'] === $r) ? 'selected' : ''; ?>><?php echo $r; ?>/5</option>
                                        <?php endfor; ?>
                                    </select>

                                    <label style="display:block; margin-bottom:6px; color:#1a1a2e;">Feedback</label>
                                    <textarea name="feedback" style="margin-bottom:10px;"><?php echo htmlspecialchars($review['feedback']); ?></textarea>

                                    <input type="hidden" name="review_id" value="<?php echo (int)$review['id']; ?>">
                                    <input type="hidden" name="update_review" value="1">
                                    <button type="submit" class="btn-primary" style="width:auto; padding:8px 14px;">Save Changes</button>
                                    <a href="myReviews.php" class="btn-primary" style="display:inline-block; width:auto; padding:8px 14px; margin-left:8px; text-decoration:none; background:#666;">Cancel</a>
                                </form>
                            <?php else: ?>
                                <div style="color:#1a1a2e; font-size:18px; margin-bottom:10px;">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo ($i <= $review['rating']) ? '★' : '<span style="color:#ccc;">★</span>';
                                    }
                                    ?>
                                </div>

                                <p style="margin:0; font-size:14px; line-height:1.5; color:#333;"><?php echo htmlspecialchars($review['feedback']); ?></p>

                                <div style="margin-top:12px; display:flex; gap:8px; align-items:center;">
                                    <a href="myReviews.php?edit_id=<?php echo (int)$review['id']; ?>" class="btn-primary" style="display:inline-block; width:auto; padding:8px 14px; text-decoration:none; background:#4a90e2;">Edit Review</a>

                                    <form action="myReviews.php" method="post" onsubmit="return confirm('Delete this review? This cannot be undone.');" style="margin:0;">
                                        <input type="hidden" name="review_id" value="<?php echo (int)$review['id']; ?>">
                                        <input type="hidden" name="delete_review" value="1">
                                        <button type="submit" class="btn-primary" style="width:auto; padding:8px 14px; background:#e74c3c;">Delete Review</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
            <?php
                endif;
            endforeach;
            ?>

        <?php endif; ?>
    </div>

</body>

</html>
