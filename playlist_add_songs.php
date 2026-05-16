<?php
/**
 * playlist_add_songs.php — Search and add songs to a playlist.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/playlists.php';

$playlistId = (int) ($_GET['id'] ?? 0);

$pageTitle = 'Add songs';
require_once __DIR__ . '/elements/header.php';

block_guest_content('adding songs to playlists');

$userId = (int) $_SESSION['user_id'];

if ($playlistId <= 0) {
    header('Location: playlists.php');
    exit;
}

$playlist = get_playlist_by_id($conn, $playlistId);
if (!$playlist || !is_playlist_owner($playlist, $userId)) {
    header('Location: playlists.php');
    exit;
}

$pageTitle = 'Add songs · ' . $playlist['name'];
$allSongs = get_songs_for_playlist_add($conn, $playlistId);

$inputClass = 'w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green';
$labelClass = 'mb-1 block text-sm font-medium text-spotify-muted';
?>

<header class="page-heading">
    <p class="mb-2">
        <a href="playlist.php?id=<?php echo urlencode((string) $playlistId); ?>" class="link-accent">← Back to playlist</a>
    </p>
    <h1>Add songs</h1>
    <p>Search the library and add tracks to <?php echo htmlspecialchars($playlist['name']); ?>.</p>
</header>

<?php if (isset($_GET['added'])): ?>
    <div class="alert">Song added to playlist.</div>
<?php elseif (isset($_GET['already'])): ?>
    <div class="alert">That song is already in this playlist.</div>
<?php endif; ?>

<div class="playlist-add-search mb-6">
    <label for="song-search" class="<?php echo $labelClass; ?>">Search</label>
    <input type="search" id="song-search" class="<?php echo $inputClass; ?>" placeholder="Search by title, artist, or genre…" autocomplete="off">
    <p id="song-search-empty" class="home-empty hidden">No songs match your search.</p>
</div>

<?php if (count($allSongs) === 0): ?>
    <p class="text-spotify-muted">No songs in the library yet. <a href="add_song.php" class="link-accent">Upload a track</a> first.</p>
<?php else: ?>
    <ul id="playlist-add-results" class="playlist-add-list">
        <?php foreach ($allSongs as $song): ?>
            <?php
            $songId = (int) $song['id'];
            $inPlaylist = (int) ($song['in_playlist'] ?? 0) === 1;
            $searchText = strtolower(
                ($song['title'] ?? '') . ' ' . ($song['artist'] ?? '') . ' ' . ($song['genre_name'] ?? '')
            );
            ?>
            <li class="playlist-add-item" data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="playlist-add-item__info">
                    <span class="playlist-add-item__title"><?php echo htmlspecialchars($song['title']); ?></span>
                    <span class="playlist-add-item__meta">
                        <?php echo htmlspecialchars($song['artist']); ?>
                        <?php if (!empty($song['genre_name'])): ?>
                            · <?php echo htmlspecialchars($song['genre_name']); ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($inPlaylist): ?>
                    <span class="playlist-add-item__badge">In playlist</span>
                <?php else: ?>
                    <form method="post" action="playlist_actions.php" class="playlist-add-item__form">
                        <input type="hidden" name="action" value="add_song">
                        <input type="hidden" name="playlist_id" value="<?php echo $playlistId; ?>">
                        <input type="hidden" name="song_id" value="<?php echo $songId; ?>">
                        <input type="hidden" name="redirect" value="add_songs">
                        <button type="submit" class="rounded-full bg-spotify-green px-5 py-2 text-sm font-bold text-black hover:bg-spotify-green-hover">Add</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<script src="assets/js/playlist-add-songs.js" defer></script>

<?php require_once __DIR__ . '/elements/footer.php'; ?>
