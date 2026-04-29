<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    die('Direct access not permitted.');
}

class ShareLink
{
    /**
     * Creates a new share token for a file.
     * Returns the full public URL.
     */
    public static function create(string $fileUuid, ?string $label = null): string
    {
        // Ensure file exists
        $file = Storage::findByUuid($fileUuid);

        $token = bin2hex(random_bytes(32)); // 64 hex chars

        db()->prepare("
            INSERT INTO share_links (file_id, token, label)
            VALUES (:file_id, :token, :label)
        ")->execute([
            ':file_id' => $file['id'],
            ':token'   => $token,
            ':label'   => $label,
        ]);

        return BASE_URL . '/s/' . $token;
    }

    /**
     * Resolves a token to its file row.
     * Increments the download counter.
     * Returns null if token is invalid or file is deleted.
     */
    public static function resolve(string $token): ?array
    {
        $stmt = db()->prepare("
            SELECT f.*, sl.token, sl.id AS share_id
            FROM share_links sl
            JOIN files f ON f.id = sl.file_id
            WHERE sl.token = ?
              AND f.deleted_at IS NULL
              AND (sl.expires_at IS NULL OR sl.expires_at > datetime('now'))
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        // Increment download counter
        db()->prepare("
            UPDATE share_links SET download_count = download_count + 1 WHERE id = ?
        ")->execute([$row['share_id']]);

        return $row;
    }

    /**
     * All share links for a specific file.
     */
    public static function forFile(string $fileUuid): array
    {
        $file = Storage::findByUuid($fileUuid);
        return db()->prepare("
            SELECT * FROM share_links WHERE file_id = ? ORDER BY created_at DESC
        ")->execute([$file['id']])
          ? (function() use ($file) {
              $s = db()->prepare("SELECT * FROM share_links WHERE file_id = ? ORDER BY created_at DESC");
              $s->execute([$file['id']]);
              return $s->fetchAll();
          })()
          : [];
    }

    public static function delete(int $shareId): void
    {
        db()->prepare("DELETE FROM share_links WHERE id = ?")->execute([$shareId]);
    }
}
