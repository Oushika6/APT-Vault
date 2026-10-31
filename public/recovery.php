<?php
require_once __DIR__ . '/../src/bootstrap.php';

$action = $_GET['action'] ?? 'show';
$user = current_user();


//SHOW recovery codes, after registration)   

if ($action === 'show') {
    if (!$user) {
        redirect('login.php');
    }

    // Already confirmed go to dashboard
    if ((int)$user['recovery_codes_confirmed'] === 1) {
        redirect('dashboard.php');
    }

    // Get codes from session (generated during registration)
    $codes = $_SESSION['temp_recovery_codes'] ?? null;

    if (!$codes) {
        // Fallback: regenerate if session lost
        $codes = generate_recovery_codes((int)$user['user_id']);
        $_SESSION['temp_recovery_codes'] = $codes;
    }

    // User clicked "I have saved them"
    if (is_post()) {
        verify_csrf();
        db()->prepare('UPDATE users SET recovery_codes_confirmed = 1 WHERE user_id = ?')
            ->execute([(int)$user['user_id']]);
        unset($_SESSION['temp_recovery_codes'], $_SESSION['temp_user_id']);
        flash('success', 'Recovery codes saved. Welcome to your vault!');
        redirect('dashboard.php');
    }

    render_header('Save your recovery codes');
    ?>
    <main class="auth-shell">
      <div class="auth-heading">
        <h1>Save your recovery codes</h1>
        <p>These codes are the only way to recover your account if you forget your master password.</p>
      </div>
      <div class="auth-card">
        <div class="form-errors" style="background:#fff3cd;color:#856404;">
          <strong>Important:</strong> Copy these codes now. You will not see them again.
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:20px 0;font-family:monospace;font-size:1.1rem;">
          <?php foreach ($codes as $code): ?>
            <div style="padding:8px 12px;background:#f8f9fa;border:1px solid #ddd;border-radius:6px;text-align:center;">
              <?= h($code) ?>
            </div>
          <?php endforeach; ?>
        </div>

        <form method="post">
          <?= csrf_field() ?>
          <button class="btn btn-primary btn-full" type="submit">
            I have saved my recovery codes
          </button>
        </form>
      </div>
    </main>
    <?php
    render_footer();
    exit;
}


   // Forgot password (Step 1 enter email)
   
if ($action === 'forgot') {
    if ($user) redirect('dashboard.php');

    $error = '';
    $email = '';

    if (is_post()) {
        verify_csrf();
        $email = strtolower(request_string('email', 180));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } else {
            $stmt = db()->prepare('SELECT user_id FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email]);
            $found = $stmt->fetch();

            if ($found) {
                $_SESSION['recovery_email'] = $email;
                $_SESSION['recovery_user_id'] = (int)$found['user_id'];
                redirect('recovery.php?action=verify');
            } else {
                // Don't reveal if email exists
                $error = 'If that email exists, you can continue with a recovery code.';
                // Still allow them to proceed for better UX in school project
                $_SESSION['recovery_email'] = $email;
                redirect('recovery.php?action=verify');
            }
        }
    }

    render_header('Forgot password');
    ?>
    <main class="auth-shell">
      <div class="auth-heading">
        <h1>Forgot password</h1>
        <p>Enter your email to start account recovery</p>
      </div>
      <div class="auth-card">
        <?php if ($error): ?><div class="form-errors"><?= h($error) ?></div><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <label for="email">Email Address <b>*</b></label>
          <input id="email" name="email" type="email" required maxlength="150"
                 value="<?= h($email) ?>" placeholder="you@example.com">
          <button class="btn btn-primary btn-full" type="submit">Continue</button>
        </form>
        <p class="auth-bottom"><a href="login.php">Back to sign in</a></p>
      </div>
    </main>
    <?php
    render_footer();
    exit;
}


   // Verify recovery code
   
if ($action === 'verify') {
    if ($user) redirect('dashboard.php');

    $email = $_SESSION['recovery_email'] ?? null;
    $userId = $_SESSION['recovery_user_id'] ?? null;

    if (!$email) {
        redirect('recovery.php?action=forgot');
    }

    $error = '';

    if (is_post()) {
        verify_csrf();
        $code = strtoupper(trim($_POST['code'] ?? ''));

        if (strlen($code) < 8) {
            $error = 'Please enter a valid recovery code.';
        } else {
            $stmt = db()->prepare('SELECT code_id, code_hash FROM recovery_codes WHERE user_id = ? AND used = 0');
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();

            $matched = null;
            foreach ($rows as $row) {
                if (password_verify($code, $row['code_hash'])) {
                    $matched = $row;
                    break;
                }
            }

            if ($matched) {
                // Mark code as used
                db()->prepare('UPDATE recovery_codes SET used = 1 WHERE code_id = ?')
                    ->execute([$matched['code_id']]);

                $_SESSION['recovery_verified'] = true;
                redirect('recovery.php?action=reset');
            } else {
                $error = 'Invalid or already used recovery code.';
                audit('recovery_failed', 'Invalid recovery code attempt');
            }
        }
    }

    render_header('Enter recovery code');
    ?>
    <main class="auth-shell">
      <div class="auth-heading">
        <h1>Enter a recovery code</h1>
        <p>Enter one of the 10 recovery codes you saved</p>
      </div>
      <div class="auth-card">
        <?php if ($error): ?><div class="form-errors"><?= h($error) ?></div><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <label for="code">Recovery Code <b>*</b></label>
          <input id="code" name="code" type="text" required
                 placeholder="XXXXXXXX-XXXXXXXX" style="text-transform:uppercase;font-family:monospace;">
          <button class="btn btn-primary btn-full" type="submit">Verify Code</button>
        </form>
        <p class="auth-bottom"><a href="recovery.php?action=forgot">Start over</a></p>
      </div>
    </main>
    <?php
    render_footer();
    exit;
}


   // Reset password 
   
if ($action === 'reset') {
    if ($user) redirect('dashboard.php');

    if (empty($_SESSION['recovery_verified']) || empty($_SESSION['recovery_user_id'])) {
        redirect('recovery.php?action=forgot');
    }

    $userId = (int) $_SESSION['recovery_user_id'];
    $error = '';

    if (is_post()) {
        verify_csrf();
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!is_string($password) || strlen($password) < 12 || strlen($password) > 128) {
            $error = 'Password must be 12–128 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                ->execute([$hash, $userId]);

            audit('password_reset', 'Password reset via recovery code', $userId);

            // Clear recovery session data
            unset($_SESSION['recovery_email'], $_SESSION['recovery_user_id'], $_SESSION['recovery_verified']);

            flash('success', 'Your password has been reset. You can now sign in.');
            redirect('login.php');
        }
    }

    render_header('Set new password');
    ?>
    <main class="auth-shell">
      <div class="auth-heading">
        <h1>Set a new password</h1>
        <p>Choose a strong master password</p>
      </div>
      <div class="auth-card">
        <?php if ($error): ?><div class="form-errors"><?= h($error) ?></div><?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <label for="password">New Master Password <b>*</b></label>
          <div class="input-adorn">
            <input id="password" name="password" type="password" minlength="12" maxlength="128" required>
            <button type="button" class="eye-btn" data-toggle-password="password">Show</button>
          </div>

          <label for="confirm_password">Confirm Password <b>*</b></label>
          <input id="confirm_password" name="confirm_password" type="password" minlength="12" required>

          <button class="btn btn-primary btn-full" type="submit">Reset Password</button>
        </form>
      </div>
    </main>
    <?php
    render_footer();
    exit;
}

// Fallback
redirect('login.php');
