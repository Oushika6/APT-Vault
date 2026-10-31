<?php
require_once __DIR__ . '/../src/bootstrap.php';
$user = require_login(); $uid = (int)$user['user_id'];
$id = is_post() 
    ? request_id('id', 'POST') 
    : request_id('id');
$editing = $id > 0;

$values = [
    'title'=>'', 
    'website_url'=>'', 
    'username'=>'', 
    'category_id'=>'', 
    'notes'=>''
    ];
$stored = null;

if ($editing) {
    $stmt = db()->prepare(
        'SELECT * FROM password_entries WHERE password_id = ? AND user_id = ?');
    $stmt->execute([$id, $uid]); 
    $stored = $stmt->fetch();
    if (!$stored) { 
        http_response_code(404); 
        exit('Password entry not found.'); }
    $values = [
        'title'=>$stored['title'], 
        'website_url'=>$stored['website_url'] ?? '', 
        'username'=>$stored['username'], 
        'category_id'=>(string)($stored['category_id'] ?? ''), 
        'notes'=>$stored['notes'] ? 
        decrypt_secret($stored['notes']) : ''];
}
$errors = [];

if (is_post()) {
    verify_csrf();
    foreach ($values as $field=>$ignore) $values[$field] = request_string($field, $field === 'notes' ? 4001 : 301);
    $password = $_POST['password'] ?? '';
    if (strlen($values['title']) < 2 || strlen($values['title']) > 100) $errors[] = 'Service name must be 2–100 characters.';
    if (strlen($values['username']) < 2 || strlen($values['username']) > 150) $errors[] = 'Username/email must be 2–150 characters.';
    if (strlen($values['website_url']) > 255 || ($values['website_url'] !== '' && (!filter_var($values['website_url'], FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($values['website_url'], PHP_URL_SCHEME)), ['http','https'], true)))) $errors[] = 'Website URL must be a valid http(s) URL (e.g. https://github.com).';
    if (strlen($values['notes']) > 4000) $errors[] = 'Additional notes may not exceed 4000 characters.';
    if (!is_string($password) || (!$editing && $password === '') || strlen((string)$password) > 1000) $errors[] = 'Enter a password (maximum 1000 characters).';
    try { $categoryId = category_for_user($values['category_id'], $uid); }
    catch (InvalidArgumentException $e) { $errors[] = $e->getMessage(); $categoryId = null; }
    if (!$errors) {
        $encryptedPassword = $password === '' && $editing ? $stored['encrypted_password'] : encrypt_secret($password);
        $encryptedNotes = $values['notes'] === '' ? null : encrypt_secret($values['notes']);
        if ($editing) {
            db()->prepare('UPDATE password_entries SET title=?, website_url=?, username=?, encrypted_password=?, notes=?, category_id=? WHERE password_id=? AND user_id=?')
                ->execute([$values['title'], $values['website_url'] ?: null, $values['username'], $encryptedPassword, $encryptedNotes, $categoryId, $id, $uid]);
            audit('password_updated', 'Updated vault entry metadata', $uid);
        } else {
            db()->prepare('INSERT INTO password_entries (user_id, title, website_url, username, encrypted_password, notes, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$uid, $values['title'], $values['website_url'] ?: null, $values['username'], $encryptedPassword, $encryptedNotes, $categoryId]);
            audit('password_created', 'Added vault entry', $uid);
        }
        flash('success', $editing ? 'Password entry updated.' : 'Password entry saved securely.');
        redirect('passwords.php');
    }
}
render_header($editing ? 'Edit Password' : 'Add Password', 'passwords');
?>
<main class="container main-content">
    <div class="form-panel">
        <div class="form-panel-head">
            <a href="passwords.php" class="back-link">
                ← My Passwords
            </a>
            <h1>
                <?= $editing ? 'Edit Password' : 'Add Password' ?>
            </h1>
            <p>
                <?= $editing ? 
                'Leave the password field blank to keep the existing password.' 
                : 'Store new credentials in your protected vault.' 
                ?>
                </p>
            </div>
<?php if ($errors): ?>
    <div class="form-errors" role="alert">
        <?= h(implode(' ', $errors)) ?>
    </div>
    <?php endif; ?>
<form method="post" data-validate="password" novalidate>
    <?= csrf_field() ?>
    <input 
    type="hidden" 
    name="id" 
    value="<?= $id ?>"
    >
  <div class="form-grid">
    <div>
        <label for="title">
            Website / Service Name *
        </label>
        <input 
        id="title" 
        name="title" 
        required maxlength="100" 
        value="<?= h($values['title']) ?>" 
        placeholder="e.g. GitHub"
        >
    </div>
    <div>
        <label for="website_url">
            Website URL
        </label>
        <input 
        
        id="website_url" 
        name="website_url" type="url" 
        maxlength="255" 
        value="<?= h($values['website_url']) ?>" 
        placeholder="https://github.com"
        >
    </div>
</div>
  <label for="username">
    Username / Email *
</label>
<input 
id="username" 
name="username" 
required maxlength="150" 
value="<?= h($values['username']) ?>" 
placeholder="you@example.com"
>
  <label for="password">
    Password 
    <?= $editing ? '(leave blank to keep current)' : '*' ?>
</label>
<div class="input-adorn">
    <input 
    id="password" 
    name="password" 
    type="password" 
    autocomplete="new-password" 
    <?= $editing ? '' : 'required' ?> 
    maxlength="1000" placeholder="<?= $editing 
    ? 'Enter a new password to replace current' 
    : 'Enter or generate a password' ?>">
    <button 
    class="eye-btn" 
    type="button" 
    data-toggle-password="password">
    Show
</button>
</div>
<div class="inline-options">
    <button id="generate-password" class="btn btn-ghost btn-small" type="button">↻ Generate random password</button><span class="form-hint">Generated locally in your browser</span></div>
  <label for="category_id">Category</label><select id="category_id" name="category_id"><option value="">Uncategorized</option><?php foreach (categories_for_user($uid) as $cat): ?><option value="<?= (int)$cat['category_id'] ?>" <?= (string)$cat['category_id'] === $values['category_id'] ? 'selected' : '' ?>><?= h($cat['category_name']) ?></option><?php endforeach; ?></select>
  <label for="notes">Additional Notes</label><textarea id="notes" name="notes" rows="4" maxlength="4000" placeholder="Optional notes..."><?= h($values['notes']) ?></textarea>
  <div class="form-actions"><a href="passwords.php" class="btn btn-muted">Cancel</a><button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Save Password' ?></button></div>
</form></div></main>
<?php render_footer(); ?>
