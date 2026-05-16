(function () {
    var search = document.getElementById('song-search');
    var list = document.getElementById('playlist-add-results');
    var emptyMsg = document.getElementById('song-search-empty');
    if (!search || !list) {
        return;
    }

    var items = list.querySelectorAll('.playlist-add-item');

    function filterSongs() {
        var query = search.value.trim().toLowerCase();
        var visible = 0;

        items.forEach(function (item) {
            var haystack = item.getAttribute('data-search') || '';
            var match = query === '' || haystack.indexOf(query) !== -1;
            item.classList.toggle('hidden', !match);
            if (match) {
                visible += 1;
            }
        });

        if (emptyMsg) {
            emptyMsg.classList.toggle('hidden', visible > 0 || items.length === 0);
        }
    }

    search.addEventListener('input', filterSongs);
    filterSongs();
})();
