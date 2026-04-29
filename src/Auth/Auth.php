<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    die('Direct access not permitted.');
}

require_once APP_ROOT . '/config/config.php';

class Auth
{
    /**
     * Attempt to authenticate. Returns true on success, false on failure.
     * Handles lockout checking and audit logging.
     */
    public static function attempt(string $username, string $password): bool
    {
        $ip = client_ip();

        if (is_ip_locked_out($ip)) {
            return false;
        }

        $valid = self::verifyCredentials($username, $password);
        record_login_attempt($ip, $username, $valid);

        if ($valid) {
            // Rotate session ID on privilege escalation (login)
            session_regenerate_id(true);
            $_SESSION['authenticated'] = true;
            $_SESSION['username']      = $username;
            $_SESSION['logged_in_at']  = time();
        }

        return $valid;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }
        session_destroy();
    }

    private static function verifyCredentials(string $username, string $password): bool
    {
        // Constant-time username comparison to prevent timing attacks
        $usernameMatch = hash_equals(OWNER_USERNAME, $username);
        // Always run password_verify even on username mismatch to prevent timing side-channel
        $passwordMatch = password_verify($password, OWNER_PASSWORD_HASH);
        return $usernameMatch && $passwordMatch;
    }
}
