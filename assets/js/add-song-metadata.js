(function () {
    const fileInput = document.getElementById('audio-file');
    const titleInput = document.getElementById('song-title');
    const artistInput = document.getElementById('song-artist');
    const genreSelect = document.getElementById('song-genre');
    const hintEl = document.getElementById('metadata-hint');

    if (!fileInput || !titleInput || !artistInput) {
        return;
    }

    function setHint(message) {
        if (hintEl) {
            hintEl.textContent = message;
        }
    }

    function parseFilename(filename) {
        const base = filename.replace(/\.[^/.]+$/, '');
        const parts = base.split(' - ');

        if (parts.length >= 2) {
            return {
                artist: parts[0].trim(),
                title: parts.slice(1).join(' - ').trim(),
            };
        }

        return { title: base.trim(), artist: '' };
    }

    function matchGenre(tagGenre) {
        if (!genreSelect || !tagGenre) {
            return;
        }

        const normalized = tagGenre.trim().toLowerCase();
        const options = genreSelect.querySelectorAll('option[value]');

        for (const option of options) {
            if (option.value === '') {
                continue;
            }
            if (option.textContent.trim().toLowerCase() === normalized) {
                genreSelect.value = option.value;
                return;
            }
        }

        for (const option of options) {
            if (option.value === '') {
                continue;
            }
            const label = option.textContent.trim().toLowerCase();
            if (normalized.includes(label) || label.includes(normalized)) {
                genreSelect.value = option.value;
                return;
            }
        }
    }

    function applyMetadata(title, artist, genre) {
        if (title) {
            titleInput.value = title;
        }
        if (artist) {
            artistInput.value = artist;
        }
        if (genre) {
            matchGenre(genre);
        }
    }

    function readWithJsMediaTags(file) {
        if (typeof jsmediatags === 'undefined') {
            return false;
        }

        jsmediatags.read(file, {
            onSuccess: function (tag) {
                const tags = tag.tags || {};
                const title = tags.title || tags.TIT2 || '';
                const artist = tags.artist || tags.TPE1 || '';
                const genre = tags.genre || tags.TCON || '';

                if (title || artist) {
                    applyMetadata(title, artist, genre);
                    setHint('Details loaded from file metadata. You can edit them before saving.');
                } else {
                    const fromName = parseFilename(file.name);
                    applyMetadata(fromName.title, fromName.artist, '');
                    setHint('No metadata tags found. Fields filled from filename — please review.');
                }
            },
            onError: function () {
                const fromName = parseFilename(file.name);
                applyMetadata(fromName.title, fromName.artist, '');
                setHint('Could not read metadata. Fields filled from filename — please review.');
            },
        });

        return true;
    }

    fileInput.addEventListener('change', function () {
        const file = fileInput.files[0];
        if (!file) {
            setHint('');
            return;
        }

        setHint('Reading file metadata…');

        if (!readWithJsMediaTags(file)) {
            const fromName = parseFilename(file.name);
            applyMetadata(fromName.title, fromName.artist, '');
            setHint('Metadata reader unavailable. Fields filled from filename — please review.');
        }
    });
})();
