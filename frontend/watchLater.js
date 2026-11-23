// 'sample' = placeholder
//'server' needs a way to return the watch later list for the user, also to remove them.
// so, like a getWAtchList and removefromWAtchList

const DATA_SOURCE = 'sample';

const sample_URL = '../callAPI.php?format=json&limit=8';
const watchList_URL = '../getWatchList.php';
const remove_URL = '../removeFromWatchList.php';
const sign_in_URL = '../signIn.php';

//fade-out time
const FADE_MS = 200;

//less animation? accessibility?
//reduced motion
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

//what i need from server

// GET getWatchList.php -> { "success": true, "movies": [
        //{ "id": 603, "title": "...", "poster_url": "https://...", or null, "vote_average": 8.1/null, "release_date": "2026-11-17"/null } ] }
// id = tmdb id = tmdb_id col 

//POST removeFromWatchList.php with tmdb_id -> { "success": true/false, "message": "..." }



// STATE
//saved movies on display
let movies = []; 

// DOM ELEMENTS
const loadingEl = document.getElementById('wl-loading');
const errorEl = document.getElementById('wl-error');
const errorMsgEl = document.getElementById('wl-error-msg');
const emptyEL = document.getElementById('wl-empty');
const gridEl = document.getElementById('wl-grid');
const countEl = document.getElementById('wl-count');
const statusEl = document.getElementById('wl-status');
const bannerEl = document.getElementById('wl-sample-banner');

//states
function showOnly(name) {
    loadingEl.hidden = name !== 'loading';
    errorEl.hidden = name !== 'error';
    emptyEL.hidden = name !== 'empty';
    gridEl.hidden = name !== 'grid';
}

function showStatus(text) {
    statusEl.textContent = text;
}

// load list
async function loadWatchList() {
    showStatus('');
    showOnly('loading');

    try {
        const url = DATA_SOURCE === 'sample' ? sample_URL : watchList_URL;

        const response = await fetch(url, { credentials: 'same-origin' });

        if (response.status === 401) {
            //not signed in
            window.location.href = sign_in_URL;
            return;
        }

        if (!response.ok) {
            throw new Error('server returned ' + response.status);
        }

        const data = await response.json();

        if (!Array.isArray(data.movies)) {
            throw new Error('The server did not send movie list.');
        }
        movies = data.movies;
        renderList();

    } catch (error) {
        console.error('Could not load watchlist:', error);
        errorMsgEl.textContent = 'Could not load your watchlist. Check MAMP is running, try again.';
        showOnly('error');
        
    }
}

//draw list
function renderList() {
    updateCount();

    if (movies.length === 0) {
        showOnly('empty');
        return;
    }

    //
    gridEl.replaceChildren();
    
    movies.forEach(function (movie) {
        gridEl.appendChild(buildMovieCard(movie));
    });

    showOnly('grid');
}

function updateCount() {
    if (movies.length === 0) {
        countEl.textContent = '';
    } else {
        countEl.textContent =  movies.length + ' saved';
    }
}

function buildMovieCard(movie) {
    const card = document.createElement('article');
    card.className = 'wl-card';

    
    //no poster?
    if (movie.poster_url) {
        const poster = document.createElement('img');
        poster.className = 'wl-poster';
        poster.src = movie.poster_url;
        poster.alt = 'Poster for ' + movie.title;
        poster.loading = 'lazy'; // wait for imgs until in view
        
        poster.addEventListener('error', function() {
            poster.replaceWith(buildNoPoster());
        });
        card.appendChild(poster);
    } else {
        card.appendChild(buildNoPoster());
    }
  

    const title = document.createElement('h2');
    title.className = 'wl-title';
    title.textContent = movie.title || 'Untitled';
    card.appendChild(title);

    const meta = document.createElement('p');
    meta.className = 'wl-meta';
    meta.textContent = formatMeta(movie);
    card.appendChild(meta);

    const actions = document.createElement('div');
    actions.className = 'wl-actions';

    const removeBtn = document.createElement('button');
    removeBtn.className = 'wl-btn';
    removeBtn.type = 'button';
    removeBtn.textContent = 'Remove';
    removeBtn.setAttribute('aria-label', 'Remove ' + (movie.title || 'this movie') + ' from Watch Later');
    removeBtn.addEventListener('click', function () {
        removeMovie(movie, card, removeBtn);
    });
    actions.appendChild(removeBtn);

    // Mark watched button TBD
    //Star Rating TBD -> rate.html

    card.appendChild(actions);
    return card;
}

function buildNoPoster() {
    const box = document.createElement('div');
    box.className = 'wl-poster wl-poster-none';
    box.textContent = 'No poster';
    return box;

}


//if movie has missing info
function formatMeta(movie) {
    const parts = [];

    if (movie.vote_average !== null && movie.vote_average !== undefined) {
        parts.push('Rating: ' + Number(movie.vote_average).toFixed(1));
    }

    //format date
    if (movie.release_date) {
        parts.push(String(movie.release_date).slice(0, 4));
    }

    return parts.length > 0 ? parts.join(' · ') : 'No rating yet';
}

//rm movie
async function removeMovie(movie, card, button) {
    showStatus('');
    button.disabled = true; // double click prevention

    try {
        if (DATA_SOURCE === 'server') {
            const formData = new FormData();
            formData.append('tmdb_id', movie.id);

            const response = await fetch(remove_URL, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
        
            if (response.status === 401) {
                window.location.href = sign_in_URL;
                return;
            }
            
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Unable to remove movie.');
            }
        }   

     
        // server agreed or ****sample mode***** it'll rm card from screen 
        card.classList.add('wl-removing');

        setTimeout(function () {
            card.remove();
            movies = movies.filter(function(saved) {
            return saved.id !== movie.id;
            });
            updateCount();

            if (movies.length === 0 ) {
                showOnly('empty');
            }
        }, prefersReducedMotion ? 0 : FADE_MS);   //reduced motion?

    } catch (error) {
        console.error('Could not remove the movie:', error);
        showStatus('Could not remove the movie. Please try again.');
        button.disabled = false; //cause card stayed so the btn has to work
    }
}

// begin

document.getElementById('wl-retry').addEventListener('click', loadWatchList);

if (DATA_SOURCE === 'sample') {
    bannerEl.hidden = false;
}

loadWatchList();





