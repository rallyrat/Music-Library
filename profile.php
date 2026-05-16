<?php
/**
 * profile.php — Profile picture and account details.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/users.php';
require_once __DIR__ . '/includes/uploads.php';

$pageTitle = 'Profile';
require_once __DIR__ . '/elements/header.php';

block_guest_content('your profile');

$userId = (int) $_SESSION['user_id'];
$user = get_user_by_id($conn, $userId);
$displayName = trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? ''));
$profileImage = $user['profile_image'] ?? $_SESSION['profile_image'] ?? null;
if ($profileImage === '') {
    $profileImage = null;
}

$inputClass = 'w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green';
$labelClass = 'mb-1 block text-sm font-medium text-spotify-muted';
?>

<header class="page-heading">
    <h1>Profile</h1>
    <p>Your account, profile picture, and contact details.</p>
</header>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert">Profile picture updated.</div>
<?php elseif (isset($_GET['details_updated'])): ?>
    <div class="alert">Account details saved.</div>
<?php elseif (isset($_GET['details_error'])): ?>
    <div class="alert alert--error">Could not save account details. Check your entries.</div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert--error">Could not update profile picture. Use JPG, PNG, GIF, or WebP.</div>
<?php endif; ?>

<div class="profile-panel">
    <form method="post" action="profile_actions.php" enctype="multipart/form-data" class="profile-avatar-form">
        <input type="hidden" name="action" value="upload_avatar">
        <div class="profile-panel__avatar">
            <?php render_user_avatar_editable($profileImage, $displayName !== '' ? $displayName : 'User', 'lg'); ?>
        </div>
    </form>

    <div class="profile-panel__forms space-y-8">
        <form method="post" action="profile_actions.php" id="profile-details-form" class="space-y-4" novalidate>
            <input type="hidden" name="action" value="update_details">
            <h2 class="text-lg font-semibold text-white">Account details</h2>
            <div>
                <label for="profile-name" class="<?php echo $labelClass; ?>">Name</label>
                <input type="text" id="profile-name" name="name" maxlength="15" class="<?php echo $inputClass; ?>"
                       value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
            </div>
            <div>
                <label for="profile-surname" class="<?php echo $labelClass; ?>">Surname</label>
                <input type="text" id="profile-surname" name="surname" maxlength="15" class="<?php echo $inputClass; ?>"
                       value="<?php echo htmlspecialchars($user['surname'] ?? ''); ?>">
            </div>
            <div>
                <label for="profile-email" class="<?php echo $labelClass; ?>">Email</label>
                <input type="email" id="profile-email" name="email" class="<?php echo $inputClass; ?>"
                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
            </div>
            <div>
                <label for="profile-mobile" class="<?php echo $labelClass; ?>">Mobile</label>
                <input type="tel" id="profile-mobile" name="mobile" maxlength="8" class="<?php echo $inputClass; ?>"
                       value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>">
            </div>
            <button type="submit" class="rounded-full bg-spotify-green px-6 py-3 text-sm font-bold text-black hover:bg-spotify-green-hover">
                Save details
            </button>
        </form>

        <dl class="profile-panel__details">
            <div>
                <dt>Role</dt>
                <dd class="capitalize"><?php echo htmlspecialchars($user['role'] ?? ''); ?></dd>
            </div>
        </dl>
    </div>
</div>

<script src="assets/js/playlist-cover.js" defer></script>

<?php require_once __DIR__ . '/elements/footer.php'; ?>
