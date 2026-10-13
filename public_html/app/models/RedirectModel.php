<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * NatConnect Foundation — 301 Redirects Model
 *
 * Tracks legacy path mappings and automatically preserves SEO backlinks
 * when published post slugs change.
 */
class RedirectModel
{
    /**
     * Record a 301 redirect mapping from old path to new path.
     */
    public static function createOrUpdate(string $oldPath, string $newPath): void
    {
        $oldPath = '/' . trim($oldPath, '/');
        $newPath = '/' . trim($newPath, '/');

        if ($oldPath === $newPath || $oldPath === '/') {
            return;
        }

        $pdo = Database::getConnection();

        // 1. Delete any redirect whose old_path equals the post's current path (prevents loops)
        $delStmt = $pdo->prepare("DELETE FROM redirects WHERE old_path = :new_path");
        $delStmt->execute(['new_path' => $newPath]);

        // 2. Flatten redirect chains: update any existing redirects whose new_path = old to point to new
        $updateStmt = $pdo->prepare("UPDATE redirects SET new_path = :new_path WHERE new_path = :old_path");
        $updateStmt->execute([
            'new_path' => $newPath,
            'old_path' => $oldPath,
        ]);

        // 3. Insert or update the redirect mapping old -> new
        $stmt = $pdo->prepare(
            "INSERT INTO redirects (old_path, new_path)
             VALUES (:old_path, :new_path)
             ON DUPLICATE KEY UPDATE new_path = :new_path_update"
        );
        $stmt->execute([
            'old_path'        => $oldPath,
            'new_path'        => $newPath,
            'new_path_update' => $newPath,
        ]);
    }

    /**
     * Look up a 301 redirect target by its old request path.
     */
    public static function getTarget(string $oldPath): ?string
    {
        $oldPath = '/' . trim($oldPath, '/');
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT new_path FROM redirects WHERE old_path = :old_path LIMIT 1");
        $stmt->execute(['old_path' => $oldPath]);
        $target = $stmt->fetchColumn();
        return $target ? (string) $target : null;
    }
}

