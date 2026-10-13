<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Database Seeder Runner
 *
 * Seeds initial categories and placeholder settings from database/seed.sql.
 * Safe to run multiple times (uses INSERT IGNORE).
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

try {
    $db = Database::getConnection();
    $sql = file_get_contents(PUBLIC_PATH . '/database/seed.sql');
    if ($sql === false) {
        throw new RuntimeException("Could not read database/seed.sql");
    }
    $db->exec($sql);
    echo "Seed data applied successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Seeding failed: " . $e->getMessage() . "\n");
    exit(1);
}
