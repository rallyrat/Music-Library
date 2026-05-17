<?php
/**
 * playlist_actions.php — Create, save, delete, and modify playlists.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/playlists.php';
require_once __DIR__ . '/includes/uploads.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$isAdmin = is_admin();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$playlistId = (int) ($_GET['playlist_id'] ?? $_POST['playlist_id'] ?? 0);
$songId = (int) ($_GET['song_id'] ?? $_POST['song_id'] ?? 0);
$redirect = safe_redirect_target($_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'playlists.php');

switch ($action) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            break;
        }
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $visibility = ($_POST['visibility'] ?? 'private') === 'public' ? 'public' : 'private';

        if (!isset($_POST['name']) || $name === '' || strlen($name) > 100 || strlen($description) > 500) {
            header('Location: playlists.php?error=create_failed&tab=mine&create=1');
            exit;
        }
        $coverPath = null;

        if (isset($_FILES['cover_image']) && ($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $coverPath = save_image_upload($_FILES['cover_image'], PLAYLIST_COVER_DIR, 'playlist');
            if ($coverPath === false) {
                header('Location: playlists.php?error=invalid_cover&tab=mine&create=1');
                exit;
            }
        }

        $newId = create_playlist($conn, $userId, $name, $description, $visibility, $coverPath);

        if ($newId !== false) {
            header('Location: playlists.php?created=1&tab=mine');
            exit;
        }

        if ($coverPath !== null) {
            delete_upload_file($coverPath);
        }
        header('Location: playlists.php?error=create_failed&tab=mine&create=1');
        exit;

    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $playlistId <= 0) {
            header('Location: playlists.php');
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $visibility = ($_POST['visibility'] ?? 'private') === 'public' ? 'public' : 'private';

        $afterUpdate = safe_redirect_target($_POST['redirect'] ?? 'playlist.php?id=' . $playlistId);
        if (!isset($_POST['name']) || $name === '' || strlen($name) > 100 || strlen($description) > 500
            || !update_playlist($conn, $playlistId, $userId, $name, $description, $visibility, $isAdmin)) {
            header('Location: edit_playlist.php?id=' . $playlistId . '&error=1');
            exit;
        }
        $separator = str_contains($afterUpdate, '?') ? '&' : '?';
        header('Location: ' . $afterUpdate . $separator . 'updated=1');
        exit;

    case 'update_cover':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $playlistId <= 0) {
            header('Location: playlists.php');
            exit;
        }
        if (!isset($_FILES['cover_image'])) {
            header('Location: playlist.php?id=' . $playlistId . '&error=invalid_cover');
            exit;
        }
        $coverPath = save_image_upload($_FILES['cover_image'], PLAYLIST_COVER_DIR, 'playlist_' . $playlistId);
        if ($coverPath === false || !update_playlist_cover($conn, $playlistId, $userId, $coverPath, $isAdmin)) {
            delete_upload_file($coverPath !== false ? $coverPath : null);
            header('Location: playlist.php?id=' . $playlistId . '&error=invalid_cover');
            exit;
        }
        header('Location: playlist.php?id=' . $playlistId . '&cover_updated=1');
        exit;

    case 'delete':
        if ($playlistId > 0 && delete_playlist($conn, $playlistId, $userId, $isAdmin)) {
            $separator = str_contains($redirect, '?') ? '&' : '?';
            header('Location: ' . $redirect . $separator . 'deleted=1');
            exit;
        }
        header('Location: ' . $redirect);
        exit;

    case 'save':
        if ($playlistId > 0) {
            save_public_playlist($conn, $userId, $playlistId);
        }
        header('Location: ' . $redirect);
        exit;

    case 'unsave':
        if ($playlistId > 0) {
            unsave_playlist($conn, $userId, $playlistId);
        }
        header('Location: playlists.php?unsaved=1&tab=saved');
        exit;

    case 'add_song':
        $addResult = -1;
        if ($playlistId > 0 && $songId > 0) {
            $addResult = add_song_to_playlist($conn, $playlistId, $userId, $songId);
        }
        if (($_POST['redirect'] ?? '') === 'add_songs') {
            $query = 'playlist_add_songs.php?id=' . urlencode((string) $playlistId);
            if ($addResult === 1) {
                $query .= '&added=1';
            } elseif ($addResult === 0) {
                $query .= '&already=1';
            }
            header('Location: ' . $query);
            exit;
        }
        if ($addResult === 1) {
            header('Location: playlist.php?id=' . urlencode((string) $playlistId) . '&added=1');
        } else {
            header('Location: playlist.php?id=' . urlencode((string) $playlistId));
        }
        exit;

    case 'remove_song':
        if ($playlistId > 0 && $songId > 0) {
            remove_song_from_playlist($conn, $playlistId, $userId, $songId);
        }
        header('Location: playlist.php?id=' . urlencode((string) $playlistId) . '&removed=1');
        exit;

    default:
        header('Location: playlists.php');
        exit;
}
