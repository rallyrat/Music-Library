(function () {
    const fileInput = document.getElementById('audio-file');
    const titleInput = document.getElementById('song-title');
    const artistInput = document.getElementById('song-artist');
    const genreSelect = document.getElementById('song-genre');
    const hintEl = document.getElementById('metadata-hint');

    if (!fileInput || !titleInput || !artistInput) {
        return;
    }

    const TAGS_TO_READ = [
        'title',
        'artist',
        'album',
        'genre',
        'TIT2',
        'TPE1',
        'TPE2',
        'TALB',
        'TCON',
        '\u00a9nam',
        '\u00a9ART',
        '\u00a9alb',
        '\u00a9gen',
    ];

    const WAV_SCAN_BYTES = 2 * 1024 * 1024;

    function getJsMediaTags() {
        const lib = window.jsmediatags;
        if (!lib) {
            return null;
        }
        if (lib.Reader) {
            return lib;
        }
        if (lib.default && lib.default.Reader) {
            return lib.default;
        }
        return null;
    }

    function isWavFile(file) {
        return /\.wav$/i.test(file.name) || (file.type && file.type.indexOf('wav') !== -1);
    }

    function setHint(message, isMuted) {
        if (!hintEl) {
            return;
        }
        hintEl.textContent = message;
        hintEl.classList.toggle('text-spotify-green', !isMuted && message !== '');
        hintEl.classList.toggle('text-spotify-muted', isMuted || message === '');
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

    function readFourCc(view, offset) {
        return String.fromCharCode(
            view.getUint8(offset),
            view.getUint8(offset + 1),
            view.getUint8(offset + 2),
            view.getUint8(offset + 3)
        );
    }

    function readChunkText(view, offset, size) {
        if (size <= 0) {
            return '';
        }

        const bytes = new Uint8Array(view.buffer, view.byteOffset + offset, size);

        if (size >= 2 && bytes[0] === 0xff && bytes[1] === 0xfe) {
            try {
                return new TextDecoder('utf-16le')
                    .decode(bytes.subarray(2))
                    .replace(/\0/g, '')
                    .trim();
            } catch (e) {
                return '';
            }
        }

        let text = '';
        for (let i = 0; i < bytes.length; i++) {
            if (bytes[i] === 0) {
                break;
            }
            text += String.fromCharCode(bytes[i]);
        }

        return text.trim();
    }

    function storeInfoField(id, text, target) {
        if (!text) {
            return;
        }

        switch (id.toUpperCase()) {
            case 'INAM':
                target.title = text;
                break;
            case 'IART':
                target.artist = text;
                break;
            case 'IGNR':
                target.genre = text;
                break;
            case 'IPRD':
                if (!target.title) {
                    target.title = text;
                }
                break;
            default:
                break;
        }
    }

    function parseInfoSubchunks(view, start, end, target) {
        let offset = start;

        while (offset + 8 <= end) {
            const subId = readFourCc(view, offset);
            const subSize = view.getUint32(offset + 4, true);
            const dataStart = offset + 8;

            if (dataStart + subSize > end) {
                break;
            }

            storeInfoField(subId, readChunkText(view, dataStart, subSize), target);
            offset += 8 + subSize + (subSize % 2);
        }
    }

    function parseWavChunks(view, start, end, target) {
        let offset = start;

        while (offset + 8 <= end) {
            const chunkId = readFourCc(view, offset);
            const chunkSize = view.getUint32(offset + 4, true);
            const dataStart = offset + 8;
            const dataEnd = dataStart + chunkSize;

            if (dataEnd > end) {
                break;
            }

            if (chunkId === 'LIST' || chunkId === 'list') {
                if (chunkSize >= 4) {
                    const listType = readFourCc(view, dataStart);
                    if (listType === 'INFO' || listType === 'info') {
                        parseInfoSubchunks(view, dataStart + 4, dataEnd, target);
                    }
                }
            }

            offset += 8 + chunkSize + (chunkSize % 2);
        }
    }

    function hasMeta(target) {
        return !!(target && (target.title || target.artist || target.genre));
    }

    function mergeMeta(a, b) {
        const out = { title: '', artist: '', genre: '' };

        [a, b].forEach(function (src) {
            if (!src) {
                return;
            }
            if (!out.title && src.title) {
                out.title = src.title;
            }
            if (!out.artist && src.artist) {
                out.artist = src.artist;
            }
            if (!out.genre && src.genre) {
                out.genre = src.genre;
            }
        });

        return hasMeta(out) ? out : null;
    }

    function scanBufferForListInfo(buffer) {
        const view = new DataView(buffer);
        const target = { title: '', artist: '', genre: '' };

        for (let offset = 0; offset + 12 <= buffer.byteLength; offset++) {
            const chunkId = readFourCc(view, offset);
            if (chunkId !== 'LIST' && chunkId !== 'list') {
                continue;
            }

            const chunkSize = view.getUint32(offset + 4, true);
            const dataStart = offset + 8;
            const dataEnd = dataStart + chunkSize;

            if (chunkSize < 4 || dataEnd > buffer.byteLength) {
                continue;
            }

            const listType = readFourCc(view, dataStart);
            if (listType === 'INFO' || listType === 'info') {
                parseInfoSubchunks(view, dataStart + 4, dataEnd, target);
            }
        }

        return hasMeta(target) ? target : null;
    }

    function scanBufferForBext(buffer) {
        const view = new DataView(buffer);
        const target = { title: '', artist: '', genre: '' };

        for (let offset = 0; offset + 8 <= buffer.byteLength; offset++) {
            const chunkId = readFourCc(view, offset);
            if (chunkId !== 'bext' && chunkId !== 'BEXT') {
                continue;
            }

            const chunkSize = view.getUint32(offset + 4, true);
            const dataStart = offset + 8;

            if (chunkSize < 256 || dataStart + 256 > buffer.byteLength) {
                continue;
            }

            const description = readChunkText(view, dataStart, 256);
            const originator = chunkSize >= 288
                ? readChunkText(view, dataStart + 256, 32)
                : '';

            if (description) {
                target.title = description;
            }
            if (originator) {
                target.artist = originator;
            }

            if (hasMeta(target)) {
                return target;
            }
        }

        return null;
    }

    function extractWavInfoFromBuffer(buffer) {
        if (!buffer || buffer.byteLength < 12) {
            return null;
        }

        const view = new DataView(buffer);
        let target = { title: '', artist: '', genre: '' };

        if (readFourCc(view, 0) === 'RIFF' && readFourCc(view, 8) === 'WAVE') {
            parseWavChunks(view, 12, buffer.byteLength, target);
        }

        return mergeMeta(
            mergeMeta(target, scanBufferForListInfo(buffer)),
            scanBufferForBext(buffer)
        );
    }

    function readArrayBuffer(blob) {
        return new Promise(function (resolve) {
            const reader = new FileReader();
            reader.onerror = function () {
                resolve(null);
            };
            reader.onload = function () {
                resolve(reader.result);
            };
            reader.readAsArrayBuffer(blob);
        });
    }

    function readWavInfo(file) {
        if (!isWavFile(file) || file.size < 44) {
            return Promise.resolve(null);
        }

        const headBytes = Math.min(file.size, WAV_SCAN_BYTES);
        const tailBytes = file.size > headBytes
            ? Math.min(512 * 1024, file.size - headBytes)
            : 0;

        return readArrayBuffer(file.slice(0, headBytes)).then(function (headBuffer) {
            let meta = extractWavInfoFromBuffer(headBuffer);

            if (hasMeta(meta)) {
                return meta;
            }

            if (tailBytes <= 0) {
                return null;
            }

            return readArrayBuffer(file.slice(file.size - tailBytes)).then(function (tailBuffer) {
                return mergeMeta(meta, extractWavInfoFromBuffer(tailBuffer));
            });
        });
    }

    function readId3FromWavBuffer(buffer) {
        if (!buffer || buffer.byteLength < 10) {
            return Promise.resolve(null);
        }

        const lib = getJsMediaTags();
        if (!lib) {
            return Promise.resolve(null);
        }

        const bytes = new Uint8Array(buffer);
        let id3Start = -1;

        for (let i = 0; i < bytes.length - 3; i++) {
            if (bytes[i] === 0x49 && bytes[i + 1] === 0x44 && bytes[i + 2] === 0x33) {
                id3Start = i;
                break;
            }
        }

        if (id3Start < 0) {
            return Promise.resolve(null);
        }

        const id3Blob = new Blob([buffer.slice(id3Start)], { type: 'audio/mpeg' });

        return new Promise(function (resolve) {
            try {
                new lib.Reader(id3Blob)
                    .setTagsToRead(TAGS_TO_READ)
                    .read({
                        onSuccess: function (tag) {
                            const meta = extractFromTags(tag.tags || {});
                            resolve(meta.title || meta.artist ? meta : null);
                        },
                        onError: function () {
                            resolve(null);
                        },
                    });
            } catch (e) {
                resolve(null);
            }
        });
    }

    function readWavMetadata(file) {
        const headBytes = Math.min(file.size, WAV_SCAN_BYTES);

        return readWavInfo(file).then(function (riffMeta) {
            if (hasMeta(riffMeta)) {
                return riffMeta;
            }

            return readArrayBuffer(file.slice(0, headBytes)).then(function (headBuffer) {
                return readId3FromWavBuffer(headBuffer).then(function (id3Meta) {
                    return mergeMeta(riffMeta, id3Meta);
                });
            });
        });
    }

    function normalizeTagValue(value) {
        if (value == null) {
            return '';
        }
        if (typeof value === 'string') {
            return value.replace(/\0/g, '').trim();
        }
        if (typeof value === 'object') {
            if (typeof value.data === 'string') {
                return value.data.replace(/\0/g, '').trim();
            }
            if (Array.isArray(value.data)) {
                try {
                    return new TextDecoder('utf-8')
                        .decode(new Uint8Array(value.data))
                        .replace(/\0/g, '')
                        .trim();
                } catch (e) {
                    return '';
                }
            }
        }
        return String(value).trim();
    }

    function pickTag(tags, keys) {
        for (let i = 0; i < keys.length; i++) {
            const value = normalizeTagValue(tags[keys[i]]);
            if (value) {
                return value;
            }
        }
        return '';
    }

    function normalizeGenre(genre) {
        let value = normalizeTagValue(genre);
        const id3v1Match = value.match(/^\(\d+\)\s*(.*)$/);
        if (id3v1Match) {
            value = id3v1Match[1].trim();
        }
        return value;
    }

    function extractFromTags(tags) {
        const title = pickTag(tags, ['title', 'TIT2', '\u00a9nam']);
        const artist = pickTag(tags, ['artist', 'TPE1', 'TPE2', '\u00a9ART']);
        const genre = normalizeGenre(pickTag(tags, ['genre', 'TCON', '\u00a9gen']));
        return { title, artist, genre };
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

    function finishWithFilename(file, message) {
        const fromName = parseFilename(file.name);
        applyMetadata(fromName.title, fromName.artist, '');
        setHint(message, true);
    }

    function applyFoundMetadata(meta, file, sourceLabel) {
        applyMetadata(meta.title, meta.artist, meta.genre || '');
        setHint(
            'Details loaded from ' + sourceLabel + '. You can edit them before saving.',
            false
        );
    }

    function readId3v1(file) {
        return new Promise(function (resolve) {
            if (!/\.mp3$/i.test(file.name) || file.size < 128) {
                resolve(null);
                return;
            }

            readArrayBuffer(file.slice(file.size - 128, file.size)).then(function (buffer) {
                if (!buffer || buffer.byteLength < 128) {
                    resolve(null);
                    return;
                }

                const view = new DataView(buffer);
                if (readFourCc(view, 0) !== 'TAG') {
                    resolve(null);
                    return;
                }

                function readField(offset, length) {
                    const bytes = new Uint8Array(buffer, offset, length);
                    let text = '';

                    for (let i = 0; i < bytes.length; i++) {
                        if (bytes[i] === 0) {
                            break;
                        }
                        text += String.fromCharCode(bytes[i]);
                    }

                    return text.trim();
                }

                resolve({
                    title: readField(3, 30),
                    artist: readField(33, 30),
                    genre: '',
                });
            });
        });
    }

    function readWithJsMediaTags(file) {
        const lib = getJsMediaTags();
        if (!lib) {
            return Promise.resolve(null);
        }

        return new Promise(function (resolve) {
            let settled = false;

            function done(result) {
                if (!settled) {
                    settled = true;
                    resolve(result);
                }
            }

            try {
                new lib.Reader(file)
                    .setTagsToRead(TAGS_TO_READ)
                    .read({
                        onSuccess: function (tag) {
                            const meta = extractFromTags(tag.tags || {});
                            if (meta.title || meta.artist) {
                                done(meta);
                                return;
                            }
                            readId3v1(file).then(done);
                        },
                        onError: function () {
                            readId3v1(file).then(done);
                        },
                    });
            } catch (e) {
                done(null);
            }
        });
    }

    function readFileMetadata(file) {
        if (isWavFile(file)) {
            return readWavMetadata(file);
        }

        return readWithJsMediaTags(file);
    }

    function noTagsMessage(file) {
        if (isWavFile(file)) {
            return 'No WAV metadata found (use RIFF INFO tags, or name files as Artist - Title). Fields filled from filename.';
        }
        return 'No metadata tags found. Fields filled from filename — please review.';
    }

    fileInput.addEventListener('change', function () {
        const file = fileInput.files[0];
        if (!file) {
            setHint('', false);
            return;
        }

        setHint('Reading file metadata…', false);

        readFileMetadata(file).then(function (meta) {
            if (meta && (meta.title || meta.artist)) {
                const source = isWavFile(file) ? 'WAV file metadata' : 'file metadata';
                applyFoundMetadata(meta, file, source);
                return;
            }

            finishWithFilename(file, noTagsMessage(file));
        });
    });
})();
