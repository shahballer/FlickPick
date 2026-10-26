<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FlickPick - Swipe Wireframe</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <?php include dirname( __DIR__) . '/partials/nav.php'; ?>

  <!-- Swipe Area??? -->
  <main class="swipe-area">
    <!-- Left button -->
    <button id="swipe-left-btn" class="swipe-btn swipe-left" aria-label="Swipe left">&#8592;</button>

    <!-- Movie card container -->
    <section class="movie-card" id="movie-card">

      <div id="stamp-save" class="stamp stamp-save">Watch Later</div>
      <div id="stamp-pass" class="stamp stamp-pass">Skip</div>

      <img id="movie-poster" class="poster" alt="" dragable="false"hidden>
      <div id="poster-placeholder" class="poster-placeholder">Poster</div>
      <h2 id="movie-title"class="movie-title">Loading...</h2>
      <p id="movie-description"class="movie-description"></p>
      <p class="movie-rating">Rating: <span id ="movie-rating"></span></p>
      <p class="movie-services">Available on: <span id="movie-services">Coming Soon</span></p>
    </section>

    <p id="status-message"></p>

    <!-- Right button -->
    <button id="swipe-right-btn" class="swipe-btn swipe-right" aria-label="Swipe right">&#8594;</button>

  </main>

  <!-- buttons at the bottom -->
  <div class="action-buttons">
    <button id="pass-btn" class="action-btn btn-pass" aria-label="Pass">X</button>
    <button class="action-btn btn-shuffle" aria-label="Random pick">&#8635;</button>
    <button id="watchlater-btn" class="action-btn btn-watchlater" aria-label="Watch later">&#9733;</button>
    <button id="like-btn" class="action-btn btn-like" aria-label="Like" aria-pressed="false">&#10084;</button>
  </div>

  <!--prefrence section pop up for after the 20 movies-->
  <div id="preference-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="preference-title" hidden>
    <div class="modal-content">

      <h2 id="preference-title">What are you in the mood for tonight?</h2>

      <p>Choose a few genres from below.</p>
      <!--Using tmdb genre ids-->
      <label><input type="checkbox" name="genre" value="28"> Action</label>
      <label><input type="checkbox" name="genre" value="35"> Comedy</label>
      <label><input type="checkbox" name="genre" value="18"> Drama</label>
      <label><input type="checkbox" name="genre" value="878"> Sci-Fi</label>
      <label><input type="checkbox" name="genre" value="27"> Horror</label>
      <label><input type="checkbox" name="genre" value="10749"> Romance</label>
     
      <button id="preference-continue-btn" type="button">Continue</button>

    </div>
  </div>

  <script src="script.js"></script>

</body>
</html>
