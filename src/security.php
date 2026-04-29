<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    die('Direct access not permitted.');
}

// ─────────────────────────────────────────────────────────────────────────────
// Session
// ─────────────────────────────────────────────────────────────────────────────

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,                  // expire on browser close
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']), // true when behind Cloudflare
        'httponly' => true,               // JS cannot read the cookie
        'samesite' => 'Strict',
    ]);

    session_name('MYCLOUDID');
    session_start();

    // Regenerate ID on first use to prevent session fixation
    if (empty($_SESSION['__initiated'])) {
        session_regenerate_id(true);
        $_SESSION['__initiated'] = true;
    }

    // Idle timeout
    if (isset($_SESSION['__last_active'])) {
        if (time() - $_SESSION['__last_active'] > SESSION_LIFETIME) {
            session_unset();
            session_destroy();
            session_start();
            session_regenerate_id(true);
        }
    }
    $_SESSION['__last_active'] = time();
}

function session_is_authenticated(): bool
{
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function require_auth(): void
{
    if (!session_is_authenticated()) {
        redirect('/login.php');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// CSRF
// ─────────────────────────────────────────────────────────────────────────────

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Validates the CSRF token from a POST request.
 * Kills the request if invalid — no graceful fallback.
 */
function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Brute-force / rate limiting
// ─────────────────────────────────────────────────────────────────────────────

function client_ip(): string
{
    // Respect Cloudflare's real-IP header; fall back to REMOTE_ADDR
    return $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
}

function is_ip_locked_out(string $ip): bool
{
    $cutoff = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_SECONDS);
    $stmt = db()->prepare("
        SELECT COUNT(*) FROM login_attempts
        WHERE ip = ? AND success = 0 AND attempted_at > ?
    ");
    $stmt->execute([$ip, $cutoff]);
    return (int) $stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
}

function record_login_attempt(string $ip, string $username, bool $success): void
{
    db()->prepare("
        INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, ?)
    ")->execute([$ip, $username, $success ? 1 : 0]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Output helpers
// ─────────────────────────────────────────────────────────────────────────────

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    die(h($message ?: "HTTP $code"));
}
