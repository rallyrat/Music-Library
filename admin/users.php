<?php
/**
 * admin/users.php — List all users.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/users.php';

require_admin();

$pageTitle = 'Admin · Users';
require_once __DIR__ . '/../elements/header.php';

$users = get_all_users($conn);
$currentUserId = (int) $_SESSION['user_id'];
?>
<header class="page-heading">
    <p class="mb-2"><a href="index.php" class="link-accent">← Admin</a></p>
    <h1>Users</h1>
    <p>Manage registered accounts and roles.</p>
</header>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert">User updated.</div>
<?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert">User deleted.</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert--error">Could not complete that action.</div>
<?php endif; ?>

<div class="overflow-x-auto rounded-lg bg-spotify-highlight/40">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-spotify-elevated text-spotify-muted">
            <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Mobile</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr class="border-b border-spotify-elevated">
                    <td class="px-4 py-3 text-white"><?php echo htmlspecialchars(trim($user['name'] . ' ' . $user['surname'])); ?></td>
                    <td class="px-4 py-3 text-spotify-muted"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="px-4 py-3 text-spotify-muted"><?php echo htmlspecialchars($user['mobile']); ?></td>
                    <td class="px-4 py-3 capitalize text-spotify-muted"><?php echo htmlspecialchars($user['role']); ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="user_form.php?id=<?php echo (int) $user['id']; ?>" class="song-icon-btn song-icon-btn--ghost" title="Edit" aria-label="Edit">
                                <svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm14.71-9.04a1.003 1.003 0 0 0 0-1.42l-2.5-2.5a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                            </a>
                            <?php if ((int) $user['id'] !== $currentUserId): ?>
                                <a href="actions.php?action=delete_user&amp;id=<?php echo (int) $user['id']; ?>" class="song-icon-btn song-icon-btn--danger" title="Delete" aria-label="Delete"
                                   onclick="return confirm('Delete this user and all their songs?');">
                                    <svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../elements/footer.php'; ?>
