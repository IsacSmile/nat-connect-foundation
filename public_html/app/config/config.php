<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Central Configuration Loader
 *
 * Reads base defaults and merges environment overrides from config.local.php (gitignored).
 */

$defaults = [
    'url' => 'http://127.0.0.1:8000',
    'app' => [
        'name'     => 'NatConnect Foundation',
        'tagline'  => 'Nature, Everything',
        'url'      => 'http://127.0.0.1:8000',
        'env'      => 'development', // 'development' or 'production'
        'debug'    => true,           // Set to false in production
        'timezone' => 'Asia/Kolkata',
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'natconnect_db',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name'     => 'natconnect_sess',
        'lifetime' => 86400 * 7, // 7 days
    ],
    'auth' => [
        'idle_timeout'         => 7200,   // 2 hours idle timeout
        'absolute_timeout'     => 43200,  // 12 hours absolute session lifetime
        'max_attempts'         => 5,      // Max failed login attempts per IP
        'max_account_attempts' => 10,     // Max failed login attempts per account email hash
        'lockout_minutes'      => 15,     // Lockout duration in minutes
    ],
    'security' => [
        'salt'            => 'replace-with-a-random-64-char-string-in-config.local.php',
        'trusted_proxies' => [], // Array of trusted proxy IPs/CIDRs (e.g. ['10.0.0.1', '172.16.0.0/12']). Default empty.
    ],
    'turnstile' => [
        'site_key'   => '',
        'secret_key' => '',
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';
$overrides = [];

if (file_exists($localConfigPath)) {
    $localValues = require $localConfigPath;
    if (is_array($localValues)) {
        $overrides = $localValues;
    }
}

// Deep merge defaults with local overrides
$config = array_replace_recursive($defaults, $overrides);

// Timezone setup: All times are IST. Never mix in UTC.
date_default_timezone_set('Asia/Kolkata');

/**
 * Access configuration values using dot notation.
 * Example: config('db.name', 'fallback')
 */
function config(?string $key = null, mixed $default = null): mixed
{
    global $config;

    if ($key === null) {
        return $config;
    }

    if ($key === 'url') {
        return $config['url'] ?? $config['app']['url'] ?? $default;
    }

    $segments = explode('.', $key);
    $target = $config;

    foreach ($segments as $segment) {
        if (!is_array($target) || !array_key_exists($segment, $target)) {
            return $default;
        }
        $target = $target[$segment];
    }

    return $target;
}

return $config;
