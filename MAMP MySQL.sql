
-- ==========================================
-- FLICKPICK DATABASE SETUP
-- Creates the database and all required tables
-- ==========================================

CREATE DATABASE IF NOT EXISTS Flick_Pick;

USE Flick_Pick;


-- ==========================================
-- USERS TABLE
-- Stores registered user accounts
-- ==========================================

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==========================================
-- WATCHLIST TABLE
-- Stores movies users want to watch
-- ==========================================

CREATE TABLE IF NOT EXISTS watchlist (
    watchlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tmdb_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    UNIQUE(user_id, tmdb_id)
);


-- ==========================================
-- USER PROFILES TABLE
-- Stores additional user preferences
-- Each user can have one profile
-- ==========================================

CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50),
    favorite_genre VARCHAR(50),

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    UNIQUE(user_id)
);


-- ==========================================
-- SEEN MOVIES TABLE
-- Stores movies users have already swiped on
-- ==========================================

CREATE TABLE IF NOT EXISTS seen_movies (
    seen_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tmdb_id INT NOT NULL,
    seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    UNIQUE(user_id, tmdb_id)
);


-- ==========================================
-- DATABASE VERIFICATION
-- Run these commands to check the tables
-- ==========================================

SHOW TABLES;

DESCRIBE users;
DESCRIBE watchlist;
DESCRIBE user_profiles;
DESCRIBE seen_movies;
