# FlickPick

FlickPick will be a web application users can access to browse movies or TV shows as if it were a dating app. By using the “swipe left” or “swipe right” concept, which many users are already accustomed to, we will provide an entertaining method for users to choose a movie. The primary goal of FlickPick is to reduce the decision fatigue many users experience and decrease selection time when choosing what to stream.


<<<<<<<<< Temporary merge branch 1
## Steps to get the database working:

1. Open MAMP and select preferences->ports-> and select the "MAMP default" button to reset the ports back to MAMP defaults. Then start MAMP "Start Servers"
2. Visit the webpage: http://localhost:8888/phpMyAdmin5/index.php?route=/database/structure&server=1&db=flick_pick this takes a moment to load
3. find the "import" option at the top of the page 
4. click the "choose file" button and import the "MAMP MySQL.sql" file
5. ensure the database is working by navigating to register.php in the browser
=========
## To get started developing in this project 

navigate to your C: drive  
find the "MAMP" folder  
Create a folder called "Config"  
Create a file within that folder called "tmdb.php"  
Inside this file paste the following contents and replace 'PASTE_YOUR_TMDB_TOKEN_HERE' with the tmdb api token  

```php
<?php

return [
    'token' => 'PASTE_YOUR_TMDB_TOKEN_HERE',
];
```

Now you must download a bundle of trusted certificate authorities and add them to the php.ini file.  

download the CA bundle from:

https://curl.se/docs/caextract.html

save this to the same config folder (C:\MAMP\Config)  

Navigate to:  
C:\MAMP\conf\php8.3.1\php.ini

add the following lines to the bottom of the file:

```ini
curl.cainfo = "C:\MAMP\Config\cacert.pem"
openssl.cafile = "C:\MAMP\Config\cacert.pem"
```

Mac users:

```ini
curl.cainfo = "/Applications/MAMP/config/cacert.pem"
openssl.cafile = "/Applications/MAMP/config/cacert.pem"
```

## Frontend movie queue

Movie data is available as JSON without exposing the TMDB token to the
frontend:

```
http://localhost/FlickPick/index.php?format=json&limit=20
```

`limit` is optional and is capped at 20. The response contains an ordered
`movies` array. Each item includes the fields needed for a movie card:

- `id`, `title`, `original_title`
- `overview`, `release_date`
- `poster_url`, `backdrop_url`
- `vote_average`, `vote_count`, `popularity`
- `genre_ids`, `original_language`, `adult`

The frontend can keep the response in memory and use an index for swiping:

```js
const response = await fetch('/FlickPick/index.php?format=json&limit=20');
const { movies } = await response.json();
let currentIndex = 0;

const currentMovie = movies[currentIndex];
const nextMovie = () => movies[++currentIndex] ?? null;
```

The API is stateless: a swipe is handled by the frontend, and the next movie
is read from the already-loaded queue. TMDB `genre_ids` can be mapped to names
with TMDB's genre list if genre labels are needed.
>>>>>>>>> Temporary merge branch 2
