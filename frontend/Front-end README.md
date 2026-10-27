Currently, watchLater, skipped, and favoriteIds live in the memory. Backend should save them
- index.php ignores the genres value that menu sends, so asking for more movie returns same 20. Support for genres and paging would make the "show me more" come to life
- each saved movie has an id, which is the TMDB id, so it fits with the watchlist table's tmdb_id column