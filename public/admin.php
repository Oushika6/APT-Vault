<?php
/*
 * admin.php
 * Administrator dashboard: registered users, support requests and the security log.
 * The admin can NOT see anyone's stored passwords or notes:
 * the password_entries and secure_notes tables are never read on this page.
 */
require_once __DIR__ . '/../src/bootstrap.php';

// Stop here unless the visitor is logged in AND has the role 'admin' (otherwise: error 403)
$admin = require_admin();
$adminId = (int)$admin['user_id'];

// These three SQL commands contain no user input, so query() is safe here.

// 1. Registered users (password_hash is NOT selected - the admin never needs it)
$stmt = db()->query('SELECT user_id, full_name, email, role, is_active, created_at
                     FROM users
                     ORDER BY created_at DESC
                     LIMIT 100');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Messages sent from the contact page
$stmt = db()->query('SELECT request_id, name, email, subject, message, status, created_at
                     FROM support_requests
                     ORDER BY created_at DESC
                     LIMIT 50');
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. The 50 newest security log entries.
//    LEFT JOIN adds the email of the user who did the action.
//    For a failed login we do not know the user, so the email is empty (NULL).
$stmt = db()->query('SELECT l.action, l.description, l.created_at, u.email AS account_email
                     FROM security_logs l
                     LEFT JOIN users u ON l.user_id = u.user_id
                     ORDER BY l.created_at DESC, l.log_id DESC
                     LIMIT 50');
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

render_header('Administrator', 'admin');
?>
<main class="container main-content">

  <div class="page-head">
    <div>
      <p class="eyebrow">ADMINISTRATOR ACCESS</p>
      <h1>Admin Dashboard</h1>
      <p>Manage user account statuses and support messages. Private vault entries are intentionally inaccessible.</p>
    </div>
  </div>

  <!-- ========== Registered users ========== -->
  <div class="panel pad-panel">
    <h2>Registered Users</h2>
    <div class="table-scroll">
      <table class="vault-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Registered</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $row): ?>
          <?php
          // Decide the status text and which button this user gets
          if ((int)$row['is_active'] === 1) {
              $statusText = 'active';
              $newStatus = 'suspended';
              $buttonText = 'Suspend';
          } else {
              $statusText = 'suspended';
              $newStatus = 'active';
              $buttonText = 'Reactivate';
          }

          // An admin can not suspend their own account or another admin
          $canChange = ((int)$row['user_id'] !== $adminId) && ($row['role'] !== 'admin');
          ?>
          <tr>
            <td><?= h($row['full_name']) ?></td>
            <td><?= h($row['email']) ?></td>
            <td><?= h($row['role']) ?></td>
            <td><span class="category-pill"><?= $statusText ?></span></td>
            <td><?= h($row['created_at']) ?></td>
            <td>
              <?php if ($canChange): ?>
                <form method="post" action="admin-action.php" class="inline-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="type" value="account">
                  <input type="hidden" name="id" value="<?= (int)$row['user_id'] ?>">
                  <input type="hidden" name="status" value="<?= $newStatus ?>">
                  <button type="submit" class="btn btn-ghost btn-small"><?= $buttonText ?></button>
                </form>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ========== Support requests ========== -->
  <div class="panel pad-panel spaced-panel">
    <h2>Support Requests</h2>
    <div class="table-scroll">
      <table class="vault-table">
        <thead>
          <tr>
            <th>From</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if (count($requests) === 0): ?>
          <tr><td colspan="5">No support requests yet.</td></tr>
        <?php endif; ?>

        <?php foreach ($requests as $request): ?>
          <tr>
            <td>
              <?= h($request['name']) ?>
              <small class="sub-line"><?= h($request['email']) ?></small>
            </td>
            <td><?= h($request['subject']) ?></td>
            <td class="message-cell"><?= h($request['message']) ?></td>
            <td><?= h($request['status']) ?></td>
            <td>
              <?php if ($request['status'] === 'open'): ?>
                <form method="post" action="admin-action.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="type" value="support">
                  <input type="hidden" name="id" value="<?= (int)$request['request_id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-small">Resolve</button>
                </form>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ========== Security log ========== -->
  <div class="panel pad-panel spaced-panel">
    <h2>Recent Audit Events</h2>
    <p class="form-hint">Audit records contain action metadata, never passwords or note content.</p>
    <div class="table-scroll">
      <table class="vault-table">
        <thead>
          <tr>
            <th>Event</th>
            <th>Account</th>
            <th>Description</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
        <?php if (count($logs) === 0): ?>
          <tr><td colspan="4">No events recorded yet.</td></tr>
        <?php endif; ?>

        <?php foreach ($logs as $log): ?>
          <tr>
            <td><?= h($log['action']) ?></td>
            <td><?= h($log['account_email'] ?? 'Unknown / unauthenticated') ?></td>
            <td><?= h($log['description']) ?></td>
            <td><?= h($log['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>
<?php render_footer(); ?>