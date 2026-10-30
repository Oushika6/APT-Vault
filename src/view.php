<?php
declare(strict_types=1);

function render_header(string $title, string $section = '', bool $landing = false): void
{
    $user = current_user();
    $notice = take_flash();
    $page = h($title) . ' · APT Vault';
    $links = $landing ? '<a href="index.php#features">Features</a><a href="index.php#security">Security</a><a href="index.php#faq">FAQ</a><a href="contact.php">Contact</a>' : '';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $page . '</title>';
    echo '<link rel="stylesheet" href="assets/css/style.css"></head><body class="' . ($landing ? 'landing-page' : 'app-page') . '">';
    echo '<header class="site-header"><a href="index.php" class="brand">APT<span>VAULT</span></a><nav class="site-nav" aria-label="Main navigation">';
    if ($user) {
        echo '<a ' . ($section === 'dashboard' ? 'class="active"' : '') . ' href="dashboard.php">Dashboard</a>';
        echo '<a ' . ($section === 'passwords' ? 'class="active"' : '') . ' href="passwords.php">Passwords</a>';
        echo '<a ' . ($section === 'notes' ? 'class="active"' : '') . ' href="notes.php">Secure Notes</a>';
        echo '<a ' . ($section === 'categories' ? 'class="active"' : '') . ' href="categories.php">Categories</a>';
        if ($user['role'] === 'admin') echo '<a ' . ($section === 'admin' ? 'class="active"' : '') . ' href="admin.php">Admin</a>';
    } else {
        echo $links;
    }
    echo '</nav><div class="header-actions">';
    if ($user) {
        echo '<span class="user-pill" title="' . h($user['email']) . '">' . h($user['full_name']) . '</span>';
        echo '<form method="post" action="logout.php" class="inline-form">' . csrf_field() . '<button type="submit" class="link-button">Log out</button></form>';
    } else {
        echo '<a href="login.php" class="header-login">Login</a><a href="register.php" class="btn btn-primary btn-small">Go to Vault</a>';
    }
    echo '</div></header>';
    if ($notice) echo '<div class="flash flash-' . h($notice['type']) . '" role="status">' . h($notice['message']) . '</div>';
}

function render_footer(): void
{
    echo '<footer class="footer"><span>© ' . date('Y') . ' APT Vault · ICT2213Y Web Technologies & Security</span><span><a href="terms.php">Terms and Conditions</a> <a href="privacy.php">Privacy Policy</a> <a href="contact.php">Contact support</a></span></footer>';
    echo '<script src="assets/js/jquery.min.js"></script><script src="assets/js/app.js"></script></body></html>';
}
