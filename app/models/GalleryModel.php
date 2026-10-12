<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\ImageUploader;
use InvalidArgumentException;
use PDO;

/**
 * NatConnect Foundation — Photo Gallery Model (Phase 4C)
 */
class GalleryModel
{
    /**
     * Validate photo alternative text (accessibility + SEO).
     * Rejects empty alt text or alt text resembling filenames.
     *
     * @throws InvalidArgumentException
     */
    public static function validateAltText(?string $alt): string
    {
        $clean = trim((string) $alt);
        if ($clean === '') {
            throw new InvalidArgumentException("Please describe what's in the photo.");
        }

        if (preg_match('/\.(jpe?g|png|webp|gif|svg|avif|bmp|tiff)$/i', $clean) || (!str_contains($clean, ' ') && mb_strlen($clean) < 10)) {
            throw new InvalidArgumentException("Please describe what's in the photo instead of using a filename.");
        }

        return $clean;
    }

    /**
     * Create a new gallery item.
     *
     * @param array{
     *   image_path: string,
     *   alt_text: string,
     *   caption?: ?string,
     *   sort_order?: int
     * } $data
     * @return int Inserted ID
     */
    public static function create(array $data): int
    {
        $imagePath = trim((string) ($data['image_path'] ?? ''));
        if ($imagePath === '') {
            throw new InvalidArgumentException('Image path is required.');
        }

        $altText = self::validateAltText($data['alt_text'] ?? null);
        $caption = isset($data['caption']) && trim((string) $data['caption']) !== '' ? trim((string) $data['caption']) : null;
        $sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO gallery_items (image_path, alt_text, caption, sort_order)
             VALUES (:image_path, :alt_text, :caption, :sort_order)"
        );
        $stmt->execute([
            'image_path' => $imagePath,
            'alt_text'   => $altText,
            'caption'    => $caption,
            'sort_order' => $sortOrder,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Update an existing gallery item's metadata (alt text and caption).
     */
    public static function update(int $id, array $data): bool
    {
        $altText = self::validateAltText($data['alt_text'] ?? null);
        $caption = isset($data['caption']) && trim((string) $data['caption']) !== '' ? trim((string) $data['caption']) : null;

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "UPDATE gallery_items 
             SET alt_text = :alt_text, caption = :caption 
             WHERE id = :id"
        );
        return $stmt->execute([
            'id'       => $id,
            'alt_text' => $altText,
            'caption'  => $caption,
        ]);
    }

    /**
     * Delete a gallery item and remove all WebP image variants from disk.
     */
    public static function delete(int $id): bool
    {
        $item = self::getById($id);
        if (!$item) {
            return false;
        }

        // Delete WebP variants (1600, 800, 400) from disk
        if (!empty($item['image_path'])) {
            ImageUploader::deleteVariants($item['image_path']);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM gallery_items WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get a single gallery item by ID.
     */
    public static function getById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM gallery_items WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retrieve all gallery items in presentation order (sort_order ASC, id DESC).
     */
    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM gallery_items ORDER BY sort_order ASC, id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve paginated gallery items for public /gallery archive.
     */
    public static function getPaginated(int $page = 1, int $perPage = 24): array
    {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $totalStmt = $pdo->query("SELECT COUNT(*) FROM gallery_items");
        $total = (int) $totalStmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT * FROM gallery_items 
             ORDER BY sort_order ASC, id DESC 
             LIMIT :limit OFFSET :offset"
        );
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
     * Reorder an item with adjacent item swap ('up' or 'down').
     * Normalizes sort_order sequentially (0, 1, 2, ...) to ensure deterministic positioning.
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
            $stmt = $pdo->prepare("UPDATE gallery_items SET sort_order = :sort WHERE id = :id");
            foreach ($items as $newSort => $item) {
                $stmt->execute([
                    'sort' => $newSort,
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

    /**
     * Retrieve the latest gallery items.
     *
     * @param int $limit
     * @return array
     */
    public static function getLatest(int $limit = 3): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM gallery_items ORDER BY sort_order ASC, id DESC LIMIT :limit");
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
