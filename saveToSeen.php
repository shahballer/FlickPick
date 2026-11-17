
<?php

session_start();

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}

// Check whether the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to save movies.'
    ]);

    exit;
}

// Get the logged-in user's ID.
$user_id = (int) $_SESSION['user_id'];

// Get the TMDB movie ID.
$tmdb_id = filter_input(INPUT_POST, 'tmdb_id', FILTER_VALIDATE_INT);

// Validate the movie ID.
if (!$tmdb_id || $tmdb_id <= 0) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid movie ID.'
    ]);

    exit;
}

try {

    // Insert the movie into the user's seen list.
    $stmt = $conn->prepare(
        'INSERT INTO seen_movies (user_id, tmdb_id)
         VALUES (?, ?)'
    );

    $stmt->bind_param('ii', $user_id, $tmdb_id);

    $stmt->execute();

    $stmt->close();

    echo json_encode([
        'success' => true
    ]);

} catch (mysqli_sql_exception $e) {

    // Ignore duplicates because the movie is already saved.
    if ($e->getCode() === 1062) {

        echo json_encode([
            'success' => true
        ]);

    } else {

        // Log technical details for developers.
        error_log('Seen movie save error: ' . $e->getMessage());

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Unable to save movie to seen list.'
        ]);
    }
}

?>
