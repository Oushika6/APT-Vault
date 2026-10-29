<?php
/*
 * admin-action.php
 * Receives the "Suspend", "Reactivate" and "Resolve" buttons from admin.php.
 * This file shows no HTML: it checks the request, updates the database,
 * writes the security log, then sends the admin back to admin.php.
 */
require_once __DIR__ . '/../src/bootstrap.php';

// 1. Only an administrator may use this file
$admin = require_admin();
$adminId = (int)$admin['user_id'];

// 2. Only accept POST: a change must never happen by simply opening a link (GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

// 3. Check the CSRF token, so another website
//    can not trick the admin's browser into pressing these buttons
verify_csrf();

// 4. Read and check the values sent by the form.
//    Hidden fields can be edited in the browser, so we never trust them.
$type = trim($_POST['type'] ?? '');
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    http_response_code(400);
    exit('Invalid record.');
}


if ($type === 'account') {
    // ---------- Suspend or reactivate a user account ----------
    $status = trim($_POST['status'] ?? '');

    // Allow-list: only these two values are accepted
    if ($status === 'active') {
        $isActive = 1;
    } elseif ($status === 'suspended') {
        $isActive = 0;
    } else {
        http_response_code(400);
        exit('Invalid status.');
    }

    // The WHERE part is a second safety check: only accounts with the role 'user'
    // can change, and never the admin's own account.
    $stmt = db()->prepare("UPDATE users
                           SET is_active = :is_active
                           WHERE user_id = :user_id AND role = 'user' AND user_id <> :admin_id");
    $stmt->execute([
        'is_active' => $isActive,
        'user_id' => $id,
        'admin_id' => $adminId
    ]);

    // rowCount() = how many rows really changed. Only log a real change.
    if ($stmt->rowCount() > 0) {
        audit('admin_account_' . $status, 'Changed account status (user ID ' . $id . ')', $adminId);
    }
    flash('success', 'Account status updated where permitted.');

} elseif ($type === 'support') {
    // ---------- Mark a support request as resolved ----------
    $stmt = db()->prepare("UPDATE support_requests
                           SET status = 'resolved'
                           WHERE request_id = :request_id AND status = 'open'");
    $stmt->execute(['request_id' => $id]);

    if ($stmt->rowCount() > 0) {
        audit('support_resolved', 'Resolved support request ID ' . $id, $adminId);
    }
    flash('success', 'Support request marked resolved.');

} else {
    // Any other "type" is not allowed
    http_response_code(400);
    exit('Invalid action.');
}


// 5. Go back to the admin page.
//    (Post/Redirect/Get: pressing refresh will not repeat the action.)
header('Location: admin.php');
exit;