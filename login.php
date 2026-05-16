<?php
/**
 * login.php — Session login via email or mobile number.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

redirect_if_logged_in();

$error = '';
$formLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formLogin = trim($_POST['login'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($formLogin === '' || $password === '') {
        $error = 'Email or mobile and password are required.';
    } else {
        $user = login_find_user($conn, $formLogin);

        if ($user && verify_user_password($conn, (int) $user['id'], $password, (string) $user['password'])) {
            login_user_session($user);
            header('Location: index.php');
            exit;
        }

        $error = 'Invalid email or mobile number, or password.';
    }
}

$pageTitle = 'Login';
$usePlayer = false;
require_once __DIR__ . '/elements/header.php';

?>
<header class="page-heading">
    <h1>Log in</h1>
    <p>Welcome back. Sign in with your email or 8-digit mobile number.</p>
</header>
<?php if (isset($_GET['registered'])): ?>
    <div class="alert">Account created. You can log in now.</div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert alert--error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post" action="login.php" id="login-form" class="mx-auto max-w-md space-y-4 rounded-xl bg-spotify-highlight p-6">
    <div>
        <label for="login-identifier" class="mb-1 block text-sm font-medium text-spotify-muted">Email or mobile</label>
        <input type="text" id="login-identifier" name="login" autocomplete="username" required
               class="w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white placeholder:text-spotify-muted focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green"
               placeholder="Email or mobile number"
               value="<?php echo htmlspecialchars($formLogin); ?>">
    </div>
    <div>
        <label for="login-password" class="mb-1 block text-sm font-medium text-spotify-muted">Password</label>
        <input type="password" id="login-password" name="password" autocomplete="current-password" required
               class="w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white placeholder:text-spotify-muted focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green"
               placeholder="Password">
    </div>
    <button type="submit"
            class="w-full rounded-full bg-spotify-green py-3 text-sm font-bold text-black transition hover:scale-[1.02] hover:bg-spotify-green-hover">
        Log in
    </button>
    <p class="text-center text-sm text-spotify-muted">
        Don&apos;t have an account?
        <a href="register.php" class="link-accent">Register</a>
    </p>
</form>

<?php require_once __DIR__ . '/elements/footer.php'; ?>
