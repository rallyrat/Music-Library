<?php
/**
 * admin/actions.php — Admin delete actions (genres, users).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/genres.php';
require_once __DIR__ . '/../includes/users.php';

require_admin();

$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

if ($action === 'delete_genre' && $id > 0) {
    if (genre_has_songs($conn, $id)) {
        header('Location: genres.php?error=in_use');
        exit;
    }
    delete_genre($conn, $id);
    header('Location: genres.php?deleted=1');
    exit;
}

if ($action === 'delete_user' && $id > 0) {
    if ($id === (int) $_SESSION['user_id']) {
        header('Location: users.php?error=1');
        exit;
    }
    if (!delete_user($conn, $id)) {
        header('Location: users.php?error=1');
        exit;
    }
    header('Location: users.php?deleted=1');
    exit;
}

header('Location: index.php');
exit;
