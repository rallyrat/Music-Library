<?php
/**
 * library.php — User's uploaded songs
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/songs.php';

$usePlayer = true;
$pageTitle = 'Your Library';
require_once __DIR__ . '/elements/header.php';
?>
<header class="page-heading">
    <h1>Your Library</h1>
    <p>Songs you have uploaded.</p>
</header>
<?php
block_guest_content('your library');

if (isset($_GET['added'])) {
    echo '<div class="alert">Song added successfully.</div>';
} elseif (isset($_GET['updated'])) {
    echo '<div class="alert">Song updated successfully.</div>';
} elseif (isset($_GET['deleted'])) {
    echo '<div class="alert">Song deleted successfully.</div>';
}

echo '<div class="mb-6">';
echo '<a href="add_song.php" class="inline-flex items-center rounded-full bg-spotify-green px-6 py-3 text-sm font-bold text-black transition hover:scale-105 hover:bg-spotify-green-hover">Add Song</a>';
echo '</div>';

$stmt = mysqli_prepare(
    $conn,
        'SELECT s.id, s.title, s.artist, s.file_path, g.name AS genre_name
     FROM songs s
     INNER JOIN genres g ON s.genre_id = g.id
     WHERE s.user_id = ?
     ORDER BY s.created_at DESC'
);
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo '<p class="text-spotify-muted">You have no songs yet. <a href="add_song.php" class="link-accent">Add a song</a> to get started.</p>';
} else {
    echo '<div class="song-list divide-y divide-spotify-elevated rounded-lg bg-spotify-highlight/40">';
    while ($row = mysqli_fetch_assoc($result)) {
        render_song_item($row, true);
    }
    echo '</div>';
}

require_once __DIR__ . '/elements/footer.php';
