<?php
declare(strict_types=1);

namespace App\Core;

/**
 * NatConnect Foundation — Authentication & Session Manager
 *
 * Implements hardened session configuration, login session regeneration,
 * and authentication state checks.
 */
class Auth
{
    private const SESSION_USER_ID = 'auth_user_id';
    private const SESSION_USER_DATA = 'auth_user_data';

    /**
     * Initialize secure session with hardened cookie flags.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');

        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );

        $lifetime = (int) config('session.lifetime', 86400 * 7);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name((string) config('session.name', 'natconnect_sess'));
        session_start();
    }

    /**
     * Check if a user is currently authenticated.
     */
    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION[self::SESSION_USER_ID]);
    }

    /**
     * Get the authenticated user ID.
     */
    public static function id(): ?int
    {
        self::startSession();
        return isset($_SESSION[self::SESSION_USER_ID]) ? (int) $_SESSION[self::SESSION_USER_ID] : null;
    }

    /**
     * Get the cached authenticated user record.
     */
    public static function user(): ?array
    {
        self::startSession();
        return $_SESSION[self::SESSION_USER_DATA] ?? null;
    }

    /**
     * Authenticate user into session and regenerate session ID to thwart fixation.
     */
    public static function login(array $user): void
    {
        self::startSession();

        // Regenerate session ID and delete old session storage
        session_regenerate_id(true);

        $_SESSION[self::SESSION_USER_ID] = (int) ($user['id'] ?? 0);
        $_SESSION[self::SESSION_USER_DATA] = [
            'id'    => (int) ($user['id'] ?? 0),
            'name'  => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role'  => (string) ($user['role'] ?? 'editor'),
        ];

        // Also rotate CSRF token on auth transition
        Csrf::rotate();
    }

    /**
     * Log out current user and invalidate session data.
     */
    public static function logout(): void
    {
        self::startSession();

        unset($_SESSION[self::SESSION_USER_ID], $_SESSION[self::SESSION_USER_DATA]);
        session_regenerate_id(true);
        Csrf::rotate();
    }
}
