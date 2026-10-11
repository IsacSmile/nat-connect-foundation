<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Local & Production Configuration Template
 *
 * Duplicate this file as config.local.php and fill in your credentials.
 * DO NOT commit config.local.php to version control.
 */

return [
    'app' => [
        'name'     => 'NatConnect Foundation',
        'url'      => 'http://127.0.0.1:8000', // Update to https://yourdomain.com on Hostinger
        'env'      => 'development',           // 'development' or 'production'
        'debug'    => true,                    // Set to false on production to hide sensitive errors
        'timezone' => 'Asia/Kolkata',
    ],
    'db' => [
        'host'    => 'localhost',              // Hostinger typically uses 'localhost'
        'port'    => 3306,
        'name'    => 'u123456789_natconnect',  // Your Hostinger database name (e.g. u123456_dbname)
        'user'    => 'u123456789_natuser',     // Your Hostinger database user
        'pass'    => 'YourHostingerDbPassword',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name'     => 'natconnect_sess',
        'lifetime' => 86400 * 7,
    ],
    'auth' => [
        'idle_timeout'         => 7200,   // 2 hours idle timeout
        'absolute_timeout'     => 43200,  // 12 hours absolute session lifetime
        'max_attempts'         => 5,      // Max failed login attempts per IP
        'max_account_attempts' => 10,     // Max failed login attempts per account email hash
        'lockout_minutes'      => 15,     // Lockout duration in minutes
    ],
    'security' => [
        // Generate a random 64-character hex string: bin2hex(random_bytes(32))
        'salt'            => 'c089d71c183cf99db4918e7e31ef408a99478f6916a0322bf20b411d7eb6bca3',
        'trusted_proxies' => [], // Array of trusted proxy IPs/CIDRs (e.g. ['10.0.0.1']). Default empty.
    ],
];
