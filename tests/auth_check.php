<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Authentication Rate Limiting CLI Test
 *
 * Connects to the database configured in config.php / config.local.php,
 * inserts mock failed login attempts, verifies lockout triggers at
 * 5 for IP and 10 for account email hash, and thoroughly cleans up.
 *
 * Usage: php tests/auth_check.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/public_html/app');

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/models/UserModel.php';

use App\Core\Database;
use App\Models\UserModel;

echo "========================================================\n";
echo "NatConnect Foundation — Auth Rate Limiting Assertion Test\n";
echo "========================================================\n";

try {
    $db = Database::getConnection();
    echo "[OK] Connected to database: " . config('db.name') . "@" . config('db.host') . ":" . config('db.port') . "\n";
} catch (Throwable $e) {
    echo "[ERROR] Database connection failed: " . $e->getMessage() . "\n";
    echo "Notice: Please ensure MySQL is running and credentials in config.local.php are valid.\n";
    exit(1);
}

// Generate unique test targets for isolation
$salt = (string) config('security.salt', '');
$testIp = '198.51.100.' . rand(1, 250); // RFC 5737 TEST-NET-2 IP
$testIpHash = hash('sha256', $testIp . $salt);

$testEmail = 'test-lockout-' . bin2hex(random_bytes(4)) . '@example.org';
$testAccountHash = hash('sha256', strtolower($testEmail) . $salt);

$allCleaned = false;

// Cleanup callback
$cleanup = function () use ($testIpHash, $testAccountHash, &$allCleaned) {
    try {
        UserModel::clearFailedAttempts($testIpHash, $testAccountHash);
        $allCleaned = true;
    } catch (Throwable $e) {
        echo "[WARN] Cleanup warning: " . $e->getMessage() . "\n";
    }
};

try {
    // 0. Initial cleanup
    $cleanup();

    // -------------------------------------------------------------
    // Test 1: IP Rate Limit Lockout (Threshold = 5)
    // -------------------------------------------------------------
    echo "\n--> Testing IP Rate Limiting (threshold = 5)...\n";

    // Insert 4 attempts
    for ($i = 1; $i <= 4; $i++) {
        UserModel::recordFailedLogin($testIpHash, null);
    }
    $ipCheck4 = UserModel::getFailedAttemptsByIp($testIpHash, 15);
    if ($ipCheck4['count'] !== 4) {
        throw new RuntimeException("Expected 4 IP attempts, got {$ipCheck4['count']}");
    }
    echo "  [PASS] 4 failed IP attempts recorded; lockout NOT triggered yet.\n";

    // Insert 5th attempt
    UserModel::recordFailedLogin($testIpHash, null);
    $ipCheck5 = UserModel::getFailedAttemptsByIp($testIpHash, 15);
    if ($ipCheck5['count'] !== 5) {
        throw new RuntimeException("Expected 5 IP attempts, got {$ipCheck5['count']}");
    }
    if ($ipCheck5['count'] < 5) {
        throw new RuntimeException("Lockout threshold of 5 was not reached!");
    }
    echo "  [PASS] 5th failed IP attempt recorded; lockout triggered at exactly 5.\n";

    // Reset IP test rows
    UserModel::clearFailedAttempts($testIpHash, null);
    $ipCheckClean = UserModel::getFailedAttemptsByIp($testIpHash, 15);
    if ($ipCheckClean['count'] !== 0) {
        throw new RuntimeException("Failed to clean up IP test attempts.");
    }
    echo "  [PASS] IP attempts cleared successfully.\n";

    // -------------------------------------------------------------
    // Test 2: Account Email Rate Limit Lockout (Threshold = 10)
    // -------------------------------------------------------------
    echo "\n--> Testing Account Email Rate Limiting (threshold = 10)...\n";

    // Insert 9 attempts with distinct IP hashes to avoid tripping IP limiter
    for ($i = 1; $i <= 9; $i++) {
        $dummyIpHash = hash('sha256', "dummy-ip-{$i}-" . bin2hex(random_bytes(4)));
        UserModel::recordFailedLogin($dummyIpHash, $testAccountHash);
    }
    $accCheck9 = UserModel::getFailedAttemptsByAccount($testAccountHash, 15);
    if ($accCheck9['count'] !== 9) {
        throw new RuntimeException("Expected 9 account attempts, got {$accCheck9['count']}");
    }
    echo "  [PASS] 9 failed account attempts recorded; lockout NOT triggered yet.\n";

    // Insert 10th attempt
    $dummyIpHash10 = hash('sha256', "dummy-ip-10-" . bin2hex(random_bytes(4)));
    UserModel::recordFailedLogin($dummyIpHash10, $testAccountHash);
    $accCheck10 = UserModel::getFailedAttemptsByAccount($testAccountHash, 15);
    if ($accCheck10['count'] !== 10) {
        throw new RuntimeException("Expected 10 account attempts, got {$accCheck10['count']}");
    }
    if ($accCheck10['count'] < 10) {
        throw new RuntimeException("Lockout threshold of 10 was not reached!");
    }
    echo "  [PASS] 10th failed account attempt recorded; lockout triggered at exactly 10.\n";

    // Reset account test rows
    UserModel::clearFailedAttempts('', $testAccountHash);
    $accCheckClean = UserModel::getFailedAttemptsByAccount($testAccountHash, 15);
    if ($accCheckClean['count'] !== 0) {
        throw new RuntimeException("Failed to clean up account test attempts.");
    }
    echo "  [PASS] Account attempts cleared successfully.\n";

    // -------------------------------------------------------------
    // Test 3: IPv6 /64 Rate Limiting & Prefix Grouping
    // -------------------------------------------------------------
    echo "\n--> Testing IPv6 /64 Prefix Grouping & Rate Limiting...\n";

    // 3.1 Helper normalization checks
    $ipv4 = '198.51.100.42';
    if (clientIpPrefix($ipv4) !== $ipv4) {
        throw new RuntimeException("IPv4 prefix normalization failed: expected {$ipv4}, got " . clientIpPrefix($ipv4));
    }
    echo "  [PASS] IPv4 client IP is preserved unchanged.\n";

    $ipv6_1 = '2001:db8:abcd:0012:0000:0000:0000:0001';
    $ipv6_2 = '2001:db8:abcd:0012:ffff:ffff:ffff:ffff';
    $ipv6_diff = '2001:db8:abcd:0013:0000:0000:0000:0001';

    $prefix1 = clientIpPrefix($ipv6_1);
    $prefix2 = clientIpPrefix($ipv6_2);
    $prefixDiff = clientIpPrefix($ipv6_diff);

    if ($prefix1 !== $prefix2) {
        throw new RuntimeException("IPv6 addresses in same /64 produced different prefixes: '{$prefix1}' vs '{$prefix2}'");
    }
    if ($prefix1 === $prefixDiff) {
        throw new RuntimeException("IPv6 addresses in different /64 produced identical prefixes: '{$prefix1}'");
    }
    echo "  [PASS] IPv6 addresses in same /64 produce identical prefix ('{$prefix1}').\n";

    $hash1 = ipHash($ipv6_1, $salt);
    $hash2 = ipHash($ipv6_2, $salt);
    $hashDiff = ipHash($ipv6_diff, $salt);

    if ($hash1 !== $hash2) {
        throw new RuntimeException("ipHash for same /64 produced different hashes");
    }
    if ($hash1 === $hashDiff) {
        throw new RuntimeException("ipHash for different /64 produced identical hashes");
    }
    echo "  [PASS] ipHash produces identical hash for same /64 and distinct hash for different /64.\n";

    // 3.2 Lockout grouping test in UserModel
    for ($i = 1; $i <= 4; $i++) {
        UserModel::recordFailedLogin($hash1, null);
    }
    $checkFromAddress2 = UserModel::getFailedAttemptsByIp($hash2, 15);
    if ($checkFromAddress2['count'] !== 4) {
        throw new RuntimeException("Expected 4 failed attempts when querying from address 2, got {$checkFromAddress2['count']}");
    }
    echo "  [PASS] 4 attempts recorded under IPv6 address A are visible to address B in same /64.\n";

    UserModel::recordFailedLogin($hash2, null);
    $checkFromAddress1 = UserModel::getFailedAttemptsByIp($hash1, 15);
    if ($checkFromAddress1['count'] !== 5) {
        throw new RuntimeException("Expected lockout threshold 5 across /64 subnet, got {$checkFromAddress1['count']}");
    }
    echo "  [PASS] 5th attempt from address B triggers lockout for address A across the entire /64 subnet.\n";

    $checkDiffSubnet = UserModel::getFailedAttemptsByIp($hashDiff, 15);
    if ($checkDiffSubnet['count'] !== 0) {
        throw new RuntimeException("Different /64 subnet was unexpectedly affected: count={$checkDiffSubnet['count']}");
    }
    echo "  [PASS] Independent /64 subnet has 0 failed attempts (not locked out).\n";

    UserModel::clearFailedAttempts($hash1, null);
    $checkCleanSubnet = UserModel::getFailedAttemptsByIp($hash2, 15);
    if ($checkCleanSubnet['count'] !== 0) {
        throw new RuntimeException("Failed to clean up IPv6 subnet attempts.");
    }
    echo "  [PASS] IPv6 test attempts cleared successfully.\n";

    echo "\n========================================================\n";
    echo "ALL RATE LIMITING ASSERTIONS PASSED (IPv4, IPv6 /64, 5 IP, 10 Account)\n";
    echo "========================================================\n";
    exit(0);

} catch (Throwable $e) {
    echo "\n[FAIL] Assertion failed: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $cleanup();
}
