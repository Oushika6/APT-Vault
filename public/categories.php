<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login();
$uid = (int) $user['user_id'];

if (is_post()) {
    verify_csrf();

    $action = request_string('action', 20);
    $id = request_id('id', 'POST');
    $name = request_string('category_name', 51);

    if (in_array($action, ['create', 'rename'], true)) {
        if (strlen($name) < 2 || strlen($name) > 50) {
            flash('error', 'Category name must be 2–50 characters.');
            redirect('categories.php');
        }

        try {

            if ($action === 'create') {
                db()->prepare(
                    'INSERT INTO categories
                        (user_id, category_name)
                    VALUES (?, ?)'
                )->execute([
                    $uid,
                    $name
                ]);
            } elseif ($id > 0) {
                db()->prepare(
                    'UPDATE categories
                    SET category_name = ?
                    WHERE category_id = ?
                    AND user_id = ?'
                )->execute([
                    $name,
                    $id,
                    $uid
                ]);
            }

            audit(
                'category_' . $action,
                'Changed a category',
                $uid
            );
            flash('success', 'Category saved.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                flash(
                    'error',
                    'You already have a category with that name.'
                );
            } else {
                throw $e;
            }
        }
    } elseif ($action === 'delete' && $id > 0) {
        db()->prepare(
            'DELETE FROM categories
            WHERE category_id = ?
            AND user_id = ?'
        )->execute([
            $id,
            $uid
        ]);
        audit(
            'category_deleted',
            'Removed a category',
            $uid
        );
        flash(
            'success',
            'Category removed. Existing items are now uncategorized.'
        );
    }
    redirect('categories.php');
}

$categories = categories_for_user($uid);

render_header('Categories', 'categories');

?>
<main class="container main-content">
    <div class="page-head">
        <div>
            <p class="eyebrow">ORGANIZATION</p>
            <h1>Categories</h1>
            <p>Create categories to organize passwords and notes.</p>
        </div>
    </div>

    <div class="dashboard-columns">
        <div class="panel pad-panel">
            <h2>Create Category</h2>

            <form method="post" data-validate="category">
                <?= csrf_field() ?>
                <input
                    type="hidden"
                    name="action"
                    value="create"
                >
                <label for="category_name">
                    Category name
                </label>
                <input
                    id="category_name"
                    name="category_name"
                    required
                    minlength="2"
                    maxlength="50"
                    placeholder="e.g. Social Media"
                >
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    + Create Category
                </button>
            </form>
        </div>

        <div class="panel pad-panel">
            <h2>Your Categories</h2>

            <?php if (!$categories): ?>
                <p>No categories created yet.</p>
            <?php else: ?>

                <?php foreach ($categories as $cat): ?>
                    <div class="category-item">
                        <form
                            method="post"
                            class="category-rename"
                            data-validate="category"
                        >
                            <?= csrf_field() ?>
                            <input
                                type="hidden"
                                name="action"
                                value="rename"
                            >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $cat['category_id'] ?>"
                            >
                            <input
                                name="category_name"
                                aria-label="Category name"
                                value="<?= h($cat['category_name']) ?>"
                                required
                                minlength="2"
                                maxlength="50"
                            >
                            <button
                                class="btn btn-ghost btn-small"
                                type="submit"
                            >
                                Rename
                            </button>
                        </form>
                        <form
                            method="post"
                            class="inline-form"
                            data-confirm="Delete this category? Its items will remain, uncategorized."
                        >
                            <?= csrf_field() ?>
                            <input
                                type="hidden"
                                name="action"
                                value="delete"
                            >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $cat['category_id'] ?>"
                            >
                            <button
                                type="submit"
                                class="link-button danger"
                            >
                                Delete
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php render_footer(); ?>
