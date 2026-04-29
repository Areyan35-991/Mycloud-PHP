<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

// Download requires auth OR a valid share token
$uuid  = trim($_GET['id']  ?? '');
$token = trim($_GET['token'] ?? '');

$file = null;

if ($token !== '') {
    // Public share-link download
    $file = ShareLink::resolve($token);
    if (!$file) {
        abort(404, 'Share link not found or has expired.');
    }
} else {
    // Authenticated download
    require_auth();
    if ($uuid === '') {
        abort(400, 'Missing file ID.');
    }
    try {
        $file = Storage::findByUuid($uuid);
    } catch (RuntimeException) {
        abort(404, 'File not found.');
    }
}

$path = Storage::diskPath($file);

if (!file_exists($path) || !is_readable($path)) {
    abort(404, 'File missing from storage.');
}

// ── Stream the file ───────────────────────────────────────────────────────────
$mimeType = $file['mime_type'];
$filename = $file['original_name'];
$size     = filesize($path);

// Decide disposition: preview in-browser vs force download
$previewable = in_array($mimeType, [
    'image/jpeg','image/png','image/gif','image/webp',
    'video/mp4','video/webm',
    'audio/mpeg','audio/ogg','audio/wav',
    'application/pdf',
], true);
$disposition = $previewable ? 'inline' : 'attachment';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($filename) . '"');
header('Content-Length: ' . $size);
header('Cache-Control: private, no-store');           // don't cache private files
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');                        // enables seek in video

// ── Range request support (seek in video/audio) ───────────────────────────────
if (isset($_SERVER['HTTP_RANGE'])) {
    [$unit, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if ($unit === 'bytes') {
        [$start, $end] = explode('-', $range, 2);
        $start = (int) $start;
        $end   = ($end === '') ? $size - 1 : (int) $end;
        $end   = min($end, $size - 1);

        if ($start > $end || $start >= $size) {
            header('HTTP/1.1 416 Range Not Satisfiable');
            header('Content-Range: bytes */' . $size);
            exit;
        }

        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        header('Content-Length: ' . ($end - $start + 1));

        $fp = fopen($path, 'rb');
        fseek($fp, $start);
        $remaining = $end - $start + 1;
        while (!feof($fp) && $remaining > 0) {
            $chunk      = min(8192, $remaining);
            $remaining -= $chunk;
            echo fread($fp, $chunk);
            flush();
        }
        fclose($fp);
        exit;
    }
}

// ── Normal (full) response ─────────────────────────────────────────────────────
readfile($path);
