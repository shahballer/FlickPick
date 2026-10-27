 <?php

$pageTitle = 'FlickPick';

// Read an optional movie limit from the URL and keep it between 1 and 20.
$requestedLimit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
$movieLimit = $requestedLimit === false || $requestedLimit === null
    ? 20
    : min(max($requestedLimit, 1), 20);

// The same file supports both the browser page and the frontend JSON API.
$isJsonRequest = ($_GET['format'] ?? '') === 'json'
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

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
$curlError = curl_error($curl);
$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Close the cURL connection after collecting the response details.
curl_close($curl);

// Return an API-friendly error when JSON was requested.
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
    if ($isJsonRequest) {
        respondWithJsonError('Unable to reach TMDB.', 502);
    }

    die('cURL error: ' . htmlspecialchars($curlError, ENT_QUOTES, 'UTF-8'));
}

// Stop and show TMDB's response if the API returned an error status.
if ($statusCode !== 200) {
    if ($isJsonRequest) {
        respondWithJsonError('TMDB returned HTTP ' . $statusCode . '.', 502);
    }

    die(
        'TMDB returned HTTP ' . $statusCode .
        '<pre>' . htmlspecialchars($response, ENT_QUOTES, 'UTF-8') . '</pre>'
    );
}

// Convert TMDB's JSON response into a PHP associative array.
$data = json_decode($response, true);

// Make sure the response was valid JSON before using it.
if (!is_array($data)) {
    if ($isJsonRequest) {
        respondWithJsonError('TMDB returned invalid JSON.', 502);
    }

    die('Invalid JSON response.');
}

// Convert TMDB's response into a stable, display-ready queue for the frontend.
// This prevents the frontend from needing to understand TMDB's full response.
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

if ($isJsonRequest) {
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
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
	<h1>FlickPick</h1>

	<h2>Trending Movies</h2>

    <!-- Render the browser version of the same movie data sent by the JSON API. -->
	<?php if (count($movies) > 0): ?>
    <ol>
        <?php foreach ($movies as $movie): ?>
            <li>
                <h3><?= htmlspecialchars($movie['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8') ?></h3>

                <!-- Some TMDB movies do not have an available poster. -->
                <?php if (!empty($movie['poster_url'])): ?>
                    <img
                        src="<?= htmlspecialchars($movie['poster_url'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="Poster for <?= htmlspecialchars($movie['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8') ?>"
                        width="200"
                    >
                <?php else: ?>
                    <p>No poster available.</p>
                <?php endif; ?>

                <p>
                    Popularity:
                    <?= htmlspecialchars((string) ($movie['popularity'] ?? 'Unavailable'), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </li>
        <?php endforeach; ?>
    </ol>
<?php else: ?>
    <p>Unable to load trending movies.</p>
<?php endif; ?>

<!-- Send the user to the sign-in page before they continue to the app. -->
<form action="signIn.php" method="get">
    <button type="submit">Sign In</button>
</form>

</body>
</html>