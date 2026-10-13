<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ImageUploader;
use Throwable;

/**
 * NatConnect Foundation — Admin Media Controller
 *
 * Handles image uploads from TinyMCE editor and Post Cover uploader:
 * - Requires active admin authentication + CSRF token (POST parameter or X-CSRF-Token header).
 * - Enforces MIME sniffing, size & resolution constraints, re-encoding, and WebP generation.
 * - Returns JSON response with `{"location": url}` as expected by TinyMCE.
 */
class MediaController
{
    /**
     * Upload an image file and return its processed URL.
     */
    public function upload(): void
    {
        Auth::requireAuth('/admin/media/upload');

        header('Content-Type: application/json; charset=UTF-8');

        // Detect when post_max_size is exceeded before CSRF verification
        if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            http_response_code(413);
            echo json_encode(['error' => 'Upload too large']);
            exit;
        }

        // Verify CSRF token from header X-CSRF-Token or POST body
        if (!Csrf::verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid or expired CSRF token. Please reload the page.']);
            exit;
        }

        // Locate uploaded file in $_FILES
        $file = $_FILES['file'] ?? $_FILES['image'] ?? $_FILES['upload'] ?? null;
        if (!$file && !empty($_FILES)) {
            $file = reset($_FILES);
        }

        if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            http_response_code(400);
            echo json_encode(['error' => 'No image file was provided for upload.']);
            exit;
        }

        try {
            $result = ImageUploader::upload($file);

            echo json_encode([
                'location' => $result['location'],
                'url'      => $result['location'],
                'url_1600' => $result['url_1600'],
                'url_800'  => $result['url_800'],
                'url_400'  => $result['url_400'],
                'width'    => $result['width'],
                'height'   => $result['height'],
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(422);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}
