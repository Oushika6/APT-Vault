<?php
require_once __DIR__ . '/../src/bootstrap.php';
if (current_user()) redirect('dashboard.php');
$errors = [];
$values = ['full_name' => '', 'email' => ''];
if (is_post()) {
    verify_csrf();
    $values['full_name'] = request_string('full_name', 120);
    $values['email'] = strtolower(request_string('email', 180));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($values['full_name']) < 2 || strlen($values['full_name']) > 100) $errors[] = 'Full name must be 2–100 characters.';
    if (strlen($values['email']) > 150 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!is_string($password) || strlen($password) < 12 || strlen($password) > 128) $errors[] = 'Password must be 12–128 characters.';
    if (!is_string($confirm) || $password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            db()->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'user')")
                ->execute([$values['full_name'], $values['email'], $hash]);

            $id = (int) db()->lastInsertId();

            foreach (['Personal', 'Work', 'University', 'Social Media', 'Education', 'Financial'] as $category) {
                db()->prepare('INSERT INTO categories (user_id, category_name) VALUES (?, ?)')->execute([$id, $category]);
            }

            // Generate recovery codes
            $codes = generate_recovery_codes($id);

            // Store codes temporarily in session so we can show them
            $_SESSION['temp_recovery_codes'] = $codes;
            $_SESSION['temp_user_id'] = $id;

            audit('account_registered', 'New user account created', $id);
            flash('success', 'Account created. Please save your recovery codes.');
            sign_in($id);   // This will redirect to recovery.php?action=show because codes are not confirmed yet
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $errors[] = 'An account already exists with that email.';
            else throw $e;
        }
    }
}
render_header('Create an account');
?>
<main class="auth-shell"><div class="auth-heading"><h1>Create your account</h1><p>Start protecting your passwords and private notes</p></div><div class="auth-card">
  <?php if ($errors): ?><div class="form-errors" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
  <form method="post" data-validate="register" novalidate>
    <?= csrf_field() ?>
    <label for="full_name">Full Name <b>*</b></label><input id="full_name" name="full_name" type="text" autocomplete="name" maxlength="100" minlength="2" required value="<?= h($values['full_name']) ?>" placeholder="e.g. Alex Johnson">
    <label for="email">Email Address <b>*</b></label><input id="email" name="email" type="email" autocomplete="email" maxlength="150" required value="<?= h($values['email']) ?>" placeholder="you@example.com">
    <label for="password">Master Password <b>*</b></label><div class="input-adorn"><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="128" required placeholder="At least 12 characters"><button type="button" class="eye-btn" data-toggle-password="password" aria-label="Show password">Show</button></div>
    <label for="confirm_password">Confirm Password <b>*</b></label><input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="12" required placeholder="Enter password again">
    <p class="form-hint">Use a unique password of at least 12 characters. This coursework app is not a production password manager.</p>
    <button type="submit" class="btn btn-primary btn-full">Create Account</button>
  </form>
  <p class="auth-bottom">Already have an account? <a href="login.php">Sign in</a></p>
</div></main>
<?php render_footer(); ?>
