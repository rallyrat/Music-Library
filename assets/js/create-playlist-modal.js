/**
 * Open/close create-playlist dialog via [data-open-create-modal] and [data-close-create-modal].
 */
(function () {
    const dialog = document.getElementById('create-playlist-modal');
    if (!dialog) {
        return;
    }

    function openModal() {
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
        const nameInput = dialog.querySelector('#playlist-name');
        if (nameInput) {
            nameInput.focus();
        }
    }

    function closeModal() {
        if (dialog.open && typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }
    }

    document.querySelectorAll('[data-open-create-modal]').forEach(function (btn) {
        btn.addEventListener('click', openModal);
    });

    document.querySelectorAll('[data-close-create-modal]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    dialog.addEventListener('click', function (e) {
        if (e.target === dialog) {
            closeModal();
        }
    });

    if (dialog.dataset.openOnLoad === 'true') {
        openModal();
    }
})();
