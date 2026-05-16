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

delete_user_song($conn, $songId, $userId);

header('Location: library.php?deleted=1');
exit;
