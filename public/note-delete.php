<?php
require_once __DIR__ . '/../src/bootstrap.php';
$user = require_login(); only_post(); verify_csrf();
$id = request_id('id', 'POST');
if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM secure_notes WHERE note_id = ? AND user_id = ?');
    $stmt->execute([$id, (int)$user['user_id']]);
    if ($stmt->rowCount()) audit('note_deleted', 'Deleted a secure note', (int)$user['user_id']);
}
flash('success', 'Note removed, if it belonged to your vault.');
redirect('notes.php');
