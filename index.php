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

$searchQuery = trim($_GET['q'] ?? '');
if ($searchQuery !== '' && isset($_GET['view']) && $_GET['view'] === 'playlists') {
    header('Location: index.php?q=' . rawurlencode($searchQuery));
    exit;
}
$activeView = ($_GET['view'] ?? 'songs') === 'playlists' ? 'playlists' : 'songs';
if ($searchQuery !== '') {
    $activeView = 'songs';
}

$songs = get_discover_songs($conn, $searchQuery);
$songCount = count($songs);
?>

<header class="page-heading">
    <h1>Good evening</h1>
    <p>Browse tracks and public playlists from the community.</p>
</header>

<form method="get" action="index.php" class="search-bar" role="search">
    <input type="hidden" name="view" value="songs">
    <input type="search" id="home-search" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>"
           class="search-bar__input" placeholder="Search songs or artists"
           aria-label="Search songs or artists" autocomplete="off">
    <button type="submit" class="search-bar__submit">Search</button>
    <?php if ($searchQuery !== ''): ?>
        <a href="index.php" class="search-bar__clear">Clear</a>
    <?php endif; ?>
</form>

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
        <?php if ($searchQuery !== ''): ?>
            <p class="home-search-meta">
                <?php echo $songCount === 1 ? '1 result' : (int) $songCount . ' results'; ?>
                for &ldquo;<?php echo htmlspecialchars($searchQuery); ?>&rdquo;
            </p>
        <?php endif; ?>
        <?php if ($songCount === 0): ?>
            <?php if ($searchQuery !== ''): ?>
                <p class="home-empty">No songs or artists match your search. Try different keywords.</p>
            <?php else: ?>
                <p class="home-empty">No songs yet. <a href="add_song.php" class="link-accent">Upload the first track</a>.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="song-list divide-y divide-spotify-elevated rounded-lg bg-spotify-highlight/40">
                <?php
                foreach ($songs as $row) {
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
