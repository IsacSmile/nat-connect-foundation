<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Cloudflare Turnstile CAPTCHA Verification Service
 */
class Turnstile
{
    /**
     * Cloudflare standard test site keys.
     */
    public const TEST_SITE_KEYS = [
        '1x00000000000000000000AA',
        '2x00000000000000000000AB',
        '3x00000000000000000000FF',
    ];

    /**
     * Cloudflare standard test secret keys.
     */
    public const TEST_SECRET_KEYS = [
        '1x0000000000000000000000000000000AA',
        '2x0000000000000000000000000000000AB',
        '3x0000000000000000000000000000000FF',
    ];

    /**
     * Optional callable mock for testing.
     * Signature: function(string $token, ?string $remoteIp): bool
     *
     * @var (callable(string, ?string): bool)|null
     */
    public static $mockVerifier = null;

    /**
     * Check if a site or secret key is a known Cloudflare test key.
     */
    public static function isTestKey(string $key): bool
    {
        $k = trim($key);
        return in_array($k, self::TEST_SITE_KEYS, true) || in_array($k, self::TEST_SECRET_KEYS, true);
    }

    /**
     * Check if Turnstile has keys configured.
     */
    public static function hasKeys(): bool
    {
        $site = trim((string) config('turnstile.site_key', ''));
        $secret = trim((string) config('turnstile.secret_key', ''));
        return $site !== '' && $secret !== '';
    }

    /**
     * Determine if Turnstile / comment submission is allowed in the current environment.
     * If debug=false and keys are empty or equal to Cloudflare's test keys,
     * returns false and logs a warning. Never accepts comments without a real key in production.
     */
    public static function canAcceptComments(): bool
    {
        $debug = (bool) config('app.debug', false);
        $site = trim((string) config('turnstile.site_key', ''));
        $secret = trim((string) config('turnstile.secret_key', ''));

        if ($site === '' || $secret === '') {
            if (!$debug) {
                error_log('[Turnstile Warning] Comments are unavailable: Turnstile keys are empty in production.');
            }
            return false;
        }

        if (self::isTestKey($site) || self::isTestKey($secret)) {
            if (!$debug) {
                error_log('[Turnstile Warning] Comments are unavailable: Cloudflare test keys detected with debug=false.');
                return false;
            }
            return true;
        }

        return true;
    }

    /**
     * Verify a Turnstile response token with Cloudflare's siteverify API.
     *
     * @param string|null $token The cf-turnstile-response token from the client.
     * @param string|null $remoteIp The visitor's IP address.
     * @return bool True if verification succeeds, false on invalid token or network error.
     */
    public static function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (!self::canAcceptComments()) {
            return false;
        }

        if (self::$mockVerifier !== null) {
            return (self::$mockVerifier)($token ?? '', $remoteIp);
        }

        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        $secret = (string) config('turnstile.secret_key', '');
        if ($secret === '') {
            return false;
        }

        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $postData = [
            'secret'   => $secret,
            'response' => $token,
        ];
        if (!empty($remoteIp)) {
            $postData['remoteip'] = $remoteIp;
        }

        $options = [
            'http' => [
                'header'        => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'        => 'POST',
                'content'       => http_build_query($postData),
                'timeout'       => 5,
                'ignore_errors' => true,
            ],
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        // Network failure or timeout triggers false
        if ($result === false) {
            return false;
        }

        $json = json_decode($result, true);
        if (!is_array($json) || empty($json['success'])) {
            return false;
        }

        return true;
    }
}
