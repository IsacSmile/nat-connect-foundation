<?php
declare(strict_types=1);

namespace App\Core;

/**
 * NatConnect Foundation — CSRF Protection Utility
 *
 * Provides cryptographically secure token generation, HTML field injection,
 * and timing-attack-safe token verification.
 */
class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    /**
     * Get or generate the session CSRF token.
     */
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Generate an HTML hidden input containing the CSRF token.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Verify a submitted CSRF token against the session token.
     *
     * @param string|null $token Submitted token, or null to inspect $_POST / headers.
     * @return bool
     */
    public static function verify(?string $token = null): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
        if (empty($sessionToken) || !is_string($sessionToken)) {
            return false;
        }

        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        if (!is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Rotate the session CSRF token (recommended after authentication state changes).
     */
    public static function rotate(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }
}
