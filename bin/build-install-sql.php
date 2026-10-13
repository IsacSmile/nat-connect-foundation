<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Build Fresh Installation SQL
 *
 * Generates database/install.sql from the LOCAL database structure:
 * 1. SHOW CREATE TABLE for every table in the database
 * 2. Strips all table-level AUTO_INCREMENT=\d+ values
 * 3. Dumps zero row data (pure schema DDL)
 * 4. Appends database/seed.sql content (default categories, settings, pages)
 * 5. Appends INSERT INTO schema_migrations for every migration file in database/migrations
 *
 * Fresh install protocol = Import database/install.sql only.
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

echo "=== NatConnect Foundation: Build install.sql ===\n";

try {
    $db = Database::getConnection();
} catch (Throwable $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// 1. Fetch all tables from the local database
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (empty($tables)) {
    fwrite(STDERR, "No tables found in database.\n");
    exit(1);
}

$output = [];
$output[] = "-- =====================================================================";
$output[] = "-- NatConnect Foundation — Fresh Installation Schema & Baseline Seed";
$output[] = "-- =====================================================================";
$output[] = "-- FRESH INSTALL PROTOCOL: Import database/install.sql only.";
$output[] = "-- Generated: " . date('Y-m-d H:i:s') . " (IST)";
$output[] = "-- Contains:";
$output[] = "--   1. Full database table structure (clean DDL, no auto-increment values)";
$output[] = "--   2. Seed categories, site settings, and default pages";
$output[] = "--   3. Recorded schema_migrations baseline (all current migrations marked applied)";
$output[] = "-- Excludes demo content, test data, and user credentials entirely.";
$output[] = "-- =====================================================================\n";
$output[] = "SET NAMES utf8mb4;";
$output[] = "SET FOREIGN_KEY_CHECKS = 0;\n";

// 2. Export table structures
$output[] = "-- ---------------------------------------------------------------------";
$output[] = "-- Table Structures";
$output[] = "-- ---------------------------------------------------------------------\n";

foreach ($tables as $table) {
    $row = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
    if (!isset($row['Create Table'])) {
        fwrite(STDERR, "Could not fetch CREATE TABLE for table: {$table}\n");
        exit(1);
    }

    $createSql = $row['Create Table'];

    // Remove table-level AUTO_INCREMENT=\d+ from the ENGINE clause
    $createSql = preg_replace('/\s+AUTO_INCREMENT=\d+/i', '', $createSql);

    $output[] = "DROP TABLE IF EXISTS `{$table}`;";
    $output[] = $createSql . ";\n";
}

// 3. Append seed.sql content
$seedFile = PUBLIC_PATH . '/database/seed.sql';
if (!file_exists($seedFile)) {
    fwrite(STDERR, "Seed file not found: {$seedFile}\n");
    exit(1);
}
$seedContent = file_get_contents($seedFile);
if ($seedContent === false) {
    fwrite(STDERR, "Could not read seed file: {$seedFile}\n");
    exit(1);
}

$output[] = "-- ---------------------------------------------------------------------";
$output[] = "-- Baseline Seed Data (from database/seed.sql)";
$output[] = "-- ---------------------------------------------------------------------\n";
$output[] = trim($seedContent) . "\n";

// 4. Append schema_migrations baseline
$migrationsDir = PUBLIC_PATH . '/database/migrations';
$migrationFiles = glob($migrationsDir . '/*.sql');
if ($migrationFiles === false || empty($migrationFiles)) {
    fwrite(STDERR, "No migration files found in {$migrationsDir}\n");
    exit(1);
}
natsort($migrationFiles);

$output[] = "-- ---------------------------------------------------------------------";
$output[] = "-- Schema Migrations Baseline";
$output[] = "-- ---------------------------------------------------------------------\n";
$output[] = "INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES";

$migrationInserts = [];
foreach ($migrationFiles as $file) {
    $version = pathinfo($file, PATHINFO_FILENAME);
    $migrationInserts[] = "  ('{$version}', NOW())";
}
$output[] = implode(",\n", $migrationInserts) . "\nON DUPLICATE KEY UPDATE `version` = `version`;\n";

$output[] = "SET FOREIGN_KEY_CHECKS = 1;\n";

// Write database/install.sql
$installFile = PUBLIC_PATH . '/database/install.sql';
$finalSql = implode("\n", $output);
if (file_put_contents($installFile, $finalSql) === false) {
    fwrite(STDERR, "Failed to write {$installFile}\n");
    exit(1);
}

echo "Successfully built {$installFile} (" . number_format(strlen($finalSql)) . " bytes)\n";
echo "Tables included: " . count($tables) . "\n";
echo "Migrations recorded: " . count($migrationFiles) . "\n";
