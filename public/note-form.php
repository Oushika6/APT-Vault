<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int)$user['user_id'];

$id = is_post() ? request_id('id', 'POST') : request_id('id');
$editing = $id > 0;

$values = [
    'title' => '',
    'category_id' => '',
    'content' => ''
];

if ($editing) {
    $stmt = db()->prepare('SELECT * FROM secure_notes WHERE note_id=? AND user_id=?');
    $stmt->execute([$id, $uid]);
    $note = $stmt->fetch();

    if (!$note) {
        http_response_code(404);
        exit('Secure note not found.');
    }

    $values = [
        'title' => $note['title'],
        'category_id' => (string)($note['category_id'] ?? ''),
        'content' => decrypt_secret($note['encrypted_content'])
    ];
}

$errors = [];

if (is_post()) {
    verify_csrf();

    $values['title'] = request_string('title', 151);
    $values['category_id'] = request_string('category_id', 20);
    $values['content'] = request_string('content', 10001);

    if (strlen($values['title']) < 2 || strlen($values['title']) > 150)
        $errors[] = 'Note title must be 2–150 characters.';

    if (strlen($values['content']) < 1 || strlen($values['content']) > 10000)
        $errors[] = 'Note content is required (up to 10,000 characters).';

    try {
        $categoryId = category_for_user($values['category_id'], $uid);
    } catch (InvalidArgumentException $e) {
        $errors[] = $e->getMessage();
        $categoryId = null;
    }

    if (!$errors) {
        $encrypted = encrypt_secret($values['content']);

        if ($editing) {
            db()->prepare('UPDATE secure_notes SET title=?, encrypted_content=?, category_id=? WHERE note_id=? AND user_id=?')
                ->execute([$values['title'], $encrypted, $categoryId, $id, $uid]);
            audit('note_updated', 'Edited a secure note', $uid);
        } else {
            db()->prepare('INSERT INTO secure_notes (user_id, category_id, title, encrypted_content) VALUES (?, ?, ?, ?)')
                ->execute([$uid, $categoryId, $values['title'], $encrypted]);
            audit('note_created', 'Created a secure note', $uid);
        }

        flash('success', $editing ? 'Secure note updated.' : 'Your note was saved securely.');
        redirect('notes.php');
    }
}

render_header($editing ? 'Edit Secure Note' : 'Add Secure Note', 'notes');
?>
<main class="container main-content">
    <div class="form-panel note-form-panel">
        <div class="form-panel-head">
            <a href="notes.php" class="back-link">← Secure Notes</a><h1><?= $editing ? 'Edit Secure Note' : 'Add Secure Note' ?></h1>
        </div>

        <?php if ($errors): ?>
            <div class="form-errors" role="alert"><?= h(implode(' ', $errors)) ?></div>
        <?php endif; ?>

        <form method="post" data-validate="note" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="form-grid">
                <div>
                    <label for="title">Note Title *</label><input id="title" name="title" required maxlength="150" value="<?= h($values['title']) ?>" placeholder="e.g. University project information">
                </div>
                <div>
                    <label for="category_id">Category</label><select name="category_id" id="category_id">
                        <option value="">Uncategorized</option>
                        <?php foreach (categories_for_user($uid) as $category): ?>
                            <option value="<?= (int)$category['category_id'] ?>" <?= $values['category_id'] === (string)$category['category_id'] ? 'selected' : '' ?>><?= h($category['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label for="content">Secure Note Content *</label><textarea id="content" name="content" rows="12" required maxlength="10000" placeholder="Enter your private content..."><?= h($values['content']) ?></textarea><p class="form-hint">This content is encrypted before being written to MySQL.</p>

            <div class="form-actions">
                <a href="notes.php" class="btn btn-muted">Cancel</a><button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Save Note' ?></button>
            </div>
        </form>
    </div>
</main>
<?php render_footer(); ?>
