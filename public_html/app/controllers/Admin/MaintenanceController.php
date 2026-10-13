<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use PDO;
use Throwable;

/**
 * NatConnect Foundation — Database Maintenance & Updates Admin Controller
 *
 * Provides web-based database migration tooling for shared hosting (Hostinger)
 * without requiring SSH or Composer on the remote server.
 */
class MaintenanceController
{
    /**
     * Resolve the migrations directory inside public_html.
     */
    public static function getMigrationsDir(): string
    {
        if (defined('ROOT_PATH') && is_dir(ROOT_PATH . '/public_html/database/migrations')) {
            return ROOT_PATH . '/public_html/database/migrations';
        }
        if (defined('PUBLIC_PATH') && is_dir(PUBLIC_PATH . '/database/migrations')) {
            return PUBLIC_PATH . '/database/migrations';
        }
        return dirname(__DIR__, 3) . '/database/migrations';
    }

    /**
     * Show migrations status and pending updates.
     */
    public function index(): void
    {
        Auth::requireAuth('/admin/maintenance');

        $db = Database::getConnection();

        // 1. Ensure schema_migrations table exists
        $db->exec("
            CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `version` VARCHAR(50) NOT NULL PRIMARY KEY,
                `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Fetch applied migrations
        $appliedStmt = $db->query("SELECT `version`, `applied_at` FROM `schema_migrations` ORDER BY `version`");
        $appliedRows = $appliedStmt->fetchAll(PDO::FETCH_ASSOC);
        $appliedMap = [];
        foreach ($appliedRows as $row) {
            $appliedMap[$row['version']] = $row['applied_at'];
        }

        // 3. Scan public_html/database/migrations directory
        $migrationsDir = self::getMigrationsDir();
        $files = glob($migrationsDir . '/*.sql') ?: [];
        natsort($files);

        $migrations = [];
        $pendingCount = 0;
        $sampleMigrationUrl = '/database/migrations/001_initial_schema.sql';

        if (!empty($files)) {
            $firstFile = reset($files);
            $sampleMigrationUrl = '/database/migrations/' . basename($firstFile);
        }

        foreach ($files as $file) {
            $filename = basename($file);
            $version = pathinfo($filename, PATHINFO_FILENAME);

            preg_match('/^(\d+)/', $filename, $matches);
            $shortPrefix = $matches[1] ?? '';

            $isApplied = isset($appliedMap[$version]) || ($shortPrefix !== '' && isset($appliedMap[$shortPrefix]));
            $appliedAt = $appliedMap[$version] ?? ($shortPrefix !== '' && isset($appliedMap[$shortPrefix]) ? $appliedMap[$shortPrefix] : null);

            if (!$isApplied) {
                $pendingCount++;
            }

            $migrations[] = [
                'filename'   => $filename,
                'version'    => $version,
                'is_applied' => $isApplied,
                'applied_at' => $appliedAt,
                'filesize'   => file_exists($file) ? filesize($file) : 0,
            ];
        }

        $results = $_SESSION['maintenance_results'] ?? null;
        unset($_SESSION['maintenance_results']);

        // Load existing self-test result if available
        $storageDir = defined('APP_PATH') ? APP_PATH . '/storage' : dirname(__DIR__, 2) . '/storage';
        $selfTestFile = $storageDir . '/self_test.json';
        $selfTestResult = null;
        if (file_exists($selfTestFile)) {
            $selfTestResult = json_decode((string) file_get_contents($selfTestFile), true);
        }

        View::render('admin/maintenance', [
            'pageTitle'          => 'Maintenance — NatConnect Foundation',
            'activeNav'          => 'maintenance',
            'migrations'         => $migrations,
            'pendingCount'       => $pendingCount,
            'appliedCount'       => count($migrations) - $pendingCount,
            'totalCount'         => count($migrations),
            'updateResults'      => $results,
            'sampleMigrationUrl' => $sampleMigrationUrl,
            'selfTestResult'     => $selfTestResult,
        ], 'layouts/admin');
    }

    /**
     * Apply pending database migrations in sequential order.
     */
    public function applyPending(): void
    {
        Auth::requireAuth('/admin/maintenance');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_errors'] = ['csrf' => 'Invalid or expired session token. Please try again.'];
            header('Location: /admin/maintenance');
            exit;
        }

        $db = Database::getConnection();

        // 1. Ensure schema_migrations table exists
        $db->exec("
            CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `version` VARCHAR(50) NOT NULL PRIMARY KEY,
                `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Fetch applied migrations
        $appliedStmt = $db->query("SELECT `version` FROM `schema_migrations`");
        $appliedRows = $appliedStmt->fetchAll(PDO::FETCH_COLUMN);
        $appliedSet = array_flip($appliedRows);

        // 3. Scan public_html/database/migrations directory
        $migrationsDir = self::getMigrationsDir();
        $files = glob($migrationsDir . '/*.sql') ?: [];
        natsort($files);

        $results = [];
        $appliedCount = 0;
        $hasError = false;

        foreach ($files as $file) {
            $filename = basename($file);
            $version = pathinfo($filename, PATHINFO_FILENAME);

            preg_match('/^(\d+)/', $filename, $matches);
            $shortPrefix = $matches[1] ?? '';

            $isApplied = isset($appliedSet[$version]) || ($shortPrefix !== '' && isset($appliedSet[$shortPrefix]));
            if ($isApplied) {
                continue;
            }

            // Special check for 002: If database was initialized with account_hash already present
            if ($shortPrefix === '002' || str_contains($filename, 'login_attempts_account_hash')) {
                $checkCol = $db->query("
                    SELECT 1
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'login_attempts'
                      AND COLUMN_NAME = 'account_hash'
                ");
                if ((bool) $checkCol->fetchColumn()) {
                    $recordStmt = $db->prepare("INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES (:version, NOW())");
                    $recordStmt->execute([':version' => $version]);
                    $results[] = [
                        'file'    => $filename,
                        'status'  => 'success',
                        'message' => 'Column already present; recorded in schema_migrations.',
                    ];
                    $appliedCount++;
                    continue;
                }
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                $hasError = true;
                $results[] = [
                    'file'    => $filename,
                    'status'  => 'error',
                    'message' => "Unable to read migration file: {$filename}",
                ];
                break;
            }

            try {
                $db->exec($sql);
                $recordStmt = $db->prepare("INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES (:version, NOW())");
                $recordStmt->execute([':version' => $version]);
                $results[] = [
                    'file'    => $filename,
                    'status'  => 'success',
                    'message' => 'Applied successfully',
                ];
                $appliedCount++;
            } catch (Throwable $e) {
                $hasError = true;
                $results[] = [
                    'file'    => $filename,
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ];
                // Stop at the first error and show it plainly
                break;
            }
        }

        $_SESSION['maintenance_results'] = $results;

        if ($hasError) {
            $lastErr = end($results);
            $_SESSION['flash_errors'] = [
                'migration' => "Migration failed on {$lastErr['file']}: {$lastErr['message']}",
            ];
        } elseif ($appliedCount > 0) {
            $_SESSION['flash_success'] = "Successfully applied {$appliedCount} pending database update(s).";
        } else {
            $_SESSION['flash_info'] = "Database is already up to date. No pending updates found.";
        }

        header('Location: /admin/maintenance');
        exit;
    }

    /**
     * Record client-side security self-test result.
     */
    public function recordSelfTest(): void
    {
        Auth::requireAuth('/admin/maintenance');

        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody ?: '', true) ?: $_POST;

        $csrfToken = $payload['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Csrf::verify($csrfToken)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $passed = !empty($payload['passed']);
        $details = $payload['details'] ?? [];

        $storageDir = defined('APP_PATH') ? APP_PATH . '/storage' : dirname(__DIR__, 2) . '/storage';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }

        $record = [
            'passed'    => $passed,
            'tested_at' => date('Y-m-d H:i:s'),
            'details'   => $details,
        ];

        file_put_contents($storageDir . '/self_test.json', json_encode($record, JSON_PRETTY_PRINT));

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $record]);
        exit;
    }
}

