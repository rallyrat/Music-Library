<?php
/**
 * admin/index.php — Admin dashboard.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_admin();

$pageTitle = 'Admin';
require_once __DIR__ . '/../elements/header.php';
?>
<header class="page-heading">
    <h1>Admin</h1>
    <p>Manage genres and user accounts.</p>
</header>

<div class="card-grid max-w-xl">
    <article class="playlist-card">
        <h3 class="playlist-card__title"><a href="genres.php">Genres</a></h3>
        <p class="playlist-card__desc">Add, edit, and delete music genres.</p>
    </article>
    <article class="playlist-card">
        <h3 class="playlist-card__title"><a href="users.php">Users</a></h3>
        <p class="playlist-card__desc">View, edit roles, and remove user accounts.</p>
    </article>
</div>

<?php require_once __DIR__ . '/../elements/footer.php'; ?>
