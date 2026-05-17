/**
 * Client-side validation with JustValidate (assignment requirement).
 * submitFormAutomatically must be true or validated forms never POST to the server.
 */
(function () {
    if (typeof JustValidate === 'undefined') {
        return;
    }

    var validateOpts = { submitFormAutomatically: true };

    function createValidator(form) {
        if (!form) {
            return null;
        }
        return new JustValidate(form, validateOpts);
    }

    var registerForm = document.getElementById('register-form');
    if (registerForm) {
        var namePattern = /^[A-Za-z][A-Za-z\s'-]*$/;
        createValidator(registerForm)
            .addField('#name', [
                { rule: 'required' },
                { rule: 'maxLength', value: 15 },
                { rule: 'customRegexp', value: namePattern, errorMessage: 'Name can only contain letters' },
            ])
            .addField('#surname', [
                { rule: 'required' },
                { rule: 'maxLength', value: 15 },
                { rule: 'customRegexp', value: namePattern, errorMessage: 'Surname can only contain letters' },
            ])
            .addField('#email', [{ rule: 'required' }, { rule: 'email' }])
            .addField('#mobile', [
                { rule: 'required' },
                { rule: 'customRegexp', value: /^\d{8}$/, errorMessage: 'Mobile must be 8 digits' },
            ])
            .addField('#password', [{ rule: 'required' }, { rule: 'minLength', value: 5 }]);
    }

    /* Login uses HTML5 validation only — avoids JustValidate blocking POST. */

    function songRules(validator, requireAudio) {
        validator
            .addField('#song-title', [{ rule: 'required' }, { rule: 'maxLength', value: 100 }])
            .addField('#song-artist', [{ rule: 'required' }, { rule: 'maxLength', value: 100 }])
            .addField('#song-genre', [{ rule: 'required' }]);
        if (requireAudio) {
            validator.addField('#audio-file', [{ rule: 'required' }]);
        }
    }

    var addSongForm = document.getElementById('add-song-form');
    if (addSongForm) {
        songRules(createValidator(addSongForm), true);
    }

    var editSongForm = document.getElementById('edit-song-form');
    if (editSongForm) {
        songRules(createValidator(editSongForm), false);
    }

    var createPlaylistForm = document.querySelector('#create-playlist-modal form');
    if (createPlaylistForm) {
        createValidator(createPlaylistForm)
            .addField('#playlist-name', [{ rule: 'required' }, { rule: 'maxLength', value: 100 }])
            .addField('#playlist-description', [{ rule: 'maxLength', value: 500 }]);
    }

    var editPlaylistForm = document.getElementById('edit-playlist-form');
    if (editPlaylistForm) {
        createValidator(editPlaylistForm)
            .addField('#playlist-name', [{ rule: 'required' }, { rule: 'maxLength', value: 100 }])
            .addField('#playlist-description', [{ rule: 'maxLength', value: 500 }]);
    }

    var profileDetailsForm = document.getElementById('profile-details-form');
    if (profileDetailsForm) {
        createValidator(profileDetailsForm)
            .addField('#profile-name', [{ rule: 'required' }, { rule: 'maxLength', value: 15 }])
            .addField('#profile-surname', [{ rule: 'required' }, { rule: 'maxLength', value: 15 }])
            .addField('#profile-email', [{ rule: 'required' }, { rule: 'email' }])
            .addField('#profile-mobile', [
                { rule: 'required' },
                { rule: 'customRegexp', value: /^\d{8}$/, errorMessage: 'Mobile must be 8 digits' },
            ]);
    }

    var adminGenreForm = document.getElementById('admin-genre-form');
    if (adminGenreForm) {
        createValidator(adminGenreForm)
            .addField('#genre-name', [{ rule: 'required' }, { rule: 'maxLength', value: 50 }]);
    }

    var adminUserForm = document.getElementById('admin-user-form');
    if (adminUserForm) {
        createValidator(adminUserForm)
            .addField('#name', [{ rule: 'required' }, { rule: 'maxLength', value: 15 }])
            .addField('#surname', [{ rule: 'required' }, { rule: 'maxLength', value: 15 }])
            .addField('#email', [{ rule: 'required' }, { rule: 'email' }])
            .addField('#mobile', [
                { rule: 'required' },
                { rule: 'customRegexp', value: /^\d{8}$/, errorMessage: 'Mobile must be 8 digits' },
            ])
            .addField('#role', [{ rule: 'required' }]);
    }
})();
