<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

// Token arrives either from ?token= query param or from /s/<token> rewrite
$token = trim($_GET['token'] ?? '');

if ($token === '') {
    abort(400, 'Invalid share link.');
}

// Basic token format validation (64 hex chars)
if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
    abort(400, 'Invalid share link format.');
}

$file = ShareLink::resolve($token);

if (!$file) {
    abort(404, 'This link is invalid or has expired.');
}

// Stream directly (resolve() already incremented download_count)
$path = Storage::diskPath($file);

if (!file_exists($path)) {
    abort(404, 'File no longer exists.');
}

$mime     = $file['mime_type'];
$filename = $file['original_name'];
$size     = filesize($path);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('Content-Length: ' . $size);
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

readfile($path);
