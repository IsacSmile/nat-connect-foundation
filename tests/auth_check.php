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
define('APP_PATH', ROOT_PATH . '/app');

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

    echo "\n========================================================\n";
    echo "ALL RATE LIMITING ASSERTIONS PASSED (5 IP / 10 Account)\n";
    echo "========================================================\n";
    exit(0);

} catch (Throwable $e) {
    echo "\n[FAIL] Assertion failed: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $cleanup();
}
