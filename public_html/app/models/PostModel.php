<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\ImageUploader;
use App\Core\Sanitizer;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * NatConnect Foundation — Editorial Post Model
 *
 * Implements CRUD, search and status filtering, pagination, unique slug generation,
 * HTMLPurifier content sanitization, read time calculation, tag sync, and automatic
 * 301 redirect management for slug changes on published articles.
 */
class PostModel
{
    /**
     * Canonical SQL fragment for published posts.
     */
    public const SQL_IS_PUBLISHED = "(status = 'published' OR (status = 'scheduled' AND published_at <= NOW()))";

    /**
     * Canonical SQL fragment for future scheduled posts.
     */
    public const SQL_IS_SCHEDULED = "(status = 'scheduled' AND published_at > NOW())";

    /**
     * SQL fragment for "is published" with optional table alias prefix.
     */
    public static function sqlPublished(?string $alias = null): string
    {
        $prefix = $alias ? rtrim($alias, '.') . '.' : '';
        return "({$prefix}status = 'published' OR ({$prefix}status = 'scheduled' AND {$prefix}published_at <= NOW()))";
    }

    /**
     * SQL fragment for "is scheduled (future)" with optional table alias prefix.
     */
    public static function sqlScheduled(?string $alias = null): string
    {
        $prefix = $alias ? rtrim($alias, '.') . '.' : '';
        return "({$prefix}status = 'scheduled' AND {$prefix}published_at > NOW())";
    }

    /**
     * Determine the effective status of a post row.
     * Scheduled posts whose published_at has passed are effectively published.
     *
     * @param array $post
     * @return 'published'|'scheduled'|'draft'
     */
    public static function effectiveStatus(array $post): string
    {
        $status = (string) ($post['status'] ?? 'draft');
        if ($status === 'scheduled') {
            $publishedAt = $post['published_at'] ?? null;
            if ($publishedAt && strtotime((string) $publishedAt) <= time()) {
                return 'published';
            }
            return 'scheduled';
        }
        if ($status === 'published') {
            return 'published';
        }
        return 'draft';
    }

    /**
     * Retrieve status counts bucketed by effective status.
     *
     * @return array{all: int, published: int, draft: int, scheduled: int}
     */
    public static function getStatusCounts(): array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::SQL_IS_PUBLISHED;
        $sqlSched = self::SQL_IS_SCHEDULED;
        $sql = "
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN {$sqlPub} THEN 1 ELSE 0 END), 0) AS published,
                COALESCE(SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END), 0) AS draft,
                COALESCE(SUM(CASE WHEN {$sqlSched} THEN 1 ELSE 0 END), 0) AS scheduled
            FROM posts
        ";
        $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

        return [
            'all'       => (int) ($row['total'] ?? 0),
            'published' => (int) ($row['published'] ?? 0),
            'draft'     => (int) ($row['draft'] ?? 0),
            'scheduled' => (int) ($row['scheduled'] ?? 0),
        ];
    }

    /**
     * Retrieve paginated posts with status, category, and search filters.
     *
     * @param array{status?: string, category_id?: int, search?: string} $filters
     * @param int $page
     * @param int $perPage
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public static function getPaginated(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::getConnection();

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        // Status filter applying effective status rules (all, published, draft, scheduled)
        if (!empty($filters['status']) && in_array($filters['status'], ['published', 'draft', 'scheduled'], true)) {
            if ($filters['status'] === 'published') {
                $conditions[] = self::sqlPublished('p');
            } elseif ($filters['status'] === 'scheduled') {
                $conditions[] = self::sqlScheduled('p');
            } elseif ($filters['status'] === 'draft') {
                $conditions[] = "p.status = 'draft'";
            }
        }

        // Category filter
        if (!empty($filters['category_id']) && (int) $filters['category_id'] > 0) {
            $conditions[] = "p.category_id = :category_id";
            $params['category_id'] = (int) $filters['category_id'];
        }

        // Title search filter
        if (!empty($filters['search']) && trim((string) $filters['search']) !== '') {
            $conditions[] = "p.title LIKE :search";
            $params['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Count total matching rows
        $countSql = "SELECT COUNT(*) FROM posts p {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch paginated records
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                {$whereClause}
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as &$item) {
            $item['effective_status'] = self::effectiveStatus($item);
        }
        unset($item);

        $totalPages = (int) ceil($total / $perPage);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, $totalPages),
        ];
    }

    /**
     * Retrieve the latest single featured published post.
     */
    public static function getFeaturedPublished(): ?array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$sqlPub}
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT 1";
        $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retrieve the latest published posts (optionally excluding specific post IDs).
     */
    public static function getLatestPublished(int $limit = 6, array|int|null $exclude = null): array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $excludeClause = '';
        if (is_array($exclude) && !empty($exclude)) {
            $clean = implode(',', array_map('intval', $exclude));
            $excludeClause = " AND p.id NOT IN ({$clean})";
        } elseif (is_numeric($exclude) && (int) $exclude > 0) {
            $excludeClause = " AND p.id != " . (int) $exclude;
        }
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$sqlPub}{$excludeClause}
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT " . (int) $limit;
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve all categories that contain at least one published post.
     */
    public static function getCategoriesWithPublishedPosts(): array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $sql = "SELECT c.*, COUNT(p.id) AS post_count
                FROM categories c
                INNER JOIN posts p ON p.category_id = c.id
                WHERE {$sqlPub}
                GROUP BY c.id, c.name, c.slug, c.description, c.created_at
                HAVING post_count > 0
                ORDER BY c.name ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve published posts for a specific category strip (optionally excluding post IDs).
     */
    public static function getPublishedForCategory(int $categoryId, int $limit = 3, array|int|null $exclude = null): array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $params = ['catId' => $categoryId];
        $excludeSql = '';
        if (is_array($exclude) && !empty($exclude)) {
            $clean = implode(',', array_map('intval', $exclude));
            $excludeSql = " AND p.id NOT IN ({$clean})";
        } elseif (is_numeric($exclude) && (int) $exclude > 0) {
            $excludeSql = ' AND p.id != :excludeId';
            $params['excludeId'] = (int) $exclude;
        }

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$sqlPub} AND p.category_id = :catId {$excludeSql}
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT " . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve paginated published posts for a category archive.
     */
    public static function getPaginatedByCategory(int $categoryId, int $page = 1, int $perPage = 12): array
    {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $sqlPub = self::sqlPublished('p');
        $countSql = "SELECT COUNT(*) FROM posts p WHERE {$sqlPub} AND p.category_id = :catId";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute(['catId' => $categoryId]);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$sqlPub} AND p.category_id = :catId
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue('catId', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalPages = (int) ceil($total / $perPage);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, $totalPages),
        ];
    }

    /**
     * Retrieve paginated published posts for a tag archive.
     */
    public static function getPaginatedByTag(int $tagId, int $page = 1, int $perPage = 12): array
    {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $sqlPub = self::sqlPublished('p');
        $countSql = "SELECT COUNT(DISTINCT p.id)
                     FROM posts p
                     INNER JOIN post_tags pt ON pt.post_id = p.id
                     WHERE {$sqlPub} AND pt.tag_id = :tagId";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute(['tagId' => $tagId]);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM posts p
                INNER JOIN post_tags pt ON pt.post_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$sqlPub} AND pt.tag_id = :tagId
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue('tagId', $tagId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalPages = (int) ceil($total / $perPage);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, $totalPages),
        ];
    }

    /**
     * Search published posts by title, excerpt, and content with LIKE wildcard escaping.
     */
    public static function searchPublished(string $query, int $page = 1, int $perPage = 12): array
    {
        $pdo = Database::getConnection();
        $query = mb_substr(trim($query), 0, 100);
        if ($query === '') {
            return [
                'items'       => [],
                'total'       => 0,
                'page'        => 1,
                'per_page'    => $perPage,
                'total_pages' => 1,
                'query'       => '',
            ];
        }

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        // Escape wildcard characters % and _ and backslash \ for LIKE
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
        $searchTerm = '%' . $escaped . '%';

        $sqlPub = self::sqlPublished('p');
        $whereClause = "WHERE {$sqlPub} AND (p.title LIKE :term1 OR p.excerpt LIKE :term2 OR p.content LIKE :term3)";

        $countSql = "SELECT COUNT(*) FROM posts p {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([
            'term1' => $searchTerm,
            'term2' => $searchTerm,
            'term3' => $searchTerm,
        ]);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                {$whereClause}
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue('term1', $searchTerm);
        $stmt->bindValue('term2', $searchTerm);
        $stmt->bindValue('term3', $searchTerm);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalPages = (int) ceil($total / $perPage);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, $totalPages),
            'query'       => $query,
        ];
    }

    /**
     * Retrieve the latest 3 published posts (used for 404 pages).
     */
    public static function getLatestPublishedPosts(int $limit = 3): array
    {
        return self::getLatestPublished($limit);
    }

    /**
     * Find a published post by slug (enforces PostModel::sqlPublished()).
     */
    public static function getBySlugPublished(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = :slug AND {$sqlPub}
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retrieve the chronologically previous published post.
     */
    public static function getPreviousPublishedPost(?string $publishedAt, int $currentId): ?array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $pub = $publishedAt ?: date('Y-m-d H:i:s');
        $sql = "SELECT p.id, p.title, p.slug, p.published_at
                FROM posts p
                WHERE {$sqlPub}
                  AND (p.published_at < :pub1 OR (p.published_at = :pub2 AND p.id < :id))
                ORDER BY p.published_at DESC, p.id DESC
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'pub1' => $pub,
            'pub2' => $pub,
            'id'   => $currentId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retrieve the chronologically next published post.
     */
    public static function getNextPublishedPost(?string $publishedAt, int $currentId): ?array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $pub = $publishedAt ?: date('Y-m-d H:i:s');
        $sql = "SELECT p.id, p.title, p.slug, p.published_at
                FROM posts p
                WHERE {$sqlPub}
                  AND (p.published_at > :pub1 OR (p.published_at = :pub2 AND p.id > :id))
                ORDER BY p.published_at ASC, p.id ASC
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'pub1' => $pub,
            'pub2' => $pub,
            'id'   => $currentId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retrieve related posts: same category first, then posts sharing tags,
     * strictly excluding current post and unpublished posts, up to $limit (default 3).
     *
     * @param int $currentPostId
     * @param int|null $categoryId
     * @param int[] $tagIds
     * @param int $limit
     * @return array
     */
    public static function getRelatedPosts(int $currentPostId, ?int $categoryId, array $tagIds = [], int $limit = 3): array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $results = [];
        $foundIds = [$currentPostId];

        // 1. Same category first
        if ($categoryId) {
            $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                    FROM posts p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE {$sqlPub}
                      AND p.category_id = :cat_id
                      AND p.id != :curr_id
                    ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                    LIMIT " . (int) $limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['cat_id' => $categoryId, 'curr_id' => $currentPostId]);
            $catPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($catPosts as $p) {
                $results[] = $p;
                $foundIds[] = (int) $p['id'];
            }
        }

        // 2. Posts sharing tags if limit not yet reached
        $remaining = $limit - count($results);
        $cleanTagIds = array_values(array_filter(array_map('intval', $tagIds), fn($id) => $id > 0));
        if ($remaining > 0 && !empty($cleanTagIds)) {
            $tagIn = implode(',', $cleanTagIds);
            $notIn = implode(',', $foundIds);
            $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, COUNT(pt.tag_id) AS matching_tags
                    FROM posts p
                    LEFT JOIN categories c ON p.category_id = c.id
                    INNER JOIN post_tags pt ON p.id = pt.post_id
                    WHERE {$sqlPub}
                      AND pt.tag_id IN ({$tagIn})
                      AND p.id NOT IN ({$notIn})
                    GROUP BY p.id, c.name, c.slug
                    ORDER BY matching_tags DESC, COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                    LIMIT " . (int) $remaining;
            $tagPosts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tagPosts as $p) {
                $results[] = $p;
            }
        }

        return $results;
    }

    /**
     * Retrieve popular published posts: most views in the last 30 days using post_views,
     * fallback to total views, excluding current post and unpublished posts, up to $limit (default 3).
     *
     * @param int $currentPostId
     * @param int $limit
     * @return array
     */
    public static function getPopularPosts(int $currentPostId, int $limit = 3): array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                       COALESCE(recent.recent_count, 0) AS recent_views
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN (
                    SELECT post_id, COUNT(*) AS recent_count
                    FROM post_views
                    WHERE viewed_on >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY post_id
                ) recent ON p.id = recent.post_id
                WHERE {$sqlPub} AND p.id != :curr_id
                ORDER BY recent_views DESC, p.views DESC, COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT " . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['curr_id' => $currentPostId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Find post by ID with category and tags.
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            return null;
        }

        $post['effective_status'] = self::effectiveStatus($post);
        $post['tags'] = TagModel::getTagsForPost($id);
        $post['tag_names'] = implode(', ', array_column($post['tags'], 'name'));

        return $post;
    }

    /**
     * Find effectively published post by ID.
     */
    public static function getByIdPublished(int $id): ?array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $stmt = $pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id = :id AND {$sqlPub}
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        return $post ?: null;
    }

    /**
     * Find post by slug.
     */
    public static function getBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.slug = :slug
             LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            return null;
        }

        $post['effective_status'] = self::effectiveStatus($post);
        $post['tags'] = TagModel::getTagsForPost((int) $post['id']);
        $post['tag_names'] = implode(', ', array_column($post['tags'], 'name'));

        return $post;
    }

    /**
     * Ensure slug uniqueness by appending -2, -3... if the slug is already taken.
     */
    public static function generateUniqueSlug(string $baseTitle, ?int $excludeId = null): string
    {
        $baseSlug = slugify($baseTitle);
        if ($baseSlug === '') {
            $baseSlug = 'post';
        }

        $pdo = Database::getConnection();
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            if ($excludeId !== null) {
                $stmt = $pdo->prepare("SELECT 1 FROM posts WHERE slug = :slug AND id != :exclude_id LIMIT 1");
                $stmt->execute(['slug' => $slug, 'exclude_id' => $excludeId]);
            } else {
                $stmt = $pdo->prepare("SELECT 1 FROM posts WHERE slug = :slug LIMIT 1");
                $stmt->execute(['slug' => $slug]);
            }

            if (!$stmt->fetchColumn()) {
                return $slug;
            }

            $counter++;
            $slug = "{$baseSlug}-{$counter}";
        }
    }

    /**
     * Create a new post.
     *
     * @param array{
     *   title: string,
     *   slug?: string,
     *   excerpt?: ?string,
     *   content?: string,
     *   category_id: int,
     *   author_name?: ?string,
     *   status: string,
     *   published_at?: ?string,
     *   meta_title?: ?string,
     *   meta_description?: ?string,
     *   tags?: string|string[]
     * } $data
     */
    public static function create(array $data): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Post title is required.');
        }

        $categoryId = (int) ($data['category_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('Please select a valid category.');
        }

        // Verify category exists
        if (CategoryModel::getById($categoryId) === null) {
            throw new InvalidArgumentException('The selected category does not exist.');
        }

        // Slug handling
        $requestedSlug = trim((string) ($data['slug'] ?? ''));
        $slugSource = $requestedSlug !== '' ? $requestedSlug : $title;
        $slug = self::generateUniqueSlug($slugSource);

        // Sanitize rich-text content with HTMLPurifier
        $rawContent = (string) ($data['content'] ?? '');
        $sanitizedContent = Sanitizer::clean($rawContent);

        // Manual excerpt (stored only if written manually by author; null otherwise)
        $rawExcerpt = trim((string) ($data['excerpt'] ?? ''));
        $excerpt = $rawExcerpt !== '' ? $rawExcerpt : null;

        // Calculate reading time
        $readTimeMinutes = readTime($sanitizedContent);

        // Author name
        $authorName = trim((string) ($data['author_name'] ?? ''));
        if ($authorName === '') {
            $authorName = 'NatConnect Foundation';
        }

        // Status & published_at rules
        $status = (string) ($data['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published', 'scheduled'], true)) {
            $status = 'draft';
        }

        $publishedAt = !empty($data['published_at']) ? str_replace('T', ' ', trim((string) $data['published_at'])) : null;
        if ($publishedAt !== null && strlen($publishedAt) === 16) {
            $publishedAt .= ':00';
        }
        $now = date('Y-m-d H:i:s');

        if ($status === 'published') {
            if ($publishedAt === null || $publishedAt === '') {
                $publishedAt = $now;
            }
        } elseif ($status === 'scheduled') {
            if ($publishedAt === null || $publishedAt === '') {
                throw new InvalidArgumentException('A publication date and time is required for scheduled posts.');
            }
        } elseif ($status === 'draft') {
            if ($publishedAt === '') {
                $publishedAt = null;
            }
        }

        // Cover media handling
        $coverType = (string) ($data['cover_type'] ?? 'image');
        if (!in_array($coverType, ['image', 'video'], true)) {
            $coverType = 'image';
        }

        $coverImage = null;
        $coverAlt   = null;
        $videoUrl   = null;

        if ($coverType === 'image') {
            $rawCoverImage = trim((string) ($data['cover_image'] ?? ''));
            if ($rawCoverImage !== '') {
                $coverAlt = trim((string) ($data['cover_alt'] ?? ''));
                if ($coverAlt === '') {
                    throw new InvalidArgumentException('Alternative text (alt text) is required for the cover image.');
                }
                $coverImage = $rawCoverImage;
            }
        } elseif ($coverType === 'video') {
            $rawVideoUrl = trim((string) ($data['video_url'] ?? ''));
            if ($rawVideoUrl !== '') {
                $videoId = youtubeId($rawVideoUrl);
                if ($videoId === null) {
                    throw new InvalidArgumentException('Please enter a valid YouTube video URL or 11-character video ID.');
                }
                $videoUrl = $videoId;
            }
        }

        // Meta tags
        $metaTitle = trim((string) ($data['meta_title'] ?? ''));
        $metaDesc  = trim((string) ($data['meta_description'] ?? ''));

        $isPick = !empty($data['is_pick']) ? 1 : 0;

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO posts (
                    title, slug, excerpt, content, category_id, author_name,
                    cover_type, cover_image, cover_alt, video_url,
                    status, is_pick, published_at, read_time_minutes, meta_title, meta_description
                 ) VALUES (
                    :title, :slug, :excerpt, :content, :category_id, :author_name,
                    :cover_type, :cover_image, :cover_alt, :video_url,
                    :status, :is_pick, :published_at, :read_time_minutes, :meta_title, :meta_description
                 )"
            );

            $stmt->execute([
                'title'             => $title,
                'slug'              => $slug,
                'excerpt'           => $excerpt !== '' ? $excerpt : null,
                'content'           => $sanitizedContent,
                'category_id'       => $categoryId,
                'author_name'       => $authorName,
                'cover_type'        => $coverType,
                'cover_image'       => $coverImage,
                'cover_alt'         => $coverAlt,
                'video_url'         => $videoUrl,
                'status'            => $status,
                'is_pick'           => $isPick,
                'published_at'      => $publishedAt,
                'read_time_minutes' => $readTimeMinutes,
                'meta_title'        => $metaTitle !== '' ? $metaTitle : null,
                'meta_description'  => $metaDesc !== '' ? $metaDesc : null,
            ]);

            $postId = (int) $pdo->lastInsertId();

            // Sync tags
            if (isset($data['tags'])) {
                TagModel::syncTagsForPost($postId, $data['tags']);
            }

            $pdo->commit();
            return $postId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update an existing post.
     */
    public static function update(int $id, array $data): bool
    {
        $existing = self::getById($id);
        if ($existing === null) {
            throw new RuntimeException("Post #{$id} not found.");
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Post title is required.');
        }

        $categoryId = (int) ($data['category_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new InvalidArgumentException('Please select a valid category.');
        }

        if (CategoryModel::getById($categoryId) === null) {
            throw new InvalidArgumentException('The selected category does not exist.');
        }

        // Slug uniqueness check
        $requestedSlug = trim((string) ($data['slug'] ?? ''));
        $slugSource = $requestedSlug !== '' ? $requestedSlug : $title;
        $newSlug = self::generateUniqueSlug($slugSource, $id);

        // Sanitize rich-text content
        $rawContent = (string) ($data['content'] ?? '');
        $sanitizedContent = Sanitizer::clean($rawContent);

        // Manual excerpt (stored only if written manually by author; null otherwise)
        $rawExcerpt = trim((string) ($data['excerpt'] ?? ''));
        $excerpt = $rawExcerpt !== '' ? $rawExcerpt : null;

        $readTimeMinutes = readTime($sanitizedContent);

        $authorName = trim((string) ($data['author_name'] ?? ''));
        if ($authorName === '') {
            $authorName = $existing['author_name'] ?: 'NatConnect Foundation';
        }

        $status = (string) ($data['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published', 'scheduled'], true)) {
            $status = $existing['status'];
        }

        $publishedAt = !empty($data['published_at']) ? str_replace('T', ' ', trim((string) $data['published_at'])) : null;
        if ($publishedAt !== null && strlen($publishedAt) === 16) {
            $publishedAt .= ':00';
        }
        $now = date('Y-m-d H:i:s');

        if ($status === 'published') {
            if ($publishedAt === null || $publishedAt === '') {
                $publishedAt = $existing['published_at'] ?: $now;
            }
        } elseif ($status === 'scheduled') {
            if ($publishedAt === null || $publishedAt === '') {
                throw new InvalidArgumentException('A publication date and time is required for scheduled posts.');
            }
        } elseif ($status === 'draft') {
            if ($publishedAt === '') {
                $publishedAt = null;
            }
        }

        // Cover media handling
        $coverType = (string) ($data['cover_type'] ?? 'image');
        if (!in_array($coverType, ['image', 'video'], true)) {
            $coverType = 'image';
        }

        $coverImage = null;
        $coverAlt   = null;
        $videoUrl   = null;

        if ($coverType === 'image') {
            $rawCoverImage = trim((string) ($data['cover_image'] ?? ''));
            if ($rawCoverImage !== '') {
                $coverAlt = trim((string) ($data['cover_alt'] ?? ''));
                if ($coverAlt === '') {
                    throw new InvalidArgumentException('Alternative text (alt text) is required for the cover image.');
                }
                $coverImage = $rawCoverImage;
            }
        } elseif ($coverType === 'video') {
            $rawVideoUrl = trim((string) ($data['video_url'] ?? ''));
            if ($rawVideoUrl !== '') {
                $videoId = youtubeId($rawVideoUrl);
                if ($videoId === null) {
                    throw new InvalidArgumentException('Please enter a valid YouTube video URL or 11-character video ID.');
                }
                $videoUrl = $videoId;
            }
        }

        // Cleanup replaced/removed cover image files
        if (!empty($existing['cover_image']) && $existing['cover_image'] !== $coverImage) {
            ImageUploader::deleteVariants($existing['cover_image']);
        }

        $metaTitle = trim((string) ($data['meta_title'] ?? ''));
        $metaDesc  = trim((string) ($data['meta_description'] ?? ''));

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Check if published post changed slug -> create 301 redirect
            if (self::effectiveStatus($existing) === 'published' && $existing['slug'] !== $newSlug) {
                RedirectModel::createOrUpdate('/post/' . $existing['slug'], '/post/' . $newSlug);
            }

            $isPick = !empty($data['is_pick']) ? 1 : 0;

            $stmt = $pdo->prepare(
                "UPDATE posts SET
                    title = :title,
                    slug = :slug,
                    excerpt = :excerpt,
                    content = :content,
                    category_id = :category_id,
                    author_name = :author_name,
                    cover_type = :cover_type,
                    cover_image = :cover_image,
                    cover_alt = :cover_alt,
                    video_url = :video_url,
                    status = :status,
                    is_pick = :is_pick,
                    published_at = :published_at,
                    updated_at = :updated_at,
                    read_time_minutes = :read_time_minutes,
                    meta_title = :meta_title,
                    meta_description = :meta_description
                 WHERE id = :id"
            );

            $stmt->execute([
                'id'                => $id,
                'title'             => $title,
                'slug'              => $newSlug,
                'excerpt'           => $excerpt !== '' ? $excerpt : null,
                'content'           => $sanitizedContent,
                'category_id'       => $categoryId,
                'author_name'       => $authorName,
                'cover_type'        => $coverType,
                'cover_image'       => $coverImage,
                'cover_alt'         => $coverAlt,
                'video_url'         => $videoUrl,
                'status'            => $status,
                'is_pick'           => $isPick,
                'published_at'      => $publishedAt,
                'updated_at'        => $now,
                'read_time_minutes' => $readTimeMinutes,
                'meta_title'        => $metaTitle !== '' ? $metaTitle : null,
                'meta_description'  => $metaDesc !== '' ? $metaDesc : null,
            ]);

            // Sync tags
            if (isset($data['tags'])) {
                TagModel::syncTagsForPost($id, $data['tags']);
            }

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Delete a single post by ID and remove its cover media files.
     */
    public static function delete(int $id): bool
    {
        $existing = self::getById($id);
        if ($existing && !empty($existing['cover_image'])) {
            ImageUploader::deleteVariants($existing['cover_image']);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Delete multiple posts by IDs in bulk and remove their cover media files.
     *
     * @param int[] $ids
     * @return int Count of deleted posts
     */
    public static function bulkDelete(array $ids): int
    {
        $cleanIds = array_values(array_filter(array_map('intval', $ids), fn($i) => $i > 0));
        if (empty($cleanIds)) {
            return 0;
        }

        $pdo = Database::getConnection();
        $inPlaceholders = implode(',', array_fill(0, count($cleanIds), '?'));

        // Cleanup cover images of posts being deleted
        $fetchStmt = $pdo->prepare("SELECT cover_image FROM posts WHERE id IN ({$inPlaceholders})");
        $fetchStmt->execute($cleanIds);
        $rows = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            if (!empty($row['cover_image'])) {
                ImageUploader::deleteVariants($row['cover_image']);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM posts WHERE id IN ({$inPlaceholders})");
        $stmt->execute($cleanIds);

        return $stmt->rowCount();
    }

    /**
     * Retrieve latest published posts marked as editor's picks.
     *
     * @param int $limit
     * @param array<int> $excludeIds
     * @return array
     */
    public static function getEditorsPicks(int $limit = 3, array $excludeIds = []): array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $excludeSql = '';
        if (!empty($excludeIds)) {
            $clean = implode(',', array_map('intval', $excludeIds));
            $excludeSql = " AND p.id NOT IN ({$clean})";
        }
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_pick = 1 AND {$sqlPub}{$excludeSql}
            ORDER BY COALESCE(p.published_at, p.created_at) DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve slides for the home hero slider:
     * Up to 5 posts with is_pick = 1 (newest first).
     * If fewer than 2 picks (after excluding specified IDs), fall back to the next newest published posts.
     *
     * @param int $limit
     * @param array|int|null $exclude
     * @return array
     */
    public static function getSliderPosts(int $limit = 5, array|int|null $exclude = null): array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $excludeClause = '';
        if (is_array($exclude) && !empty($exclude)) {
            $clean = implode(',', array_map('intval', $exclude));
            $excludeClause = " AND p.id NOT IN ({$clean})";
        } elseif (is_numeric($exclude) && (int) $exclude > 0) {
            $excludeClause = " AND p.id != " . (int) $exclude;
        }

        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_pick = 1 AND {$sqlPub}{$excludeClause}
            ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        $picks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($picks) >= 2) {
            return $picks;
        }

        return self::getLatestPublished($limit, $exclude);
    }

    /**
     * Retrieve up to 3 most viewed posts in the last 30 days, excluding specified post IDs.
     *
     * @param int $limit
     * @param array<int> $excludeIds
     * @return array
     */
    public static function getMostRead(int $limit = 3, array $excludeIds = []): array
    {
        $pdo = Database::getConnection();
        $sqlPub = self::sqlPublished('p');
        $excludeSql = '';
        if (!empty($excludeIds)) {
            $clean = implode(',', array_map('intval', $excludeIds));
            $excludeSql = " AND p.id NOT IN ({$clean})";
        }
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                       COALESCE(recent.recent_count, 0) AS recent_views
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN (
                    SELECT post_id, COUNT(*) AS recent_count
                    FROM post_views
                    WHERE viewed_on >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY post_id
                ) recent ON p.id = recent.post_id
                WHERE {$sqlPub}{$excludeSql}
                ORDER BY recent_views DESC, p.views DESC, COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

