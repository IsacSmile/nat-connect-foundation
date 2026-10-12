<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * NatConnect Foundation — Dashboard Data Access Model
 *
 * Provides aggregated platform analytics, top performing articles,
 * and recent editorial activity. All queries enforce prepared statements.
 */
class DashboardModel
{
    /**
     * Retrieve aggregate counts for the dashboard overview.
     * Uses ONE query with conditional SUM/CASE for post status counts,
     * plus a lightweight query for pending comments.
     *
     * @return array{
     *   total_posts: int,
     *   published_posts: int,
     *   draft_posts: int,
     *   scheduled_posts: int,
     *   total_views: int,
     *   pending_comments: int
     * }
     */
    public static function getStats(): array
    {
        $defaultStats = [
            'total_posts'      => 0,
            'published_posts'  => 0,
            'draft_posts'      => 0,
            'scheduled_posts'  => 0,
            'total_views'      => 0,
            'pending_comments' => 0,
        ];

        try {
            $db = Database::getConnection();

            // ONE query with conditional SUM/CASE using canonical PostModel SQL fragments
            $sqlPub = PostModel::SQL_IS_PUBLISHED;
            $sqlSched = PostModel::SQL_IS_SCHEDULED;
            $postQuery = "
                SELECT
                    COUNT(*) AS total_posts,
                    COALESCE(SUM(CASE WHEN {$sqlPub} THEN 1 ELSE 0 END), 0) AS published_posts,
                    COALESCE(SUM(CASE WHEN `status` = 'draft' THEN 1 ELSE 0 END), 0) AS draft_posts,
                    COALESCE(SUM(CASE WHEN {$sqlSched} THEN 1 ELSE 0 END), 0) AS scheduled_posts,
                    COALESCE(SUM(`views`), 0) AS total_views
                FROM `posts`
            ";
            $postStmt = $db->query($postQuery);
            $postRow = $postStmt->fetch();

            // Comments moderation count
            $commentStmt = $db->query("SELECT COUNT(*) FROM `comments` WHERE `status` = 'pending'");
            $pendingComments = (int) $commentStmt->fetchColumn();

            return [
                'total_posts'      => (int) ($postRow['total_posts'] ?? 0),
                'published_posts'  => (int) ($postRow['published_posts'] ?? 0),
                'draft_posts'      => (int) ($postRow['draft_posts'] ?? 0),
                'scheduled_posts'  => (int) ($postRow['scheduled_posts'] ?? 0),
                'total_views'      => (int) ($postRow['total_views'] ?? 0),
                'pending_comments' => $pendingComments,
            ];
        } catch (Throwable) {
            return $defaultStats;
        }
    }

    /**
     * Retrieve top articles ordered by view count.
     *
     * @param int $limit Maximum records to return (default: 5)
     * @return array<int, array>
     */
    public static function getTopPostsByViews(int $limit = 5): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT `id`, `title`, `slug`, `status`, `published_at`, `updated_at`, `created_at`, `views`
                FROM `posts`
                ORDER BY `views` DESC, `id` DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $posts = $stmt->fetchAll() ?: [];
            foreach ($posts as &$post) {
                $post['effective_status'] = PostModel::effectiveStatus($post);
            }
            unset($post);

            return $posts;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Retrieve the 5 most recently updated or created articles.
     *
     * @param int $limit Maximum records to return (default: 5)
     * @return array<int, array>
     */
    public static function getRecentlyUpdatedPosts(int $limit = 5): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT `id`, `title`, `slug`, `status`, `published_at`, `updated_at`, `created_at`, `views`
                FROM `posts`
                ORDER BY COALESCE(`updated_at`, `created_at`) DESC, `id` DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $posts = $stmt->fetchAll() ?: [];
            foreach ($posts as &$post) {
                $post['effective_status'] = PostModel::effectiveStatus($post);
            }
            unset($post);

            return $posts;
        } catch (Throwable) {
            return [];
        }
    }
}
