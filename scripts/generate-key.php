<?php
/*
 * generate-key.php
 * Creates a new random encryption key for the .env file.
 * Run it from the command prompt, inside the project folder:
 *     C:\xampp\php\php.exe scripts\generate-key.php
 */

// PHP_SAPI tells us how PHP was started. 'cli' = command line.
// A key must never be shown in a web browser, so we refuse to run there.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This tool can only be run from the command line.');
}

// 32 random bytes = a 256-bit key (the size AES-256 needs).
// random_bytes() uses the computer's secure random number generator.
$randomBytes = random_bytes(32);

// Random bytes are not readable text, so we turn them into base64 text
// that can be written in the .env file.
$key = base64_encode($randomBytes);

echo 'APP_KEY=base64:' . $key . "\n";