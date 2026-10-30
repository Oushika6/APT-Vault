<?php
require_once __DIR__ . '/../src/bootstrap.php';
private_response();
header('Content-Type: application/json; charset=utf-8');
$user = require_login(); only_post(); verify_csrf();
$id = request_id('id', 'POST');
$stmt = db()->prepare('SELECT encrypted_password FROM password_entries WHERE password_id = ? AND user_id = ?');
$stmt->execute([$id, (int)$user['user_id']]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); echo json_encode(['error'=>'Entry not found']); exit; }
try {
    $secret = decrypt_secret($row['encrypted_password']);
    audit('password_revealed', 'Vault secret accessed', (int)$user['user_id']);
    echo json_encode(['password'=>$secret], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('APT Vault password decryption failure');
    http_response_code(500);
    echo json_encode(['error'=>'Unable to decrypt this entry']);
}
