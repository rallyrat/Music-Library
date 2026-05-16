<?php
/**
 * playlist.php — View a playlist and manage its songs.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/songs.php';
require_once __DIR__ . '/includes/playlists.php';
require_once __DIR__ . '/includes/uploads.php';

$playlistId = (int) ($_GET['id'] ?? 0);

$usePlayer = true;
$pageTitle = 'Playlist';
require_once __DIR__ . '/elements/header.php';

block_guest_content('playlists');

$userId = (int) $_SESSION['user_id'];

if ($playlistId <= 0) {
    header('Location: playlists.php');
    exit;
}

$playlist = get_playlist_by_id($conn, $playlistId);
if (!$playlist || !can_view_playlist($conn, $playlist, $userId)) {
    header('Location: playlists.php');
    exit;
}

$isOwner = is_playlist_owner($playlist, $userId);
$songs = get_playlist_songs($conn, $playlistId);
$favoriteIds = get_user_favorite_ids($conn, $userId);

$pageTitle = $playlist['name'];
$ownerName = playlist_owner_label($playlist);
$ownerAvatar = $playlist['owner_profile_image'] ?? null;
?>
<div class="playlist-hero">
    <?php if ($isOwner): ?>
        <form method="post" action="playlist_actions.php" enctype="multipart/form-data" class="playlist-cover-form">
            <input type="hidden" name="action" value="update_cover">
            <input type="hidden" name="playlist_id" value="<?php echo $playlistId; ?>">
            <?php render_playlist_cover_editable($playlist['cover_image'] ?? null, $playlist['name'], 'hero'); ?>
        </form>
    <?php else: ?>
        <?php render_playlist_cover($playlist['cover_image'] ?? null, $playlist['name'], 'hero'); ?>
    <?php endif; ?>
    <div class="playlist-hero__info">
        <h1><?php echo htmlspecialchars($playlist['name']); ?></h1>
        <p class="playlist-hero__meta">
            <span class="playlist-hero__owner">
                <?php render_user_avatar($ownerAvatar, $ownerName, 'sm'); ?>
                <?php echo htmlspecialchars($ownerName); ?>
            </span>
            · <?php echo is_playlist_public($playlist) ? 'Public' : 'Private'; ?>
            <?php if ($isOwner): ?> · You own this playlist<?php endif; ?>
        </p>
        <?php if (!empty($playlist['description'])): ?>
            <p class="playlist-hero__desc"><?php echo htmlspecialchars($playlist['description']); ?></p>
        <?php endif; ?>
    </div>
    </div>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert">Playlist updated.</div>
<?php elseif (isset($_GET['cover_updated'])): ?>
    <div class="alert">Playlist cover updated.</div>
<?php elseif (isset($_GET['error']) && ($_GET['error'] ?? '') === 'invalid_cover'): ?>
    <div class="alert alert--error">Cover must be JPG, PNG, GIF, or WebP.</div>
<?php endif; ?>

<?php if (isset($_GET['added'])): ?>
    <div class="alert">Song added to playlist.</div>
<?php elseif (isset($_GET['removed'])): ?>
    <div class="alert">Song removed from playlist.</div>
<?php endif; ?>

<?php if (!$isOwner && is_playlist_public($playlist)): ?>
    <?php if (user_saved_playlist($conn, $userId, $playlistId)): ?>
        <p class="mb-4 text-sm text-spotify-green">This playlist is in your saved list.</p>
    <?php else: ?>
        <p class="mb-4">
            <a href="playlist_actions.php?action=save&amp;playlist_id=<?php echo urlencode((string) $playlistId); ?>" class="link-accent">Save this playlist</a>
        </p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($isOwner): ?>
    <p class="mb-6">
        <a href="playlist_add_songs.php?id=<?php echo urlencode((string) $playlistId); ?>" class="inline-flex rounded-full bg-spotify-green px-6 py-3 text-sm font-bold text-black hover:bg-spotify-green-hover">
            Add songs
        </a>
    </p>
<?php endif; ?>

<section>
    <h2 class="mb-4 text-lg font-semibold text-white">Tracks</h2>
    <?php if (count($songs) === 0): ?>
        <p class="text-spotify-muted">
            No songs in this playlist yet.
            <?php if ($isOwner): ?>
                <a href="playlist_add_songs.php?id=<?php echo urlencode((string) $playlistId); ?>" class="link-accent">Add songs</a>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <div class="song-list divide-y divide-spotify-elevated rounded-lg bg-spotify-highlight/40">
            <?php foreach ($songs as $song): ?>
                <?php render_song_item(
                    $song,
                    false,
                    true,
                    is_favorite($favoriteIds, (int) $song['id']),
                    $isOwner ? $playlistId : 0
                ); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<p class="mt-6">
    <a href="playlists.php" class="link-accent">← Back to playlists</a>
</p>

<script src="assets/js/playlist-cover.js" defer></script>

<?php require_once __DIR__ . '/elements/footer.php'; ?>
