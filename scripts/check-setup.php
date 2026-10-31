<?php
/*
 * check-setup.php
 * Checks that PHP, the .env file and the database are ready for APT Vault.
 * Run it from the command prompt, inside the project folder:
 *     C:\xampp\php\php.exe scripts\check-setup.php
 */

// This is a command-line tool only: refuse to run inside a web browser
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This tool can only be run from the command line.');
}

require __DIR__ . '/../src/helpers.php';  // for env_config()
require __DIR__ . '/../src/db.php';       // for db()
require __DIR__ . '/../src/crypto.php';   // for vault_key()

echo "APT Vault configuration check\n";


// 1. The PHP extensions the website needs
$extensions = ['pdo_mysql', 'openssl', 'session'];

foreach ($extensions as $extension) {
    if (extension_loaded($extension)) {
        echo "  $extension: enabled\n";
    } else {
        echo "  $extension: MISSING\n";
    }
}


// 2. The encryption key in .env
//    vault_key()  throws an error if APP_KEY is missing or the wrong size.
try {
    vault_key();
    echo "  APP_KEY: configured (32 bytes)\n";
} catch (Throwable $e) {
    echo "  APP_KEY: NOT configured. Run generate-key.php and edit .env\n";
}


// 3. The database connection and the 7 tables
try {
    $pdo = db();
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "  MySQL: connected ($version)\n";

    $tables = [
        'users',
        'categories',
        'password_entries',
        'secure_notes',
        'support_requests',
        'security_logs',
        'password_reset_tokens'
    ];

    foreach ($tables as $table) {
        // The table names are written by us, not typed by a user.
        // quote() still puts safe quotes around each name: 'users'
        $result = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));

        if ($result->fetchColumn()) {
            echo "  $table: exists\n";
        } else {
            echo "  $table: MISSING\n";
        }
    }
} catch (Throwable $e) {
    echo "  MySQL: NOT connected. Verify MySQL is running, pdo_mysql is enabled and .env is correct.\n";
    exit(1);
}