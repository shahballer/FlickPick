<?php 

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../signIn.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Later</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="watchLater.css">
</head>
<body>
    <?php include dirname( __DIR__) . '/partials/nav.php'; ?>

    <main class="wl-page"> <!--class name changed-->
        <div class="wl-head">
            <h1>My Watch Later</h1>
            <!--wl.js will fill in the saved/watched movies--> 
            <p id="wl-count" class="wl-count" aria-live="polite"></p>
        </div>

        <!--sample data -->
        <p id="wl-sample-banner" class="wl-banner" hidden> 
            Sample data, trending movies, need the backend
        </p>

        <p id="wl-status" class="wl-status" role="status" aria-live="polite"></p>
        <!-- wl can show 1 of 4 states. -->

        <section id="wl-loading" class="wl-message">
            <p>Loading your watchlist...</p>
        </section> 

        <section id="wl-error" class="wl-message" role="alert" hidden>
            <p id="wl-error-msg">Something went wrong.</p>
            <button id="wl-retry" class="wl-btn wl-btn-primary" type="button">Try again</button>
        </section>

        <section id="wl-empty" class="wl-message" hidden>
            <p>Nothing saved yet. Swipe right on a movie to save it here.</p>
            <a class="wl-btn wl-btn-primary" href="index.php">Start swiping</a>
        </section>

        <div id="wl-grid" class="wl-grid" hidden></div>



        <!--
        <div class="toolbar">
            <div class="segmented" role="group" aria-label="Show">
                <button type="button" aria-pressed="true">To watch</button>
                <button type="button" aria-pressed="false">Watched</button>
                <button type="button" aria-pressed="false">All</button>
            </div>
            <div class="segmented" role="group" aria-label="Sort by">
                <button type="button" aria-pressed="true">Popular</button>
                <button type="button" aria-pressed="false">Oldest</button>
            </div>
        </div>
        -->

        <!--
        <div class="state">
           <--movies will be pulled from tmdb --
            <div class="grid">
                
                <article class="card"><div class="ph poster-ph">Poster</div><h3>Movie Title</h3><p class="meta">&#9733; 7.2 &middot; 2023</p>
                    <div class="actions"><button class="btn btn-small" type="button">Mark watched</button><button class="btn btn-small" type="button">Remove</button></div></article>
                <article class="card"><span class="badge">&#10003; Watched</span><div class="ph poster-ph">Poster</div><h3>Movie Title</h3>
                    <p class="meta">Not rated yet</p>
                    <div class="actions"><a class="btn btn-small btn-primary" href="rate.html">Rate it</a></div></article>
               
            </div>
        </div>
       -->
    </main>

    <script src="watchLater.js"></script>
   
</body>
</html>