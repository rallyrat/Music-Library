<?php
/**
 * delete_song.php
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/songs.php';

require_login();

$songId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];
$isAdmin = is_admin();
$redirect = safe_redirect_target($_GET['redirect'] ?? 'library.php');

if ($songId > 0 && delete_user_song($conn, $songId, $userId, $isAdmin)) {
    $separator = str_contains($redirect, '?') ? '&' : '?';
    header('Location: ' . $redirect . $separator . 'deleted=1');
    exit;
}

header('Location: ' . $redirect);
exit;
