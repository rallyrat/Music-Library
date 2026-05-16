<?php
/**
 * admin/genres.php — List and manage genres.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/genres.php';

require_admin();

$pageTitle = 'Admin · Genres';
require_once __DIR__ . '/../elements/header.php';

$genres = get_all_genres($conn);
?>
<header class="page-heading">
    <p class="mb-2"><a href="index.php" class="link-accent">← Admin</a></p>
    <h1>Genres</h1>
    <p>Manage genre categories used when uploading songs.</p>
</header>

<?php if (isset($_GET['created'])): ?>
    <div class="alert">Genre created.</div>
<?php elseif (isset($_GET['updated'])): ?>
    <div class="alert">Genre updated.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert">Genre deleted.</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert--error">
        <?php echo ($_GET['error'] ?? '') === 'in_use' ? 'Cannot delete a genre that has songs.' : 'Could not save genre.'; ?>
    </div>
<?php endif; ?>

<p class="mb-6">
    <a href="genre_form.php" class="inline-flex rounded-full bg-spotify-green px-6 py-3 text-sm font-bold text-black hover:bg-spotify-green-hover">Add genre</a>
</p>

<?php if (count($genres) === 0): ?>
    <p class="text-spotify-muted">No genres yet.</p>
<?php else: ?>
    <div class="overflow-x-auto rounded-lg bg-spotify-highlight/40">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-spotify-elevated text-spotify-muted">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($genres as $genre): ?>
                    <tr class="border-b border-spotify-elevated">
                        <td class="px-4 py-3 font-medium text-white"><?php echo htmlspecialchars($genre['name']); ?></td>
                        <td class="px-4 py-3 text-spotify-muted"><?php echo htmlspecialchars($genre['description'] ?? ''); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a href="genre_form.php?id=<?php echo (int) $genre['id']; ?>" class="song-icon-btn song-icon-btn--ghost" title="Edit" aria-label="Edit">
                                    <svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm14.71-9.04a1.003 1.003 0 0 0 0-1.42l-2.5-2.5a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                </a>
                                <a href="actions.php?action=delete_genre&amp;id=<?php echo (int) $genre['id']; ?>" class="song-icon-btn song-icon-btn--danger" title="Delete" aria-label="Delete"
                                   onclick="return confirm('Delete this genre?');">
                                    <svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../elements/footer.php'; ?>
