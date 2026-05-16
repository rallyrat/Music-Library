<?php

/**
 * Genre CRUD for admin and song forms.
 */
require_once __DIR__ . '/db.php';

/**
 * Fetch genre rows using mysqli_stmt_bind_result (assignment requirement).
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_genres_bound(mysqli_stmt $stmt): array
{
    mysqli_stmt_store_result($stmt);

    $id = $name = $description = null;
    mysqli_stmt_bind_result($stmt, $id, $name, $description);

    $rows = [];
    while (mysqli_stmt_fetch($stmt)) {
        $rows[] = [
            'id' => (int) $id,
            'name' => $name,
            'description' => $description,
        ];
    }

    return $rows;
}

function get_all_genres(mysqli $conn): array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, name, description FROM genres ORDER BY name ASC');
    mysqli_stmt_execute($stmt);

    return fetch_genres_bound($stmt);
}

/**
 * @return array<string, mixed>|null
 */
function get_genre_by_id(mysqli $conn, int $genreId): ?array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, name, description FROM genres WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $genreId);
    mysqli_stmt_execute($stmt);

    return db_fetch_one($stmt);
}

function create_genre(mysqli $conn, string $name, string $description): bool
{
    $stmt = mysqli_prepare($conn, 'INSERT INTO genres (name, description) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt, 'ss', $name, $description);

    return mysqli_stmt_execute($stmt);
}

function update_genre(mysqli $conn, int $genreId, string $name, string $description): bool
{
    $stmt = mysqli_prepare($conn, 'UPDATE genres SET name = ?, description = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ssi', $name, $description, $genreId);

    return mysqli_stmt_execute($stmt);
}

function delete_genre(mysqli $conn, int $genreId): bool
{
    $stmt = mysqli_prepare($conn, 'DELETE FROM genres WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $genreId);

    return mysqli_stmt_execute($stmt);
}

function genre_has_songs(mysqli $conn, int $genreId): bool
{
    $stmt = mysqli_prepare($conn, 'SELECT id FROM songs WHERE genre_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $genreId);
    mysqli_stmt_execute($stmt);

    return db_stmt_has_row($stmt);
}
