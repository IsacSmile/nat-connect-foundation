<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class SettingModel
{
    /**
     * Static in-memory cache for the duration of the HTTP request.
     * Guarantees settings are retrieved via exactly one query per request.
     *
     * @var array<string, string|null>|null
     */
    private static ?array $cache = null;

    /**
     * Retrieve all settings as key-value pairs.
     *
     * @return array<string, string|null>
     */
    public static function getAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $db = Database::getConnection();
        $stmt = $db->query('SELECT `key`, `value` FROM `settings`');
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        self::$cache = is_array($rows) ? $rows : [];
        return self::$cache;
    }

    /**
     * Get a specific setting value with an optional fallback.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::getAll();
        return $all[$key] ?? $default;
    }

    /**
     * Set a single setting value.
     */
    public static function set(string $key, ?string $value): void
    {
        self::setMany([$key => $value]);
    }

    /**
     * Upsert multiple settings within a transaction.
     *
     * @param array<string, string|null> $settings
     */
    public static function setMany(array $settings): void
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('
                INSERT INTO `settings` (`key`, `value`)
                VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
            ');

            foreach ($settings as $key => $value) {
                $stmt->execute([
                    ':key'   => $key,
                    ':value' => $value !== null ? (string) $value : null,
                ]);
            }

            $db->commit();
            self::$cache = null;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Determine if donation features are active.
     * Active only if at least one donation setting field is filled.
     */
    public static function isDonateActive(): bool
    {
        $all = self::getAll();
        $intro = trim((string) ($all['donate_intro'] ?? ''));
        $upiId = trim((string) ($all['donate_upi_id'] ?? ''));
        $upiQr = trim((string) ($all['donate_upi_qr'] ?? ''));
        $bank = trim((string) ($all['donate_bank_details'] ?? ''));

        return $intro !== '' || $upiId !== '' || $upiQr !== '' || $bank !== '';
    }

    /**
     * Clear the in-memory cache (primarily for tests).
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
