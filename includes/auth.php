<?php
/**
 * Shared session bootstrap for auth-aware pages.
 * Sessions identify logged-in users; role distinguishes admin from regular users.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Block non-admin users from admin pages.
 */
function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Redirect to login — use for actions (POST, toggles, deletes) only.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect_if_logged_in(): void
{
    if (is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Verify password (bcrypt). Upgrades legacy plain-text passwords stored in the DB.
 */
function verify_user_password(mysqli $conn, int $userId, string $plainPassword, string $storedHash): bool
{
    $storedHash = trim($storedHash);
    $plainPassword = trim($plainPassword);

    if ($storedHash !== '' && password_verify($plainPassword, $storedHash)) {
        return true;
    }

    if (strlen($storedHash) < 60 && hash_equals($storedHash, $plainPassword)) {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'si', $newHash, $userId);
        mysqli_stmt_execute($stmt);

        return true;
    }

    return false;
}

/** @param array<string, mixed> $user */
function login_user_session(array $user): void
{
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['surname'] = $user['surname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['mobile'] = $user['mobile'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['profile_image'] = $user['profile_image'] ?? '';
}

/**
 * Find a user by email (case-insensitive) or mobile. Requires db.php loaded first.
 *
 * @return array<string, mixed>|null
 */
function login_find_user(mysqli $conn, string $login): ?array
{
    $login = trim($login);
    if ($login === '') {
        return null;
    }

    $emailLookup = strtolower($login);
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, name, surname, email, mobile, password, role, profile_image
         FROM users
         WHERE LOWER(TRIM(email)) = ? OR TRIM(mobile) = ?
         ORDER BY (role = \'admin\') DESC, id ASC
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $emailLookup, $login);
    if (!mysqli_stmt_execute($stmt)) {
        return null;
    }

    return db_fetch_one($stmt);
}

/**
 * Show sign-in prompt and stop rendering protected page content.
 */
function render_login_required(string $feature = 'this feature'): void
{
    $label = htmlspecialchars($feature, ENT_QUOTES, 'UTF-8');
    echo '<section class="login-required rounded-xl border border-spotify-elevated bg-spotify-highlight p-8 text-center">';
    echo '<h2 class="text-xl font-semibold text-white">Sign in required</h2>';
    echo '<p class="mt-2 text-spotify-muted">Log in or create an account to use ' . $label . '.</p>';
    echo '<div class="mt-6 flex flex-wrap justify-center gap-3">';
    echo '<a href="login.php" class="inline-flex rounded-full bg-spotify-green px-6 py-3 text-sm font-bold text-black hover:bg-spotify-green-hover">Log in</a>';
    echo '<a href="register.php" class="inline-flex rounded-full border border-spotify-elevated px-6 py-3 text-sm font-semibold text-white hover:border-white">Register</a>';
    echo '</div></section>';
}

/**
 * End the page after header if the visitor is not logged in.
 */
function block_guest_content(string $feature): void
{
    if (is_logged_in()) {
        return;
    }

    render_login_required($feature);
    require_once __DIR__ . '/../elements/footer.php';
    exit;
}
