<?php

require_once __DIR__ . '/db.php';

/** Audio files stored under uploads/songs/ (assignment folder name). */
const SONG_UPLOAD_DIR = 'uploads/songs';

/**
 * Validate an uploaded audio file; returns extension or false.
 */
function validate_audio_upload(array $file): string|false
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return false;
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowed = ['mp3', 'wav', 'ogg', 'm4a'];

    return in_array($extension, $allowed, true) ? $extension : false;
}

/**
 * Save upload to uploads/audio/ and return relative path (e.g. uploads/audio/song_xxx.mp3).
 */
function save_audio_upload(array $file): string|false
{
    $extension = validate_audio_upload($file);
    if ($extension === false) {
        return false;
    }

    $uploadDir = dirname(__DIR__) . '/' . SONG_UPLOAD_DIR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('song_', true) . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return false;
    }

    return SONG_UPLOAD_DIR . '/' . $filename;
}

/**
 * Delete an audio file from disk if it exists.
 */
function delete_audio_file(?string $relativePath): void
{
    if ($relativePath === null || $relativePath === '') {
        return;
    }

    $fullPath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

/**
 * Fetch a song only if it belongs to the given user.
 *
 * @return array<string, mixed>|null
 */
function get_user_song(mysqli $conn, int $songId, int $userId): ?array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, title, artist, genre_id, user_id, file_path FROM songs WHERE id = ? AND user_id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $songId, $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return $row ?: null;
}

/**
 * Insert a song row with a local file path.
 */
function insert_song(
    mysqli $conn,
    string $title,
    string $artist,
    int $genreId,
    int $userId,
    string $filePath
): bool {
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO songs (title, artist, genre_id, user_id, file_path) VALUES (?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ssiis', $title, $artist, $genreId, $userId, $filePath);

    return mysqli_stmt_execute($stmt);
}

/**
 * Update song metadata; pass $newFilePath to replace audio (deletes old file).
 */
function update_song(
    mysqli $conn,
    int $songId,
    int $userId,
    string $title,
    string $artist,
    int $genreId,
    ?string $newFilePath,
    ?string $oldFilePath
): bool {
    if ($newFilePath !== null) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE songs SET title = ?, artist = ?, genre_id = ?, file_path = ? WHERE id = ? AND user_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ssisii', $title, $artist, $genreId, $newFilePath, $songId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        if ($ok && $oldFilePath !== null && $oldFilePath !== $newFilePath) {
            delete_audio_file($oldFilePath);
        }
        return $ok;
    }

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE songs SET title = ?, artist = ?, genre_id = ? WHERE id = ? AND user_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'ssiii', $title, $artist, $genreId, $songId, $userId);

    return mysqli_stmt_execute($stmt);
}

/**
 * Delete song file and database row for the owning user.
 */
function delete_user_song(mysqli $conn, int $songId, int $userId): bool
{
    $song = get_user_song($conn, $songId, $userId);
    if (!$song) {
        return false;
    }

    $stmt = mysqli_prepare($conn, 'DELETE FROM songs WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $songId, $userId);
    $ok = mysqli_stmt_execute($stmt);

    if ($ok) {
        delete_audio_file($song['file_path'] ?? null);
    }

    return $ok;
}

/**
 * Render a song row with details and a Play button for the footer player.
 */
function render_song_item(
    array $song,
    bool $showManageLinks = false,
    bool $showFavorite = false,
    bool $isFavorited = false,
    int $removeFromPlaylistId = 0
): void {
    $title = htmlspecialchars($song['title'] ?? '');
    $artist = htmlspecialchars($song['artist'] ?? '');
    $genre = htmlspecialchars($song['genre_name'] ?? $song['genre'] ?? '');
    $id = (int) ($song['id'] ?? 0);
    $src = htmlspecialchars($song['file_path'] ?? '');

    echo '<article class="song-item group flex items-center gap-4 rounded-md px-4 py-3 transition-colors hover:bg-white/10">';
    echo '<div class="song-item__details min-w-0 flex-1">';
    echo '<h3 class="truncate text-base font-medium text-white">' . $title . '</h3>';
    echo '<p class="truncate text-sm text-spotify-muted">' . $artist;
    if ($genre !== '') {
        echo ' · ' . $genre;
    }
    echo '</p>';
    echo '</div>';

    echo '<div class="song-item__actions flex flex-shrink-0 items-center gap-2">';
    echo '<button type="button" class="play-song-btn song-icon-btn song-icon-btn--play"';
    echo ' data-song-id="' . $id . '"';
    echo ' data-src="' . $src . '"';
    echo ' data-title="' . $title . '"';
    echo ' data-artist="' . $artist . '"';
    echo ' aria-label="Play" title="Play">';
    echo '<svg class="song-icon song-icon--play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7L8 5z"/></svg>';
    echo '<svg class="song-icon song-icon--pause is-hidden" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 5h4v14H6V5zm8 0h4v14h-4V5z"/></svg>';
    echo '</button>';

    if ($showFavorite && $id > 0) {
        if ($isFavorited) {
            echo '<a href="toggle_favorite.php?song_id=' . urlencode((string) $id) . '&action=remove"';
            echo ' class="favorite-btn flex h-9 w-9 items-center justify-center rounded-full border border-red-500/50 bg-red-500/10 text-lg text-red-400 transition hover:scale-105" title="Remove from favorites">♥</a>';
        } else {
            echo '<a href="toggle_favorite.php?song_id=' . urlencode((string) $id) . '&action=add"';
            echo ' class="favorite-btn flex h-9 w-9 items-center justify-center rounded-full border border-spotify-elevated text-lg text-spotify-muted transition hover:scale-105 hover:border-white hover:text-white" title="Add to favorites">♡</a>';
        }
    }

    if ($removeFromPlaylistId > 0 && $id > 0) {
        echo '<a href="playlist_actions.php?action=remove_song&amp;playlist_id=' . urlencode((string) $removeFromPlaylistId);
        echo '&amp;song_id=' . urlencode((string) $id) . '" class="song-icon-btn song-icon-btn--danger"';
        echo ' aria-label="Remove from playlist" title="Remove from playlist"';
        echo ' onclick="return confirm(\'Remove from playlist?\');">';
        echo '<svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
        echo '</a>';
    }

    if ($showManageLinks && $id > 0) {
        echo '<a href="edit_song.php?id=' . urlencode((string) $id) . '" class="song-icon-btn song-icon-btn--ghost"';
        echo ' aria-label="Edit song" title="Edit">';
        echo '<svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm14.71-9.04a1.003 1.003 0 0 0 0-1.42l-2.5-2.5a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
        echo '</a>';
        echo '<a href="delete_song.php?id=' . urlencode((string) $id) . '" class="song-icon-btn song-icon-btn--danger"';
        echo ' aria-label="Delete song" title="Delete"';
        echo ' onclick="return confirm(\'Are you sure you want to delete this song?\');">';
        echo '<svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
        echo '</a>';
    }

    echo '</div>';
    echo '</article>';
}

/** @return int[] */
function get_user_favorite_ids(mysqli $conn, int $userId): array
{
    $stmt = mysqli_prepare($conn, 'SELECT song_id FROM favourites WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ids[] = (int) $row['song_id'];
    }

    return $ids;
}

function is_favorite(array $favoriteIds, int $songId): bool
{
    return in_array($songId, $favoriteIds, true);
}

/** Toggle/add/remove favorite; returns query string e.g. "removed=1" when unfavorited. */
function toggle_user_favorite(mysqli $conn, int $userId, int $songId, string $action): ?string
{
    if ($songId <= 0) {
        return null;
    }

    $checkStmt = mysqli_prepare($conn, 'SELECT id FROM songs WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($checkStmt, 'i', $songId);
    mysqli_stmt_execute($checkStmt);
    if (!db_stmt_has_row($checkStmt)) {
        return null;
    }

    $existsStmt = mysqli_prepare(
        $conn,
        'SELECT 1 FROM favourites WHERE user_id = ? AND song_id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($existsStmt, 'ii', $userId, $songId);
    mysqli_stmt_execute($existsStmt);
    $alreadyFavorited = db_stmt_has_row($existsStmt);

    $shouldFavorite = $action === 'add' || ($action === 'toggle' && !$alreadyFavorited);

    if ($shouldFavorite && !$alreadyFavorited) {
        $insertStmt = mysqli_prepare($conn, 'INSERT INTO favourites (user_id, song_id) VALUES (?, ?)');
        mysqli_stmt_bind_param($insertStmt, 'ii', $userId, $songId);
        mysqli_stmt_execute($insertStmt);
    } elseif (!$shouldFavorite && $alreadyFavorited) {
        $deleteStmt = mysqli_prepare($conn, 'DELETE FROM favourites WHERE user_id = ? AND song_id = ?');
        mysqli_stmt_bind_param($deleteStmt, 'ii', $userId, $songId);
        mysqli_stmt_execute($deleteStmt);
    }

    return !$shouldFavorite ? 'removed=1' : null;
}
