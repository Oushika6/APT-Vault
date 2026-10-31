<?php
require_once __DIR__ . '/../src/bootstrap.php';
$user = require_login();
$stmt = db()->prepare('SELECT p.password_id, p.title, p.username, p.website_url, p.created_at, p.updated_at, c.category_name FROM password_entries p LEFT JOIN categories c ON p.category_id = c.category_id AND c.user_id = p.user_id WHERE p.user_id = ? ORDER BY p.updated_at DESC');
$stmt->execute([(int)$user['user_id']]);
$entries = $stmt->fetchAll();
render_header('My Passwords', 'passwords');
?>
<main class="container main-content"><div class="page-head"><div><p class="eyebrow">YOUR PRIVATE VAULT</p><h1>My Passwords</h1><p>Manage passwords without leaving them exposed.</p></div><a href="password-form.php" class="btn btn-primary">+ Add Password</a></div>
<div class="toolbar"><label class="search-wrap"><span class="sr-only">Search passwords</span><input id="password-search" type="search" placeholder="Search passwords..."></label><div class="filter-row" data-filter-group="passwords"><button type="button" class="chip active" data-filter="all">All</button><?php foreach (categories_for_user((int)$user['user_id']) as $category): ?><button type="button" class="chip" data-filter="<?= h($category['category_name']) ?>"><?= h($category['category_name']) ?></button><?php endforeach; ?></div></div>
<div class="panel table-panel"><div class="table-scroll"><table class="vault-table"><thead><tr><th>Service / Website</th><th>Username / Email</th><th>Category</th><th>Password</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($entries as $row): ?><tr class="password-row" data-search="<?= h(strtolower($row['title'] . ' ' . $row['username'] . ' ' . ($row['category_name'] ?? ''))) ?>" data-category="<?= h($row['category_name'] ?? '') ?>">
  <td><strong class="service-name"><?= h($row['title']) ?></strong><?php if ($row['website_url']): ?><small class="sub-line"><?= h($row['website_url']) ?></small><?php endif; ?></td>
  <td><?= h($row['username']) ?></td><td><span class="category-pill"><?= h($row['category_name'] ?? 'Uncategorized') ?></span></td>
  <td><span class="secret-label" id="secret-<?= (int)$row['password_id'] ?>">••••••••••••••••</span></td>
  <td><div class="table-actions"><button type="button" class="icon-action reveal-secret" data-id="<?= (int)$row['password_id'] ?>" data-csrf="<?= h(csrf_token()) ?>" aria-label="Reveal or hide password" title="Reveal / hide">◉</button><button type="button" class="icon-action copy-secret" data-id="<?= (int)$row['password_id'] ?>" data-csrf="<?= h(csrf_token()) ?>" aria-label="Copy password" title="Copy password">▣</button><a href="password-form.php?id=<?= (int)$row['password_id'] ?>" class="icon-action" aria-label="Edit <?= h($row['title']) ?>" title="Edit">✎</a><form action="password-delete.php" method="post" class="inline-form" data-confirm="Delete this password entry?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$row['password_id'] ?>"><button type="submit" class="icon-action danger" aria-label="Delete <?= h($row['title']) ?>" title="Delete">✕</button></form></div></td>
</tr><?php endforeach; ?>
</tbody></table></div><p class="empty-list" id="password-empty" <?= $entries ? 'hidden' : '' ?>>No matching passwords. Add your first credential or change the search.</p></div>
<p class="form-hint">Passwords remain encrypted in MySQL and are retrieved only after an authenticated, owner-checked request. Use fictional passwords in this coursework application.</p></main>
<?php render_footer(); ?>
