<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int) $user['user_id'];

$stmt = db()->prepare(
    'SELECT
        n.note_id,
        n.title,
        n.encrypted_content,
        n.updated_at,
        c.category_name
    FROM secure_notes n
    LEFT JOIN categories c
        ON n.category_id = c.category_id
        AND c.user_id = n.user_id
    WHERE n.user_id = ?
    ORDER BY n.updated_at DESC'
);

$stmt->execute([$uid]);
$notes = $stmt->fetchAll();

render_header('Secure Notes', 'notes');
?>

<main class="container main-content">
    <div class="page-head">
        <div>
            <p class="eyebrow">ENCRYPTED NOTES</p>
            <h1>Secure Notes</h1>
            <p>Your private notes, stored with authenticated encryption.</p>
        </div>
        <a class="btn btn-primary" href="note-form.php">
            + Add Note
        </a>
    </div>

    <div class="toolbar">
        <label class="search-wrap">
            <span class="sr-only">
                Search notes
            </span>
            <input
                id="notes-search"
                type="search"
                placeholder="Search notes..."
            >
        </label>
        <div class="filter-row" data-filter-group="notes">
            <button
                type="button"
                class="chip active"
                data-filter="all"
            >
                All
            </button>
            <?php foreach (categories_for_user($uid) as $category): ?>
                <button
                    type="button"
                    class="chip"
                    data-filter="<?= h($category['category_name']) ?>"
                >
                    <?= h($category['category_name']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="notes-grid">
        <?php foreach ($notes as $note): ?>
            <?php
            try {
                $content = decrypt_secret($note['encrypted_content']);
            } catch (Throwable $e) {
                $content = '[Could not decrypt note; verify APP_KEY]';
            }
            ?>

            <article
                class="note-card"
                data-search="<?= h(strtolower($note['title'] . ' ' . ($note['category_name'] ?? '') . ' ' . $content)) ?>"
                data-category="<?= h($note['category_name'] ?? '') ?>"
            >
                <h2>
                    <?= h($note['title']) ?>
                </h2>
                <p class="note-preview">
                    <?= h(
                        strlen($content) > 180
                            ? substr($content, 0, 180) . '…'
                            : $content
                    ) ?>
                </p>
                <div class="note-meta">
                    <span class="category-pill">
                        <?= h($note['category_name'] ?? 'Uncategorized') ?>
                    </span>
                    <span class="encrypted-pill">
                        Encrypted
                    </span>
                </div>
                <div class="note-actions">
                    <a href="note-form.php?id=<?= (int) $note['note_id'] ?>">
                        View / Edit
                    </a>
                    <form
                        action="note-delete.php"
                        method="post"
                        class="inline-form"
                        data-confirm="Permanently delete this note?"
                    >
                        <?= csrf_field() ?>
                        <input
                            name="id"
                            value="<?= (int) $note['note_id'] ?>"
                            type="hidden"
                        >
                        <button
                            type="submit"
                            class="link-button danger"
                        >
                            Delete
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <p
        class="empty-list"
        id="notes-empty"
        <?= $notes ? 'hidden' : '' ?>
    >
        No matching notes found. Create a secure note or change your search.
    </p>

</main>

<?php render_footer(); ?>
