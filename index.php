<?php

$pageTitle = 'FlickPick';

$tmdbConfig = require 'C:/MAMP/config/tmdb.php'; // Include the TMDB configuration file
$tmdbToken = trim($tmdbConfig['token']); // Get the TMDB API token from the configuration file

$curl = curl_init('https://api.themoviedb.org/3/trending/movie/day');

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $tmdbToken,
        'Accept: application/json',
    ],
]);

$response = curl_exec($curl);
$curlError = curl_error($curl);
$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

if ($response === false) {
    die('cURL error: ' . htmlspecialchars($curlError, ENT_QUOTES, 'UTF-8'));
}

if ($statusCode !== 200) {
    die(
        'TMDB returned HTTP ' . $statusCode .
        '<pre>' . htmlspecialchars($response, ENT_QUOTES, 'UTF-8') . '</pre>'
    );
}

$data = json_decode($response, true);

if (!is_array($data)) {
    die('Invalid JSON response.');
}

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