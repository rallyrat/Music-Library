<?php
/**
 * index.php — Home / Discover
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/songs.php';
require_once __DIR__ . '/includes/playlists.php';

$usePlayer = true;
$pageTitle = 'Home';
require_once __DIR__ . '/elements/header.php';

$isLoggedIn = is_logged_in();
$userId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;
$favoriteIds = $isLoggedIn ? get_user_favorite_ids($conn, $userId) : [];
$publicPlaylists = get_public_playlists_for_home($conn, $userId);

$activeView = ($_GET['view'] ?? 'songs') === 'playlists' ? 'playlists' : 'songs';

$stmt = mysqli_prepare(
    $conn,
    'SELECT s.id, s.title, s.artist, s.file_path, g.name AS genre_name
     FROM songs s
     INNER JOIN genres g ON s.genre_id = g.id
     ORDER BY s.created_at DESC'
);
mysqli_stmt_execute($stmt);
$songsResult = mysqli_stmt_get_result($stmt);
$songCount = mysqli_num_rows($songsResult);
?>

<header class="page-heading">
    <h1>Good evening</h1>
    <p>Browse tracks and public playlists from the community.</p>
</header>

<div data-view-tabs data-view-tabs-param="view" data-view-tabs-default="songs">
    <nav class="view-tabs" role="tablist" aria-label="Home views">
        <button type="button" class="view-tab<?php echo $activeView === 'songs' ? ' view-tab--active' : ''; ?>" role="tab" data-view-tab="songs" aria-selected="<?php echo $activeView === 'songs' ? 'true' : 'false'; ?>">
            Songs
        </button>
        <button type="button" class="view-tab<?php echo $activeView === 'playlists' ? ' view-tab--active' : ''; ?>" role="tab" data-view-tab="playlists" aria-selected="<?php echo $activeView === 'playlists' ? 'true' : 'false'; ?>">
            Playlists
        </button>
    </nav>

    <section class="view-panel" data-view-panel="songs" role="tabpanel"<?php echo $activeView !== 'songs' ? ' hidden' : ''; ?>>
        <?php if ($songCount === 0): ?>
            <p class="home-empty">No songs yet. <a href="add_song.php" class="link-accent">Upload the first track</a>.</p>
        <?php else: ?>
            <div class="song-list divide-y divide-spotify-elevated rounded-lg bg-spotify-highlight/40">
                <?php
                while ($row = mysqli_fetch_assoc($songsResult)) {
                    render_song_item($row, false, $isLoggedIn, $isLoggedIn && is_favorite($favoriteIds, (int) $row['id']));
                }
                ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="view-panel" data-view-panel="playlists" role="tabpanel"<?php echo $activeView !== 'playlists' ? ' hidden' : ''; ?>>
        <?php if (count($publicPlaylists) === 0): ?>
            <p class="home-empty">No public playlists yet. Create one on <a href="playlists.php" class="link-accent">Playlists</a> and set visibility to Public.</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($publicPlaylists as $playlist): ?>
                    <?php render_playlist_card($conn, $playlist, $userId, false); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script src="assets/js/view-tabs.js" defer></script>

<?php
require_once __DIR__ . '/elements/footer.php';
