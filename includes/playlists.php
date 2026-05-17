<?php

require_once __DIR__ . '/uploads.php';

/**
 * Fetch playlist row with owner name.
 *
 * @return array<string, mixed>|null
 */
function get_playlist_by_id(mysqli $conn, int $playlistId): ?array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT p.id, p.user_id, p.name, p.description, p.cover_image, p.visibility, p.created_at,
                u.name AS owner_name, u.surname AS owner_surname, u.profile_image AS owner_profile_image
         FROM playlists p
         INNER JOIN users u ON p.user_id = u.id
         WHERE p.id = ?
         LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $playlistId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return $row ?: null;
}

function is_playlist_owner(array $playlist, int $userId): bool
{
    return (int) ($playlist['user_id'] ?? 0) === $userId;
}

function is_playlist_public(array $playlist): bool
{
    return ($playlist['visibility'] ?? 'private') === 'public';
}

function user_saved_playlist(mysqli $conn, int $userId, int $playlistId): bool
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT 1 FROM saved_playlists WHERE user_id = ? AND playlist_id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $playlistId);
    mysqli_stmt_execute($stmt);

    return (bool) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

function can_view_playlist(mysqli $conn, array $playlist, int $userId): bool
{
    if (is_playlist_owner($playlist, $userId)) {
        return true;
    }
    if (is_playlist_public($playlist)) {
        return true;
    }

    return user_saved_playlist($conn, $userId, (int) $playlist['id']);
}

function playlist_owner_label(array $playlist): string
{
    return trim(($playlist['owner_name'] ?? '') . ' ' . ($playlist['owner_surname'] ?? ''));
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_user_owned_playlists(mysqli $conn, int $userId): array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT p.id, p.user_id, p.name, p.description, p.cover_image, p.visibility, p.created_at,
                (SELECT COUNT(*) FROM playlist_songs ps WHERE ps.playlist_id = p.id) AS song_count
         FROM playlists p
         WHERE p.user_id = ?
         ORDER BY p.created_at DESC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_user_saved_playlists(mysqli $conn, int $userId): array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT p.id, p.name, p.description, p.cover_image, p.visibility, p.user_id, p.created_at,
                u.name AS owner_name, u.surname AS owner_surname, u.profile_image AS owner_profile_image,
                (SELECT COUNT(*) FROM playlist_songs ps WHERE ps.playlist_id = p.id) AS song_count,
                sp.saved_at
         FROM saved_playlists sp
         INNER JOIN playlists p ON sp.playlist_id = p.id
         INNER JOIN users u ON p.user_id = u.id
         WHERE sp.user_id = ?
         ORDER BY sp.saved_at DESC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

/**
 * All public playlists for Home (others first, then current user's).
 *
 * @return array<int, array<string, mixed>>
 */
function get_public_playlists_for_home(mysqli $conn, int $userId): array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT p.id, p.name, p.description, p.cover_image, p.visibility, p.user_id, p.created_at,
                u.name AS owner_name, u.surname AS owner_surname, u.profile_image AS owner_profile_image,
                (SELECT COUNT(*) FROM playlist_songs ps WHERE ps.playlist_id = p.id) AS song_count
         FROM playlists p
         INNER JOIN users u ON p.user_id = u.id
         WHERE p.visibility = \'public\'
         ORDER BY CASE WHEN p.user_id = ? THEN 1 ELSE 0 END, p.created_at DESC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_playlist_songs(mysqli $conn, int $playlistId): array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT s.id, s.title, s.artist, s.file_path, g.name AS genre_name
         FROM playlist_songs ps
         INNER JOIN songs s ON ps.song_id = s.id
         INNER JOIN genres g ON s.genre_id = g.id
         WHERE ps.playlist_id = ?
         ORDER BY ps.added_at ASC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $playlistId);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

/**
 * All library songs with whether they are already on the playlist.
 *
 * @return array<int, array<string, mixed>>
 */
function get_songs_for_playlist_add(mysqli $conn, int $playlistId): array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT s.id, s.title, s.artist, s.file_path, g.name AS genre_name,
                CASE WHEN ps.song_id IS NOT NULL THEN 1 ELSE 0 END AS in_playlist
         FROM songs s
         INNER JOIN genres g ON s.genre_id = g.id
         LEFT JOIN playlist_songs ps ON ps.song_id = s.id AND ps.playlist_id = ?
         ORDER BY s.title ASC, s.artist ASC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $playlistId);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function create_playlist(
    mysqli $conn,
    int $userId,
    string $name,
    string $description,
    string $visibility,
    ?string $coverImage = null
): int|false {
    $visibility = $visibility === 'public' ? 'public' : 'private';

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO playlists (user_id, name, description, cover_image, visibility) VALUES (?, ?, ?, ?, ?)'
    );
    $coverForDb = $coverImage ?? '';
    mysqli_stmt_bind_param($stmt, 'issss', $userId, $name, $description, $coverForDb, $visibility);

    if (!mysqli_stmt_execute($stmt)) {
        return false;
    }

    return (int) mysqli_insert_id($conn);
}

function update_playlist(
    mysqli $conn,
    int $playlistId,
    int $userId,
    string $name,
    string $description,
    string $visibility,
    bool $isAdmin = false
): bool {
    $playlist = get_playlist_by_id($conn, $playlistId);
    if (!$playlist || (!is_playlist_owner($playlist, $userId) && !$isAdmin)) {
        return false;
    }

    $name = trim($name);
    if ($name === '') {
        return false;
    }

    $visibility = $visibility === 'public' ? 'public' : 'private';

  if ($isAdmin) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE playlists SET name = ?, description = ?, visibility = ? WHERE id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $description, $visibility, $playlistId);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE playlists SET name = ?, description = ?, visibility = ? WHERE id = ? AND user_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'sssii', $name, $description, $visibility, $playlistId, $userId);
    }

    return mysqli_stmt_execute($stmt);
}

function update_playlist_cover(mysqli $conn, int $playlistId, int $userId, string $coverPath, bool $isAdmin = false): bool
{
    $playlist = get_playlist_by_id($conn, $playlistId);
    if (!$playlist || (!is_playlist_owner($playlist, $userId) && !$isAdmin)) {
        return false;
    }

    if ($isAdmin) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE playlists SET cover_image = ? WHERE id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'si', $coverPath, $playlistId);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE playlists SET cover_image = ? WHERE id = ? AND user_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'sii', $coverPath, $playlistId, $userId);
    }
    $ok = mysqli_stmt_execute($stmt);

    if ($ok && !empty($playlist['cover_image']) && $playlist['cover_image'] !== $coverPath) {
        delete_upload_file($playlist['cover_image']);
    }

    return $ok;
}

function delete_playlist(mysqli $conn, int $playlistId, int $userId, bool $isAdmin = false): bool
{
    $playlist = get_playlist_by_id($conn, $playlistId);
    if (!$playlist || (!is_playlist_owner($playlist, $userId) && !$isAdmin)) {
        return false;
    }

    if ($isAdmin) {
        $stmt = mysqli_prepare($conn, 'DELETE FROM playlists WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $playlistId);
    } else {
        $stmt = mysqli_prepare($conn, 'DELETE FROM playlists WHERE id = ? AND user_id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $playlistId, $userId);
    }
    $ok = mysqli_stmt_execute($stmt);

    if ($ok && !empty($playlist['cover_image'])) {
        delete_upload_file($playlist['cover_image']);
    }

    return $ok;
}

function save_public_playlist(mysqli $conn, int $userId, int $playlistId): bool
{
    $playlist = get_playlist_by_id($conn, $playlistId);
    if (!$playlist || !is_playlist_public($playlist) || is_playlist_owner($playlist, $userId)) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT IGNORE INTO saved_playlists (user_id, playlist_id) VALUES (?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $playlistId);

    return mysqli_stmt_execute($stmt);
}

function unsave_playlist(mysqli $conn, int $userId, int $playlistId): bool
{
    $stmt = mysqli_prepare(
        $conn,
        'DELETE FROM saved_playlists WHERE user_id = ? AND playlist_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $playlistId);

    return mysqli_stmt_execute($stmt);
}

/**
 * @return int 1 if added, 0 if already in playlist, -1 on failure
 */
function add_song_to_playlist(mysqli $conn, int $playlistId, int $userId, int $songId): int
{
    $playlist = get_playlist_by_id($conn, $playlistId);
    if (!$playlist || !is_playlist_owner($playlist, $userId)) {
        return -1;
    }

    $check = mysqli_prepare($conn, 'SELECT id FROM songs WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($check, 'i', $songId);
    mysqli_stmt_execute($check);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($check))) {
        return -1;
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT IGNORE INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $playlistId, $songId);
    if (!mysqli_stmt_execute($stmt)) {
        return -1;
    }

    return mysqli_stmt_affected_rows($stmt) > 0 ? 1 : 0;
}

function remove_song_from_playlist(mysqli $conn, int $playlistId, int $userId, int $songId): bool
{
    $playlist = get_playlist_by_id($conn, $playlistId);
    if (!$playlist || !is_playlist_owner($playlist, $userId)) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conn,
        'DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $playlistId, $songId);

    return mysqli_stmt_execute($stmt);
}

function render_playlist_card(
    mysqli $conn,
    array $playlist,
    int $userId,
    bool $isSavedList = false,
    bool $showAdminManage = false,
    string $manageRedirect = 'index.php'
): void {
    $id = (int) $playlist['id'];
    $isOwner = (int) ($playlist['user_id'] ?? 0) === $userId;
    $isPublic = ($playlist['visibility'] ?? '') === 'public';
    $alreadySaved = user_saved_playlist($conn, $userId, $id);

    $coverPath = $playlist['cover_image'] ?? null;
    $playlistUrl = 'playlist.php?id=' . urlencode((string) $id);

    echo '<article class="playlist-card">';
    echo '<div class="playlist-card__layout">';
    echo '<a href="' . $playlistUrl . '" class="playlist-card__media" aria-hidden="true" tabindex="-1">';
    render_playlist_cover($coverPath, $playlist['name']);
    echo '</a>';
    echo '<div class="playlist-card__body">';
    echo '<h3 class="playlist-card__title">';
    echo '<a href="' . $playlistUrl . '">';
    echo htmlspecialchars($playlist['name']);
    echo '</a></h3>';

    if (!empty($playlist['description'])) {
        echo '<p class="playlist-card__desc">' . htmlspecialchars($playlist['description']) . '</p>';
    }

    echo '<p class="playlist-card__meta">';
    echo 'By ' . htmlspecialchars(playlist_owner_label($playlist));
    echo ' · ' . (int) ($playlist['song_count'] ?? 0) . ' songs';
    echo ' · ' . ($isPublic ? 'Public' : 'Private');
    echo '</p>';

    echo '<div class="playlist-card__actions">';

    if ($isSavedList) {
        echo '<a href="playlist_actions.php?action=unsave&amp;playlist_id=' . urlencode((string) $id) . '"';
        echo ' class="song-icon-btn song-icon-btn--danger" aria-label="Remove from saved" title="Remove from saved"';
        echo ' onclick="return confirm(\'Remove from saved playlists?\');">';
        echo '<svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
        echo '</a>';
    } elseif ($isPublic && !$isOwner) {
        if ($alreadySaved) {
            echo '<span class="playlist-card__saved text-sm">Saved</span>';
        } else {
            echo '<a href="playlist_actions.php?action=save&amp;playlist_id=' . urlencode((string) $id) . '" class="text-sm link-accent">Save playlist</a>';
        }
    }

    if (($isOwner && !$isSavedList) || $showAdminManage) {
        $redirectParam = $manageRedirect !== '' && $manageRedirect !== 'playlists.php'
            ? '&amp;redirect=' . urlencode($manageRedirect)
            : '';
        echo '<a href="edit_playlist.php?id=' . urlencode((string) $id) . $redirectParam . '" class="song-icon-btn song-icon-btn--ghost"';
        echo ' aria-label="Edit playlist" title="Edit">';
        echo '<svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm14.71-9.04a1.003 1.003 0 0 0 0-1.42l-2.5-2.5a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
        echo '</a>';
        echo '<a href="playlist_actions.php?action=delete&amp;playlist_id=' . urlencode((string) $id) . $redirectParam . '"';
        echo ' class="song-icon-btn song-icon-btn--danger" aria-label="Delete playlist" title="Delete"';
        echo ' onclick="return confirm(\'Delete this playlist?\');">';
        echo '<svg class="song-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
        echo '</a>';
    }

    echo '</div></div></div></article>';
}
