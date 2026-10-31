<?php
/*
 * db.php - Member 6 (database coordination)
 * Opens ONE connection to the MySQL database with PDO.
 * Every page gets the connection by calling db(), for example:
 *     $stmt = db()->prepare('SELECT ... WHERE user_id = :id');
 */

function db()
{
    // "static" makes PHP remember $pdo between calls to db().
    // So one page uses ONE connection, instead of opening a new one each time.
    static $pdo = null;

    if ($pdo === null) {
        // The connection details come from the .env file, not from the code,
        // so the password never ends up on GitHub.
        $host = env_config('DB_HOST', '127.0.0.1');
        $port = env_config('DB_PORT', '3306');
        $dbname = env_config('DB_NAME', 'aptvault');
        $username = env_config('DB_USER', 'root');
        $password = env_config('DB_PASS', '');

        // DSN = which driver, which server, which database, which character set
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

        $pdo = new PDO($dsn, $username, $password, [
            // If an SQL command fails, PHP throws an exception (an error we can catch)
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            // Rows come back with column names as keys: $row['email']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Use REAL prepared statements in MySQL (values stay separate from the SQL)
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }

    return $pdo;
}