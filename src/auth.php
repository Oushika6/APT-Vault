<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $loaded = false, $user = null;
    if ($loaded) return $user;
    $loaded = true;

    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) || $id < 1) return null;

    // Auto-logout after 5 minutes of inactivity
    $last = $_SESSION['last_activity'] ?? 0;
    if ($last && (time() - $last) > 300) {
        sign_out();
        flash('error', 'You were signed out due to inactivity.');
        return null;
    }
    $_SESSION['last_activity'] = time();

    $query = db()->prepare('SELECT user_id, full_name, email, role, is_active, recovery_codes_confirmed FROM users WHERE user_id = ?');
    $query->execute([$id]);
    $found = $query->fetch();

    if (!$found || (int)$found['is_active'] !== 1) {
        unset($_SESSION['user_id']);
        return null;
    }

    $user = $found;
    return $user;
}

function require_login(): array
{
    private_response();
    $user = current_user();
    if (!$user) {
        flash('error', 'Please sign in to access your vault.');
        redirect('login.php');
    }

    // Force user to confirm recovery codes if not done yet
    if ((int)$user['recovery_codes_confirmed'] === 0) {
        redirect('recovery.php?action=show');
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Administrator access required.');
    }
    return $user;
}

function sign_in(int $id): never
{
    $notice = $_SESSION['flash'] ?? null;
    session_regenerate_id(true);
    $_SESSION = [
        'user_id' => $id,
        'csrf' => bin2hex(random_bytes(32)),
        'last_activity' => time()
    ];
    if ($notice) $_SESSION['flash'] = $notice;
    redirect('dashboard.php');
}

function sign_out(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $p['path'],
            'secure' => $p['secure'],
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_destroy();
}

/** Generate 10 recovery codes and store their hashes */
function generate_recovery_codes(int $userId): array
{
    // Delete any old codes first
    db()->prepare('DELETE FROM recovery_codes WHERE user_id = ?')->execute([$userId]);

    $codes = [];
    $stmt = db()->prepare('INSERT INTO recovery_codes (user_id, code_hash) VALUES (?, ?)');

    for ($i = 0; $i < 10; $i++) {
        $code = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        $codes[] = $code;
        $stmt->execute([$userId, password_hash($code, PASSWORD_DEFAULT)]);
    }

    return $codes;
}
