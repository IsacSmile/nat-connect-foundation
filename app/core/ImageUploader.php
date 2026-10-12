<?php
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use RuntimeException;

/**
 * NatConnect Foundation — Secure Image Uploader
 *
 * Implements strict defense-in-depth image upload security:
 * - Real content inspection via finfo MIME detection (accepts ONLY image/jpeg, image/png, image/webp; rejects SVG & GIF).
 * - Maximum file size 8 MB and maximum dimension limit of 25 megapixels.
 * - Validation with getimagesize().
 * - Re-encoding with GD (stripping EXIF metadata including GPS coordinates).
 * - Generating WebP variants (quality ~80) in 3 widths: 1600, 800, 400 without upscaling (falls back to JPEG if no WebP support).
 * - Cryptographically random filenames (bin2hex(random_bytes(16))) stored in uploads/YYYY/MM/.
 * - Rejection of .htaccess or any non-image filename/extension.
 * - Directory listing protection: automatically creates empty index.html in every new uploads/YYYY/ and uploads/YYYY/MM/ folder.
 */
class ImageUploader
{
    public const MAX_FILE_SIZE = 8 * 1024 * 1024; // 8 Megabytes
    public const MAX_PIXELS = 25_000_000;         // 25 Megapixels
    public const WEBP_QUALITY = 80;
    public const JPEG_QUALITY = 85;

    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    public const TARGET_WIDTHS = [1600, 800, 400];

    /**
     * Check GD library availability and supported formats.
     *
     * @return array{
     *   gd_loaded: bool,
     *   version: ?string,
     *   webp_support: bool,
     *   jpeg_support: bool,
     *   png_support: bool
     * }
     */
    public static function getGdSupport(): array
    {
        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            return [
                'gd_loaded'    => false,
                'version'      => null,
                'webp_support' => false,
                'jpeg_support' => false,
                'png_support'  => false,
            ];
        }

        $info = gd_info();

        return [
            'gd_loaded'    => true,
            'version'      => $info['GD Version'] ?? null,
            'webp_support' => !empty($info['WebP Support']),
            'jpeg_support' => !empty($info['JPEG Support']),
            'png_support'  => !empty($info['PNG Support']),
        ];
    }

    /**
     * Upload and re-encode an image file from $_FILES array.
     *
     * @param array $file $_FILES['input_name'] entry
     * @return array{
     *   hash: string,
     *   extension: string,
     *   location: string,
     *   url_1600: string,
     *   url_800: string,
     *   url_400: string,
     *   width: int,
     *   height: int,
     *   variants: array<int, string>
     * }
     */
    public static function upload(array $file): array
    {
        $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(self::getUploadErrorMessage($errorCode));
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $origName = (string) ($file['name'] ?? '');
        $fileSize = (int) ($file['size'] ?? 0);

        return self::processFile($tmpPath, $origName, $fileSize);
    }

    /**
     * Process, validate, re-encode, and save an image from a local path.
     * Allows testing via synthetic files in test scripts.
     */
    public static function processFile(string $tmpPath, string $origName, int $fileSize): array
    {
        if ($tmpPath === '' || !file_exists($tmpPath)) {
            throw new InvalidArgumentException('Uploaded file was not found.');
        }

        // 1. File size check (Max 8 MB)
        if ($fileSize <= 0) {
            $fileSize = (int) filesize($tmpPath);
        }

        if ($fileSize <= 0) {
            throw new InvalidArgumentException('Uploaded image file is empty.');
        }

        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException('Image file size exceeds the 8 MB maximum limit.');
        }

        // 2. Reject .htaccess or non-image extensions
        $cleanOrigName = strtolower(basename($origName));
        if ($cleanOrigName !== '') {
            if (str_contains($cleanOrigName, '.htaccess') || str_starts_with($cleanOrigName, '.')) {
                throw new InvalidArgumentException('Invalid file name.');
            }

            $ext = pathinfo($cleanOrigName, PATHINFO_EXTENSION);
            if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                throw new InvalidArgumentException('Only JPG, PNG, and WebP image formats are permitted.');
            }
        }

        // 3. Detect actual MIME type using finfo on byte contents (not extension or client MIME)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            throw new RuntimeException('Failed to inspect file MIME type.');
        }

        $detectedMime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException(
                'Invalid file format detected (' . htmlspecialchars((string) $detectedMime) . '). Only JPEG, PNG, and WebP images are allowed (SVG and GIF are rejected).'
            );
        }

        // 4. Verify with getimagesize()
        $imageSize = @getimagesize($tmpPath);
        if ($imageSize === false) {
            throw new InvalidArgumentException('File is not a valid or readable image.');
        }

        $origWidth  = (int) ($imageSize[0] ?? 0);
        $origHeight = (int) ($imageSize[1] ?? 0);
        $imageType  = (int) ($imageSize[2] ?? 0);

        if ($origWidth <= 0 || $origHeight <= 0) {
            throw new InvalidArgumentException('Invalid image dimensions.');
        }

        // 5. Reject images over 25 megapixels (prevents memory exhaustion)
        $megapixels = $origWidth * $origHeight;
        if ($megapixels > self::MAX_PIXELS) {
            throw new InvalidArgumentException('This photo is too large. Please resize it below 25 megapixels.');
        }

        // 6. Check GD support
        $gdSupport = self::getGdSupport();
        if (!$gdSupport['gd_loaded']) {
            throw new RuntimeException('PHP GD library is required on the server to process and re-encode images.');
        }

        // 7. Decode source image into GD
        $srcImage = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($tmpPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($tmpPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpPath) : false,
            default        => false,
        };

        if (!$srcImage) {
            throw new InvalidArgumentException('Failed to decode image data with GD. The image file may be corrupt.');
        }

        // Determine output format: WebP preferred, fallback to JPEG if GD lacks WebP
        $useWebp = $gdSupport['webp_support'] && function_exists('imagewebp');
        $outputExt = $useWebp ? 'webp' : 'jpg';

        // 8. Prepare target directory uploads/YYYY/MM/ and ensure empty index.html
        $year = date('Y');
        $month = date('m');

        $uploadsBase = defined('ROOT_PATH') ? ROOT_PATH . '/public_html/uploads' : dirname(__DIR__, 2) . '/public_html/uploads';
        $yearDir = "{$uploadsBase}/{$year}";
        $monthDir = "{$yearDir}/{$month}";

        self::ensureDirectoryProtected($uploadsBase);
        self::ensureDirectoryProtected($yearDir);
        self::ensureDirectoryProtected($monthDir);

        // 9. Generate random filename hash
        $hash = bin2hex(random_bytes(16));

        // 10. Re-encode and save 3 responsive variants (1600, 800, 400) without upscaling
        $variants = [];

        foreach (self::TARGET_WIDTHS as $targetWidth) {
            // Never upscale: if image is smaller than target width, cap at original width
            $variantWidth = min($origWidth, $targetWidth);
            $variantHeight = (int) max(1, round(($origHeight * $variantWidth) / $origWidth));

            $canvas = imagecreatetruecolor($variantWidth, $variantHeight);
            if (!$canvas) {
                imagedestroy($srcImage);
                throw new RuntimeException('Failed to allocate image buffer.');
            }

            // Maintain transparency for PNG / WebP sources
            if ($useWebp || $imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
                imagefilledrectangle($canvas, 0, 0, $variantWidth, $variantHeight, $transparent);
            }

            imagecopyresampled(
                $canvas,
                $srcImage,
                0, 0, 0, 0,
                $variantWidth,
                $variantHeight,
                $origWidth,
                $origHeight
            );

            $variantFilename = "{$hash}-{$targetWidth}.{$outputExt}";
            $variantPath = "{$monthDir}/{$variantFilename}";

            if ($useWebp) {
                $saved = imagewebp($canvas, $variantPath, self::WEBP_QUALITY);
            } else {
                $saved = imagejpeg($canvas, $variantPath, self::JPEG_QUALITY);
            }

            imagedestroy($canvas);

            if (!$saved) {
                imagedestroy($srcImage);
                throw new RuntimeException("Failed to save re-encoded {$outputExt} image variant.");
            }

            $variants[$targetWidth] = "/uploads/{$year}/{$month}/{$variantFilename}";
        }

        imagedestroy($srcImage);

        $primaryUrl = $variants[1600];

        return [
            'hash'        => $hash,
            'extension'   => $outputExt,
            'location'    => $primaryUrl,
            'primary_url' => $primaryUrl,
            'url_1600'    => $variants[1600],
            'url_800'     => $variants[800],
            'url_400'     => $variants[400],
            'width'       => $origWidth,
            'height'      => $origHeight,
            'variants'    => $variants,
        ];
    }

    /**
     * Alias for variantUrl()
     */
    public static function getWebpUrl(?string $location, int $width = 800): string
    {
        return self::variantUrl($location, $width);
    }

    /**
     * Get the URL for a specific variant width (e.g. 400, 800, 1600).
     */
    public static function variantUrl(?string $location, int $width = 800): string
    {
        if (empty($location)) {
            return '';
        }

        $pattern = '#^/?uploads/(\d{4})/(\d{2})/([a-f0-9]{32})(?:-\d+)?\.(webp|jpg|jpeg|png)$#i';
        if (preg_match($pattern, trim($location), $matches)) {
            return "/uploads/{$matches[1]}/{$matches[2]}/{$matches[3]}-{$width}.{$matches[4]}";
        }

        return $location;
    }

    /**
     * Delete all responsive variants associated with a cover image path.
     *
     * @param string|null $imagePath e.g. "/uploads/2026/10/a1b2c3d4...-1600.webp"
     * @return int Number of variant files successfully removed
     */
    public static function deleteVariants(?string $imagePath): int
    {
        if (empty($imagePath)) {
            return 0;
        }

        // Parse path: /uploads/YYYY/MM/{hash}[-{width}].{ext}
        $pattern = '#^/?uploads/(\d{4})/(\d{2})/([a-f0-9]{32})(?:-\d+)?\.(webp|jpg|jpeg|png)$#i';
        if (!preg_match($pattern, trim($imagePath), $matches)) {
            return 0;
        }

        $year = $matches[1];
        $month = $matches[2];
        $hash = $matches[3];

        $uploadsBase = defined('ROOT_PATH') ? ROOT_PATH . '/public_html/uploads' : dirname(__DIR__, 2) . '/public_html/uploads';
        $dir = "{$uploadsBase}/{$year}/{$month}";

        if (!is_dir($dir)) {
            return 0;
        }

        // Match all files starting with the hash in this directory
        $deleted = 0;
        $files = glob("{$dir}/{$hash}*");
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file) && unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Build responsive image attributes (src, srcset, sizes, width, height)
     * for an uploaded image variant path.
     *
     * @param string|null $location e.g. "/uploads/2026/10/a1b2c3d4...-1600.webp"
     * @param string $sizes Responsive sizes attribute
     * @return array{src: string, srcset: string, sizes: string, width: int, height: int}|null
     */
    public static function getResponsiveAttributes(?string $location, string $sizes = '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw'): ?array
    {
        if (empty($location)) {
            return null;
        }

        $pattern = '#^/?uploads/(\d{4})/(\d{2})/([a-f0-9]{32})(?:-\d+)?\.(webp|jpg|jpeg|png)$#i';
        if (!preg_match($pattern, trim($location), $matches)) {
            return [
                'src'    => $location,
                'srcset' => $location,
                'sizes'  => $sizes,
                'width'  => 800,
                'height' => 450,
            ];
        }

        $year = $matches[1];
        $month = $matches[2];
        $hash = $matches[3];
        $ext = $matches[4];

        $url400  = "/uploads/{$year}/{$month}/{$hash}-400.{$ext}";
        $url800  = "/uploads/{$year}/{$month}/{$hash}-800.{$ext}";
        $url1600 = "/uploads/{$year}/{$month}/{$hash}-1600.{$ext}";

        return [
            'src'    => $url800,
            'srcset' => "{$url400} 400w, {$url800} 800w, {$url1600} 1600w",
            'sizes'  => $sizes,
            'width'  => 800,
            'height' => 450,
        ];
    }

    /**
     * Retrieve URL for a specific image variant width (400, 800, 1600).
     */
    public static function getVariantUrl(?string $location, int $width = 400): string
    {
        if (empty($location)) {
            return '';
        }
        $pattern = '#^/?uploads/(\d{4})/(\d{2})/([a-f0-9]{32})(?:-\d+)?\.(webp|jpg|jpeg|png)$#i';
        if (preg_match($pattern, trim($location), $matches)) {
            return "/uploads/{$matches[1]}/{$matches[2]}/{$matches[3]}-{$width}.{$matches[4]}";
        }
        return $location;
    }

    /**
     * Ensure directory exists and contains an empty index.html to prevent
     * directory listing on hosts where "Options -Indexes" is disabled.
     */
    public static function ensureDirectoryProtected(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $indexFile = rtrim($dir, '/') . '/index.html';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, '');
        }
    }

    /**
     * Format a PHP ini size string (e.g. 2M, 10M, 1G) into a human-readable MB string.
     */
    public static function formatIniSize(string $val): string
    {
        $val = trim($val);
        if ($val === '') {
            return '10 MB';
        }
        $last = strtolower($val[strlen($val) - 1]);
        $num = (float) $val;
        return match ($last) {
            'g' => ($num * 1024) . ' MB',
            'm' => $num . ' MB',
            'k' => round($num / 1024, 1) . ' MB',
            default => round($num / (1024 * 1024), 1) . ' MB',
        };
    }

    /**
     * Map PHP upload error code to human-readable message.
     */
    public static function getUploadErrorMessage(int $code): string
    {
        $limitStr = self::formatIniSize((string) ini_get('upload_max_filesize'));

        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => "This image is larger than the server allows ({$limitStr})",
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the form file size limit.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded. Please check your connection and try again.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Server error: failed to write image file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A server extension blocked the file upload.',
            default               => 'Unknown file upload error.',
        };
    }
}
