
//movie data
const API_URL = 'http://localhost:8888/FlickPick/index.php?format=json';

//drag 100 px to count as swipe
const Swipe_Threshold = 100;

//swipe animation in seconds
const fly_ms = 250;

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

//ids of movies user swiped already, liked. Genre id of preferred genres
const seenIds = new Set();
const favoriteIds = new Set();
let preferredGenres = new Set();

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
// show error messages? func?
function showMessage(text) {
    statusMessageEl.textContent = text;
}

//
async function loadMovies() {
    showMessage('');
    //rn it returns the trending list, but if user picked genres...
    try {
        let url = API_URL; //keep or ..?
        if (preferredGenres.length > 0) {
            url += '&genres=' + preferredGenres.join(',');
        }
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Server returned ' + response.status);
        }

        const data = await response.json();

        //discard movies that L swipped
        movies = data.movies.filter(function (movie){
            return !seenIds.has(movie.id);
        });
        currentIndex = 0;
        
        if (movies.length === 0) {
            cardElement.hidden = true;
            if(seenIds.size > 0) {
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
    currentIndex ++;

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

// card dissapears, track what user chose, and next movie
function swipeCard(direction) {
    //ignores if no card/card dissapearing 
    if (isAnimating || cardElement.hidden || movies.length === 0) {
        return;
    }

    isAnimating = true;

    const movie = movies[currentIndex];
    const sign = direction === 'right' ? 1 : -1; // 1 for right, -1 for left

    // if saved (liked) show the save stamp, otherwise show the pass stamp
    if (direction === 'right') {
        stampSaveElement.style.opacity = 1;
    } else {
        stampPassElement.style.opacity = 1;
    }

    //card out of the screen
    cardElement.classList.remove('dragging', 'snapping');
    cardElement.classList.add('flying');
    cardElement.style.transform = 'translateX(' + sign * window.innerWidth +'px) rotate(' + sign * 30 + 'deg)';

    //card has been dragged off, track the user choice
    setTimeout(function () {
        if (direction === 'right') {
            watchLater.push(movie); // FOR LATER: save to watchlist tbl (user_id + tmdb_id)
        } else {
            skipped.push(movie); 
        }
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
    if(isAnimating || cardElement.hidden) return;
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

    // mv card  and tilt a lil where its dragging
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
function onPointerCancel () {
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
})
document.getElementById('swipe-right-btn').addEventListener('click', function () {
    swipeCard('right');
});

//keyboard (L arrow to pass/skip/ignore, R arrow to like/save)
document.addEventListener('keydown', function (event) {
    if(!modalElement.hidden) return; // don't swipe when pop-up open

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
    if(cardElement.hidden || movies.length === 0) return;

    const movie = movies[currentIndex];
    if(favoriteIds.has(movie.id)) {
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

//initial load
loadMovies();