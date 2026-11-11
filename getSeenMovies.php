<?php

// Start the user's session.
session_start();

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow logged-in users to retrieve their seen movies.
if (
    !isset($_SESSION['user_id']) ||
    !is_numeric($_SESSION['user_id'])
) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'movies' => [],
        'message' => 'User not logged in.'
    ]);

    exit;
}

// Get the logged-in user's ID.
$user_id = (int) $_SESSION['user_id'];

try {

    // Only retrieve movies belonging to the logged-in user.
    $stmt = $conn->prepare(
        'SELECT tmdb_id
         FROM seen_movies
         WHERE user_id = ?'
    );

    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    // Store the retrieved TMDB movie IDs.
    $seenMovies = [];

    while ($row = $result->fetch_assoc()) {
        $seenMovies[] = (int) $row['tmdb_id'];
    }

    $stmt->close();

    // Return the movie IDs as JSON.
    echo json_encode([
        'success' => true,
        'movies' => $seenMovies
    ]);

} catch (mysqli_sql_exception $e) {

    // Log technical details for developers.
    error_log('Get seen movies error: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'movies' => [],
        'message' => 'Unable to retrieve seen movies.'
    ]);
}

// Close the database connection.
$conn->close();

