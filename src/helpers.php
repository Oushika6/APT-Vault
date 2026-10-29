<?php
/*
 * helpers.php
 * Small reusable functions that every page of APT Vault can use.
 *
 * Member 6: env_config, h, redirect, flash, take_flash, request_string,
 *           request_id, audit (security log), is_post
 * Member 5: category_for_user, categories_for_user
 */


/* ------------------------------------------------------------------
 * Read one setting (database name, encryption key...) from the .env file.
 * Example: env_config('DB_NAME', 'aptvault') gives "aptvault".
 * ------------------------------------------------------------------ */
function env_config($name, $default = null)
{
    // 1. If the server already has this setting as an environment variable, use it.
    $serverValue = getenv($name);
    if ($serverValue !== false) {
        return $serverValue;
    }

    // 2. Otherwise read the .env file (it is in the project folder, one level above src).
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) {
        return $default;
    }

    // file() gives us the file as an array, one line per item
    $lines = file($envFile, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip empty lines and comment lines that start with #
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Split "DB_NAME=aptvault" into "DB_NAME" and "aptvault"
        // (the 2 means: split only at the FIRST "=", because the key itself can end with "=")
        $parts = explode('=', $line, 2);

        if (count($parts) === 2 && trim($parts[0]) === $name) {
            // Remove spaces, then remove quotes, so DB_PASS="" becomes an empty password
            $value = trim($parts[1]);
            return trim($value, '"');
        }
    }

    // The setting was not found in the file
    return $default;
}


/* ------------------------------------------------------------------
 * Make text safe to print inside HTML. This is our protection against XSS.
 * <script> becomes &lt;script&gt; so the browser shows it as text and does not run it.
 * ------------------------------------------------------------------ */
function h($value)
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* ------------------------------------------------------------------
 * Send the browser to another page of OUR website, then stop the script.
 * ------------------------------------------------------------------ */
function redirect($page)
{
    // Only allow a simple page name such as "dashboard.php" (optionally "page.php?x=1").
    // A full address such as "https://evil.com" is refused, so nobody can use our
    // site to send users to another website.
    if (!preg_match('/^[a-zA-Z0-9_-]+\.php(\?[a-zA-Z0-9_=&-]*)?$/', $page)) {
        $page = 'index.php';
    }

    header('Location: ' . $page);
    exit;
}


/* ------------------------------------------------------------------
 * Save a one-time message (e.g. "Password saved") to show on the next page.
 * $type is 'success' or 'error'. It is kept in the session.
 * ------------------------------------------------------------------ */
function flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}


/* ------------------------------------------------------------------
 * Read the one-time message and delete it, so it is shown only once.
 * Returns null when there is no message.
 * ------------------------------------------------------------------ */
function take_flash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $message;
}


/* ------------------------------------------------------------------
 * Read a text field sent by a POST form: trimmed and cut to a maximum length.
 * Example: request_string('email', 180)
 * ------------------------------------------------------------------ */
function request_string($key, $maxLength = 10000)
{
    $value = $_POST[$key] ?? '';

    // A normal form field is text. Anything else (for example an array
    // sent by an attacker with name="email[]") is ignored.
    if (!is_string($value)) {
        return '';
    }

    // Keep ONE extra character, so the page's own check can still notice
    // that the user typed too much (e.g. strlen($title) > 150).
    $value = substr($value, 0, $maxLength + 1);

    return trim($value);
}


/* ------------------------------------------------------------------
 * Read an ID number from the address bar (GET) or from a form (POST).
 * Returns the number, or 0 if it is not a positive whole number.
 * Example: request_id('id') for passwords.php?id=5  ->  5
 * ------------------------------------------------------------------ */
function request_id($key, $method = 'GET')
{
    if ($method === 'POST') {
        $value = $_POST[$key] ?? '';
    } else {
        $value = $_GET[$key] ?? '';
    }

    // FILTER_VALIDATE_INT with min_range 1: only 1, 2, 3 ... are accepted
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($id === false) {
        return 0;
    }
    return $id;
}


/* ------------------------------------------------------------------
 * MEMBER 6 - SECURITY LOG
 * Writes one line into the security_logs table: WHO did WHAT.
 * The date and time are added automatically by MySQL (created_at).
 *
 * Examples used by the team:
 *   audit('login_failure', 'Invalid sign-in attempt');
 *   audit('password_deleted', 'Deleted a vault entry', $userId);
 *
 * RULE: never pass a password or the content of a note to this function.
 * ------------------------------------------------------------------ */
function audit($action, $description = '', $userId = null)
{
    try {
        $sql = 'INSERT INTO security_logs (user_id, action, description)
                VALUES (:user_id, :action, :description)';

        // Prepared statement: the values are sent separately from the SQL,
        // so they can never change the SQL command (no SQL injection).
        $stmt = db()->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description
        ]);
    } catch (Throwable $e) {
        // If writing the log fails, do NOT crash the website for the user.
        // The problem is written to the server's private error log instead.
        error_log('APT Vault audit insert failed: ' . $e->getMessage());
    }
}


/* ------------------------------------------------------------------
 * MEMBER 5 - Check that a chosen category belongs to the logged-in user.
 * Returns the category ID, or null for "Uncategorized".
 * If the category is not allowed, it throws an error message that the page shows.
 * ------------------------------------------------------------------ */
function category_for_user($id, $userId)
{
    // No category chosen = "Uncategorized"
    if ($id === null || $id === '') {
        return null;
    }

    $categoryId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($categoryId === false) {
        throw new InvalidArgumentException('Invalid category.');
    }

    // The category must exist AND belong to this user
    $stmt = db()->prepare('SELECT category_id FROM categories
                           WHERE category_id = :category_id AND user_id = :user_id');
    $stmt->execute([
        'category_id' => $categoryId,
        'user_id' => $userId
    ]);

    if ($stmt->fetch(PDO::FETCH_ASSOC) === false) {
        throw new InvalidArgumentException('Category does not belong to your account.');
    }

    return $categoryId;
}


/* ------------------------------------------------------------------
 * MEMBER 5 - Get all categories of one user, in alphabetical order.
 * ------------------------------------------------------------------ */
function categories_for_user($userId)
{
    $stmt = db()->prepare('SELECT category_id, category_name FROM categories
                           WHERE user_id = :user_id
                           ORDER BY category_name');
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/* ------------------------------------------------------------------
 * true when the page was sent by a form with method="post".
 * ------------------------------------------------------------------ */
function is_post()
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    return $method === 'POST';
}