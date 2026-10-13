<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Database Migration Runner
 *
 * CLI-only runner that scans database/migrations/*.sql in numeric order,
 * tracks applied migrations in the `schema_migrations` table, and applies
 * pending schema changes safely.
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

use App\Core\Database;

echo "=== NatConnect Foundation Migration Runner ===\n";

try {
    $db = Database::getConnection();
} catch (Throwable $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// 1. Ensure schema_migrations table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `version` VARCHAR(50) NOT NULL PRIMARY KEY,
        `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// 2. Fetch already applied migration versions
$appliedStmt = $db->query("SELECT `version` FROM `schema_migrations`");
$appliedRows = $appliedStmt->fetchAll(PDO::FETCH_COLUMN);
$appliedSet = array_flip($appliedRows);

// 3. Scan migrations directory
$migrationsDir = PUBLIC_PATH . '/database/migrations';
$files = glob($migrationsDir . '/*.sql');
if ($files === false || empty($files)) {
    echo "No migration files found in {$migrationsDir}.\n";
    exit(0);
}

// Sort migrations in natural numeric order (001, 002, ...)
natsort($files);

$appliedCount = 0;
$skippedCount = 0;

foreach ($files as $file) {
    $filename = basename($file);
    $version = pathinfo($filename, PATHINFO_FILENAME); // e.g. "001_initial_schema"

    // Extract leading numeric prefix (e.g. "001") to recognize manual phpMyAdmin inserts
    preg_match('/^(\d+)/', $filename, $matches);
    $shortPrefix = $matches[1] ?? '';

    $isApplied = isset($appliedSet[$version]) || ($shortPrefix !== '' && isset($appliedSet[$shortPrefix]));

    if ($isApplied) {
        echo "[SKIPPED] {$filename} (already applied)\n";
        $skippedCount++;
        continue;
    }

    // Special check for 002: If database was initialized with 001, account_hash already exists
    if ($shortPrefix === '002' || str_contains($filename, 'login_attempts_account_hash')) {
        $checkColStmt = $db->query("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'login_attempts'
              AND COLUMN_NAME = 'account_hash'
        ");
        $colExists = (bool) $checkColStmt->fetchColumn();

        if ($colExists) {
            $recordStmt = $db->prepare("INSERT INTO `schema_migrations` (`version`) VALUES (:version)");
            $recordStmt->execute([':version' => $version]);
            echo "[APPLIED] {$filename} (account_hash already present; marked applied)\n";
            $appliedCount++;
            continue;
        }
    }

    // Read and execute migration SQL
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "[ERROR] Unable to read migration file: {$filename}\n");
        exit(1);
    }

    try {
        $db->exec($sql);
        $recordStmt = $db->prepare("INSERT INTO `schema_migrations` (`version`) VALUES (:version)");
        $recordStmt->execute([':version' => $version]);
        echo "[APPLIED] {$filename}\n";
        $appliedCount++;
    } catch (Throwable $e) {
        fwrite(STDERR, "[ERROR] Migration failed on {$filename}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "----------------------------------------------\n";
echo "Migrations completed. Applied: {$appliedCount}, Skipped: {$skippedCount}.\n";
