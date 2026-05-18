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
$isAdmin = $isLoggedIn && is_admin();
$userId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;
$favoriteIds = $isLoggedIn ? get_user_favorite_ids($conn, $userId) : [];
$publicPlaylists = get_public_playlists_for_home($conn, $userId);

$searchQuery = trim($_GET['q'] ?? '');
$homeRedirect = 'index.php';
if ($searchQuery !== '') {
    $homeRedirect .= '?q=' . rawurlencode($searchQuery);
}
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

if (isset($_GET['deleted'])) {
    $flashAlert = '<div class="alert">Song deleted.</div>';
} elseif (isset($_GET['updated'])) {
    $flashAlert = '<div class="alert">Song updated.</div>';
} else {
    $flashAlert = '';
}
?>

<header class="page-heading">
    <h1 id="home-greeting">Good Morning</h1>
    <script>
    (function () {
        var hour = new Date().getHours();
        document.getElementById('home-greeting').textContent =
            hour >= 17 ? 'Good Evening' : 'Good Morning';
    })();
    </script>
    <p>Browse tracks and public playlists from the community.</p>
</header>
<?php echo $flashAlert; ?>

<form method="get" action="index.php" class="mb-5 w-full max-w-md" role="search">
    <input type="hidden" name="view" value="songs">
    <div class="flex w-full items-center gap-3 rounded-md border border-spotify-elevated bg-spotify-highlight px-3.5 focus-within:border-spotify-green focus-within:ring-1 focus-within:ring-spotify-green">
        <svg class="h-5 w-5 shrink-0 text-spotify-muted" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
        <input type="search" id="home-search" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>"
               class="min-w-0 flex-1 border-0 bg-transparent py-3 text-sm text-white outline-none placeholder:text-spotify-muted focus:ring-0"
               placeholder="Search songs or artists" aria-label="Search songs or artists" autocomplete="off">
        <?php if ($searchQuery !== ''): ?>
            <a href="index.php" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-spotify-muted hover:bg-spotify-elevated hover:text-white"
               aria-label="Clear search" title="Clear search">
                <svg class="h-4 w-4" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </a>
        <?php endif; ?>
    </div>
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
                    render_song_item(
                        $row,
                        false,
                        $isLoggedIn,
                        $isLoggedIn && is_favorite($favoriteIds, (int) $row['id']),
                        0,
                        $isAdmin,
                        $homeRedirect
                    );
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
                    <?php render_playlist_card($conn, $playlist, $userId, false, $isAdmin, $homeRedirect); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script src="assets/js/view-tabs.js" defer></script>

<?php
require_once __DIR__ . '/elements/footer.php';
