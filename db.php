<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "Flick_Pick";
$username = "root";
$password = "root";
$port = 8889;

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

echo "Database connected successfully!<br><br>";

$user_id = 2;

$sql = "SELECT * FROM watchlist WHERE user_id = $user_id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        echo "TMDB ID: " . $row["tmdb_id"] . "<br>";
        echo "Saved: " . $row["saved_at"] . "<br>";
        echo "<hr>";
    }

} else {
    echo "This user has no saved movies.";
}

$conn->close();

?>


