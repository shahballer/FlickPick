 <?php

// Read an optional movie limit from the URL and keep it between 1 and 20.
$requestedLimit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
$movieLimit = $requestedLimit === false || $requestedLimit === null
    ? 20
    : min(max($requestedLimit, 1), 20);

// Load the TMDB token from a file outside the public htdocs folder.
$tmdbConfig = require dirname(__DIR__, 2) . '/config/tmdb.php'; // Include the TMDB configuration file
$tmdbToken = trim($tmdbConfig['token']); // Get the TMDB API token from the configuration file

// Create a request for movies that are trending today.
$curl = curl_init('https://api.themoviedb.org/3/trending/movie/day');

// Configure cURL to return TMDB's response so PHP can process it.
curl_setopt_array($curl, [
    // Return the API response as a string instead of displaying it immediately.
    CURLOPT_RETURNTRANSFER => true,
    // Send the token so TMDB can authenticate the request.
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $tmdbToken,
        'Accept: application/json',
    ],
]);

$response = curl_exec($curl);
$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Close the cURL connection after collecting the response details.
curl_close($curl);

// Return errors in the same JSON format as successful API responses.
function respondWithJsonError(string $message, int $statusCode): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => [
            'message' => $message,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Stop and show a useful message if the network request failed.
if ($response === false) {
    respondWithJsonError('Unable to reach TMDB.', 502);
}

// Stop and show TMDB's response if the API returned an error status.
if ($statusCode !== 200) {
    respondWithJsonError('TMDB returned HTTP ' . $statusCode . '.', 502);
}

// Convert TMDB's JSON response into a PHP associative array.
$data = json_decode($response, true);

// Make sure the response was valid JSON before using it.
if (!is_array($data)) {
    respondWithJsonError('TMDB returned invalid JSON.', 502);
}

// Convert TMDB's response into a stable queue for the frontend.
$movies = array_values(array_map(
    static function (array $movie): array {
        return [
            'id' => $movie['id'] ?? null,
            'title' => $movie['title'] ?? 'Untitled',
            'original_title' => $movie['original_title'] ?? null,
            'overview' => $movie['overview'] ?? '',
            'release_date' => $movie['release_date'] ?? null,
            'poster_url' => isset($movie['poster_path'])
                ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path']
                : null,
            'backdrop_url' => isset($movie['backdrop_path'])
                ? 'https://image.tmdb.org/t/p/w1280' . $movie['backdrop_path']
                : null,
            'vote_average' => $movie['vote_average'] ?? null,
            'vote_count' => $movie['vote_count'] ?? 0,
            'popularity' => $movie['popularity'] ?? null,
            'genre_ids' => $movie['genre_ids'] ?? [],
            'original_language' => $movie['original_language'] ?? null,
            'adult' => $movie['adult'] ?? false,
        ];
    },
    array_slice($data['results'] ?? [], 0, $movieLimit)
));

// Return one ordered array so the frontend can move through it after each swipe.
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'movies' => $movies,
    'count' => count($movies),
    'queue' => [
        'type' => 'ordered',
        'current_index' => 0,
        'next_index' => count($movies) > 1 ? 1 : null,
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);