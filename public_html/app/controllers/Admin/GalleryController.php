<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ImageUploader;
use App\Core\View;
use App\Models\GalleryModel;
use InvalidArgumentException;
use Throwable;

/**
 * NatConnect Foundation — Admin Gallery Controller (Phase 4C)
 */
class GalleryController
{
    /**
     * List and manage gallery items (/admin/gallery)
     */
    public function index(): void
    {
        Auth::requireAuth('/admin/gallery');

        $items = GalleryModel::getAll();

        View::render('admin/gallery', [
            'pageTitle' => 'Gallery — NatConnect Foundation',
            'activeNav' => 'gallery',
            'items'     => $items,
        ], 'layouts/admin');
    }

    /**
     * Handle single file upload in multi-file upload queue (POST /admin/gallery/upload)
     */
    public function upload(): void
    {
        Auth::requireAuth('/admin/gallery');

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
        if (!Csrf::verify($csrfToken)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid or expired session token.']);
            exit;
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No image file uploaded.']);
            exit;
        }

        try {
            $uploadResult = ImageUploader::upload($_FILES['file']);
            $primaryUrl = $uploadResult['primary_url'];

            // Alt text from payload (required, validated with standard alt text rules)
            $providedAlt = trim((string) ($_POST['alt_text'] ?? ''));
            $altText = GalleryModel::validateAltText($providedAlt);

            $caption = trim((string) ($_POST['caption'] ?? ''));

            $id = GalleryModel::create([
                'image_path' => $primaryUrl,
                'alt_text'   => $altText,
                'caption'    => $caption !== '' ? $caption : null,
                'sort_order' => 0,
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id'      => $id,
                'url'     => $primaryUrl,
                'thumb'   => ImageUploader::variantUrl($primaryUrl, 400),
                'alt'     => $providedAlt,
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Update photo metadata (alt text and caption) (POST /admin/gallery/{id}/edit)
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/gallery');

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (!Csrf::verify($csrfToken)) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid session token.']);
                exit;
            }
            $_SESSION['flash_error'] = 'Invalid session token.';
            header('Location: /admin/gallery');
            exit;
        }

        $altText = trim((string) ($_POST['alt_text'] ?? ''));
        $caption = trim((string) ($_POST['caption'] ?? ''));

        try {
            GalleryModel::update($id, [
                'alt_text' => $altText,
                'caption'  => $caption,
            ]);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            $_SESSION['flash_success'] = 'Photo updated successfully.';
        } catch (Throwable $e) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => $e->getMessage()]);
                exit;
            }
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: /admin/gallery');
        exit;
    }

    /**
     * Delete photo and its variants (POST /admin/gallery/{id}/delete)
     */
    public function delete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/gallery');

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (!Csrf::verify($csrfToken)) {
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid session token.']);
                exit;
            }
            $_SESSION['flash_error'] = 'Invalid session token.';
            header('Location: /admin/gallery');
            exit;
        }

        try {
            GalleryModel::delete($id);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            $_SESSION['flash_success'] = 'Photo deleted successfully.';
        } catch (Throwable $e) {
            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => $e->getMessage()]);
                exit;
            }
            $_SESSION['flash_error'] = 'Failed to delete photo: ' . $e->getMessage();
        }

        header('Location: /admin/gallery');
        exit;
    }

    /**
     * Reorder photo position up or down (POST /admin/gallery/{id}/move)
     */
    public function reorder(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/gallery');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Invalid session token.';
            header('Location: /admin/gallery');
            exit;
        }

        $direction = trim((string) ($_POST['direction'] ?? ''));
        GalleryModel::reorder($id, $direction);

        header('Location: /admin/gallery');
        exit;
    }
}
