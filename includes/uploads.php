<?php

const PROFILE_UPLOAD_DIR = 'uploads/profiles';
/** Playlist cover images stored under uploads/covers/. */
const PLAYLIST_COVER_DIR = 'uploads/covers';

/**
 * Validate an image upload; returns extension or false.
 */
function validate_image_upload(array $file): string|false
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return false;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return false;
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowed, true)) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
        return false;
    }

    return $extension === 'jpeg' ? 'jpg' : $extension;
}

/**
 * Save image to a subdirectory under the project root.
 */
function save_image_upload(array $file, string $uploadDir, string $filenamePrefix): string|false
{
    $extension = validate_image_upload($file);
    if ($extension === false) {
        return false;
    }

    $fullDir = dirname(__DIR__) . '/' . $uploadDir;
    if (!is_dir($fullDir)) {
        mkdir($fullDir, 0755, true);
    }

    $filename = $filenamePrefix . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $destination = $fullDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return false;
    }

    return $uploadDir . '/' . $filename;
}

function delete_upload_file(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    $fullPath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function upload_url(?string $relativePath): ?string
{
    if ($relativePath === null || trim($relativePath) === '') {
        return null;
    }

    return $relativePath;
}

function user_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        if (mb_strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : '?';
}

function render_user_avatar(?string $imagePath, string $displayName, string $size = 'md'): void
{
    $class = 'avatar avatar--' . ($size === 'lg' ? 'lg' : ($size === 'sm' ? 'sm' : 'md'));
    $alt = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
    $url = upload_url($imagePath);

    echo '<span class="' . $class . '" aria-hidden="true">';
    if ($url !== null) {
        echo '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" class="avatar__img">';
    } else {
        echo '<span class="avatar__initials">' . htmlspecialchars(user_initials($displayName)) . '</span>';
    }
    echo '</span>';
}

function render_playlist_cover(?string $imagePath, string $playlistName, string $size = 'card'): void
{
    $class = 'playlist-cover playlist-cover--' . ($size === 'hero' ? 'hero' : 'card');
    $alt = htmlspecialchars($playlistName, ENT_QUOTES, 'UTF-8');
    $url = upload_url($imagePath);

    echo '<span class="' . $class . '">';
    if ($url !== null) {
        echo '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" class="playlist-cover__img">';
    } else {
        echo '<span class="playlist-cover__placeholder" aria-hidden="true">♫</span>';
    }
    echo '</span>';
}

/**
 * Hover overlay for clickable image uploads (playlists, profile, etc.).
 */
function render_image_upload_overlay(string $label): void
{
    $labelText = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    echo '<span class="playlist-cover-edit__overlay">';
    echo '<span class="playlist-cover-edit__overlay-inner">';
    echo '<span class="playlist-cover-edit__icon" aria-hidden="true">';
    echo '<svg width="20" height="20" viewBox="0 0 24 24" focusable="false"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>';
    echo '</span>';
    echo '<span class="playlist-cover-edit__label">' . $labelText . '</span>';
    echo '</span></span>';
}

/**
 * Clickable cover for playlist owners (submits parent form on file select).
 */
function render_playlist_cover_editable(?string $imagePath, string $playlistName, string $size = 'hero'): void
{
    $class = 'playlist-cover playlist-cover--' . ($size === 'hero' ? 'hero' : 'card');
    $alt = htmlspecialchars($playlistName, ENT_QUOTES, 'UTF-8');
    $url = upload_url($imagePath);

    echo '<label class="playlist-cover-edit ' . $class . '" title="Change cover image">';
    echo '<input type="file" name="cover_image" class="playlist-cover-edit__input"';
    echo ' accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Upload playlist cover">';
    if ($url !== null) {
        echo '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" class="playlist-cover__img">';
    } else {
        echo '<span class="playlist-cover__placeholder" aria-hidden="true">♫</span>';
    }
    render_image_upload_overlay('Change cover');
    echo '</label>';
}

/**
 * Clickable profile picture. Submits parent form on select unless $previewOnly is true.
 */
function render_user_avatar_editable(
    ?string $imagePath,
    string $displayName,
    string $size = 'lg',
    bool $previewOnly = false
): void {
    $sizeClass = $size === 'lg' ? 'lg' : ($size === 'sm' ? 'sm' : 'md');
    $alt = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
    $url = upload_url($imagePath);
    $overlayLabel = $url !== null ? 'Change photo' : 'Add photo';

    echo '<label class="avatar-edit playlist-cover-edit avatar avatar--' . $sizeClass . '"';
    echo ' title="' . htmlspecialchars($overlayLabel, ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="file" name="profile_image" class="playlist-cover-edit__input"';
    if ($previewOnly) {
        echo ' data-upload-preview';
    }
    echo ' accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Upload profile picture">';
    if ($url !== null) {
        echo '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" class="avatar__img">';
    } else {
        echo '<span class="avatar__initials">' . htmlspecialchars(user_initials($displayName)) . '</span>';
    }
    render_image_upload_overlay($overlayLabel);
    echo '</label>';
}
