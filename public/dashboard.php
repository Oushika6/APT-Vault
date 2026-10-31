<?php
require_once __DIR__ . '/../src/bootstrap.php';
$user = require_login();
$id = (int)$user['user_id'];
$stmt = db()->prepare('SELECT COUNT(*) FROM password_entries WHERE user_id = ?'); $stmt->execute([$id]); $pwCount = $stmt->fetchColumn();
$stmt = db()->prepare('SELECT COUNT(*) FROM secure_notes WHERE user_id = ?'); $stmt->execute([$id]); $noteCount = $stmt->fetchColumn();
$stmt = db()->prepare('SELECT COUNT(*) FROM categories WHERE user_id = ?'); $stmt->execute([$id]); $catCount = $stmt->fetchColumn();
$stmt = db()->prepare('SELECT title, updated_at FROM password_entries WHERE user_id = ? ORDER BY updated_at DESC LIMIT 4'); $stmt->execute([$id]); $recent = $stmt->fetchAll();
render_header('Dashboard', 'dashboard');
?>
<main class="container main-content"><div class="page-head"><div><p class="eyebrow">PERSONAL VAULT</p><h1>Welcome back, <?= h($user['full_name']) ?></h1><p>Your private information, organized in one place.</p></div><a href="password-form.php" class="btn btn-primary">+ Add Password</a></div>
<div class="stats-grid"><div class="stat-card"><span>Stored passwords</span><strong><?= (int)$pwCount ?></strong><a href="passwords.php">View passwords →</a></div><div class="stat-card"><span>Secure notes</span><strong><?= (int)$noteCount ?></strong><a href="notes.php">View notes →</a></div><div class="stat-card"><span>Categories</span><strong><?= (int)$catCount ?></strong><a href="categories.php">Manage categories →</a></div></div>
<div class="dashboard-columns"><section class="panel"><div class="panel-head"><h2>Quick Actions</h2></div><div class="quick-actions"><a href="password-form.php"><span>⊕</span><b>Add a password</b><small>Store new login details</small></a><a href="note-form.php"><span>▤</span><b>Create a secure note</b><small>Protect important information</small></a><a href="categories.php"><span>▦</span><b>Organize categories</b><small>Keep everything easy to find</small></a></div></section><section class="panel"><div class="panel-head"><h2>Recently updated passwords</h2><a href="passwords.php">See all</a></div><?php if (!$recent): ?><p class="empty-mini">No saved passwords yet.</p><?php else: ?><div class="recent-list"><?php foreach ($recent as $item): ?><div><b><?= h($item['title']) ?></b><small><?= h($item['updated_at']) ?></small></div><?php endforeach; ?></div><?php endif; ?></section></div>
</main>
<?php render_footer(); ?>
