<?php
/**
 * favorites.php
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/songs.php';

$usePlayer = true;
$pageTitle = 'Favorites';
require_once __DIR__ . '/elements/header.php';
?>
<header class="page-heading">
    <h1>Favorites</h1>
    <p>Tracks you have saved. Press ♥ to remove.</p>
</header>
<?php
block_guest_content('favorites');

$userId = (int) $_SESSION['user_id'];

if (isset($_GET['removed'])) {
    echo '<div class="alert">Song removed from favorites.</div>';
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT s.id, s.title, s.artist, s.file_path, g.name AS genre_name
     FROM favourites f
     INNER JOIN songs s ON f.song_id = s.id
     INNER JOIN genres g ON s.genre_id = g.id
     WHERE f.user_id = ?
     ORDER BY f.added_at DESC'
);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo '<p class="text-spotify-muted">No favorites yet. Browse <a href="index.php" class="link-accent">Home</a> and tap ♡ on songs you like.</p>';
} else {
    echo '<div class="song-list divide-y divide-spotify-elevated rounded-lg bg-spotify-highlight/40">';
    while ($row = mysqli_fetch_assoc($result)) {
        render_song_item($row, false, true, true);
    }
    echo '</div>';
}

require_once __DIR__ . '/elements/footer.php';
