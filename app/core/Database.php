<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * NatConnect Foundation — PDO Singleton Database Wrapper
 *
 * Enforces prepared statements, strict exception mode, disabled emulation, and UTF-8mb4.
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Private constructor prevents direct instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Alias for getConnection()
     */
    public static function pdo(): PDO
    {
        return self::getConnection();
    }

    /**
     * Get the shared PDO connection instance.
     *
     * @return PDO
     * @throws RuntimeException|PDOException
     */
    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host    = (string) config('db.host', '127.0.0.1');
        $port    = (int)    config('db.port', 3306);
        $dbname  = (string) config('db.name', 'natconnect_db');
        $user    = (string) config('db.user', 'root');
        $pass    = (string) config('db.pass', '');
        $charset = (string) config('db.charset', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ];

        try {
            self::$instance = new PDO($dsn, $user, $pass, $options);
            self::$instance->exec("SET time_zone = '+05:30'");
            return self::$instance;
        } catch (PDOException $e) {
            $logDir = dirname(__DIR__) . '/storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logMessage = sprintf(
                "[%s] Database connection failed: %s\n",
                date('Y-m-d H:i:s'),
                $e->getMessage()
            );
            @file_put_contents($logDir . '/error.log', $logMessage, FILE_APPEND);

            if (config('app.debug', false)) {
                throw $e;
            }

            throw new RuntimeException("Database connection error. Please contact the administrator.");
        }
    }

    /**
     * For testing or resetting connection state.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }
}
