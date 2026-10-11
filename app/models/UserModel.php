<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * NatConnect Foundation — User & Authentication Data Access Model
 *
 * Encapsulates database queries for user management, credential verification,
 * and login rate limiting. All queries enforce prepared statements.
 */
class UserModel
{
    /**
     * Find user record by primary key ID.
     */
    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, name, email, password_hash, role, created_at FROM users WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Find user record by email address.
     */
    public static function findByEmail(string $email): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, name, email, password_hash, role, created_at FROM users WHERE email = :email LIMIT 1");
        $stmt->bindValue(':email', trim($email), PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Update user profile information (name and email).
     */
    public static function updateProfile(int $id, string $name, string $email): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
        return $stmt->execute([
            ':name'  => trim($name),
            ':email' => trim($email),
            ':id'    => $id,
        ]);
    }

    /**
     * Update password hash for an existing user.
     */
    public static function updatePassword(int $id, string $newPasswordHash): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        return $stmt->execute([
            ':hash' => $newPasswordHash,
            ':id'   => $id,
        ]);
    }

    /**
     * Check if an email is registered to any other user.
     */
    public static function isEmailTakenByOther(string $email, int $currentUserId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $stmt->execute([
            ':email' => trim($email),
            ':id'    => $currentUserId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Retrieve failed login attempt count and oldest attempt timestamp for an IP hash in the given window.
     *
     * @return array{count: int, oldest_attempt: string|null}
     */
    public static function getFailedAttemptsByIp(string $ipHash, int $windowMinutes = 15): array
    {
        $db = Database::getConnection();
        $cutoff = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));

        $stmt = $db->prepare("
            SELECT COUNT(*) AS attempt_count, MIN(attempted_at) AS oldest_attempt
            FROM login_attempts
            WHERE ip_hash = :ip_hash AND attempted_at >= :cutoff
        ");
        $stmt->execute([
            ':ip_hash' => $ipHash,
            ':cutoff'  => $cutoff,
        ]);
        $row = $stmt->fetch();

        return [
            'count'          => (int) ($row['attempt_count'] ?? 0),
            'oldest_attempt' => $row['oldest_attempt'] ?? null,
        ];
    }

    /**
     * Retrieve failed login attempt count and oldest attempt timestamp for an account email hash in the given window.
     *
     * @return array{count: int, oldest_attempt: string|null}
     */
    public static function getFailedAttemptsByAccount(string $accountHash, int $windowMinutes = 15): array
    {
        $db = Database::getConnection();
        $cutoff = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));

        $stmt = $db->prepare("
            SELECT COUNT(*) AS attempt_count, MIN(attempted_at) AS oldest_attempt
            FROM login_attempts
            WHERE account_hash = :account_hash AND attempted_at >= :cutoff
        ");
        $stmt->execute([
            ':account_hash' => $accountHash,
            ':cutoff'       => $cutoff,
        ]);
        $row = $stmt->fetch();

        return [
            'count'          => (int) ($row['attempt_count'] ?? 0),
            'oldest_attempt' => $row['oldest_attempt'] ?? null,
        ];
    }

    /**
     * Alias for getFailedAttemptsByIp for backward compatibility.
     *
     * @return array{count: int, oldest_attempt: string|null}
     */
    public static function getFailedAttempts(string $ipHash, int $windowMinutes = 15): array
    {
        return self::getFailedAttemptsByIp($ipHash, $windowMinutes);
    }

    /**
     * Record a failed login attempt for the specified IP hash and optional account hash.
     */
    public static function recordFailedLogin(string $ipHash, ?string $accountHash = null): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO login_attempts (ip_hash, account_hash, attempted_at) VALUES (:ip_hash, :account_hash, :attempted_at)");
        $stmt->execute([
            ':ip_hash'      => $ipHash,
            ':account_hash' => $accountHash,
            ':attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Clear all recorded failed attempts for an IP hash and account hash upon successful login.
     */
    public static function clearFailedAttempts(string $ipHash, ?string $accountHash = null): void
    {
        $db = Database::getConnection();
        if ($accountHash !== null) {
            $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_hash = :ip_hash OR account_hash = :account_hash");
            $stmt->execute([
                ':ip_hash'      => $ipHash,
                ':account_hash' => $accountHash,
            ]);
        } else {
            $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_hash = :ip_hash");
            $stmt->execute([':ip_hash' => $ipHash]);
        }
    }

    /**
     * Opportunistically purge stale login attempt rows older than the specified hours (24h).
     */
    public static function purgeOldAttempts(int $hours = 24): void
    {
        try {
            $db = Database::getConnection();
            $cutoff = date('Y-m-d H:i:s', time() - ($hours * 3600));
            $stmt = $db->prepare("DELETE FROM login_attempts WHERE attempted_at < :cutoff");
            $stmt->execute([':cutoff' => $cutoff]);
        } catch (Throwable) {
            // Non-critical background maintenance; ignore failure
        }
    }
}
