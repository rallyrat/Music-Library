<?php
/**
 * Database connection and prepared-statement helpers.
 * Uses mysqli_stmt_get_result when available; falls back to bind_result (no mysqlnd).
 */
$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) {
    $dbConfig = require $configPath;
    $host = $dbConfig['db_host'] ?? 'localhost';
    $user = $dbConfig['db_user'] ?? 'root';
    $password = $dbConfig['db_pass'] ?? '';
    $database = $dbConfig['db_name'] ?? 'music_library_db';
} else {
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'music_library_db';
}

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die('Database connection failed: ' . htmlspecialchars(mysqli_connect_error()));
}

mysqli_set_charset($conn, 'utf8mb4');

/**
 * Fetch one associative row from an executed SELECT (mysqlnd or bind_result fallback).
 */
function db_fetch_one(mysqli_stmt $stmt): ?array
{
    if (function_exists('mysqli_stmt_get_result')) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($result);

            return $row ?: null;
        }
    }

    if (!mysqli_stmt_store_result($stmt)) {
        return null;
    }

    $meta = mysqli_stmt_result_metadata($stmt);
    if (!$meta) {
        return null;
    }

    $row = [];
    $bindRefs = [];
    while ($field = $meta->fetch_field()) {
        $row[$field->name] = null;
        $bindRefs[] = &$row[$field->name];
    }
    $meta->free();

    call_user_func_array([$stmt, 'bind_result'], $bindRefs);

    if (!mysqli_stmt_fetch($stmt)) {
        return null;
    }

    return $row;
}

/** Return true if the executed SELECT found at least one row. */
function db_stmt_has_row(mysqli_stmt $stmt): bool
{
    return db_fetch_one($stmt) !== null;
}
