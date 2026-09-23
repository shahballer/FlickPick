<?php

$pageTitle = 'FlickPick';

// Load the TMDB token from a file outside the public htdocs folder.
$tmdbConfig = require 'C:/MAMP/config/tmdb.php'; // Include the TMDB configuration file
$tmdbToken = trim($tmdbConfig['token']); // Get the TMDB API token from the configuration file

// Create a request for movies that are trending today.
$curl = curl_init('https://api.themoviedb.org/3/trending/movie/day');

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

// Stop and show a useful message if the network request failed.
if ($response === false) {
    die('cURL error: ' . htmlspecialchars($curlError, ENT_QUOTES, 'UTF-8'));
}

// Stop and show TMDB's response if the API returned an error status.
if ($statusCode !== 200) {
    die(
        'TMDB returned HTTP ' . $statusCode .
        '<pre>' . htmlspecialchars($response, ENT_QUOTES, 'UTF-8') . '</pre>'
    );
}

// Convert TMDB's JSON response into a PHP associative array.
$data = json_decode($response, true);

// Make sure the response was valid JSON before using it.
if (!is_array($data)) {
    die('Invalid JSON response.');
}

// Keep only the first five movies from the trending results.
$movies = array_slice($data['results'] ?? [], 0, 5);
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

    <!-- Display the movie titles returned by TMDB. -->
	<?php if (count($movies) > 0): ?>
    <ol>
        <?php foreach ($movies as $movie): ?>
            <li>
                <?= htmlspecialchars($movie['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8') ?>
            </li>
        <?php endforeach; ?>
    </ol>
<?php else: ?>
    <p>Unable to load trending movies.</p>
<?php endif; ?>

</body>
</html>