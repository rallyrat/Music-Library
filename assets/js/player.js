(function () {
    const STORAGE_KEY = 'musicLibraryPlayer';

    const audio = document.getElementById('player-audio');
    const titleEl = document.getElementById('player-title');
    const artistEl = document.getElementById('player-artist');
    const playBtn = document.getElementById('player-play-pause');
    const iconPlay = document.getElementById('icon-play');
    const iconPause = document.getElementById('icon-pause');
    const prevBtn = document.getElementById('player-prev');
    const nextBtn = document.getElementById('player-next');
    const seekBar = document.getElementById('player-seek');
    const currentTimeEl = document.getElementById('player-current-time');
    const durationEl = document.getElementById('player-duration');
    const volumeBar = document.getElementById('player-volume');
    const playerBar = document.getElementById('player-bar');

    if (!audio || !playBtn) {
        return;
    }

    let queue = [];
    let currentIndex = -1;
    let activeButton = null;
    let saveTimer = null;
    let restoring = false;

    function hasActiveTrack() {
        return !!(audio.dataset.trackSrc && audio.dataset.trackSrc.length > 0);
    }

    function clearStoredState() {
        try {
            sessionStorage.removeItem(STORAGE_KEY);
        } catch (e) {
            /* ignore */
        }
    }

    function clearPlayer() {
        audio.pause();
        delete audio.dataset.trackSrc;
        audio.removeAttribute('src');
        audio.load();
        currentIndex = -1;
        if (activeButton) {
            setSongRowPlayState(activeButton, false);
        }
        activeButton = null;
        setPlayIcon(false);
        if (seekBar) {
            seekBar.value = 0;
            seekBar.max = 0;
        }
        if (currentTimeEl) {
            currentTimeEl.textContent = '0:00';
        }
        if (durationEl) {
            durationEl.textContent = '0:00';
        }
        setPlayerEmpty(true);
    }

    function setPlayerEmpty(empty) {
        if (playerBar) {
            playerBar.classList.toggle('player-bar--empty', empty);
        }
        if (empty) {
            if (titleEl) {
                titleEl.textContent = '';
            }
            if (artistEl) {
                artistEl.textContent = '';
            }
        }
    }

    function setTrackInfo(title, artist) {
        setPlayerEmpty(false);
        if (titleEl) {
            titleEl.textContent = title;
        }
        if (artistEl) {
            artistEl.textContent = artist;
        }
    }

    function formatTime(seconds) {
        if (!Number.isFinite(seconds) || seconds < 0) {
            return '0:00';
        }
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return mins + ':' + String(secs).padStart(2, '0');
    }

    function normalizeSrc(src) {
        if (!src) {
            return '';
        }
        try {
            const path = new URL(src, window.location.href).pathname;
            const uploadsIdx = path.indexOf('/uploads/');
            if (uploadsIdx !== -1) {
                return path.slice(uploadsIdx + 1);
            }
            return path.replace(/^\//, '');
        } catch (e) {
            return src;
        }
    }

    function srcMatches(a, b) {
        return normalizeSrc(a) === normalizeSrc(b);
    }

    function loadStoredState() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function saveState() {
        const trackSrc = audio.dataset.trackSrc || '';
        if (!trackSrc) {
            return;
        }

        const payload = {
            src: trackSrc,
            title: titleEl ? titleEl.textContent : '',
            artist: artistEl ? artistEl.textContent : '',
            currentTime: audio.currentTime || 0,
            playing: !audio.paused,
            volume: audio.volume,
        };

        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
        } catch (e) {
            /* storage full or unavailable */
        }
    }

    function scheduleSave() {
        if (restoring) {
            return;
        }
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveState, 200);
    }

    function setPlayIcon(playing) {
        if (iconPlay && iconPause) {
            iconPlay.classList.toggle('is-hidden', playing);
            iconPause.classList.toggle('is-hidden', !playing);
        }
        playBtn.setAttribute('aria-label', playing ? 'Pause' : 'Play');
        playBtn.setAttribute('title', playing ? 'Pause' : 'Play');
    }

    function getQueueFromPage() {
        return Array.from(document.querySelectorAll('.play-song-btn'));
    }

    function findButtonForSrc(src) {
        const buttons = getQueueFromPage();
        for (let i = 0; i < buttons.length; i += 1) {
            if (srcMatches(buttons[i].dataset.src, src)) {
                return buttons[i];
            }
        }
        return null;
    }

    function setSongRowPlayState(button, playing) {
        if (!button) {
            return;
        }
        button.classList.toggle('is-playing', playing);
        button.setAttribute('aria-label', playing ? 'Pause' : 'Play');
        button.setAttribute('title', playing ? 'Pause' : 'Play');

        const playIcon = button.querySelector('.song-icon--play');
        const pauseIcon = button.querySelector('.song-icon--pause');
        if (playIcon) {
            playIcon.classList.toggle('is-hidden', playing);
        }
        if (pauseIcon) {
            pauseIcon.classList.toggle('is-hidden', !playing);
        }
    }

    function setActiveButton(button, playing) {
        if (activeButton && activeButton !== button) {
            setSongRowPlayState(activeButton, false);
        }
        activeButton = button;
        if (activeButton) {
            setSongRowPlayState(activeButton, !!playing);
        }
    }

    function applyTrackToPlayer(src, title, artist, button, autoplay, seekTime) {
        setTrackInfo(title, artist);
        audio.dataset.trackSrc = src;
        audio.src = src;
        audio.load();
        setActiveButton(button, autoplay);
        setPlayIcon(autoplay);

        const seek = Number(seekTime) || 0;

        function startPlayback() {
            if (seek > 0 && Number.isFinite(audio.duration)) {
                audio.currentTime = Math.min(seek, audio.duration);
            }
            if (autoplay) {
                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(function () {
                        setPlayIcon(false);
                        setSongRowPlayState(activeButton, false);
                        scheduleSave();
                    });
                }
            } else {
                audio.pause();
                setPlayIcon(false);
                setSongRowPlayState(activeButton, false);
            }
            scheduleSave();
        }

        if (audio.readyState >= 1) {
            startPlayback();
        } else {
            audio.addEventListener('loadedmetadata', startPlayback, { once: true });
        }
    }

    function loadTrack(index, autoplay, seekTime) {
        queue = getQueueFromPage();
        if (index < 0 || index >= queue.length) {
            return;
        }

        currentIndex = index;
        const button = queue[index];
        applyTrackToPlayer(
            button.dataset.src,
            button.dataset.title || 'Unknown title',
            button.dataset.artist || 'Unknown artist',
            button,
            autoplay,
            seekTime
        );
    }

    function playTrack(button) {
        queue = getQueueFromPage();
        const index = queue.indexOf(button);
        if (index === -1) {
            return;
        }

        if (currentIndex === index && hasActiveTrack()) {
            if (!audio.paused) {
                audio.pause();
                setPlayIcon(false);
                setSongRowPlayState(activeButton, false);
                scheduleSave();
                return;
            }
            audio.play();
            setPlayIcon(true);
            setActiveButton(button, true);
            scheduleSave();
            return;
        }

        loadTrack(index, true);
    }

    function playPrev() {
        if (!hasActiveTrack()) {
            return;
        }
        queue = getQueueFromPage();
        if (queue.length === 0) {
            return;
        }
        const nextIndex = currentIndex <= 0 ? queue.length - 1 : currentIndex - 1;
        loadTrack(nextIndex, true);
    }

    function playNext() {
        if (!hasActiveTrack()) {
            return;
        }
        queue = getQueueFromPage();
        if (queue.length === 0) {
            return;
        }
        const nextIndex = currentIndex >= queue.length - 1 ? 0 : currentIndex + 1;
        loadTrack(nextIndex, true);
    }

    function restoreFromStorage() {
        const state = loadStoredState();
        if (!state || !state.src) {
            return;
        }

        if (state.title === 'Playback failed') {
            clearStoredState();
            return;
        }

        restoring = true;
        queue = getQueueFromPage();
        const button = findButtonForSrc(state.src);
        currentIndex = button ? queue.indexOf(button) : -1;

        if (volumeBar && state.volume != null) {
            volumeBar.value = state.volume;
            audio.volume = Number(state.volume);
        }

        function onRestoreFailed() {
            restoring = false;
            clearStoredState();
            clearPlayer();
        }

        audio.addEventListener('error', onRestoreFailed, { once: true });
        audio.addEventListener(
            'loadedmetadata',
            function () {
                audio.removeEventListener('error', onRestoreFailed);
                restoring = false;
            },
            { once: true }
        );

        applyTrackToPlayer(
            state.src,
            state.title || 'Unknown title',
            state.artist || 'Unknown artist',
            button,
            !!state.playing,
            state.currentTime
        );
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.play-song-btn');
        if (button) {
            playTrack(button);
        }
    });

    playBtn.addEventListener('click', function () {
        if (!hasActiveTrack()) {
            queue = getQueueFromPage();
            if (queue.length > 0) {
                loadTrack(0, true);
            }
            return;
        }

        if (audio.paused) {
            audio.play();
            setPlayIcon(true);
            setSongRowPlayState(activeButton, true);
        } else {
            audio.pause();
            setPlayIcon(false);
            setSongRowPlayState(activeButton, false);
        }
        scheduleSave();
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', playPrev);
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', playNext);
    }

    audio.addEventListener('loadedmetadata', function () {
        if (seekBar) {
            seekBar.max = Math.floor(audio.duration) || 0;
        }
        if (durationEl) {
            durationEl.textContent = formatTime(audio.duration);
        }
    });

    audio.addEventListener('timeupdate', function () {
        if (seekBar) {
            seekBar.value = Math.floor(audio.currentTime);
        }
        if (currentTimeEl) {
            currentTimeEl.textContent = formatTime(audio.currentTime);
        }
        scheduleSave();
    });

    audio.addEventListener('ended', function () {
        playNext();
    });

    audio.addEventListener('pause', function () {
        if (activeButton) {
            setSongRowPlayState(activeButton, false);
        }
        setPlayIcon(false);
        scheduleSave();
    });

    audio.addEventListener('play', function () {
        if (activeButton) {
            setSongRowPlayState(activeButton, true);
        }
        setPlayIcon(true);
        scheduleSave();
    });

    if (seekBar) {
        seekBar.addEventListener('input', function () {
            audio.currentTime = Number(seekBar.value);
            scheduleSave();
        });
    }

    if (volumeBar) {
        volumeBar.addEventListener('input', function () {
            audio.volume = Number(volumeBar.value);
            scheduleSave();
        });
        audio.volume = volumeBar.value;
    }

    audio.addEventListener('error', function () {
        if (!hasActiveTrack() || restoring) {
            clearStoredState();
            clearPlayer();
            return;
        }
        setPlayIcon(false);
        setTrackInfo('Playback failed', 'Re-upload this song or try another track');
        scheduleSave();
    });

    window.addEventListener('pagehide', saveState);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            saveState();
        }
    });

    setPlayIcon(false);
    clearPlayer();
    restoreFromStorage();
    if (!hasActiveTrack()) {
        clearPlayer();
    }
})();
