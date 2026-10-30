<?php
require_once __DIR__ . '/../src/bootstrap.php';
$user = current_user();
$errors = [];
$values = ['name'=>$user['full_name'] ?? '', 'email'=>$user['email'] ?? '', 'subject'=>'', 'message'=>''];
if (is_post()) {
    verify_csrf();
    foreach ($values as $name=>$_) $values[$name] = request_string($name, $name === 'message' ? 3001 : 181);
    if (strlen($values['name']) < 2 || strlen($values['name']) > 100) $errors[] = 'Full name is required (2–100 characters).';
    if (strlen($values['email']) > 150 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($values['subject']) < 3 || strlen($values['subject']) > 150) $errors[] = 'Subject must be 3–150 characters.';
    if (strlen($values['message']) < 10 || strlen($values['message']) > 3000) $errors[] = 'Message must be 10–3000 characters.';
    if (!$errors) {
        db()->prepare("INSERT INTO support_requests (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'open')")
            ->execute(array_values($values));
        flash('success', 'Your message was received. Thank you for contacting APT Vault.');
        redirect('contact.php');
    }
}
render_header('Contact');
?>
<main class="container main-content contact-page"><p class="eyebrow">CONTACT</p><h1>Get in Touch</h1><p>Have a question or need support? We're here to help.</p><div class="panel contact-panel">
<?php if ($errors): ?><div class="form-errors" role="alert"><?= h(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="post" data-validate="contact" novalidate><?= csrf_field() ?>
<div class="form-grid"><div><label for="name">Full Name *</label><input name="name" id="name" required maxlength="100" value="<?= h($values['name']) ?>" placeholder="Alex Johnson"></div><div><label for="email">Email Address *</label><input type="email" name="email" id="email" required maxlength="150" value="<?= h($values['email']) ?>" placeholder="alex@example.com"></div></div>
<label for="subject">Subject *</label><input id="subject" name="subject" maxlength="150" required value="<?= h($values['subject']) ?>" placeholder="How can we help?">
<label for="message">Message *</label><textarea id="message" name="message" rows="8" maxlength="3000" required placeholder="Describe your question or issue..."><?= h($values['message']) ?></textarea>
<button class="btn btn-primary btn-full" type="submit">↗ &nbsp; Send Message</button>
</form></div></main>
<?php render_footer(); ?>
