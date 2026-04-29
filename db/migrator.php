<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    die('Direct access not permitted.');
}

/**
 * Runs all pending migrations in order.
 * Each migration is a plain SQL file named 001_name.sql, 002_name.sql, etc.
 * Applied migrations are tracked in the `schema_migrations` table.
 */
function run_migrations(PDO $pdo): void
{
    // Bootstrap the migration tracker table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            version     INTEGER PRIMARY KEY,
            name        TEXT    NOT NULL,
            applied_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        )
    ");

    $applied = $pdo->query("SELECT version FROM schema_migrations")
                   ->fetchAll(PDO::FETCH_COLUMN);

    $files = glob(APP_ROOT . '/db/migrations/*.sql');
    sort($files);

    foreach ($files as $file) {
        $version = (int) basename($file);
        if (in_array($version, $applied, true)) {
            continue;
        }

        $sql = file_get_contents($file);
        $pdo->exec($sql);

        $stmt = $pdo->prepare(
            "INSERT INTO schema_migrations (version, name) VALUES (?, ?)"
        );
        $stmt->execute([$version, basename($file)]);
    }
}
