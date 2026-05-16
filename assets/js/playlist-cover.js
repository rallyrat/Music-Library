(function () {
    document.querySelectorAll('.playlist-cover-edit__input').forEach(function (input) {
        var form = input.closest('form');
        if (!form) {
            return;
        }
        input.addEventListener('change', function () {
            if (input.files && input.files.length > 0) {
                form.submit();
            }
        });
    });
})();
