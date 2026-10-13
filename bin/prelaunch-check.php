<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Automated Pre-Launch Verification Script (CLI Wrapper)
 *
 * Runs LaunchCheck::runAll() and outputs results to the console.
 * Exit code 1 on any failure, 0 on clean pass.
 *
 * Usage: php bin/prelaunch-check.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI access only.\n";
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public_html');
define('APP_PATH', PUBLIC_PATH . '/app');

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Turnstile.php';
require_once APP_PATH . '/core/LaunchCheck.php';

use App\Core\LaunchCheck;

echo "========================================================\n";
echo "NatConnect Foundation — Pre-Launch Readiness Check\n";
echo "========================================================\n\n";

$result = LaunchCheck::runAll();

foreach ($result['checks'] as $check) {
    if ($check['passed']) {
        echo "  [PASS] {$check['message']}\n";
    } else {
        echo "  [FAIL] {$check['message']}\n";
        if (!empty($check['details'])) {
            foreach ($check['details'] as $detail) {
                echo "         - {$detail}\n";
            }
        }
    }
}

echo "\n========================================================\n";
if (!$result['allPassed']) {
    echo "[FAILED] Pre-launch check detected {$result['failCount']} issue(s) that must be resolved before launch.\n";
    exit(1);
} else {
    echo "[SUCCESS] All pre-launch checks passed! Site is ready for production.\n";
    exit(0);
}
