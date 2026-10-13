<?php
declare(strict_types=1);

/**
 * Shared helpers for the content pack builders in bin/ (CLI only, never deployed).
 *
 * The including script must define ROOT_PATH (where ImageUploader writes, normally a
 * staging folder under build/), PUBLIC_PATH and APP_PATH before requiring this file.
 */

require_once PUBLIC_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Sanitizer.php';
require_once APP_PATH . '/core/ImageUploader.php';
date_default_timezone_set('Asia/Kolkata');

/** MySQL string literal (utf8mb4-safe). */
function q(?string $v): string
{
    if ($v === null) {
        return 'NULL';
    }
    return "'" . strtr($v, [
        "\\"   => "\\\\",
        "\0"   => "\\0",
        "\n"   => "\\n",
        "\r"   => "\\r",
        "'"    => "\\'",
        "\x1a" => "\\Z",
    ]) . "'";
}

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

/**
 * Run an image through the same pipeline as the admin uploader (on a copy).
 * Returns the primary location, e.g. /uploads/2026/10/<hash>-1600.webp.
 */
function packProcessImage(string $path): string
{
    if (!is_file($path)) {
        throw new RuntimeException("Image not found: {$path}");
    }
    $tmp = tempnam(sys_get_temp_dir(), 'ncpack');
    copy($path, $tmp);
    try {
        $result = \App\Core\ImageUploader::processFile($tmp, basename($path), (int) filesize($tmp));
    } finally {
        @unlink($tmp);
    }
    return $result['location'];
}

/** True when alt text reads as a sentence rather than a filename or a single word. */
function packAltOk(string $alt): bool
{
    return mb_strlen($alt) >= 20 && str_contains($alt, ' ') && !preg_match('/\.(jpe?g|png|webp)$/i', $alt);
}

/** Zip everything under $stageDir/public_html/uploads with paths starting at uploads/. */
function packZipUploads(string $stageDir, string $zipPath): int
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("Could not create {$zipPath}");
    }
    $root = $stageDir . '/public_html/';
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . 'uploads', FilesystemIterator::SKIP_DOTS));
    $count = 0;
    foreach ($it as $f) {
        $rel = substr($f->getPathname(), strlen($root));
        if ($rel === 'uploads/index.html') {
            continue; // already present on every install
        }
        $zip->addFile($f->getPathname(), $rel);
        $count++;
    }
    $zip->close();
    return $count;
}
