<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// ── Security headers sent on every response ───────────────────────────────────
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
header("Content-Security-Policy: "
    . "default-src 'self'; "
    . "script-src 'self' 'unsafe-inline'; "  // inline JS for upload progress
    . "style-src 'self' 'unsafe-inline'; "
    . "img-src 'self' data: blob:; "
    . "media-src 'self'; "
    . "frame-ancestors 'none';"
);

// ── Load config ───────────────────────────────────────────────────────────────
require_once APP_ROOT . '/config/config.php';

// ── Error handling ────────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline): bool {
        error_log("[$errno] $errstr in $errfile:$errline");
        return true;
    });
    set_exception_handler(function(Throwable $e): void {
        error_log((string) $e);
        http_response_code(500);
        require APP_ROOT . '/templates/error.php';
        exit;
    });
}

// ── Autoload classes ──────────────────────────────────────────────────────────
spl_autoload_register(function(string $class): void {
    $map = [
        'Auth'      => APP_ROOT . '/src/Auth/Auth.php',
        'Storage'   => APP_ROOT . '/src/Storage/Storage.php',
        'ShareLink' => APP_ROOT . '/src/Share/ShareLink.php',
    ];
    if (isset($map[$class])) {
        require_once $map[$class];
    }
});

// ── Core helpers ──────────────────────────────────────────────────────────────
require_once APP_ROOT . '/src/db.php';
require_once APP_ROOT . '/src/security.php';

// ── Start session ─────────────────────────────────────────────────────────────
start_secure_session();

// ── Ensure storage directories exist ─────────────────────────────────────────
foreach ([STORAGE_PATH, LOG_PATH] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
}
