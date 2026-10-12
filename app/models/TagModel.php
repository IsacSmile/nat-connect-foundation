<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\ImageUploader;
use App\Models\RedirectModel;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * NatConnect Foundation — Tag & Taxonomies Model
 */
class TagModel
{
    /**
     * Retrieve all tags with their associated post count and taxonomy metadata.
     *
     * @return array
     */
    public static function getAllWithCounts(): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                t.id, t.name, t.slug, t.description, t.image_path, t.image_alt,
                t.show_as_topic, t.show_as_cause, t.sort_order, t.created_at,
                COUNT(pt.post_id) AS post_count
            FROM tags t
            LEFT JOIN post_tags pt ON t.id = pt.tag_id
            GROUP BY 
                t.id, t.name, t.slug, t.description, t.image_path, t.image_alt,
                t.show_as_topic, t.show_as_cause, t.sort_order, t.created_at
            ORDER BY t.sort_order ASC, t.name ASC
        ";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a tag by ID.
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM tags WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retrieve active topic tags ordered for chips.
     */
    public static function getTopics(int $limit = 50): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT t.*, COUNT(pt.post_id) AS post_count
            FROM tags t
            LEFT JOIN post_tags pt ON t.id = pt.tag_id
            WHERE t.show_as_topic = 1
            GROUP BY t.id, t.name, t.slug, t.description, t.image_path, t.image_alt,
                     t.show_as_topic, t.show_as_cause, t.sort_order, t.created_at
            ORDER BY t.sort_order ASC, t.name ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve active cause tags.
     */
    public static function getCauses(int $limit = 20): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT t.*, COUNT(pt.post_id) AS post_count
            FROM tags t
            LEFT JOIN post_tags pt ON t.id = pt.tag_id
            WHERE t.show_as_cause = 1
            GROUP BY t.id, t.name, t.slug, t.description, t.image_path, t.image_alt,
                     t.show_as_topic, t.show_as_cause, t.sort_order, t.created_at
            ORDER BY t.sort_order ASC, t.name ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate tag data.
     * Enforces that causes MUST have an image and a description.
     *
     * @throws InvalidArgumentException
     */
    public static function validate(array $data, ?int $excludeId = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Tag name is required.');
        }

        $rawSlug = trim((string) ($data['slug'] ?? ''));
        $slug = slugify($rawSlug !== '' ? $rawSlug : $name);
        if ($slug === '') {
            throw new InvalidArgumentException('Tag slug is required.');
        }

        // Check slug uniqueness
        $pdo = Database::getConnection();
        if ($excludeId !== null && $excludeId > 0) {
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = :slug AND id != :id LIMIT 1");
            $stmt->execute(['slug' => $slug, 'id' => $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = :slug LIMIT 1");
            $stmt->execute(['slug' => $slug]);
        }
        if ($stmt->fetchColumn()) {
            throw new InvalidArgumentException("A tag with slug '{$slug}' already exists.");
        }

        $rawDesc = trim((string) ($data['description'] ?? ''));
        $description = $rawDesc !== '' ? $rawDesc : null;

        $rawImagePath = trim((string) ($data['image_path'] ?? ''));
        $imagePath = $rawImagePath !== '' ? $rawImagePath : null;

        $rawImageAlt = trim((string) ($data['image_alt'] ?? ''));
        $imageAlt = $rawImageAlt !== '' ? $rawImageAlt : null;

        $showAsTopic = !empty($data['show_as_topic']) ? 1 : 0;
        $showAsCause = !empty($data['show_as_cause']) ? 1 : 0;
        $sortOrder = (int) ($data['sort_order'] ?? 0);

        // Validation rule: A cause must have an image and a description
        if ($showAsCause === 1) {
            if ($description === null || $description === '') {
                throw new InvalidArgumentException('A cause must have a description.');
            }
            if ($imagePath === null || $imagePath === '') {
                throw new InvalidArgumentException('A cause must have an image.');
            }
            if ($imageAlt === null || $imageAlt === '') {
                throw new InvalidArgumentException('Alternative text (alt text) is required for the cause image.');
            }
        }

        // If an image is provided (for cause or topic), alt text is required and cannot be a filename
        if ($imagePath !== null) {
            if ($imageAlt === null || $imageAlt === '') {
                throw new InvalidArgumentException('Alternative text (alt text) is required for the image.');
            }
            if (preg_match('/\.(jpe?g|png|webp|gif|svg|avif|bmp|tiff)$/i', $imageAlt) || (!str_contains($imageAlt, ' ') && mb_strlen($imageAlt) < 10)) {
                throw new InvalidArgumentException("Please describe what's in the photo instead of using a filename.");
            }
        }

        return [
            'name'          => $name,
            'slug'          => $slug,
            'description'   => $description,
            'image_path'    => $imagePath,
            'image_alt'     => $imageAlt,
            'show_as_topic' => $showAsTopic,
            'show_as_cause' => $showAsCause,
            'sort_order'    => $sortOrder,
        ];
    }

    /**
     * Create a new tag.
     */
    public static function create(array $data): int
    {
        $validated = self::validate($data);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO tags (name, slug, description, image_path, image_alt, show_as_topic, show_as_cause, sort_order)
            VALUES (:name, :slug, :description, :image_path, :image_alt, :show_as_topic, :show_as_cause, :sort_order)
        ");
        $stmt->execute($validated);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing tag.
     */
    public static function update(int $id, array $data): bool
    {
        $existing = self::getById($id);
        if ($existing === null) {
            throw new RuntimeException("Tag #{$id} not found.");
        }

        $validated = self::validate($data, $id);

        // If slug changed, record a 301 redirect
        if ($existing['slug'] !== $validated['slug']) {
            RedirectModel::createOrUpdate('/tag/' . $existing['slug'], '/tag/' . $validated['slug']);
        }

        // If image replaced or removed, cleanup old variants
        if (!empty($existing['image_path']) && $existing['image_path'] !== $validated['image_path']) {
            try {
                ImageUploader::deleteVariants($existing['image_path']);
            } catch (\Throwable) {
                // Ignore variant deletion failure
            }
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE tags SET
                name = :name,
                slug = :slug,
                description = :description,
                image_path = :image_path,
                image_alt = :image_alt,
                show_as_topic = :show_as_topic,
                show_as_cause = :show_as_cause,
                sort_order = :sort_order
            WHERE id = :id
        ");
        $validated['id'] = $id;
        return $stmt->execute($validated);
    }

    /**
     * Delete a tag and remove post association links (removes tag links only, never posts).
     */
    public static function delete(int $id): bool
    {
        $existing = self::getById($id);
        if ($existing === null) {
            return false;
        }

        $pdo = Database::getConnection();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            // Remove tag associations only, never posts
            $delLinks = $pdo->prepare("DELETE FROM post_tags WHERE tag_id = :id");
            $delLinks->execute(['id' => $id]);

            $delTag = $pdo->prepare("DELETE FROM tags WHERE id = :id");
            $delTag->execute(['id' => $id]);

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        // Clean up image variants if any
        if (!empty($existing['image_path'])) {
            try {
                ImageUploader::deleteVariants($existing['image_path']);
            } catch (\Throwable) {
                // Ignore variant deletion failure
            }
        }

        return true;
    }

    /**
     * Get all tag names as a simple list for editor autocomplete/suggestions.
     *
     * @return string[]
     */
    public static function getSuggestions(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT name FROM tags ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get a tag by slug.
     */
    public static function getBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM tags WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get all tags associated with a specific post.
     *
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    public static function getTagsForPost(int $postId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT t.id, t.name, t.slug
             FROM tags t
             INNER JOIN post_tags pt ON t.id = pt.tag_id
             WHERE pt.post_id = :post_id
             ORDER BY t.name ASC"
        );
        $stmt->execute(['post_id' => $postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Synchronize tags for a post from a comma-separated or array list of tag names.
     * Creates any missing tags dynamically with unique slugs.
     *
     * @param int $postId
     * @param string[]|string $tags
     */
    public static function syncTagsForPost(int $postId, array|string $tags): void
    {
        if (is_string($tags)) {
            $tagList = array_map('trim', explode(',', $tags));
        } else {
            $tagList = array_map('trim', $tags);
        }

        // Filter out empty strings and deduplicate case-insensitively
        $cleanTags = [];
        foreach ($tagList as $tag) {
            $tag = trim($tag);
            if ($tag !== '') {
                $lower = strtolower($tag);
                if (!isset($cleanTags[$lower])) {
                    $cleanTags[$lower] = $tag;
                }
            }
        }

        $pdo = Database::getConnection();
        $tagIds = [];

        foreach ($cleanTags as $tagName) {
            // Find existing tag by name or slug
            $slug = slugify($tagName);
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = :name OR slug = :slug LIMIT 1");
            $stmt->execute(['name' => $tagName, 'slug' => $slug]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                $tagIds[] = (int) $existingId;
            } else {
                // Generate a unique slug if needed
                $baseSlug = $slug !== '' ? $slug : 'tag';
                $uniqueSlug = $baseSlug;
                $counter = 1;
                while (true) {
                    $check = $pdo->prepare("SELECT 1 FROM tags WHERE slug = :s LIMIT 1");
                    $check->execute(['s' => $uniqueSlug]);
                    if (!$check->fetchColumn()) {
                        break;
                    }
                    $counter++;
                    $uniqueSlug = "{$baseSlug}-{$counter}";
                }

                $insert = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
                $insert->execute(['name' => $tagName, 'slug' => $uniqueSlug]);
                $tagIds[] = (int) $pdo->lastInsertId();
            }
        }

        // Sync pivot table
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $del = $pdo->prepare("DELETE FROM post_tags WHERE post_id = :post_id");
            $del->execute(['post_id' => $postId]);

            if (!empty($tagIds)) {
                $ins = $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)");
                foreach ($tagIds as $tagId) {
                    $ins->execute(['post_id' => $postId, 'tag_id' => $tagId]);
                }
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
