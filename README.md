# Music Library

A Spotify-style music library web app built with PHP and MySQL. Users can upload songs, build playlists, favorite tracks, and manage their profile. Admins can manage genres, users, and moderate content on the home page.

## Features

- **Home** — Discover songs and public playlists; search by title or artist; time-based welcome message (**Good Morning** / **Good Evening**) using the visitor’s local browser time
- **Persistent player** — Playback continues across pages (session storage); works from admin pages as well
- **Library** — Upload and manage your own songs (MP3, WAV, OGG, M4A)
- **Metadata on upload** — Title, artist, and genre are read from file tags when possible (ID3, WAV RIFF INFO, ID3-in-WAV); fields remain editable before save
- **Playlists** — Create public or private playlists with optional cover art
- **Favorites** — Save tracks to your favorites list
- **Profile** — Update account details and profile picture (clickable avatar upload)
- **Register** — Optional profile photo with live preview; name/surname limited to letters, spaces, hyphens, and apostrophes
- **Admin** — Manage genres and users; on Home, admins can edit or delete any song or public playlist

## Requirements

- PHP 8+ with mysqli
- MySQL / MariaDB
- Writable `uploads/` folders for profiles, songs, and playlist covers
- Apache with `mod_rewrite` (optional, for `.htaccess` rules)

## Local setup (XAMPP)

1. Place the project in `htdocs` (e.g. `htdocs/project-main`).
2. Import `database/schema.sql` in phpMyAdmin into `music_library_db`.
3. No `config.php` is required locally — defaults are `localhost`, user `root`, empty password, database `music_library_db` (see `includes/db.php`).
4. Ensure these folders exist and are writable: `uploads/profiles/`, `uploads/songs/`, `uploads/covers/`.
5. Open `http://localhost/project-main/` in your browser.

**Default admin**

| Field | Value |
|-------|--------|
| Email | `admin@musiclibrary.com` |
| Mobile | `12345678` |
| Password | `admin1` |

Change the admin password after first login via **Admin → Users**.

## Deployment (cPanel / InfinityFree / PHP hosting)

### Manual upload

1. Upload project files to your web root (e.g. `public_html`).
2. In phpMyAdmin, create a database and import **`database/schema.sql`** (not deployed automatically — keep it local or import separately).
3. Copy `includes/config.example.php` to `includes/config.php` and set database host, user, password, and database name.
4. Ensure `uploads/profiles/`, `uploads/songs/`, and `uploads/covers/` exist on the server and are writable (chmod `755`).
5. Log in with the default admin credentials above.

### Git deployment (`.cpanel.yml`)

If you use cPanel **Git Version Control** with automatic deployment, the repo includes `.cpanel.yml`, which:

- Syncs files to `public_html` with safe permissions (`644` files, `755` directories)
- **Does not** overwrite on the server: `includes/config.php`, `includes/db.php`, or uploaded media under `uploads/`
- **Does not** deploy: `.git`, `database/`, `README.md`, `.cpanel.yml`, `*.zip`, `.vscode/`

After deploy, hard refresh the browser (**Ctrl+F5**) when checking CSS or JavaScript changes.

### PHP / MySQL limits (large uploads)

On XAMPP or shared hosting, you may need to raise limits for bigger audio files:

- **PHP** (`php.ini`): `upload_max_filesize`, `post_max_size` (e.g. `64M`), `memory_limit`, `max_execution_time`
- **MySQL** (`my.ini`): `max_allowed_packet` (e.g. `64M`) for large inserts

Restart Apache and MySQL after changing config.

### SSL (Let’s Encrypt)

The root `.htaccess` includes a rule so `/.well-known/acme-challenge/` is not blocked during certificate validation.

## Project structure

| Path | Purpose |
|------|---------|
| `database/schema.sql` | Full database schema and seed data (import manually; excluded from cPanel deploy) |
| `includes/` | Database (`db.php`), auth, songs, playlists, users, uploads |
| `includes/config.php` | Production DB credentials (gitignored; not deployed from repo) |
| `admin/` | Admin dashboard (genres, users) |
| `elements/` | Shared header, footer, and player bar |
| `assets/css/app.css` | Main styles (loaded after Tailwind CDN) |
| `assets/js/` | Player, form validation, playlist UI, song metadata reader, home greeting |
| `uploads/` | User-uploaded profiles, songs, and playlist covers (gitignored contents) |
| `.cpanel.yml` | cPanel Git deployment tasks |
| `.htaccess` | Directory index, security, ACME challenge passthrough |

## Notes

- **Tailwind CSS** is loaded from the CDN in the header; layout utilities are used alongside `app.css`.
- **Song files** are stored on disk under `uploads/songs/`; paths are saved in the database.
- **WAV metadata** uses a custom browser parser (RIFF INFO / BEXT / embedded ID3); many tools only tag MP3/M4A reliably unless you use a tag editor that writes WAV INFO (e.g. Mp3tag).
