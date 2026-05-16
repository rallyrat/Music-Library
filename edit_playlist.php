<?php
/**
 * edit_playlist.php — Edit playlist name, description, and visibility.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/playlists.php';

$playlistId = (int) ($_GET['id'] ?? 0);

$pageTitle = 'Edit playlist';
require_once __DIR__ . '/elements/header.php';

block_guest_content('editing playlists');

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

$pageTitle = 'Edit · ' . $playlist['name'];
$isPublic = is_playlist_public($playlist);

$inputClass = 'w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green';
$labelClass = 'mb-1 block text-sm font-medium text-spotify-muted';
?>

<header class="page-heading">
    <p class="mb-2">
        <a href="playlist.php?id=<?php echo urlencode((string) $playlistId); ?>" class="link-accent">← Back to playlist</a>
    </p>
    <h1>Edit playlist</h1>
    <p>Update the name, description, and visibility. Change the cover on the playlist page.</p>
</header>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert--error">Could not save changes. Check the name and try again.</div>
<?php endif; ?>

<form method="post" action="playlist_actions.php" id="edit-playlist-form" class="max-w-lg space-y-4" novalidate>
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="playlist_id" value="<?php echo $playlistId; ?>">

    <div>
        <label for="playlist-name" class="<?php echo $labelClass; ?>">Name</label>
        <input type="text" id="playlist-name" name="name" required maxlength="100" class="<?php echo $inputClass; ?>"
               value="<?php echo htmlspecialchars($playlist['name'] ?? ''); ?>">
    </div>

    <div>
        <label for="playlist-description" class="<?php echo $labelClass; ?>">Description</label>
        <textarea id="playlist-description" name="description" rows="3" maxlength="500" class="<?php echo $inputClass; ?>"><?php echo htmlspecialchars($playlist['description'] ?? ''); ?></textarea>
    </div>

    <div>
        <label for="playlist-visibility" class="<?php echo $labelClass; ?>">Visibility</label>
        <select id="playlist-visibility" name="visibility" class="<?php echo $inputClass; ?>">
            <option value="private"<?php echo !$isPublic ? ' selected' : ''; ?>>Private — only you can see it</option>
            <option value="public"<?php echo $isPublic ? ' selected' : ''; ?>>Public — shown on Home, others can save it</option>
        </select>
    </div>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit" class="rounded-full bg-spotify-green px-8 py-3 text-sm font-bold text-black hover:bg-spotify-green-hover">
            Save changes
        </button>
        <a href="playlist.php?id=<?php echo urlencode((string) $playlistId); ?>" class="inline-flex items-center rounded-full border border-spotify-elevated px-8 py-3 text-sm font-semibold text-white hover:border-white">
            Cancel
        </a>
    </div>
</form>

<?php require_once __DIR__ . '/elements/footer.php'; ?>
