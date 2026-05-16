(function () {
    function initialsFromFields(nameInput, surnameInput) {
        var parts = [];
        if (nameInput && nameInput.value.trim()) {
            parts.push(nameInput.value.trim());
        }
        if (surnameInput && surnameInput.value.trim()) {
            parts.push(surnameInput.value.trim());
        }
        var text = '';
        for (var i = 0; i < parts.length && text.length < 2; i++) {
            text += parts[i].charAt(0).toUpperCase();
        }
        return text || '?';
    }

    function previewImage(input) {
        var label = input.closest('.playlist-cover-edit');
        if (!label || !input.files || !input.files.length) {
            return;
        }

        var file = input.files[0];
        if (!file.type || file.type.indexOf('image/') !== 0) {
            return;
        }

        var reader = new FileReader();
        reader.onload = function (event) {
            var img = label.querySelector('.avatar__img, .playlist-cover__img');
            if (!img) {
                img = document.createElement('img');
                img.className = label.classList.contains('avatar-edit') ? 'avatar__img' : 'playlist-cover__img';
                img.alt = '';
                var initials = label.querySelector('.avatar__initials');
                var placeholder = label.querySelector('.playlist-cover__placeholder');
                if (initials) {
                    initials.remove();
                }
                if (placeholder) {
                    placeholder.remove();
                }
                var overlay = label.querySelector('.playlist-cover-edit__overlay');
                label.insertBefore(img, overlay || null);
            }
            img.src = event.target.result;

            var overlayLabel = label.querySelector('.playlist-cover-edit__label');
            if (overlayLabel) {
                overlayLabel.textContent = 'Change photo';
            }
            label.title = 'Change photo';
        };
        reader.readAsDataURL(file);
    }

    document.querySelectorAll('.playlist-cover-edit__input').forEach(function (input) {
        var form = input.closest('form');
        var previewOnly = input.hasAttribute('data-upload-preview');

        input.addEventListener('change', function () {
            if (!input.files || !input.files.length) {
                return;
            }

            if (previewOnly) {
                previewImage(input);
                return;
            }

            if (form) {
                form.submit();
            }
        });
    });

    var registerForm = document.getElementById('register-form');
    if (registerForm) {
        var nameInput = registerForm.querySelector('#name');
        var surnameInput = registerForm.querySelector('#surname');
        var initialsEl = registerForm.querySelector('.avatar__initials');

        function syncInitials() {
            if (!initialsEl) {
                return;
            }
            initialsEl.textContent = initialsFromFields(nameInput, surnameInput);
        }

        if (nameInput) {
            nameInput.addEventListener('input', syncInitials);
        }
        if (surnameInput) {
            surnameInput.addEventListener('input', syncInitials);
        }
    }
})();
