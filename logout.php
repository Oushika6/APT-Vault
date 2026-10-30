<?php
require_once __DIR__ . '/../src/bootstrap.php';
only_post();
verify_csrf();
$user = current_user();
if ($user) audit('logout', 'Signed out', (int)$user['user_id']);
sign_out();
header('Location: login.php', true, 303);
exit;
