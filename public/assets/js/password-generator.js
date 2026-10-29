/* APT Vault - Password Generator (Member 4) */
(function ($) {
  'use strict';

  $(function () {

    // Web Crypto uses a CSPRNG and rejection sampling rather than Math.random().
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

      var sets = [
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'abcdefghijklmnopqrstuvwxyz',
        '0123456789',
        '!@#$%^&*()-_=+'
      ];
      var all = sets.join('');

      // One character from each set guarantees a mix of types.
      var characters = sets.map(function (s) {
        return s[randomIntBelow(s.length)];
      });

      // Fill up to 20 characters from all sets combined.
      while (characters.length < 20) {
        characters.push(all[randomIntBelow(all.length)]);
      }

      // Fisher-Yates shuffle so the guaranteed characters aren't always first.
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
