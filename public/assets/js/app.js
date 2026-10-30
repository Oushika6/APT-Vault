/* Password Generator: creates a random 20-character password using the Web Crypto API. */
(function ($) {
  'use strict';

  const PASSWORD_LENGTH = 20;
  const UINT32_RANGE = 4294967296;
  const CHARACTER_SETS = [
    'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    'abcdefghijklmnopqrstuvwxyz',
    '0123456789',
    '!@#$%^&*()-_=+'
  ];
  const ALL_CHARACTERS = CHARACTER_SETS.join('');

  // Rejection sampling avoids modulo bias.
  function randomIntBelow(limit) {
    const maxAccepted = Math.floor(UINT32_RANGE / limit) * limit;
    const buffer = new Uint32Array(1);

    do {
      window.crypto.getRandomValues(buffer);
    } while (buffer[0] >= maxAccepted);

    return buffer[0] % limit;
  }

  function shuffle(items) {
    for (let i = items.length - 1; i > 0; i--) {
      const j = randomIntBelow(i + 1);
      [items[i], items[j]] = [items[j], items[i]];
    }
    return items;
  }

  function generatePassword() {
    // One character from each set, then fill up to the full length.
    const characters = CHARACTER_SETS.map(function (set) {
      return set[randomIntBelow(set.length)];
    });

    while (characters.length < PASSWORD_LENGTH) {
      characters.push(ALL_CHARACTERS[randomIntBelow(ALL_CHARACTERS.length)]);
    }

    return shuffle(characters).join('');
  }

  $(function () {
    $('#generate-password').on('click', function () {
      if (!window.crypto || !window.crypto.getRandomValues) {
        window.alert('Secure password generation requires a modern browser in a secure context.');
        return;
      }

      $('#password').val(generatePassword()).attr('type', 'text');
      $('[data-toggle-password="password"]').text('Hide');
    });
  });
})(jQuery);
