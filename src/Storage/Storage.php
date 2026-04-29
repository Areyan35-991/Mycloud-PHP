<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) { die('Direct access not permitted.'); }

class Storage
{
    public static function store(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::uploadErrorMessage($file['error']));
        }
        if ($file['size'] > MAX_UPLOAD_BYTES) {
            throw new RuntimeException('File exceeds the 500 MB limit.');
        }

        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!array_key_exists($mimeType, ALLOWED_TYPES)) {
            throw new RuntimeException("File type '{$mimeType}' is not permitted.");
        }

        $ext        = ALLOWED_TYPES[$mimeType];
        $uuid       = self::uuid4();
        $storedName = $uuid . '.' . $ext;
        $destPath   = STORAGE_PATH . '/' . $storedName;

        $sha256 = hash_file('sha256', $file['tmp_name']);
        if ($sha256 === false) { throw new RuntimeException('Could not hash file.'); }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Rejected: not a valid uploaded file.');
        }
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException('Failed to save file. Check storage permissions.');
        }

        self::stripMetadata($destPath, $mimeType);

        $originalName = self::sanitizeFilename($file['name']);
        $stmt = db()->prepare(
            "INSERT INTO files (uuid, original_name, stored_name, mime_type, size, sha256)
             VALUES (:uuid, :original_name, :stored_name, :mime_type, :size, :sha256)"
        );
        $stmt->execute([
            ':uuid' => $uuid, ':original_name' => $originalName,
            ':stored_name' => $storedName, ':mime_type' => $mimeType,
            ':size' => $file['size'], ':sha256' => $sha256,
        ]);

        return self::findByUuid($uuid);
    }

    public static function delete(string $uuid): void
    {
        $row  = self::findByUuid($uuid);
        $path = STORAGE_PATH . '/' . $row['stored_name'];
        if (file_exists($path)) { unlink($path); }
        db()->prepare("UPDATE files SET deleted_at = datetime('now') WHERE uuid = ?")->execute([$uuid]);
    }

    public static function findByUuid(string $uuid): array
    {
        $stmt = db()->prepare("SELECT * FROM files WHERE uuid = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$uuid]);
        $row = $stmt->fetch();
        if (!$row) { throw new RuntimeException("File not found: {$uuid}"); }
        return $row;
    }

    public static function all(): array
    {
        return db()->query("SELECT * FROM files WHERE deleted_at IS NULL ORDER BY uploaded_at DESC")->fetchAll();
    }

    public static function diskPath(array $row): string { return STORAGE_PATH . '/' . $row['stored_name']; }

    public static function humanSize(int $bytes): string
    {
        $units = ['B','KB','MB','GB']; $i = 0;
        while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    private static function uuid4(): string
    {
        $d = random_bytes(16);
        $d[6] = chr(ord($d[6]) & 0x0f | 0x40);
        $d[8] = chr(ord($d[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    private static function sanitizeFilename(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^\w\s\-.]/', '', $name) ?? 'file';
        return mb_substr(trim($name) ?: 'file', 0, 255);
    }

    private static function stripMetadata(string $path, string $mimeType): void
    {
        if (!extension_loaded('gd')) { return; }
        match ($mimeType) {
            'image/jpeg' => (@imagejpeg(@imagecreatefromjpeg($path), $path, 92)),
            'image/png'  => (@imagepng(@imagecreatefrompng($path),  $path, 6)),
            default      => null,
        };
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
            UPLOAD_ERR_PARTIAL    => 'Upload was partially received.',
            UPLOAD_ERR_NO_FILE    => 'No file was included.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp directory missing.',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk.',
            default               => 'Unexpected upload error.',
        };
    }
}
