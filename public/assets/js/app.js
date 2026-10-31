(function ($) {
    'use strict';

    $(function () {
        $('form[data-validate]').on('submit', function (event) {
            var form = this;
            var invalid = Array.from(form.querySelectorAll('[required]')).find(function (field) {
                return !field.value.trim();
            });
            var email = form.querySelector('input[type="email"]');

            if (!invalid && email && !email.checkValidity())
                invalid = email;

            if (invalid) {
                event.preventDefault();
                invalid.focus();
                invalid.setCustomValidity('Please complete this required field correctly.');
                invalid.reportValidity();
                invalid.addEventListener('input', function () {
                    invalid.setCustomValidity('');
                }, {once: true});
                return;
            }

            if (form.dataset.validate === 'register') {
                var pw = form.querySelector('[name="password"]');
                var confirmation = form.querySelector('[name="confirm_password"]');

                if (pw.value.length < 12 || pw.value !== confirmation.value) {
                    event.preventDefault();
                    window.alert('Choose at least 12 characters and confirm your password correctly.');
                    confirmation.focus();
                }
            }
        });

        $(document).on('click', '[data-toggle-password]', function () {
            var field = document.getElementById(this.dataset.togglePassword);

            if (!field)
                return;

            field.type = field.type === 'password' ? 'text' : 'password';
            $(this).text(field.type === 'password' ? 'Show' : 'Hide');
        });

        $(document).on('submit', 'form[data-confirm]', function (event) {
            if (!window.confirm(this.dataset.confirm))
                event.preventDefault();
        });

        var filters = {passwords: 'all', notes: 'all'};

        function applyFilters(kind) {
            var prefix = kind === 'passwords' ? 'password' : 'notes';
            var search = ($('#' + prefix + '-search').val() || '').toLowerCase().trim();
            var visible = 0;
            var selector = kind === 'passwords' ? '.password-row' : '.note-card';

            $(selector).each(function () {
                var matches = (this.dataset.search || '').includes(search) &&
                    (filters[kind] === 'all' || (this.dataset.category || '').toLowerCase() === filters[kind].toLowerCase());
                $(this).toggle(matches);

                if (matches)
                    visible++;
            });

            $('#' + prefix + '-empty').prop('hidden', visible > 0);
        }

        $('#password-search').on('input', function () {
            applyFilters('passwords');
        });

        $('#notes-search').on('input', function () {
            applyFilters('notes');
        });

        $('[data-filter-group]').on('click', '[data-filter]', function () {
            var kind = $(this).closest('[data-filter-group]').data('filter-group');
            filters[kind] = this.dataset.filter;
            $(this).siblings().removeClass('active');
            $(this).addClass('active');
            applyFilters(kind);
        });

        function retrieveSecret(button, done) {
            $.ajax({
                url: 'password-reveal.php',
                method: 'POST',
                dataType: 'json',
                data: {id: button.dataset.id, csrf: button.dataset.csrf},
                cache: false
            }).done(function (data) {
                done(data.password);
            }).fail(function () {
                window.alert('Password could not be retrieved. Sign in again if your session expired.');
            });
        }

        $(document).on('click', '.reveal-secret', function () {
            var button = this;
            var output = document.getElementById('secret-' + button.dataset.id);

            if (!output)
                return;

            if (output.dataset.visible === 'true') {
                output.textContent = '••••••••••••••••';
                output.dataset.visible = 'false';
                return;
            }

            retrieveSecret(button, function (secret) {
                output.textContent = secret;
                output.dataset.visible = 'true';
                window.setTimeout(function () {
                    output.textContent = '••••••••••••••••';
                    output.dataset.visible = 'false';
                }, 15000);
            });
        });

        $(document).on('click', '.copy-secret', function () {
            var button = this;

            retrieveSecret(button, function (secret) {
                if (!navigator.clipboard || !window.isSecureContext) {
                    window.alert('Clipboard copy needs HTTPS or localhost. Use the Reveal button on this demo.');
                    return;
                }

                navigator.clipboard.writeText(secret).then(function () {
                    var original = button.textContent;
                    button.textContent = '✓';
                    window.setTimeout(function () {
                        button.textContent = original;
                    }, 1300);
                }).catch(function () {
                    window.alert('Browser blocked clipboard access.');
                });
            });
        });

        function randomIntBelow(limit) {
            var upper = Math.floor(4294967296 / limit) * limit;
            var x = new Uint32Array(1);

            do {
                window.crypto.getRandomValues(x);
            } while (x[0] >= upper);

            return x[0] % limit;
        }

        $('#generate-password').on('click', function () {
            if (!window.crypto || !window.crypto.getRandomValues) {
                window.alert('Secure password generation requires a modern browser in a secure context.');
                return;
            }

            var sets = ['ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz', '0123456789', '!@#$%^&*()-_=+'];
            var all = sets.join('');
            var characters = sets.map(function (s) {
                return s[randomIntBelow(s.length)];
            });

            while (characters.length < 20)
                characters.push(all[randomIntBelow(all.length)]);

            for (var i = characters.length - 1; i > 0; i--) {
                var j = randomIntBelow(i + 1);
                var tmp = characters[i];
                characters[i] = characters[j];
                characters[j] = tmp;
            }

            $('#password').val(characters.join('')).attr('type', 'text');
            $('[data-toggle-password="password"]').text('Hide');
        });
    });
})(jQuery);
