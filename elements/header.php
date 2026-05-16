<?php
/**
 * header.php
 *
 * Spotify-style layout shell with Tailwind CSS.
 * Set $usePlayer = true before including header/footer to show the player bar.
 */
$usePlayer = $usePlayer ?? true;
$pageTitle = $pageTitle ?? 'Music Library';
$isLoggedIn = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$pathPrefix = (str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/')) ? '../' : '';
if ($isLoggedIn && !function_exists('is_admin')) {
    require_once __DIR__ . '/../includes/auth.php';
}
$showAdminNav = $isLoggedIn && is_admin();
$userDisplayName = trim(($isLoggedIn ? ($_SESSION['name'] ?? '') . ' ' . ($_SESSION['surname'] ?? '') : ''));
$userProfileImage = $isLoggedIn ? ($_SESSION['profile_image'] ?? null) : null;
if ($userProfileImage === '') {
    $userProfileImage = null;
}
if ($isLoggedIn) {
    require_once __DIR__ . '/../includes/uploads.php';
}

if (!function_exists('render_logo_mark')) {
    function render_logo_mark(int $size = 32, string $class = 'site-logo__icon'): void
    {
        $size = max(16, min(64, $size));
        $classAttr = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        echo '<svg class="' . $classAttr . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 48 48" aria-hidden="true" focusable="false">';
        echo '<circle cx="24" cy="24" r="24" fill="#1db954"/>';
        echo '<g fill="#000"><circle cx="11" cy="23" r="8"/><circle cx="37" cy="23" r="8"/><rect x="13" y="9.5" width="22" height="25" rx="11"/></g>';
        echo '<rect x="16" y="19" width="6" height="3" rx="0.75" fill="#1db954"/>';
        echo '<rect x="26" y="19" width="6" height="3" rx="0.75" fill="#1db954"/>';
        echo '<rect x="14" y="27" width="20" height="2.25" rx="0.5" fill="#1db954"/>';
        echo '<rect x="23.5" y="11" width="1" height="22" rx="0.5" fill="#1db954"/>';
        echo '</svg>';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> · Music Library</title>
    <link rel="icon" href="<?php echo $pathPrefix; ?>assets/images/music-library-logo.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        spotify: {
                            black: '#000000',
                            base: '#121212',
                            highlight: '#181818',
                            elevated: '#282828',
                            green: '#1db954',
                            'green-hover': '#1ed760',
                            muted: '#b3b3b3',
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="<?php echo $pathPrefix; ?>assets/css/app.css">
</head>
<body class="h-full bg-spotify-base text-white antialiased<?php echo $usePlayer ? ' has-player' : ''; ?>">
<div class="flex h-full min-h-screen">

    <aside class="hidden w-64 flex-shrink-0 flex-col bg-black px-3 py-6 md:flex">
        <a href="<?php echo $pathPrefix; ?>index.php" class="site-logo mb-8 px-3">
            <?php render_logo_mark(36); ?>
            <span class="site-logo__text">Music Library</span>
        </a>

        <nav class="flex flex-1 flex-col gap-1">
            <a href="<?php echo $pathPrefix; ?>index.php" class="nav-link"<?php echo $currentPage === 'index.php' ? ' aria-current="page"' : ''; ?>>Home</a>
            <a href="<?php echo $pathPrefix; ?>library.php" class="nav-link"<?php echo $currentPage === 'library.php' ? ' aria-current="page"' : ''; ?>>Your Library</a>
            <a href="<?php echo $pathPrefix; ?>favorites.php" class="nav-link"<?php echo $currentPage === 'favorites.php' ? ' aria-current="page"' : ''; ?>>Favorites</a>
            <a href="<?php echo $pathPrefix; ?>playlists.php" class="nav-link"<?php echo in_array($currentPage, ['playlists.php', 'playlist.php', 'playlist_add_songs.php', 'edit_playlist.php'], true) ? ' aria-current="page"' : ''; ?>>Playlists</a>
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo $pathPrefix; ?>profile.php" class="nav-link"<?php echo $currentPage === 'profile.php' ? ' aria-current="page"' : ''; ?>>Profile</a>
            <?php endif; ?>
            <?php if ($showAdminNav): ?>
                <a href="<?php echo $pathPrefix; ?>admin/index.php" class="nav-link"<?php echo str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/') ? ' aria-current="page"' : ''; ?>>Admin</a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-account mt-auto border-t border-spotify-elevated pt-4">
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo $pathPrefix; ?>profile.php" class="sidebar-account__profile nav-link"<?php echo $currentPage === 'profile.php' ? ' aria-current="page"' : ''; ?>>
                    <?php render_user_avatar($userProfileImage, $userDisplayName !== '' ? $userDisplayName : 'User', 'sm'); ?>
                    <span class="sidebar-account__name"><?php echo htmlspecialchars($userDisplayName); ?></span>
                </a>
                <a href="<?php echo $pathPrefix; ?>logout.php" class="nav-link"<?php echo $currentPage === 'logout.php' ? ' aria-current="page"' : ''; ?>>Logout</a>
            <?php else: ?>
                <a href="<?php echo $pathPrefix; ?>login.php" class="nav-link"<?php echo $currentPage === 'login.php' ? ' aria-current="page"' : ''; ?>>Login</a>
            <?php endif; ?>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-spotify-elevated bg-spotify-base/95 px-4 py-3 backdrop-blur md:hidden">
            <a href="<?php echo $pathPrefix; ?>index.php" class="site-logo site-logo--compact">
                <?php render_logo_mark(28); ?>
                <span class="site-logo__text">Music Library</span>
            </a>
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo $pathPrefix; ?>logout.php" class="text-sm text-spotify-muted hover:text-white">Logout</a>
            <?php else: ?>
                <a href="<?php echo $pathPrefix; ?>login.php" class="text-sm text-spotify-muted hover:text-white">Login</a>
            <?php endif; ?>
        </header>

        <nav class="flex gap-2 overflow-x-auto border-b border-spotify-elevated bg-spotify-highlight px-4 py-2 md:hidden">
            <a href="<?php echo $pathPrefix; ?>index.php" class="nav-link whitespace-nowrap"<?php echo $currentPage === 'index.php' ? ' aria-current="page"' : ''; ?>>Home</a>
            <a href="<?php echo $pathPrefix; ?>library.php" class="nav-link whitespace-nowrap"<?php echo $currentPage === 'library.php' ? ' aria-current="page"' : ''; ?>>Library</a>
            <a href="<?php echo $pathPrefix; ?>favorites.php" class="nav-link whitespace-nowrap"<?php echo $currentPage === 'favorites.php' ? ' aria-current="page"' : ''; ?>>Favorites</a>
            <a href="<?php echo $pathPrefix; ?>playlists.php" class="nav-link whitespace-nowrap"<?php echo $currentPage === 'playlists.php' || $currentPage === 'playlist.php' ? ' aria-current="page"' : ''; ?>>Playlists</a>
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo $pathPrefix; ?>profile.php" class="nav-link whitespace-nowrap"<?php echo $currentPage === 'profile.php' ? ' aria-current="page"' : ''; ?>>Profile</a>
            <?php else: ?>
                <a href="<?php echo $pathPrefix; ?>login.php" class="nav-link whitespace-nowrap"<?php echo $currentPage === 'login.php' ? ' aria-current="page"' : ''; ?>>Login</a>
            <?php endif; ?>
        </nav>

        <main class="flex-1 overflow-y-auto bg-gradient-to-b from-spotify-highlight to-spotify-base px-4 py-6 sm:px-8 pb-8">
