<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sanitizer;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * NatConnect Foundation — Editable Pages Model
 */
class PageModel
{
    /**
     * Slugs reserved by core application routes and assets.
     */
    public const RESERVED_SLUGS = [
        'admin',
        'api',
        'post',
        'category',
        'tag',
        'search',
        'gallery',
        'donate',
        'contact',
        'uploads',
        'assets',
        'setup',
        'index',
    ];

    /**
     * Get all pages for admin listing.
     */
    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM pages ORDER BY is_system DESC, nav_order ASC, title ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get published pages configured to appear in the header navigation menu.
     */
    public static function getNavPages(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM pages WHERE status = 'published' AND show_in_nav = 1 ORDER BY nav_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find page by ID.
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find page by slug (draft or published).
     */
    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find published page by slug.
     */
    public static function findBySlugPublished(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = :slug AND status = 'published' LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Check if slug is unique across pages table.
     */
    public static function isSlugUnique(string $slug, ?int $excludeId = null): bool
    {
        $pdo = Database::getConnection();
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT 1 FROM pages WHERE slug = :slug AND id != :id LIMIT 1');
            $stmt->execute(['slug' => $slug, 'id' => $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT 1 FROM pages WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $slug]);
        }
        return !$stmt->fetch();
    }

    /**
     * Create a new page.
     */
    public static function create(array $data): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Page title is required.');
        }

        $rawSlug = trim((string) ($data['slug'] ?? ''));
        $slug = $rawSlug !== '' ? slugify($rawSlug) : slugify($title);
        if ($slug === '') {
            throw new InvalidArgumentException('Page slug is required.');
        }

        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            throw new InvalidArgumentException("The slug '{$slug}' is reserved by the system. Please choose another.");
        }

        if (!self::isSlugUnique($slug)) {
            throw new InvalidArgumentException("The slug '{$slug}' is already in use by another page.");
        }

        $content = Sanitizer::clean((string) ($data['content'] ?? ''));
        $status = ($data['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $showInNav = !empty($data['show_in_nav']) ? 1 : 0;
        $navLabel = !empty($data['nav_label']) ? mb_substr(trim((string) $data['nav_label']), 0, 40) : null;
        $navOrder = (int) ($data['nav_order'] ?? 0);
        $metaTitle = !empty($data['meta_title']) ? mb_substr(trim((string) $data['meta_title']), 0, 255) : null;
        $metaDescription = !empty($data['meta_description']) ? trim((string) $data['meta_description']) : null;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO pages (title, slug, content, meta_title, meta_description, status, show_in_nav, nav_label, nav_order, is_system, created_at, updated_at)
             VALUES (:title, :slug, :content, :meta_title, :meta_description, :status, :show_in_nav, :nav_label, :nav_order, 0, NOW(), NOW())'
        );
        $stmt->execute([
            'title'            => $title,
            'slug'             => $slug,
            'content'          => $content,
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDescription,
            'status'           => $status,
            'show_in_nav'      => $showInNav,
            'nav_label'        => $navLabel,
            'nav_order'        => $navOrder,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing page.
     */
    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Page not found.');
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Page title is required.');
        }

        $rawSlug = trim((string) ($data['slug'] ?? ''));
        $slug = $rawSlug !== '' ? slugify($rawSlug) : slugify($title);
        if ($slug === '') {
            throw new InvalidArgumentException('Page slug is required.');
        }

        // System pages: slug cannot be changed
        if (!empty($existing['is_system'])) {
            if ($slug !== $existing['slug']) {
                throw new InvalidArgumentException('The URL slug of a system page cannot be modified.');
            }
        } else {
            if (in_array($slug, self::RESERVED_SLUGS, true)) {
                throw new InvalidArgumentException("The slug '{$slug}' is reserved by the system. Please choose another.");
            }
            if (!self::isSlugUnique($slug, $id)) {
                throw new InvalidArgumentException("The slug '{$slug}' is already in use by another page.");
            }
        }

        $status = ($data['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        // Privacy Policy cannot be unpublished
        if ($existing['slug'] === 'privacy' && $status !== 'published') {
            throw new InvalidArgumentException('The Privacy Policy page cannot be unpublished.');
        }

        $content = Sanitizer::clean((string) ($data['content'] ?? ''));
        $showInNav = !empty($data['show_in_nav']) ? 1 : 0;
        $navLabel = !empty($data['nav_label']) ? mb_substr(trim((string) $data['nav_label']), 0, 40) : null;
        $navOrder = (int) ($data['nav_order'] ?? 0);
        $metaTitle = !empty($data['meta_title']) ? mb_substr(trim((string) $data['meta_title']), 0, 255) : null;
        $metaDescription = !empty($data['meta_description']) ? trim((string) $data['meta_description']) : null;

        // Changing a published page slug creates a 301 redirect
        if ($existing['status'] === 'published' && $existing['slug'] !== $slug) {
            RedirectModel::createOrUpdate('/' . $existing['slug'], '/' . $slug);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE pages
             SET title = :title,
                 slug = :slug,
                 content = :content,
                 meta_title = :meta_title,
                 meta_description = :meta_description,
                 status = :status,
                 show_in_nav = :show_in_nav,
                 nav_label = :nav_label,
                 nav_order = :nav_order,
                 updated_at = NOW()
             WHERE id = :id'
        );
        return $stmt->execute([
            'id'               => $id,
            'title'            => $title,
            'slug'             => $slug,
            'content'          => $content,
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDescription,
            'status'           => $status,
            'show_in_nav'      => $showInNav,
            'nav_label'        => $navLabel,
            'nav_order'        => $navOrder,
        ]);
    }

    /**
     * Delete a page.
     */
    public static function delete(int $id): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            return false;
        }

        if (!empty($existing['is_system'])) {
            throw new RuntimeException('System pages cannot be deleted.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM pages WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
