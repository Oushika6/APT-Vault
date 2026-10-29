<?php
/*
 * bootstrap.php - Member 6
 * Every page starts with:   require_once __DIR__ . '/../src/bootstrap.php';
 * This file:
 *   1. loads all the shared code
 *   2. hides technical errors from visitors
 *   3. starts the session with safe cookie settings
 *   4. sends the security headers with every page
 */


// 1. Load the shared files. __DIR__ means "the folder this file is in" (src).
//    require_once: the page stops if a file is missing, and each file is loaded only once.
require_once __DIR__ . '/helpers.php';   // helper functions + security log (Members 5, 6)
require_once __DIR__ . '/db.php';        // database connection (Member 6)
require_once __DIR__ . '/security.php';  // CSRF tokens (Member 1)
require_once __DIR__ . '/crypto.php';    // encryption of vault data (Member 4)
require_once __DIR__ . '/auth.php';      // login and admin checks (Member 1)
require_once __DIR__ . '/view.php';      // page header and footer (Member 2)


// 2. Never show technical error details to visitors:
//    they could reveal SQL commands, file paths or secrets to an attacker.
ini_set('display_errors', '0');

function show_friendly_error($error)
{
    // The full details go to the private server error log, for us to read.
    error_log('APT Vault error: ' . $error);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
    }

    // The visitor only sees a short, harmless message.
    echo 'APT Vault could not complete this request. Check your .env configuration and Apache/PHP error logs.';
}

// If any error is not caught somewhere else, PHP calls show_friendly_error()
set_exception_handler('show_friendly_error');


// 3. Safer session settings (against session hijacking and session fixation)
ini_set('session.use_strict_mode', '1');   // refuse session IDs that our server did not create
ini_set('session.use_only_cookies', '1');  // never accept a session ID written in the URL
ini_set('session.cookie_httponly', '1');   // JavaScript cannot read the session cookie

// The "secure" cookie option only works with HTTPS, so we check how the site was opened
$usingHttps = false;
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off') {
    $usingHttps = true;
}

session_name('APTVAULTSESSID');
session_set_cookie_params([
    'lifetime' => 0,          // the cookie is deleted when the browser is closed
    'path' => '/',
    'secure' => $usingHttps,  // with HTTPS: the cookie is only sent over HTTPS
    'httponly' => true,       // not readable by JavaScript (helps if XSS happens)
    'samesite' => 'Lax'       // not sent with forms posted from other websites (helps against CSRF)
]);
session_start();


// 4. Security headers sent with every page
header('X-Content-Type-Options: nosniff');                    // browser must not guess file types
header('Referrer-Policy: strict-origin-when-cross-origin');   // do not leak full page addresses to other sites
header('X-Frame-Options: DENY');                              // our pages cannot be put inside another site's frame
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");

// Member 6: tell search engines not to index, follow or cache any APT Vault page
header('X-Robots-Tag: noindex, nofollow, noarchive');