Currently, watchLater, skipped, and favoriteIds live in the memory. Backend should save them
- callAPI.php has reusable functions for genre filters and paging. The
  frontend can be wired to those functions when preference requests are added.
- each saved movie has an id, which is the TMDB id, so it fits with the watchlist table's tmdb_id column