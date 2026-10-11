<?php
declare(strict_types=1);

namespace App\Core;

/**
 * NatConnect Foundation — Authentication & Session Security Manager
 *
 * Implements hardened session configuration, login session regeneration,
 * activity tracking, idle and absolute session expiration, open redirect validation,
 * and admin security response headers.
 */
class Auth
{
    private const SESSION_USER_ID       = 'auth_user_id';
    private const SESSION_USER_DATA     = 'auth_user_data';
    private const SESSION_LOGIN_TIME    = 'auth_login_time';
    private const SESSION_LAST_ACTIVITY = 'auth_last_activity';

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

        $lifetime = (int) config('session.lifetime', 86400 * 7);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name((string) config('session.name', 'natconnect_sess'));
        session_start();
    }

    /**
     * Check if a user is currently authenticated without checking expiration.
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
     * Authenticate user into session, record login and activity timestamps,
     * and regenerate session ID to thwart session fixation.
     */
    public static function login(array $user): void
    {
        self::startSession();

        // Regenerate session ID and delete old session storage
        session_regenerate_id(true);

        $now = time();
        $_SESSION[self::SESSION_USER_ID]       = (int) ($user['id'] ?? 0);
        $_SESSION[self::SESSION_USER_DATA]     = [
            'id'    => (int) ($user['id'] ?? 0),
            'name'  => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role'  => (string) ($user['role'] ?? 'editor'),
        ];
        $_SESSION[self::SESSION_LOGIN_TIME]    = $now;
        $_SESSION[self::SESSION_LAST_ACTIVITY] = $now;

        // Rotate CSRF token on auth transition
        Csrf::rotate();
    }

    /**
     * Log out current user and invalidate session data.
     */
    public static function logout(): void
    {
        self::startSession();

        unset(
            $_SESSION[self::SESSION_USER_ID],
            $_SESSION[self::SESSION_USER_DATA],
            $_SESSION[self::SESSION_LOGIN_TIME],
            $_SESSION[self::SESSION_LAST_ACTIVITY]
        );

        session_regenerate_id(true);
        Csrf::rotate();
    }

    /**
     * Enforce authentication, idle timeout (2h default), and absolute lifetime (12h default).
     * Redirects unauthenticated or expired requests to /admin/login.
     */
    public static function requireAuth(string $requestUri = '/admin'): void
    {
        self::startSession();
        self::sendAdminHeaders();

        // Build valid redirect target
        $validNext = self::isValidAdminRedirect($requestUri) ? $requestUri : '/admin';
        $loginUrl = '/admin/login?next=' . urlencode($validNext);

        if (!self::check()) {
            redirect($loginUrl);
        }

        $now = time();
        $idleTimeout = (int) config('auth.idle_timeout', 7200);       // 2 hours idle
        $absoluteTimeout = (int) config('auth.absolute_timeout', 43200); // 12 hours absolute

        // Check idle timeout
        $lastActivity = (int) ($_SESSION[self::SESSION_LAST_ACTIVITY] ?? 0);
        if ($lastActivity > 0 && ($now - $lastActivity) > $idleTimeout) {
            self::logout();
            setFlash('error', 'Session expired due to inactivity. Please sign in again.');
            redirect($loginUrl);
        }

        // Check absolute session lifetime
        $loginTime = (int) ($_SESSION[self::SESSION_LOGIN_TIME] ?? 0);
        if ($loginTime > 0 && ($now - $loginTime) > $absoluteTimeout) {
            self::logout();
            setFlash('error', 'Session expired. Please sign in again.');
            redirect($loginUrl);
        }

        // Keep last activity updated
        $_SESSION[self::SESSION_LAST_ACTIVITY] = $now;
    }

    /**
     * Validate a destination URL for post-login redirect to thwart open redirect attacks.
     * Must start with /admin and must not contain //, backslashes, or URI schemes.
     */
    public static function isValidAdminRedirect(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // Must strictly start with /admin
        if (!str_starts_with($url, '/admin')) {
            return false;
        }

        // Disallow protocol-relative URLs (//evil.com)
        if (str_contains($url, '//')) {
            return false;
        }

        // Disallow scheme indicators (http:, javascript:)
        if (str_contains($url, ':')) {
            return false;
        }

        // Disallow backslashes (browsers normalize \ to /)
        if (str_contains($url, '\\')) {
            return false;
        }

        return true;
    }

    /**
     * Emit mandatory privacy and cache-busting headers on all admin responses.
     */
    public static function sendAdminHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    /**
     * Check if the current request is over HTTPS.
     * Checks $_SERVER['HTTPS'] or port 443, and X-Forwarded-Proto ONLY from trusted proxies.
     */
    public static function isHttps(): bool
    {
        // 1. Direct HTTPS flag
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        // 2. Direct port 443
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        // 3. X-Forwarded-Proto ONLY from trusted proxies
        $remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $trustedProxies = (array) config('security.trusted_proxies', []);

        if (!empty($trustedProxies) && isTrustedProxy($remoteAddr, $trustedProxies)) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
                return true;
            }
        }

        return false;
    }
}
