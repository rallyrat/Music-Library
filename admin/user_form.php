<?php
/**
 * admin/user_form.php — Edit a user account and role.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/users.php';

require_admin();

$userId = (int) ($_GET['id'] ?? $_POST['user_id'] ?? 0);
$user = $userId > 0 ? get_user_by_id($conn, $userId) : null;

if (!$user) {
    header('Location: users.php');
    exit;
}

$error = '';
$form = [
    'name' => $user['name'],
    'surname' => $user['surname'],
    'email' => $user['email'],
    'mobile' => $user['mobile'],
    'role' => $user['role'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $form = [
        'name' => trim($_POST['name'] ?? ''),
        'surname' => trim($_POST['surname'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'mobile' => trim($_POST['mobile'] ?? ''),
        'role' => ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user',
    ];

    if (!isset($_POST['name'], $_POST['surname'], $_POST['email'], $_POST['mobile'], $_POST['role'])
        || $form['name'] === '' || $form['surname'] === '' || $form['email'] === '' || $form['mobile'] === '') {
        $error = 'All fields are required.';
    } elseif (strlen($form['name']) > 15 || strlen($form['surname']) > 15
        || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)
        || !preg_match('/^\d{8}$/', $form['mobile'])) {
        $error = 'Please check name, email, and mobile (8 digits).';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'si', $form['email'], $userId);
        mysqli_stmt_execute($stmt);
        if (db_stmt_has_row($stmt)) {
            $error = 'That email is already in use.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE mobile = ? AND id != ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'si', $form['mobile'], $userId);
            mysqli_stmt_execute($stmt);
            if (db_stmt_has_row($stmt)) {
                $error = 'That mobile number is already in use.';
            } elseif (update_user_admin($conn, $userId, $form['name'], $form['surname'], $form['email'], $form['mobile'], $form['role'])) {
                if ($userId === (int) $_SESSION['user_id']) {
                    $updated = get_user_by_id($conn, $userId);
                    if ($updated) {
                        sync_user_session_from_row($updated);
                    }
                }
                header('Location: users.php?updated=1');
                exit;
            } else {
                $error = 'Could not update user.';
            }
        }
    }
}

$pageTitle = 'Edit user';
require_once __DIR__ . '/../elements/header.php';

$inputClass = 'w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green';
$labelClass = 'mb-1 block text-sm font-medium text-spotify-muted';
?>
<header class="page-heading">
    <p class="mb-2"><a href="users.php" class="link-accent">← Users</a></p>
    <h1>Edit user</h1>
</header>

<?php if ($error !== ''): ?>
    <div class="alert alert--error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post" id="admin-user-form" class="mx-auto max-w-lg space-y-4 rounded-xl bg-spotify-highlight p-6">
    <input type="hidden" name="save_user" value="1">
    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
    <div>
        <label for="name" class="<?php echo $labelClass; ?>">Name</label>
        <input type="text" id="name" name="name" maxlength="15" class="<?php echo $inputClass; ?>" value="<?php echo htmlspecialchars($form['name']); ?>">
    </div>
    <div>
        <label for="surname" class="<?php echo $labelClass; ?>">Surname</label>
        <input type="text" id="surname" name="surname" maxlength="15" class="<?php echo $inputClass; ?>" value="<?php echo htmlspecialchars($form['surname']); ?>">
    </div>
    <div>
        <label for="email" class="<?php echo $labelClass; ?>">Email</label>
        <input type="email" id="email" name="email" class="<?php echo $inputClass; ?>" value="<?php echo htmlspecialchars($form['email']); ?>">
    </div>
    <div>
        <label for="mobile" class="<?php echo $labelClass; ?>">Mobile</label>
        <input type="tel" id="mobile" name="mobile" maxlength="8" class="<?php echo $inputClass; ?>" value="<?php echo htmlspecialchars($form['mobile']); ?>">
    </div>
    <div>
        <label for="role" class="<?php echo $labelClass; ?>">Role</label>
        <select id="role" name="role" class="<?php echo $inputClass; ?>">
            <option value="user"<?php echo $form['role'] === 'user' ? ' selected' : ''; ?>>User</option>
            <option value="admin"<?php echo $form['role'] === 'admin' ? ' selected' : ''; ?>>Admin</option>
        </select>
    </div>
    <button type="submit" class="rounded-full bg-spotify-green px-8 py-3 text-sm font-bold text-black hover:bg-spotify-green-hover">Save</button>
</form>

<?php require_once __DIR__ . '/../elements/footer.php'; ?>
