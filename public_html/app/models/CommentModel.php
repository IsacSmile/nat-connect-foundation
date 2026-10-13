<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class CommentModel
{
    /**
     * Create a new comment record (default status: 'pending').
     */
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO comments (post_id, name, email, body, status, ip_hash, created_at)
             VALUES (:post_id, :name, :email, :body, :status, :ip_hash, NOW())'
        );
        $stmt->execute([
            'post_id' => (int) $data['post_id'],
            'name'    => mb_substr(trim((string) $data['name']), 0, 80),
            'email'   => mb_substr(trim((string) $data['email']), 0, 191),
            'body'    => trim((string) $data['body']),
            'status'  => $data['status'] ?? 'pending',
            'ip_hash' => $data['ip_hash'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Retrieve all approved comments for a specific post, oldest first.
     * Note: email is excluded for visitor privacy.
     */
    public static function getApprovedForPost(int $postId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, post_id, name, body, created_at
             FROM comments
             WHERE post_id = :post_id AND status = "approved"
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute(['post_id' => $postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count approved comments for a post.
     */
    public static function countApprovedForPost(int $postId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM comments WHERE post_id = :post_id AND status = "approved"'
        );
        $stmt->execute(['post_id' => $postId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get aggregate counts across comment statuses (pending, approved, spam).
     *
     * @return array<string, int>
     */
    public static function getStatusCounts(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT status, COUNT(*) as cnt FROM comments GROUP BY status');
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        return [
            'pending'  => (int) ($rows['pending'] ?? 0),
            'approved' => (int) ($rows['approved'] ?? 0),
            'spam'     => (int) ($rows['spam'] ?? 0),
        ];
    }

    /**
     * Retrieve comments by status with post titles for admin moderation.
     */
    public static function getByStatus(string $status, int $limit = 50, int $offset = 0): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT c.id, c.post_id, c.name, c.email, c.body, c.status, c.ip_hash, c.created_at,
                    p.title AS post_title, p.slug AS post_slug
             FROM comments c
             LEFT JOIN posts p ON c.post_id = p.id
             WHERE c.status = :status
             ORDER BY c.created_at DESC, c.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total comments matching a specific status.
     */
    public static function countByStatus(string $status): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Update comment status ('pending', 'approved', 'spam').
     */
    public static function updateStatus(int $id, string $status): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE comments SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Bulk update status for an array of comment IDs.
     */
    public static function bulkUpdateStatus(array $ids, string $status): int
    {
        $intIds = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        if (empty($intIds)) {
            return 0;
        }

        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($intIds), '?'));
        $stmt = $pdo->prepare("UPDATE comments SET status = ? WHERE id IN ($placeholders)");
        $params = array_merge([$status], array_values($intIds));
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Delete a single comment by ID.
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM comments WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Bulk delete comments by ID array.
     */
    public static function bulkDelete(array $ids): int
    {
        $intIds = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        if (empty($intIds)) {
            return 0;
        }

        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($intIds), '?'));
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id IN ($placeholders)");
        $stmt->execute(array_values($intIds));

        return $stmt->rowCount();
    }

    /**
     * Count comments submitted by an IP hash within the last $minutes minutes.
     */
    public static function countRecentByIpHash(string $ipHash, int $minutes = 10): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM comments WHERE ip_hash = :ip_hash AND created_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)'
        );
        $stmt->bindValue(':ip_hash', $ipHash, PDO::PARAM_STR);
        $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
