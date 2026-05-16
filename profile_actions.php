<?php
/**
 * profile_actions.php — Profile picture and account details.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/users.php';

require_login();

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

if ($action === 'update_details') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');

    if (!isset($_POST['name'], $_POST['surname'], $_POST['email'], $_POST['mobile'])
        || $name === '' || $surname === '' || $email === '' || $mobile === '') {
        header('Location: profile.php?details_error=1');
        exit;
    }
    if (strlen($name) > 15 || strlen($surname) > 15 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !preg_match('/^\d{8}$/', $mobile)) {
        header('Location: profile.php?details_error=1');
        exit;
    }

    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'si', $email, $userId);
    mysqli_stmt_execute($stmt);
    if (db_stmt_has_row($stmt)) {
        header('Location: profile.php?details_error=1');
        exit;
    }

    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE mobile = ? AND id != ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'si', $mobile, $userId);
    mysqli_stmt_execute($stmt);
    if (db_stmt_has_row($stmt)) {
        header('Location: profile.php?details_error=1');
        exit;
    }

    if (!update_user_details($conn, $userId, $name, $surname, $email, $mobile)) {
        header('Location: profile.php?details_error=1');
        exit;
    }

    $user = get_user_by_id($conn, $userId);
    if ($user) {
        sync_user_session_from_row($user);
    }

    header('Location: profile.php?details_updated=1');
    exit;
}

if ($action !== 'upload_avatar') {
    header('Location: profile.php');
    exit;
}

if (!isset($_FILES['profile_image'])) {
    header('Location: profile.php?error=invalid_image');
    exit;
}

$path = save_image_upload($_FILES['profile_image'], PROFILE_UPLOAD_DIR, 'user_' . $userId);

if ($path === false) {
    header('Location: profile.php?error=invalid_image');
    exit;
}

if (!update_user_profile_image($conn, $userId, $path)) {
    delete_upload_file($path);
    header('Location: profile.php?error=save_failed');
    exit;
}

sync_profile_image_session($path);
header('Location: profile.php?updated=1');
exit;
