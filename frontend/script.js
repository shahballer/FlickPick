
//movie data
const API_URL = 'http://localhost:8888/FlickPick/index.php?format=json';

// mvoie list
let movies = [];

//current movie shown
let currentIndex = 0;


const cardElement = document.getElementById('movie-card');
const posterElement = document.getElementById('movie-poster');
const placeholderElement = document.getElementById('poster-placeholder');
const movieTitleEl = document.getElementById('movie-title');
const movieDescriptEl = document.getElementById('movie-description');
const movieRatingEl = document.getElementById('movie-rating');
const statusMessageEl = document.getElementById('status-message');

// show error messages? func?
function showMessage(text) {
    statusMessageEl.textContent = text;
}

//
async function loadMovies() {
    showMessage('');
    try {
        const response = await fetch(API_URL);
        if (!response.ok) {
            throw new Error('Server returned ' + response.status);
        }

        const data = await response.json();
        movies = data.movies;

        if (movies.length === 0) {
            cardElement.hidden = true;
            showMessage('No movies found at the moment');
            return;
        }

        currentIndex = 0;
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
}

//next movie
function showNextMovie() {
    currentIndex ++;

    if (currentIndex >= movies.length) {
        cardElement.hidden = true;
        showMessage("That's all the movies for now");
        return;
    }

    renderCard();
}

function handlePass() {
    //create a func that remembers user 'passed' on the movie.
    showNextMovie();
}

function handleLike() {
    //create func that remembers movie is "liked"
    showNextMovie();
}

//btn funcs
document.getElementById('pass-btn').addEventListener('click' , handlePass);
document.getElementById('like-btn').addEventListener('click', handleLike);
document.getElementById('swipe-left-btn').addEventListener('click', handlePass);
document.getElementById('swipe-right-btn').addEventListener('click', handleLike);

//start the thing
loadMovies();