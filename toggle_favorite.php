<?php
/**
 * toggle_favorite.php — Add or remove a song from the logged-in user's favorites.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/songs.php';

require_login();

$songId = (int) ($_GET['song_id'] ?? 0);
$action = $_GET['action'] ?? 'toggle';
$userId = (int) $_SESSION['user_id'];
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';

$query = toggle_user_favorite($conn, $userId, $songId, $action);
if ($query !== null && str_contains($redirect, 'favorites.php')) {
    $redirect .= (str_contains($redirect, '?') ? '&' : '?') . $query;
}

header('Location: ' . $redirect);
exit;
