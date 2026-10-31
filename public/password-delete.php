<?php
require_once __DIR__ . '/../src/bootstrap.php';
$user = require_login(); only_post(); verify_csrf();
$id = request_id('id', 'POST');
if ($id > 0) {
    $query = db()->prepare('DELETE FROM password_entries WHERE password_id = ? AND user_id = ?');
    $query->execute([$id, (int)$user['user_id']]);
    if ($query->rowCount()) audit('password_deleted', 'Deleted a vault entry', (int)$user['user_id']);
}
flash('success', 'Password entry removed, if it belonged to your vault.');
redirect('passwords.php');
