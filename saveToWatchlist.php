<?php

session_start();

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Check whether the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to save movies.'
    ]);
    exit;
}

// Get the logged-in user's ID
$user_id = (int) $_SESSION['user_id'];

// Get the TMDB movie ID
$tmdb_id = filter_input(INPUT_POST, 'tmdb_id', FILTER_VALIDATE_INT);

// Validate movie ID
if (!$tmdb_id || $tmdb_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid movie ID.'
    ]);
    exit;
}

try {

    // Insert the movie into the user's watchlist
    $stmt = $conn->prepare(
        'INSERT INTO watchlist (user_id, tmdb_id)
         VALUES (?, ?)'
    );

    $stmt->bind_param('ii', $user_id, $tmdb_id);

    $stmt->execute();

    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Movie added to your watchlist.'
    ]);

} catch (mysqli_sql_exception $e) {

    // Handle duplicate movies
    if ($e->getCode() === 1062) {
        echo json_encode([
            'success' => true,
            'message' => 'Already in your watchlist!'
        ]);
    } else {
        error_log('Watchlist save error: ' . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to save movie.'
        ]);
    }
}

?>

