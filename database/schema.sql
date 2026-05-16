-- =============================================================================
-- Music Library — complete database schema (fresh install)
-- =============================================================================
-- Import this file once in phpMyAdmin or MySQL CLI.
-- For a clean reinstall, drop database music_library_db first, then import again.
--
-- After import, create writable folders on the server:
--   uploads/profiles/   user avatars
--   uploads/songs/      audio files (MP3, WAV, OGG, M4A)
--   uploads/covers/     playlist cover images
-- =============================================================================

CREATE DATABASE IF NOT EXISTS music_library_db;
USE music_library_db;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(15) NOT NULL,
    surname VARCHAR(15) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(8) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Genres
CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Songs (audio stored as file paths under uploads/songs/)
CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    artist VARCHAR(100) NOT NULL,
    genre_id INT NOT NULL,
    user_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    cover_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Playlists
CREATE TABLE IF NOT EXISTS playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    visibility ENUM('public', 'private') NOT NULL DEFAULT 'private',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Saved public playlists
CREATE TABLE IF NOT EXISTS saved_playlists (
    user_id INT NOT NULL,
    playlist_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, playlist_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE
);

-- Playlist songs
CREATE TABLE IF NOT EXISTS playlist_songs (
    playlist_id INT NOT NULL,
    song_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (playlist_id, song_id),
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
);

-- Favourites
CREATE TABLE IF NOT EXISTS favourites (
    user_id INT NOT NULL,
    song_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, song_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
);

-- Seed genres
INSERT INTO genres (name, description) VALUES
('Pop', 'Popular mainstream music'),
('Rock', 'Rock and alternative tracks'),
('Hip Hop', 'Rap and hip hop beats'),
('Electronic', 'EDM and electronic sounds'),
('Jazz', 'Smooth jazz and classics');

-- Default admin (login: admin@musiclibrary.com or 12345678 | password: admin1)
INSERT INTO users (name, surname, email, mobile, password, role) VALUES
('Admin', 'User', 'admin@musiclibrary.com', '12345678',
 '$2y$10$2nmgDAXOMwy7ddUgwfYyOugZO7I7T.NsZBvX/HU7ebrD6cpkPT4pq', 'admin');
