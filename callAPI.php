<?php

// Keep the public queue size bounded while allowing future callers to request fewer movies.
function normalizeMovieLimit(?int $requestedLimit): int
{
    // TMDB can return large result pages, so keep the frontend queue predictable.
    return $requestedLimit === null ? 20 : min(max($requestedLimit, 1), 20);
}

function respondWithJsonError(string $message, int $statusCode): never
{
    // Every failure uses JSON so fetch() can handle errors without receiving an HTML page.
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => [
            'message' => $message,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Make an authenticated request to a TMDB endpoint.
 *
 * @return array<string, mixed>
 */
function requestTmdb(string $endpoint, array $query = []): array
{
    // The token stays on the server; only the normalized movie response is sent to the browser.
    $tmdbConfig = require dirname(__DIR__, 2) . '/config/tmdb.php';
    $tmdbToken = trim($tmdbConfig['token'] ?? '');

    // Pass query parameters through http_build_query() so filters are URL-encoded safely.
    $url = 'https://api.themoviedb.org/3/' . ltrim($endpoint, '/');
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $curl = curl_init($url);
    if ($curl === false) {
        respondWithJsonError('Unable to initialize the TMDB request.', 500);
    }

    curl_setopt_array($curl, [
        // The API needs the response in PHP so it can validate and normalize the data.
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $tmdbToken,
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // A false response indicates a transport problem, such as a timeout or TLS failure.
    if ($response === false) {
        respondWithJsonError('Unable to reach TMDB.', 502);
    }

    if ($statusCode !== 200) {
        respondWithJsonError('TMDB returned HTTP ' . $statusCode . '.', 502);
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        respondWithJsonError('TMDB returned invalid JSON.', 502);
    }

    return $data;
}

/**
 * Return the fields used by the frontend movie card.
 *
 * @param array<string, mixed> $movie
 * @return array<string, mixed>
 */
function normalizeMovie(array $movie): array
{
    // Keep this contract stable: frontend/script.js reads these names regardless of source.
    return [
        'id' => $movie['id'] ?? null,
        'title' => $movie['title'] ?? 'Untitled',
        'original_title' => $movie['original_title'] ?? null,
        'overview' => $movie['overview'] ?? '',
        'release_date' => $movie['release_date'] ?? null,
        // TMDB returns relative image paths; the frontend needs complete image URLs.
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
}

/**
 * Fetch trending movies.
 *
 * @return array<int, array<string, mixed>>
 */
function fetchTrendingMovies(int $limit = 20, string $timeWindow = 'day'): array
{
    // TMDB only accepts these two time windows for the trending endpoint.
    if (!in_array($timeWindow, ['day', 'week'], true)) {
        respondWithJsonError('The trending time window must be day or week.', 400);
    }

    // Trending requests do not need filters; the time window selects the endpoint.
    $data = requestTmdb('trending/movie/' . $timeWindow);
    return normalizeMovieResults($data, $limit);
}

/**
 * Fetch movies using TMDB discover filters.
 *
 * Supported filters include genres, sort_by, year, language, min_rating,
 * runtime_min, runtime_max, and page.
 *
 * @param array<string, mixed> $filters
 * @return array<int, array<string, mixed>>
 */
function fetchDiscoverMovies(array $filters = [], int $limit = 20): array
{
    // Discover is the flexible source for user preferences and genre selections.
    // The keys below are internal names that are translated into TMDB parameter names.
    $query = [
        'sort_by' => $filters['sort_by'] ?? 'popularity.desc',
    ];

    if (!empty($filters['genres'])) {
        // TMDB expects genre IDs as a comma-separated string, such as "28,35".
        $query['with_genres'] = is_array($filters['genres'])
            ? implode(',', $filters['genres'])
            : $filters['genres'];
    }
    if (isset($filters['year']) && is_numeric($filters['year'])) {
        // Use the movie release year filter rather than filtering results in PHP.
        $query['primary_release_year'] = (int) $filters['year'];
    }
    if (!empty($filters['language'])) {
        // This is the two-letter TMDB language code, such as "en" or "fr".
        $query['with_original_language'] = $filters['language'];
    }
    if (isset($filters['min_rating']) && is_numeric($filters['min_rating'])) {
        // Clamp user input to TMDB's 0-10 rating range before sending it upstream.
        $query['vote_average.gte'] = max(0, min((float) $filters['min_rating'], 10));
    }
    if (isset($filters['runtime_min']) && is_numeric($filters['runtime_min'])) {
        $query['with_runtime.gte'] = max(0, (int) $filters['runtime_min']);
    }
    if (isset($filters['runtime_max']) && is_numeric($filters['runtime_max'])) {
        $query['with_runtime.lte'] = max(0, (int) $filters['runtime_max']);
    }
    if (isset($filters['page']) && is_numeric($filters['page'])) {
        // TMDB pages are 1-based and support up to page 500.
        $query['page'] = max(1, min((int) $filters['page'], 500));
    }

    $data = requestTmdb('discover/movie', $query);
    return normalizeMovieResults($data, $limit);
}

/**
 * Select a movie source for future callers without changing the response shape.
 *
 * @param array<string, mixed> $options
 * @return array<int, array<string, mixed>>
 */
function fetchMovies(array $options = []): array
{
    // This is the future frontend entry point: build an options array, then choose a source.
    $limit = normalizeMovieLimit(isset($options['limit']) ? (int) $options['limit'] : null);
    $source = $options['source'] ?? 'trending';

    if ($source === 'trending') {
        // Example future options: ['source' => 'trending', 'time_window' => 'week'].
        return fetchTrendingMovies($limit, $options['time_window'] ?? 'day');
    }

    if ($source === 'discover') {
        // Discover options may include genres, sort_by, year, language, rating, runtime, and page.
        return fetchDiscoverMovies($options, $limit);
    }

    respondWithJsonError('Unsupported movie source.', 400);
}

/**
 * @param array<string, mixed> $data
 * @return array<int, array<string, mixed>>
 */
function normalizeMovieResults(array $data, int $limit): array
{
    // Both trending and discover responses expose their movies under "results".
    $results = is_array($data['results'] ?? null) ? $data['results'] : [];
    $movies = [];

    // Slice after the TMDB request so the public queue never exceeds the configured limit.
    foreach (array_slice($results, 0, normalizeMovieLimit($limit)) as $movie) {
        if (is_array($movie)) {
            $movies[] = normalizeMovie($movie);
        }
    }

    return $movies;
}

// The current public endpoint remains trending by default for backwards compatibility.
// Later, request parameters can be validated here and passed into fetchMovies().
$requestedLimit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
$movies = fetchMovies([
    'source' => 'trending',
    'limit' => $requestedLimit === false ? null : $requestedLimit,
]);

// Return the queue format expected by the frontend, independent of the selected TMDB source.
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
