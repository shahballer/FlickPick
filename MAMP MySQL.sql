CREATE DATABASE Flick_Pick;
USE Flick_Pick;
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SHOW TABLES;
CREATE TABLE watchlist (
    watchlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tmdb_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id),

    UNIQUE(user_id, tmdb_id)
);
SHOW TABLES;
DESCRIBE watchlist;
CREATE TABLE user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50),
    favorite_genre VARCHAR(50),

    FOREIGN KEY (user_id) REFERENCES users(user_id),

    UNIQUE(user_id)
);
SHOW TABLES;
DESCRIBE user_profiles;
