<?php
// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'guest');
define('DB_PASSWORD', 'ggcITEC4450@');
define('DB_NAME', 'tunereview');

// API credentials
define('LASTFM_API_KEY', 'c6b7d59255f7bc32032d65bad4fe26b4');

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>