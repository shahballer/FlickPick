
//movie data
const API_URL = 'http://localhost:8888/FlickPick/callAPI.php?format=json';

// PHP endpoint for saving movies to the user's watchlist
const WATCHLIST_URL = 'http://localhost:8888/FlickPick/saveToWatchlist.php';

// PHP endpoint for saving movies to the user's seen list
const SEEN_URL = 'http://localhost:8888/FlickPick/saveToSeen.php';

//drag 100 px to count as swipe
const Swipe_Threshold = 100;

//swipe animation in seconds
const fly_ms = 250;

//notification timing
const notification_ms = 3000;
const fade_ms = 200;
let notificationTimer;
let notificationFadeTimer;

//reduce animation for users who prefer reduced motion??
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

//movie card stuff ***

// mvoie list and current movie index
let movies = [];
let currentIndex = 0;

//saved movies (swipe R)
const watchLater = [];

//skipped (swipe L)
const skipped = [];

// Save a movie to the logged-in user's database watchlist
async function saveMovieToWatchlist(movieId) {

    // Prepare the movie ID to send to PHP
    const formData = new FormData();
    formData.append('tmdb_id', movieId);

    try {

        const response = await fetch(WATCHLIST_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();

        // DEBUG: Display the actual PHP response
        console.log('PHP response status:', response.status);
        console.log('PHP response data:', JSON.stringify(result, null, 2));

        // Check whether PHP successfully saved the movie
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to save movie.');
        }

        return result.message;

    } catch (error) {

        console.error('Error saving movie:', error);

        throw error;
    }
}


// Save a movie to the logged-in user's seen list.
async function saveMovieToSeen(movieId) {

    const formData = new FormData();
    formData.append('tmdb_id', movieId);

    try {

        console.log('Saving movie to seen list:', movieId);
        console.log('Sending POST request to:', SEEN_URL);

        const response = await fetch(SEEN_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        console.log('Seen list HTTP status:', response.status);
        console.log('Seen list request method:', 'POST');

        const responseText = await response.text();

        console.log('Seen list raw PHP response:', responseText);

        let result;

        try {
            result = JSON.parse(responseText);
        } catch (error) {
            throw new Error('PHP did not return valid JSON: ' + responseText);
        }

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || 'Unable to save movie to seen list.'
            );
        }

        console.log('Movie successfully saved to seen list!');

        return result;

    } catch (error) {

        console.error('Error saving movie to seen list:', error);

        throw error;
    }
}



//ids of movies user swiped already, liked. Genre id of preferred genres
const seenIds = new Set();
const favoriteIds = new Set();
let preferredGenres = new Set();

// Load previously seen movie IDs from the database
async function loadSeenMovies() {

    try {
        const response = await fetch(
            'http://localhost:8888/FlickPick/getSeenMovies.php',
            {
                credentials: 'same-origin'
            }
        );

        if (!response.ok) {
            throw new Error('Unable to load seen movies.');
        }

        const seenMovies = await response.json();

        // DEBUG: See exactly what PHP returned
        console.log('Seen movies PHP response:', seenMovies);

        // Handle different possible PHP response formats
        let movieIds = seenMovies;

        if (!Array.isArray(movieIds)) {
            movieIds = seenMovies.seenMovies ||
                       seenMovies.movies ||
                       seenMovies.seen ||
                       [];
        }

        // Make sure PHP returned an array
        if (!Array.isArray(movieIds)) {
            throw new Error('PHP did not return a valid seen movies array.');
        }

        // Add previously seen movie IDs to the Set
        movieIds.forEach(function (movieId) {

            const id = Number(movieId);

            if (Number.isFinite(id)) {
                seenIds.add(id);
            }

        });

        // DEBUG: Display loaded movie IDs
        console.log('Loaded seen movie IDs:', Array.from(seenIds));

    } catch (error) {
        console.error('Error loading seen movies:', error);
    }
}

//Swipe states, holding card? swiping? pointer position, and card drag distance
let isDragging = false;
let isAnimating = false;
let startX = 0;
let dragX = 0;

// elements on the card
const cardElement = document.getElementById('movie-card');
const posterElement = document.getElementById('movie-poster');
const placeholderElement = document.getElementById('poster-placeholder');
const movieTitleEl = document.getElementById('movie-title');
const movieDescriptEl = document.getElementById('movie-description');
const movieRatingEl = document.getElementById('movie-rating');
const statusMessageEl = document.getElementById('status-message');

const stampSaveElement = document.getElementById('stamp-save');
const stampPassElement = document.getElementById('stamp-pass');
const likeBtn = document.getElementById('like-btn');
const modalElement = document.getElementById('preference-modal');

//movies shown

// Display notification underneath the action buttons
function showMessage(text) {

    // Clear previous notification timers
    clearTimeout(notificationTimer);
    clearTimeout(notificationFadeTimer);

    // Remove previous animation classes
    statusMessageEl.classList.remove('is-fading');

    if (!text) {
        statusMessageEl.textContent = '';
        statusMessageEl.classList.remove('is-visible');
        return;
    }

    // Display the new notification
    statusMessageEl.textContent = text;
    statusMessageEl.classList.add('is-visible');

    // Keep notification visible for 5 seconds
    notificationTimer = setTimeout(function () {

        // Start fading out
        statusMessageEl.classList.add('is-fading');

        // Remove notification after fade animation
        notificationFadeTimer = setTimeout(function () {
            statusMessageEl.classList.remove('is-visible', 'is-fading');
            statusMessageEl.textContent = '';
        }, fade_ms);

    }, notification_ms);
}

//
async function loadMovies() {
    showMessage('');

    //rn it returns the trending list, but if user picked genres...
    try {
        let url = API_URL;

        if (preferredGenres.length > 0) {
            url += '&genres=' + preferredGenres.join(',');
        }

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error('Server returned ' + response.status);
        }

        const data = await response.json();

        //discard movies that L swipped
        movies = data.movies.filter(function (movie) {
            return !seenIds.has(movie.id);
        });

        currentIndex = 0;

        if (movies.length === 0) {
            cardElement.hidden = true;

            if (seenIds.size > 0) {
                showMessage("You swiped till the end, check your Watch later or update your Preferences");
            } else {
                showMessage("No movies found. Try updating your Preferences");
            }

            return;
        }

        cardElement.hidden = false;

        renderCard();

    } catch (error) {
        console.error(error);
        cardElement.hidden = true;
        showMessage('Could not load movies. Check if MAMP is running');
    }
}

//get info for the card
function renderCard() {
    const movie = movies[currentIndex];

    movieTitleEl.textContent = movie.title;
    movieDescriptEl.textContent = movie.overview || 'No description is available';

    //movie rating
    if (movie.vote_average !== null && movie.vote_average !== undefined) {
        movieRatingEl.textContent = movie.vote_average.toFixed(1) + '/10';
    } else {
        movieRatingEl.textContent = 'Not yet rated';
    }

    //if no poster available
    if (movie.poster_url) {
        posterElement.src = movie.poster_url;
        posterElement.alt = 'Poster for ' + movie.title;
        posterElement.hidden = false;
        placeholderElement.hidden = true;
    } else {
        posterElement.hidden = true;
        placeholderElement.hidden = false;
    }

    //show if favorited
    updateFavoriteButton(movie);
}

//next movie
function showNextMovie() {
    currentIndex++;

    if (currentIndex >= movies.length) {
        //end and preferences questions show
        cardElement.hidden = true;
        openPreferencesMenu();
        return;
    }

    renderCard();
}

// the main thing, the swipe feature :(

// card/labels back to normal ig (at the btm of css)
function resetCardStyle() {
    cardElement.classList.remove('dragging', 'flying', 'snapping');
    cardElement.style.transform = '';
    stampSaveElement.style.opacity = 0;
    stampPassElement.style.opacity = 0;
}

// Card disappears, track what user chose, and move to next movie
async function swipeCard(direction) {

    // Ignore input if there is no active card or animation is running
    if (isAnimating || cardElement.hidden || movies.length === 0) {
        return;
    }

    isAnimating = true;
    const movie = movies[currentIndex];

    // SAVE movie to database if user swipes right
    if (direction === 'right') {
        try {
            const saveMessage = await saveMovieToWatchlist(movie.id);

            // Keep local copy updated
            if (!watchLater.some(function (savedMovie) {
                return savedMovie.id === movie.id;
            })) {
                watchLater.push(movie);
            }

            showMessage(saveMessage);

        } catch (error) {
            console.error('Watchlist error:', error);
            showMessage(error.message || 'Unable to save movie.');

            isAnimating = false;
            snapBack();
            return;
        }
    }

    // SAVE movie to the seen database (both directions)
    try {
        await saveMovieToSeen(movie.id);

    } catch (error) {
        console.error('Seen list error:', error);

        isAnimating = false;
        snapBack();
        return;
    }

    // User swiped left
    if (direction === 'left') {
        skipped.push(movie);
        showMessage('Ignored');
    }

    const sign = direction === 'right' ? 1 : -1;

    // Display the correct swipe stamp
    if (direction === 'right') {
        stampSaveElement.style.opacity = 1;
    } else {
        stampPassElement.style.opacity = 1;
    }

    // Animate card off screen
    cardElement.classList.remove('dragging', 'snapping');
    cardElement.classList.add('flying');
    cardElement.style.transform =
        'translateX(' + sign * window.innerWidth + 'px) rotate(' + sign * 30 + 'deg)';

    // Move to next movie after animation
    setTimeout(function () {
        seenIds.add(movie.id);
        resetCardStyle();
        isAnimating = false;
        showNextMovie();

    }, prefersReducedMotion ? 0 : fly_ms);
}

// you didn't swipe hard enough dude, gotta do it again
function snapBack() {
    cardElement.classList.add('snapping');
    cardElement.style.transform = '';
    stampSaveElement.style.opacity = 0;
    stampPassElement.style.opacity = 0;
}

// dragging w/mouse or finger; aka the pointer events yay
function onPointerDown(event) {
    if (isAnimating || cardElement.hidden) return;

    //L click only
    if (event.pointerType === 'mouse' && event.button !== 0) return;

    isDragging = true;
    startX = event.clientX;
    dragX = 0;

    cardElement.classList.remove('snapping');
    cardElement.classList.add('dragging');

    //keep getting pointer events even if it leaves card elem
    cardElement.setPointerCapture(event.pointerId);
}

function onPointerMove(event) {
    if (!isDragging) return;

    dragX = event.clientX - startX;

    // mv card and tilt a lil where its dragging
    const tilt = dragX / 15;
    cardElement.style.transform = 'translateX(' + dragX + 'px) rotate(' + tilt + 'deg)';

    // fade in label. 0 means invisible, 1 is visible
    const strength = Math.min(Math.abs(dragX) / Swipe_Threshold, 1);
    stampSaveElement.style.opacity = dragX > 0 ? strength : 0;
    stampPassElement.style.opacity = dragX < 0 ? strength : 0;
}

function onPointerUp() {
    if (!isDragging) return;

    isDragging = false;
    cardElement.classList.remove('dragging');

    if (dragX > Swipe_Threshold) {
        swipeCard('right');
    } else if (dragX < -Swipe_Threshold) {
        swipeCard('left');
    } else {
        snapBack();
    }
}

// runs when pointerevent is canceled by the browser
function onPointerCancel() {
    if (!isDragging) return;

    isDragging = false;
    cardElement.classList.remove('dragging');
    snapBack();
}

cardElement.addEventListener('pointerdown', onPointerDown);
cardElement.addEventListener('pointermove', onPointerMove);
cardElement.addEventListener('pointerup', onPointerUp);
cardElement.addEventListener('pointercancel', onPointerCancel);

// buttons and the keyboard

// skip btn (X and L arrow)
document.getElementById('pass-btn').addEventListener('click', function () {
    swipeCard('left');
});

document.getElementById('swipe-left-btn').addEventListener('click', function () {
    swipeCard('left');
});

//Watch Later btn (bookmark/ R arrow)
document.getElementById('watchlater-btn').addEventListener('click', function () {
    swipeCard('right');
});

document.getElementById('swipe-right-btn').addEventListener('click', function () {
    swipeCard('right');
});

//keyboard (L arrow to pass/skip/ignore, R arrow to like/save)
document.addEventListener('keydown', function (event) {
    if (!modalElement.hidden) return;

    if (event.key === 'ArrowLeft') {
        swipeCard('left');
    }

    if (event.key === 'ArrowRight') {
        swipeCard('right');
    }
});

// FAVS (heart): mark movie w/o moving on
function updateFavoriteButton(movie) {
    const isFavorite = favoriteIds.has(movie.id);
    likeBtn.classList.toggle('active', isFavorite);
    likeBtn.setAttribute('aria-pressed', String(isFavorite));
}

likeBtn.addEventListener('click', function () {
    if (cardElement.hidden || movies.length === 0) return;

    const movie = movies[currentIndex];

    if (favoriteIds.has(movie.id)) {
        favoriteIds.delete(movie.id);
    } else {
        favoriteIds.add(movie.id); // FOR LATER save favorites to DB
    }

    updateFavoriteButton(movie);
});

//prefrences menu for after they swipped through everything
function openPreferencesMenu() {
    modalElement.hidden = false;

    const firstBox = modalElement.querySelector('input');

    if (firstBox) firstBox.focus();
}

document.getElementById('preference-continue-btn').addEventListener('click', function () {
    //get ids of checked preferences
    const checkedPreferences = modalElement.querySelectorAll('input[name="genre"]:checked');

    preferredGenres = Array.from(checkedPreferences).map(function (box) {
        return box.value;
    });

    modalElement.hidden = true;
    loadMovies();
});

// Initial load
async function initializeFlickPick() {
    await loadSeenMovies();
    await loadMovies();
}

initializeFlickPick();
