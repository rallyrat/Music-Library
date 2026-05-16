# Music Library

A Spotify-style music library web app built with PHP and MySQL. Users can upload songs, build playlists, favorite tracks, and manage their profile. Admins can manage genres and users.

## Requirements

- PHP 8+ with mysqli
- MySQL / MariaDB
- Writable `uploads/` folders for profiles, songs, and playlist covers

## Local setup (XAMPP)

1. Place the project in `htdocs` (e.g. `htdocs/project-main`).
2. Import `database/schema.sql` in phpMyAdmin into `music_library_db`.
3. No `config.php` is required locally — defaults are `localhost`, user `root`, empty password, database `music_library_db`.
4. Ensure these folders exist and are writable: `uploads/profiles/`, `uploads/songs/`, `uploads/covers/`.
5. Open `http://localhost/project-main/` in your browser.

**Default admin:** `admin@musiclibrary.com` or mobile `12345678` — password `admin1`

## Deployment (InfinityFree / PHP hosting)

1. Upload all project files to your hosting `htdocs` folder.
2. In phpMyAdmin, create a database and import **`database/schema.sql`**.
3. Copy `includes/config.example.php` to `includes/config.php` and set your database host, user, password, and database name.
4. Create `uploads/profiles/`, `uploads/songs/`, and `uploads/covers/` on the server (chmod 755).
5. Log in with the default admin credentials above.
6. Change the admin password after first login via **Admin → Users**.

## Project structure

| Path | Purpose |
|------|---------|
| `database/schema.sql` | Full database schema and seed data |
| `includes/` | Database, auth, songs, playlists, users, uploads |
| `admin/` | Admin dashboard (genres, users) |
| `elements/` | Header and footer layout |
| `assets/` | CSS, JavaScript, logo |
