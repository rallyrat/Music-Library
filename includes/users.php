<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/uploads.php';

/**
 * @return array<string, mixed>|null
 */
function get_user_by_id(mysqli $conn, int $userId): ?array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, name, surname, email, mobile, profile_image, role FROM users WHERE id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return $row ?: null;
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_all_users(mysqli $conn): array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, name, surname, email, mobile, profile_image, role FROM users ORDER BY name, surname'
    );
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function create_user(
    mysqli $conn,
    string $name,
    string $surname,
    string $email,
    string $mobile,
    string $hashedPassword
): int|false {
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO users (name, surname, email, mobile, password) VALUES (?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'sssss', $name, $surname, $email, $mobile, $hashedPassword);

    if (!mysqli_stmt_execute($stmt)) {
        return false;
    }

    return (int) mysqli_insert_id($conn);
}

function update_user_profile_image(mysqli $conn, int $userId, string $imagePath): bool
{
    $current = get_user_by_id($conn, $userId);
    if (!$current) {
        return false;
    }

    $stmt = mysqli_prepare($conn, 'UPDATE users SET profile_image = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $imagePath, $userId);
    $ok = mysqli_stmt_execute($stmt);

    if ($ok && !empty($current['profile_image']) && $current['profile_image'] !== $imagePath) {
        delete_upload_file($current['profile_image']);
    }

    return $ok;
}

function update_user_details(
    mysqli $conn,
    int $userId,
    string $name,
    string $surname,
    string $email,
    string $mobile
): bool {
    $stmt = mysqli_prepare(
        $conn,
        'UPDATE users SET name = ?, surname = ?, email = ?, mobile = ? WHERE id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $surname, $email, $mobile, $userId);

    return mysqli_stmt_execute($stmt);
}

function update_user_admin(
    mysqli $conn,
    int $userId,
    string $name,
    string $surname,
    string $email,
    string $mobile,
    string $role
): bool {
    $stmt = mysqli_prepare(
        $conn,
        'UPDATE users SET name = ?, surname = ?, email = ?, mobile = ?, role = ? WHERE id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'sssssi', $name, $surname, $email, $mobile, $role, $userId);

    return mysqli_stmt_execute($stmt);
}

function delete_user(mysqli $conn, int $userId): bool
{
    $user = get_user_by_id($conn, $userId);
    if (!$user) {
        return false;
    }

    $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    $ok = mysqli_stmt_execute($stmt);

    if ($ok && !empty($user['profile_image'])) {
        delete_upload_file($user['profile_image']);
    }

    return $ok;
}

function sync_profile_image_session(?string $path): void
{
    if (is_logged_in()) {
        $_SESSION['profile_image'] = $path ?? '';
    }
}

function sync_user_session_from_row(array $user): void
{
    $_SESSION['name'] = $user['name'];
    $_SESSION['surname'] = $user['surname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['mobile'] = $user['mobile'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['profile_image'] = $user['profile_image'] ?? '';
}
