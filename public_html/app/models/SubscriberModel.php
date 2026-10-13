<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * NatConnect Foundation — Subscriber Model (Design Phase D1)
 *
 * Handles newsletter subscription, rate limiting, anti-enumeration,
 * search pagination, and formula-injection-safe CSV export.
 */
class SubscriberModel
{
    /**
     * Check IP rate limit and record an attempt if within limit.
     * Max 5 submissions per hour per IP hash.
     *
     * @param string $ipHash
     * @param int $maxAttempts
     * @return bool True if allowed, false if rate limited.
     */
    public static function checkRateLimit(string $ipHash, int $maxAttempts = 5): bool
    {
        $pdo = Database::getConnection();

        // Opportunistic cleanup of logs older than 24 hours (1% probability)
        if (mt_rand(1, 100) === 1) {
            try {
                $pdo->exec("DELETE FROM subscribe_rate_limits WHERE recorded_at < NOW() - INTERVAL 24 HOUR");
            } catch (\Throwable) {
                // Ignore cleanup errors
            }
        }

        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM subscribe_rate_limits 
            WHERE ip_hash = :ip_hash AND recorded_at >= NOW() - INTERVAL 1 HOUR
        ");
        $stmt->execute(['ip_hash' => $ipHash]);
        $attempts = (int) $stmt->fetchColumn();

        if ($attempts >= $maxAttempts) {
            return false;
        }

        // Record this submission attempt
        $ins = $pdo->prepare("INSERT INTO subscribe_rate_limits (ip_hash, recorded_at) VALUES (:ip_hash, NOW())");
        $ins->execute(['ip_hash' => $ipHash]);

        return true;
    }

    /**
     * Subscribe an email address.
     *
     * Enforces:
     * - Valid email address
     * - Lowercased email
     * - Maximum 254 characters
     * - Mandatory explicit consent
     * - Non-enumeration: Always returns "Thanks! You're on the list." even if duplicate
     *
     * @param string $rawEmail
     * @param bool $consent
     * @param string $ipHash
     * @param string $source
     * @return array{success: bool, message: string}
     */
    public static function subscribe(string $rawEmail, bool $consent, string $ipHash, string $source = 'website'): array
    {
        if (!$consent) {
            throw new InvalidArgumentException('Please confirm your consent to receive updates.');
        }

        $email = strtolower(trim($rawEmail));

        if ($email === '') {
            throw new InvalidArgumentException('Email address is required.');
        }

        if (mb_strlen($email) > 254) {
            throw new InvalidArgumentException('Email address cannot exceed 254 characters.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        $source = substr(trim($source), 0, 30);
        if ($source === '') {
            $source = 'website';
        }

        $pdo = Database::getConnection();

        // INSERT IGNORE ensures duplicate email does not fail and does not create a second row
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO subscribers (email, consent_at, ip_hash, source, created_at)
            VALUES (:email, NOW(), :ip_hash, :source, NOW())
        ");
        $stmt->execute([
            'email'   => $email,
            'ip_hash' => $ipHash,
            'source'  => $source,
        ]);

        return [
            'success' => true,
            'message' => "Thanks! You're on the list.",
        ];
    }

    /**
     * Count total subscribers.
     */
    public static function getCount(): int
    {
        $pdo = Database::getConnection();
        return (int) $pdo->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();
    }

    /**
     * Retrieve paginated subscribers with optional search.
     *
     * @param ?string $search
     * @param int $page
     * @param int $perPage
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function getPaginated(?string $search = null, int $page = 1, int $perPage = 25): array
    {
        $pdo = Database::getConnection();

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $conditions[] = "email LIKE :search";
            $params['search'] = '%' . trim($search) . '%';
        }

        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM subscribers {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Items newest first
        $itemsStmt = $pdo->prepare("
            SELECT id, email, consent_at, source, created_at
            FROM subscribers
            {$where}
            ORDER BY created_at DESC, id DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) {
            $itemsStmt->bindValue($k, $v);
        }
        $itemsStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $itemsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $itemsStmt->execute();

        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalPages = max(1, (int) ceil($total / $perPage));

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Delete a subscriber by ID.
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM subscribers WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Escape cell content to prevent CSV formula injection attacks.
     * Cells starting with =, +, -, @ are prefixed with a single quote '.
     */
    public static function escapeCsvCell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }
        return $value;
    }

    /**
     * Export all subscribers to CSV.
     * Columns: Email, Consent Date, Source.
     *
     * @return array<int, array{email: string, consent_date: string, source: string}>
     */
    public static function getExportData(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT email, consent_at, source
            FROM subscribers
            ORDER BY created_at DESC, id DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $escaped = [];
        foreach ($rows as $row) {
            $escaped[] = [
                'email'        => self::escapeCsvCell((string) $row['email']),
                'consent_date' => self::escapeCsvCell((string) $row['consent_at']),
                'source'       => self::escapeCsvCell((string) $row['source']),
            ];
        }

        return $escaped;
    }
}
