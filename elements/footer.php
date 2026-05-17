<?php
/**
 * footer.php — Optional player bar and document close.
 * Set $usePlayer = true before including header on pages with playable songs.
 */
$usePlayer = $usePlayer ?? true;
$pathPrefix = $pathPrefix ?? ((str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/')) ? '../' : '');

if (!isset($appCssVersion)) {
    $appCssPath = __DIR__ . '/../assets/css/app.css';
    $appCssVersion = is_file($appCssPath) ? (string) filemtime($appCssPath) : '1';
}

if ($usePlayer):
?>
<div id="player-bar" class="player-bar player-bar--empty" aria-label="Music player">
    <div class="player-bar__track" id="player-track-info" aria-live="polite">
        <span id="player-title" class="player-bar__title"></span>
        <span id="player-artist" class="player-bar__artist"></span>
    </div>

    <div class="player-bar__center">
        <div class="player-bar__controls">
            <button type="button" id="player-prev" class="player-btn" title="Previous track" aria-label="Previous track">
                <svg class="player-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 6h2v12H6V6zm3.5 6 8.5 6V6l-8.5 6z"/>
                </svg>
            </button>
            <button type="button" id="player-play-pause" class="player-btn player-btn--play" title="Play" aria-label="Play">
                <svg id="icon-play" class="player-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M8 5v14l11-7L8 5z"/>
                </svg>
                <svg id="icon-pause" class="player-icon is-hidden" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 5h4v14H6V5zm8 0h4v14h-4V5z"/>
                </svg>
            </button>
            <button type="button" id="player-next" class="player-btn" title="Next track" aria-label="Next track">
                <svg class="player-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 18l8.5-6L6 6v12zm10-12h2v12h-2V6z"/>
                </svg>
            </button>
        </div>
        <div class="player-bar__progress">
            <span id="player-current-time">0:00</span>
            <input type="range" id="player-seek" min="0" max="0" value="0" aria-label="Seek">
            <span id="player-duration">0:00</span>
        </div>
    </div>

    <div class="player-bar__volume">
        <span class="player-btn player-btn--volume pointer-events-none" aria-hidden="true">
            <svg class="player-icon" viewBox="0 0 24 24">
                <path d="M3 10v4h4l5 5V5L7 10H3zm13.5 2a4.5 4.5 0 0 0-2.5-4.03v8.05a4.5 4.5 0 0 0 2.5-4.02zM14 3.23v2.06a7 7 0 0 1 0 13.94v2.06a9 9 0 0 0 0-18.06z"/>
            </svg>
        </span>
        <input type="range" id="player-volume" min="0" max="1" step="0.01" value="0.8" aria-label="Volume">
    </div>
</div>

<audio id="player-audio" preload="metadata"></audio>
<script src="<?php echo $pathPrefix; ?>assets/js/player.js?v=5"></script>
<?php endif; ?>
<script src="https://unpkg.com/just-validate@4.3.0/dist/just-validate.production.min.js"></script>
<script src="<?php echo $pathPrefix; ?>assets/js/form-validation.js?v=3"></script>
        </main>
    </div>
</div>
<link rel="stylesheet" href="<?php echo $pathPrefix; ?>assets/css/app.css?v=<?php echo htmlspecialchars($appCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</body>
</html>
