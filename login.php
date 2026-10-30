<?php
require_once __DIR__ . '/../src/bootstrap.php';
if (current_user()) redirect('dashboard.php');
$error = '';
$email = '';
if (is_post()) {
    verify_csrf();
    $email = strtolower(request_string('email', 180));
    $password = $_POST['password'] ?? '';
    $attempts = $_SESSION['login_attempts'] ?? [];
    $attempts = array_values(array_filter($attempts, fn($time) => $time >= time() - 900));
    if (count($attempts) >= 6) {
        $error = 'Too many sign-in attempts. Try again in 15 minutes.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password)) {
        $error = 'Invalid email or password.';
    } else {
        $query = db()->prepare('SELECT user_id, password_hash, is_active FROM users WHERE email = ?');
        $query->execute([$email]);
        $user = $query->fetch();
        if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
            }
            audit('login_success', 'Successful login', (int)$user['user_id']);
            unset($_SESSION['login_attempts']);
            sign_in((int)$user['user_id']);
        }
        $attempts[] = time();
        $_SESSION['login_attempts'] = $attempts;
        audit('login_failure', 'Invalid sign-in attempt');
        $error = 'Invalid email or password, or the account is unavailable.';
    }
}
render_header('Sign in');
?>
<main class="auth-shell"><div class="auth-heading"><h1>Welcome back</h1><p>Sign in to access your secure vault</p></div><div class="auth-card">
  <?php if ($error): ?><div class="form-errors" role="alert"><?= h($error) ?></div><?php endif; ?>
  <form method="post" data-validate="login" novalidate><?= csrf_field() ?>
    <label for="email">Email Address <b>*</b></label><input id="email" name="email" type="email" autocomplete="username" placeholder="you@example.com" maxlength="150" required value="<?= h($email) ?>">
    <label for="password">Master Password <b>*</b></label><div class="input-adorn"><input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter your master password"><button type="button" class="eye-btn" data-toggle-password="password" aria-label="Show password">Show</button></div>
    <div class="form-between"><span class="note-muted">Keep your master password private</span><a href="contact.php">Need help signing in?</a></div>
    <button class="btn btn-primary btn-full" type="submit">Sign In</button>
  </form><div class="or-divider">OR</div><p class="auth-bottom">Don't have an account? <a href="register.php">Create account</a></p>
</div></main>
<?php render_footer(); ?>
