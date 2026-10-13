<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class EngagementModel
{
    /**
     * Record a unique post view for a visitor hash on the current date.
     * Increments posts.views only if a new row was inserted into post_views.
     *
     * @param int $postId
     * @param string $visitorHash
     * @return bool True if a new view was counted, false if already viewed today.
     */
    public static function recordView(int $postId, string $visitorHash): bool
    {
        $pdo = Database::getConnection();

        // Opportunistic 1% pruning of views older than 90 days
        if (mt_rand(1, 100) === 1) {
            self::pruneOldViews(90);
        }

        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO post_views (post_id, visitor_hash, viewed_on) VALUES (:post_id, :visitor_hash, CURDATE())'
        );
        $stmt->execute([
            'post_id'      => $postId,
            'visitor_hash' => $visitorHash,
        ]);

        if ($stmt->rowCount() > 0) {
            $upd = $pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = :post_id');
            $upd->execute(['post_id' => $postId]);
            return true;
        }

        return false;
    }

    /**
     * Delete post_views records older than $days days.
     */
    public static function pruneOldViews(int $days = 90): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM post_views WHERE viewed_on < DATE_SUB(CURDATE(), INTERVAL :days DAY)');
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Check if a specific visitor hash has liked a given post.
     */
    public static function hasUserLiked(int $postId, string $visitorHash): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT 1 FROM post_likes WHERE post_id = :post_id AND visitor_hash = :visitor_hash LIMIT 1'
        );
        $stmt->execute([
            'post_id'      => $postId,
            'visitor_hash' => $visitorHash,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Toggle like state for a post and visitor hash.
     * Returns associative array: ['liked' => bool, 'count' => int].
     */
    public static function toggleLike(int $postId, string $visitorHash): array
    {
        $pdo = Database::getConnection();

        $alreadyLiked = self::hasUserLiked($postId, $visitorHash);

        if ($alreadyLiked) {
            // Unlike
            $del = $pdo->prepare(
                'DELETE FROM post_likes WHERE post_id = :post_id AND visitor_hash = :visitor_hash'
            );
            $del->execute([
                'post_id'      => $postId,
                'visitor_hash' => $visitorHash,
            ]);

            $upd = $pdo->prepare(
                'UPDATE posts SET likes = GREATEST(0, CAST(likes AS SIGNED) - 1) WHERE id = :post_id'
            );
            $upd->execute(['post_id' => $postId]);
            $isLiked = false;
        } else {
            // Like
            $ins = $pdo->prepare(
                'INSERT IGNORE INTO post_likes (post_id, visitor_hash) VALUES (:post_id, :visitor_hash)'
            );
            $ins->execute([
                'post_id'      => $postId,
                'visitor_hash' => $visitorHash,
            ]);

            $upd = $pdo->prepare('UPDATE posts SET likes = likes + 1 WHERE id = :post_id');
            $upd->execute(['post_id' => $postId]);
            $isLiked = true;
        }

        // Fetch current count
        $cntStmt = $pdo->prepare('SELECT likes FROM posts WHERE id = :post_id');
        $cntStmt->execute(['post_id' => $postId]);
        $count = (int) $cntStmt->fetchColumn();

        return [
            'liked' => $isLiked,
            'count' => $count,
        ];
    }

    /**
     * Check if an IP hash is within the allowed like action rate limit.
     */
    public static function checkLikeRateLimit(string $ipHash, int $limit = 60, int $hours = 1): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM like_rate_limits WHERE ip_hash = :ip_hash AND created_at > DATE_SUB(NOW(), INTERVAL :hours HOUR)'
        );
        $stmt->bindValue(':ip_hash', $ipHash, PDO::PARAM_STR);
        $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
        $stmt->execute();

        $count = (int) $stmt->fetchColumn();
        return $count < $limit;
    }

    /**
     * Record a like action attempt for an IP hash.
     */
    public static function recordLikeAttempt(string $ipHash): void
    {
        $pdo = Database::getConnection();

        // 1% opportunistic pruning of rate limits older than 24 hours
        if (mt_rand(1, 100) === 1) {
            self::pruneOldLikeRateLimits(24);
        }

        $stmt = $pdo->prepare('INSERT INTO like_rate_limits (ip_hash) VALUES (:ip_hash)');
        $stmt->execute(['ip_hash' => $ipHash]);
    }

    /**
     * Prune rate limits older than $hours hours.
     */
    public static function pruneOldLikeRateLimits(int $hours = 24): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM like_rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL :hours HOUR)'
        );
        $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
