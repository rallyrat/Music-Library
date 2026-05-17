<?php
/**
 * add_song.php
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/songs.php';
$error = '';

if (is_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_song'])) {
    $userId = (int) $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $artist = trim($_POST['artist'] ?? '');
    $genreId = (int) ($_POST['genre_id'] ?? 0);

    if (!isset($_POST['title'], $_POST['artist'], $_POST['genre_id']) || $title === '' || $artist === '' || $genreId <= 0) {
        $error = 'Title, artist, and genre are required.';
    } elseif (!isset($_FILES['audio']) || ($_FILES['audio']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $error = 'Please upload an audio file.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM genres WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $genreId);
        mysqli_stmt_execute($stmt);
        if (!db_stmt_has_row($stmt)) {
            $error = 'Please select a valid genre.';
        } else {
            $filePath = save_audio_upload($_FILES['audio']);

            if ($filePath === false) {
                $error = 'Please upload a valid audio file (MP3, WAV, OGG, or M4A).';
            } elseif (insert_song($conn, $title, $artist, $genreId, $userId, $filePath)) {
                header('Location: library.php?added=1');
                exit;
            } else {
                delete_audio_file($filePath);
                $error = 'Failed to save song. Please try again.';
            }
        }
    }
}

$pageTitle = 'Add Song';
require_once __DIR__ . '/elements/header.php';

$genreStmt = mysqli_prepare($conn, 'SELECT id, name FROM genres ORDER BY name');
mysqli_stmt_execute($genreStmt);
$genres = mysqli_stmt_get_result($genreStmt);

?>
<header class="page-heading">
    <h1>Add song</h1>
    <p>Upload a track. Audio is saved in <?php echo htmlspecialchars(SONG_UPLOAD_DIR); ?>.</p>
</header>
<?php block_guest_content('uploading songs'); ?>
<?php if ($error !== ''): ?>
    <div class="alert alert--error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php
$inputClass = 'w-full rounded-md border border-spotify-elevated bg-spotify-base px-4 py-3 text-white file:mr-4 file:rounded-full file:border-0 file:bg-spotify-green file:px-4 file:py-2 file:text-sm file:font-bold file:text-black focus:border-spotify-green focus:outline-none focus:ring-1 focus:ring-spotify-green';
$labelClass = 'mb-1 block text-sm font-medium text-spotify-muted';
?>

<form method="post" enctype="multipart/form-data" id="add-song-form" class="mx-auto max-w-lg space-y-4 rounded-xl bg-spotify-highlight p-6">
    <input type="hidden" name="add_song" value="1">

    <div>
        <label for="audio-file" class="<?php echo $labelClass; ?>">Audio file</label>
        <input type="file" id="audio-file" name="audio" accept="audio/*" required class="<?php echo $inputClass; ?>">
    </div>
    <p id="metadata-hint" class="text-sm text-spotify-green" aria-live="polite"></p>

    <div>
        <label for="song-title" class="<?php echo $labelClass; ?>">Title</label>
        <input type="text" id="song-title" name="title" placeholder="Title" required class="<?php echo $inputClass; ?>">
    </div>
    <div>
        <label for="song-artist" class="<?php echo $labelClass; ?>">Artist</label>
        <input type="text" id="song-artist" name="artist" placeholder="Artist" required class="<?php echo $inputClass; ?>">
    </div>
    <div>
        <label for="song-genre" class="<?php echo $labelClass; ?>">Genre</label>
        <select id="song-genre" name="genre_id" required class="<?php echo $inputClass; ?>">
            <option value="">Select genre</option>
            <?php while ($genre = mysqli_fetch_assoc($genres)): ?>
                <option value="<?php echo (int) $genre['id']; ?>">
                    <?php echo htmlspecialchars($genre['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="flex flex-wrap gap-3 pt-2">
        <button type="submit"
                class="rounded-full bg-spotify-green px-8 py-3 text-sm font-bold text-black transition hover:scale-105 hover:bg-spotify-green-hover">
            Save song
        </button>
        <a href="library.php"
           class="inline-flex items-center rounded-full border border-spotify-elevated px-8 py-3 text-sm font-semibold text-white transition hover:border-white">
            Cancel
        </a>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/jsmediatags@3.9.7/dist/jsmediatags.min.js" defer></script>
<script src="assets/js/add-song-metadata.js?v=4" defer></script>

<?php require_once __DIR__ . '/elements/footer.php'; ?>
