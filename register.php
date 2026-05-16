<?php
/**
 * register.php — New user registration with server-side validation.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

redirect_if_logged_in();

$error = '';
$form = ['name' => '', 'surname' => '', 'email' => '', 'mobile' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'name' => trim($_POST['name'] ?? ''),
        'surname' => trim($_POST['surname'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'mobile' => trim($_POST['mobile'] ?? ''),
    ];
    $plainPassword = $_POST['password'] ?? '';

    if (!isset($_POST['name'], $_POST['surname'], $_POST['email'], $_POST['mobile'], $_POST['password'])
        || $form['name'] === '' || $form['surname'] === '' || $form['email'] === ''
        || $form['mobile'] === '' || $plainPassword === '') {
        $error = 'All fields are required.';
    } elseif (strlen($form['name']) > 15 || strlen($form['surname']) > 15) {
        $error = 'Name and surname must be at most 15 characters.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^\d{8}$/', $form['mobile'])) {
        $error = 'Mobile number must be exactly 8 digits.';
    } elseif (strlen($plainPassword) < 5) {
        $error = 'Password must be at least 5 characters.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $form['email']);
        mysqli_stmt_execute($stmt);
        if (db_stmt_has_row($stmt)) {
            $error = 'That email is already registered.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE mobile = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $form['mobile']);
            mysqli_stmt_execute($stmt);
            if (db_stmt_has_row($stmt)) {
                $error = 'That mobile number is already registered.';
            } else {
                $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare(
                    $conn,
                    'INSERT INTO users (name, surname, email, mobile, password) VALUES (?, ?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param($stmt, 'sssss', $form['name'], $form['surname'], $form['email'], $form['mobile'], $hashedPassword);

                if (mysqli_stmt_execute($stmt)) {
                    header('Location: login.php?registered=1');
                    exit;
                }
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
$usePlayer = false;
require_once __DIR__ . '/elements/header.php';

?>
<header class="page-heading">
    <h1>Create account</h1>
    <p>Join Music Library to upload and favorite tracks.</p>
</header>
<?php if ($error !== ''): ?>
    <div class="alert alert--error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php
$inputClass = 'w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white placeholder:text-spotify-muted focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green';
$labelClass = 'mb-1 block text-sm font-medium text-spotify-muted';
?>

<form method="post" id="register-form" class="mx-auto max-w-md space-y-4 rounded-xl bg-spotify-highlight p-6">
    <div>
        <label for="name" class="<?php echo $labelClass; ?>">Name</label>
        <input type="text" id="name" name="name" maxlength="15" required class="<?php echo $inputClass; ?>" placeholder="Name"
               value="<?php echo htmlspecialchars($form['name']); ?>">
    </div>
    <div>
        <label for="surname" class="<?php echo $labelClass; ?>">Surname</label>
        <input type="text" id="surname" name="surname" maxlength="15" required class="<?php echo $inputClass; ?>" placeholder="Surname"
               value="<?php echo htmlspecialchars($form['surname']); ?>">
    </div>
    <div>
        <label for="email" class="<?php echo $labelClass; ?>">Email</label>
        <input type="email" id="email" name="email" required class="<?php echo $inputClass; ?>" placeholder="Email"
               value="<?php echo htmlspecialchars($form['email']); ?>">
    </div>
    <div>
        <label for="mobile" class="<?php echo $labelClass; ?>">Mobile</label>
        <input type="tel" id="mobile" name="mobile" maxlength="8" required class="<?php echo $inputClass; ?>" placeholder="8 digits"
               value="<?php echo htmlspecialchars($form['mobile']); ?>">
    </div>
    <div>
        <label for="password" class="<?php echo $labelClass; ?>">Password</label>
        <input type="password" id="password" name="password" minlength="5" required class="<?php echo $inputClass; ?>" placeholder="Min. 5 characters">
    </div>
    <button type="submit"
            class="w-full rounded-full bg-spotify-green py-3 text-sm font-bold text-black transition hover:scale-[1.02] hover:bg-spotify-green-hover">
        Register
    </button>
    <p class="text-center text-sm text-spotify-muted">
        Already have an account?
        <a href="login.php" class="link-accent">Log in</a>
    </p>
</form>

<?php require_once __DIR__ . '/elements/footer.php'; ?>
