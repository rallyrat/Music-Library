<?php
/**
 * admin/genre_form.php — Add or edit a genre.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/genres.php';
require_admin();

$genreId = (int) ($_GET['id'] ?? $_POST['genre_id'] ?? 0);
$isEdit = $genreId > 0;
$genre = $isEdit ? get_genre_by_id($conn, $genreId) : null;

if ($isEdit && !$genre) {
    header('Location: genres.php');
    exit;
}

$error = '';
$name = $genre['name'] ?? '';
$description = $genre['description'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_genre'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!isset($_POST['name']) || $name === '' || strlen($name) > 50 || strlen($description) > 255) {
        $error = 'Name is required (max 50 characters).';
    } elseif ($isEdit) {
        if (update_genre($conn, $genreId, $name, $description)) {
            header('Location: genres.php?updated=1');
            exit;
        }
        $error = 'Could not update genre.';
    } else {
        if (create_genre($conn, $name, $description)) {
            header('Location: genres.php?created=1');
            exit;
        }
        $error = 'Could not create genre. Name may already exist.';
    }
}

$pageTitle = $isEdit ? 'Edit genre' : 'Add genre';
require_once __DIR__ . '/../elements/header.php';

$inputClass = 'w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green';
$labelClass = 'mb-1 block text-sm font-medium text-spotify-muted';
?>
<header class="page-heading">
    <p class="mb-2"><a href="genres.php" class="link-accent">← Genres</a></p>
    <h1><?php echo $isEdit ? 'Edit genre' : 'Add genre'; ?></h1>
</header>

<?php if ($error !== ''): ?>
    <div class="alert alert--error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post" id="admin-genre-form" class="mx-auto max-w-lg space-y-4 rounded-xl bg-spotify-highlight p-6">
    <input type="hidden" name="save_genre" value="1">
    <?php if ($isEdit): ?>
        <input type="hidden" name="genre_id" value="<?php echo $genreId; ?>">
    <?php endif; ?>
    <div>
        <label for="genre-name" class="<?php echo $labelClass; ?>">Name</label>
        <input type="text" id="genre-name" name="name" maxlength="50" class="<?php echo $inputClass; ?>"
               value="<?php echo htmlspecialchars($name); ?>">
    </div>
    <div>
        <label for="genre-description" class="<?php echo $labelClass; ?>">Description</label>
        <textarea id="genre-description" name="description" rows="3" class="<?php echo $inputClass; ?>"><?php echo htmlspecialchars($description); ?></textarea>
    </div>
    <button type="submit" class="rounded-full bg-spotify-green px-8 py-3 text-sm font-bold text-black hover:bg-spotify-green-hover">Save</button>
</form>

<?php require_once __DIR__ . '/../elements/footer.php'; ?>
