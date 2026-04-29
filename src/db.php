<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    die('Direct access not permitted.');
}

require_once APP_ROOT . '/db/migrator.php';

/**
 * Returns the single PDO instance for the app.
 * Runs pending migrations on first call.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Enable WAL mode and foreign keys for this connection
    $pdo->exec("PRAGMA journal_mode = WAL");
    $pdo->exec("PRAGMA foreign_keys = ON");

    // Auto-run any pending migrations
    run_migrations($pdo);

    return $pdo;
}
