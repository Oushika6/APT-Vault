<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Invalid security token. Refresh the form and try again.');
    }
}

function private_response(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
}

function only_post(): void
{
    if (!is_post()) { http_response_code(405); header('Allow: POST'); exit('Method not allowed.'); }
}
