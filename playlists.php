<?php
/**
 * playlists.php — User's playlists and saved public playlists.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/playlists.php';

$pageTitle = 'Playlists';
require_once __DIR__ . '/elements/header.php';
?>
<header class="page-heading">
    <h1>Playlists</h1>
    <p>Create playlists or manage ones you saved from the community.</p>
</header>
<?php
block_guest_content('playlists');

$userId = (int) $_SESSION['user_id'];
$owned = get_user_owned_playlists($conn, $userId);
$saved = get_user_saved_playlists($conn, $userId);

$defaultTab = count($owned) === 0 && count($saved) > 0 ? 'saved' : 'mine';
$activeTab = ($_GET['tab'] ?? $defaultTab) === 'saved' ? 'saved' : 'mine';
$openCreateModal = isset($_GET['error']) || isset($_GET['create']);

$inputClass = 'w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green';
$labelClass = 'mb-1 block text-sm font-medium text-spotify-muted';
$hasOwned = count($owned) > 0;
$hasSaved = count($saved) > 0;
?>

<?php if (isset($_GET['created'])): ?>
    <div class="alert">Playlist created.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert">Playlist deleted.</div>
<?php elseif (isset($_GET['unsaved'])): ?>
    <div class="alert">Playlist removed from saved.</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert--error">
        <?php if (($_GET['error'] ?? '') === 'invalid_cover'): ?>
            Cover must be JPG, PNG, GIF, or WebP.
        <?php else: ?>
            Could not create playlist. Please try again.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div data-view-tabs data-view-tabs-param="tab" data-view-tabs-default="<?php echo htmlspecialchars($defaultTab); ?>">
    <nav class="view-tabs" role="tablist" aria-label="Playlist views">
        <button type="button" class="view-tab<?php echo $activeTab === 'mine' ? ' view-tab--active' : ''; ?>" role="tab" data-view-tab="mine" aria-selected="<?php echo $activeTab === 'mine' ? 'true' : 'false'; ?>">
            My playlists
        </button>
        <button type="button" class="view-tab<?php echo $activeTab === 'saved' ? ' view-tab--active' : ''; ?>" role="tab" data-view-tab="saved" aria-selected="<?php echo $activeTab === 'saved' ? 'true' : 'false'; ?>">
            Saved
        </button>
    </nav>

    <section class="view-panel" data-view-panel="mine" role="tabpanel"<?php echo $activeTab !== 'mine' ? ' hidden' : ''; ?>>
        <?php if (!$hasOwned): ?>
            <div class="playlists-empty">
                <button type="button" class="playlist-fab" data-open-create-modal aria-label="Create playlist">+</button>
            </div>
        <?php else: ?>
            <div class="playlists-toolbar">
                <button type="button" class="playlist-create-btn" data-open-create-modal aria-label="Create playlist">
                    <span aria-hidden="true">+</span> New playlist
                </button>
            </div>
            <div class="card-grid">
                <?php foreach ($owned as $playlist): ?>
                    <?php render_playlist_card($conn, $playlist, $userId, false); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="view-panel" data-view-panel="saved" role="tabpanel"<?php echo $activeTab !== 'saved' ? ' hidden' : ''; ?>>
        <?php if (!$hasSaved): ?>
            <p class="home-empty">No saved playlists yet. Browse public playlists on <a href="index.php?view=playlists" class="link-accent">Home</a>.</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($saved as $playlist): ?>
                    <?php render_playlist_card($conn, $playlist, $userId, true); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<dialog id="create-playlist-modal" class="create-playlist-modal"<?php echo $openCreateModal ? ' data-open-on-load="true"' : ''; ?>>
    <div class="create-playlist-modal__inner">
        <div class="create-playlist-modal__header">
            <h2>Create playlist</h2>
            <button type="button" class="create-playlist-modal__close" data-close-create-modal aria-label="Close">&times;</button>
        </div>
        <form method="post" action="playlist_actions.php" enctype="multipart/form-data" class="space-y-4" novalidate>
            <input type="hidden" name="action" value="create">
            <div>
                <label for="playlist-cover" class="<?php echo $labelClass; ?>">Cover image (optional)</label>
                <input type="file" id="playlist-cover" name="cover_image" accept="image/jpeg,image/png,image/gif,image/webp" class="<?php echo $inputClass; ?>">
            </div>
            <div>
                <label for="playlist-name" class="<?php echo $labelClass; ?>">Name</label>
                <input type="text" id="playlist-name" name="name" required maxlength="100" class="<?php echo $inputClass; ?>">
            </div>
            <div>
                <label for="playlist-description" class="<?php echo $labelClass; ?>">Description</label>
                <textarea id="playlist-description" name="description" rows="3" maxlength="500" class="<?php echo $inputClass; ?>"></textarea>
            </div>
            <div>
                <label for="playlist-visibility" class="<?php echo $labelClass; ?>">Visibility</label>
                <select id="playlist-visibility" name="visibility" class="<?php echo $inputClass; ?>">
                    <option value="private">Private — only you can see it</option>
                    <option value="public">Public — shown on Home, others can save it</option>
                </select>
            </div>
            <button type="submit" class="w-full rounded-full bg-spotify-green px-8 py-3 text-sm font-bold text-black hover:bg-spotify-green-hover">
                Create playlist
            </button>
        </form>
    </div>
</dialog>

<script src="assets/js/view-tabs.js" defer></script>
<script src="assets/js/create-playlist-modal.js" defer></script>

<?php require_once __DIR__ . '/elements/footer.php'; ?>
