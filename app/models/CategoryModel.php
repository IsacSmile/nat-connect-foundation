<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * NatConnect Foundation — Category Data Model
 *
 * Handles taxonomy records, post association counts, slug validation,
 * and safe reassignment upon category deletion.
 */
class CategoryModel
{
    /**
     * Retrieve all categories with their associated post count.
     *
     * @return array<int, array{id: int, name: string, slug: string, description: ?string, created_at: string, post_count: int}>
     */
    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT c.id, c.name, c.slug, c.description, c.sort_order, c.created_at, COUNT(p.id) AS post_count
                FROM categories c
                LEFT JOIN posts p ON c.id = p.category_id
                GROUP BY c.id, c.name, c.slug, c.description, c.sort_order, c.created_at
                ORDER BY c.sort_order ASC, c.name ASC";

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a category by its primary key ID.
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Find a category by slug.
     */
    public static function getBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Check if a slug is already taken (optionally excluding a specific category ID).
     */
    public static function isSlugTaken(string $slug, ?int $excludeId = null): bool
    {
        $pdo = Database::getConnection();
        if ($excludeId !== null) {
            $stmt = $pdo->prepare("SELECT 1 FROM categories WHERE slug = :slug AND id != :exclude_id LIMIT 1");
            $stmt->execute(['slug' => $slug, 'exclude_id' => $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT 1 FROM categories WHERE slug = :slug LIMIT 1");
            $stmt->execute(['slug' => $slug]);
        }

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get the count of posts currently assigned to a category.
     */
    public static function getPostCount(int $id): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category_id = :id");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Validate slug syntax (lowercase alphanumeric and hyphens only).
     */
    public static function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * Create a new category.
     */
    public static function create(string $name, string $slug, ?string $description = null): int
    {
        $name = trim($name);
        $slug = strtolower(trim($slug));

        if ($name === '') {
            throw new InvalidArgumentException('Category name cannot be empty.');
        }

        if (!self::isValidSlug($slug)) {
            throw new InvalidArgumentException('Slug must be lowercase alphanumeric and hyphens only.');
        }

        if (self::isSlugTaken($slug)) {
            throw new InvalidArgumentException("Category slug '{$slug}' is already in use.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)");
        $stmt->execute([
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description !== null && trim($description) !== '' ? trim($description) : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing category.
     */
    public static function update(int $id, string $name, string $slug, ?string $description = null): bool
    {
        $name = trim($name);
        $slug = strtolower(trim($slug));

        if ($name === '') {
            throw new InvalidArgumentException('Category name cannot be empty.');
        }

        if (!self::isValidSlug($slug)) {
            throw new InvalidArgumentException('Slug must be lowercase alphanumeric and hyphens only.');
        }

        if (self::isSlugTaken($slug, $id)) {
            throw new InvalidArgumentException("Category slug '{$slug}' is already in use.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE categories SET name = :name, slug = :slug, description = :description WHERE id = :id"
        );

        return $stmt->execute([
            'id'          => $id,
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description !== null && trim($description) !== '' ? trim($description) : null,
        ]);
    }

    /**
     * Delete a category. If it has posts, requires specifying a valid destination category.
     */
    public static function delete(int $id, ?int $moveToCategoryId = null): bool
    {
        $postCount = self::getPostCount($id);
        $pdo = Database::getConnection();

        if ($postCount > 0) {
            if ($moveToCategoryId === null || $moveToCategoryId === $id) {
                throw new RuntimeException("Cannot delete category with {$postCount} posts without choosing a different category to move them to.");
            }

            // Verify destination category exists
            $dest = self::getById($moveToCategoryId);
            if ($dest === null) {
                throw new RuntimeException("Target category for moving posts does not exist.");
            }

            $pdo->beginTransaction();
            try {
                // Reassign posts
                $reassignStmt = $pdo->prepare("UPDATE posts SET category_id = :dest_id WHERE category_id = :src_id");
                $reassignStmt->execute([
                    'dest_id' => $moveToCategoryId,
                    'src_id'  => $id,
                ]);

                // Delete category
                $delStmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
                $delStmt->execute(['id' => $id]);

                $pdo->commit();
                return true;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }

        // Zero posts: delete directly
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Reorder a category with adjacent category swap ('up' or 'down').
     * Normalizes sort_order sequentially (1, 2, 3, ...) to ensure deterministic positioning.
     */
    public static function reorder(int $id, string $direction): bool
    {
        if ($direction !== 'up' && $direction !== 'down') {
            return false;
        }

        $items = self::getAll();
        $count = count($items);
        if ($count < 2) {
            return false;
        }

        $currentIndex = null;
        foreach ($items as $idx => $item) {
            if ((int) $item['id'] === $id) {
                $currentIndex = $idx;
                break;
            }
        }

        if ($currentIndex === null) {
            return false;
        }

        $swapIndex = ($direction === 'up') ? $currentIndex - 1 : $currentIndex + 1;
        if ($swapIndex < 0 || $swapIndex >= $count) {
            return false; // Already at topmost or bottommost position
        }

        // Swap positions in list
        $temp = $items[$currentIndex];
        $items[$currentIndex] = $items[$swapIndex];
        $items[$swapIndex] = $temp;

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE categories SET sort_order = :sort WHERE id = :id");
            foreach ($items as $idx => $item) {
                $stmt->execute([
                    'sort' => $idx + 1,
                    'id'   => (int) $item['id'],
                ]);
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
}

