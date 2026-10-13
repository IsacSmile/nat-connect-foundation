<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Content Pack Builder (CLI only, never deployed)
 *
 * Turns content/launch-posts/posts.json + its cover images into a pack that can be
 * loaded on Hostinger using only phpMyAdmin and the hPanel File Manager:
 *
 *   build/content-pack/import.sql   Posts, tags and post_tags (idempotent, slug-based)
 *   build/content-pack/uploads.zip  WebP cover variants at uploads/YYYY/MM/ paths
 *   build/content-pack/remove.sql   Deletes exactly these posts (and their now-unused tags)
 *   build/content-pack/titles.txt   Post list to share with the client
 *
 * Every cover goes through ImageUploader::processFile (finfo check, re-encode,
 * EXIF strip, 1600/800/400 variants). Every body goes through Sanitizer::clean.
 * No database connection is used; nothing is written into public_html/uploads.
 *
 * Usage: php bin/build-content-pack.php [path/to/posts.json]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI access only.\n";
    exit(1);
}

$repoRoot = dirname(__DIR__);
$outDir   = $repoRoot . '/build/content-pack';
$stageDir = $outDir . '/stage';

// ImageUploader writes to ROOT_PATH . '/public_html/uploads'. Point ROOT_PATH at a
// staging folder so the real uploads/ directory is never touched.
define('ROOT_PATH', $stageDir);
define('PUBLIC_PATH', $repoRoot . '/public_html');
define('APP_PATH', PUBLIC_PATH . '/app');

require_once PUBLIC_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Sanitizer.php';
require_once APP_PATH . '/core/ImageUploader.php';
date_default_timezone_set('Asia/Kolkata');

use App\Core\ImageUploader;
use App\Core\Sanitizer;

$jsonPath = $argv[1] ?? ($repoRoot . '/content/launch-posts/posts.json');
$baseDir  = dirname($jsonPath);
$posts    = json_decode((string) @file_get_contents($jsonPath), true);
if (!is_array($posts) || $posts === []) {
    fwrite(STDERR, "[ERROR] Could not read posts from {$jsonPath}\n");
    exit(1);
}

$categories = [
    'Causes'           => 'causes',
    'News'             => 'news',
    'Campaigns'        => 'campaigns',
    'RTI Applications' => 'rti-applications',
];

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

rrmdir($outDir);
mkdir($stageDir . '/public_html/uploads', 0775, true);

// ---------------------------------------------------------------------------
// 1. Validate and process every post
// ---------------------------------------------------------------------------
$errors = [];
$seenSlugs = [];
$rows = [];
$allTags = [];

foreach ($posts as $i => $p) {
    $n = $i + 1;
    $title = trim((string) ($p['title'] ?? ''));
    $slug  = trim((string) ($p['slug'] ?? ''));
    $cat   = (string) ($p['category'] ?? '');
    $alt   = trim((string) ($p['cover_alt'] ?? ''));

    if ($title === '') {
        $errors[] = "#{$n}: missing title";
    }
    if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
        $errors[] = "#{$n}: invalid slug '{$slug}'";
    }
    if (isset($seenSlugs[$slug])) {
        $errors[] = "#{$n}: duplicate slug '{$slug}'";
    }
    $seenSlugs[$slug] = true;
    if (!isset($categories[$cat])) {
        $errors[] = "#{$n}: unknown category '{$cat}'";
    }
    if (mb_strlen($alt) < 20 || !str_contains($alt, ' ') || preg_match('/\.(jpe?g|png|webp)$/i', $alt)) {
        $errors[] = "#{$n}: cover alt text must be a descriptive sentence";
    }
    if (($p['status'] ?? '') !== 'published') {
        $errors[] = "#{$n}: only published posts are supported in a pack";
    }
    $publishedAt = (string) ($p['published_at'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $publishedAt) || strtotime($publishedAt) > time()) {
        $errors[] = "#{$n}: published_at must be a past 'Y-m-d H:i:s' date";
    }

    $rawHtml = (string) ($p['content_html'] ?? '');
    $clean   = Sanitizer::clean($rawHtml);
    $rawTextLen   = mb_strlen(preg_replace('/\s+/', ' ', strip_tags($rawHtml)) ?? '');
    $cleanTextLen = mb_strlen(preg_replace('/\s+/', ' ', strip_tags($clean)) ?? '');
    if ($cleanTextLen < $rawTextLen * 0.98) {
        $errors[] = "#{$n}: sanitizer removed visible text; check the HTML";
    }

    $imgPath = $baseDir . '/' . (string) ($p['cover_image'] ?? '');
    if (!is_file($imgPath)) {
        $errors[] = "#{$n}: cover image not found at {$imgPath}";
        continue;
    }

    // Process through the same pipeline as the admin uploader (work on a copy).
    $tmp = tempnam(sys_get_temp_dir(), 'ncpack');
    copy($imgPath, $tmp);
    try {
        $result = ImageUploader::processFile($tmp, basename($imgPath), (int) filesize($tmp));
    } catch (Throwable $e) {
        $errors[] = "#{$n}: image rejected: " . $e->getMessage();
        continue;
    } finally {
        @unlink($tmp);
    }

    $tags = [];
    foreach ((array) ($p['tags'] ?? []) as $t) {
        $tSlug = (string) ($t['slug'] ?? '');
        $tName = (string) ($t['name'] ?? '');
        if ($tSlug === '' || $tName === '') {
            $errors[] = "#{$n}: tag missing slug or name";
            continue;
        }
        $tags[] = $tSlug;
        $allTags[$tSlug] = $tName;
    }

    $rows[] = [
        'title'        => $title,
        'slug'         => $slug,
        'category'     => $categories[$cat] ?? '',
        'excerpt'      => trim((string) ($p['excerpt'] ?? '')) ?: null,
        'content'      => $clean,
        'cover_image'  => $result['location'],
        'cover_alt'    => $alt,
        'cover_caption'=> trim((string) ($p['cover_caption'] ?? '')) ?: null,
        'is_pick'      => !empty($p['is_pick']) ? 1 : 0,
        'published_at' => $publishedAt,
        'read_time'    => readTime($clean),
        'meta_title'   => trim((string) ($p['meta_title'] ?? '')) ?: null,
        'meta_desc'    => trim((string) ($p['meta_description'] ?? '')) ?: null,
        'tags'         => $tags,
        'variants'     => $result['variants'],
    ];
}

if ($errors !== []) {
    fwrite(STDERR, "[ERROR] Pack not built:\n  - " . implode("\n  - ", $errors) . "\n");
    rrmdir($outDir);
    exit(1);
}

// ---------------------------------------------------------------------------
// 2. import.sql
// ---------------------------------------------------------------------------
$sql   = [];
$sql[] = '-- NatConnect Foundation content pack: ' . count($rows) . ' posts';
$sql[] = '-- Generated ' . date('Y-m-d H:i') . ' IST by bin/build-content-pack.php';
$sql[] = '-- Import in phpMyAdmin AFTER backing up the database. Safe to run twice:';
$sql[] = '-- posts whose slug already exists are skipped. Views and likes start at 0.';
$sql[] = '';
$sql[] = 'SET NAMES utf8mb4;';
$sql[] = "SET time_zone = '+05:30';";
$sql[] = 'START TRANSACTION;';
$sql[] = '';
$sql[] = '-- Tags (existing tags with the same slug are reused)';
$tagValues = [];
foreach ($allTags as $tSlug => $tName) {
    $tagValues[] = '(' . q($tName) . ', ' . q($tSlug) . ')';
}
$sql[] = "INSERT IGNORE INTO `tags` (`name`, `slug`) VALUES\n  " . implode(",\n  ", $tagValues) . ';';
$sql[] = '';

foreach ($rows as $r) {
    $sql[] = '-- ' . $r['title'];
    $sql[] = 'INSERT INTO `posts` (`title`, `slug`, `excerpt`, `content`, `cover_type`, `cover_image`, `cover_alt`, '
        . '`cover_caption`, `video_url`, `author_name`, `category_id`, `status`, `is_pick`, `published_at`, '
        . '`updated_at`, `created_at`, `views`, `likes`, `read_time_minutes`, `meta_title`, `meta_description`)';
    $sql[] = 'SELECT ' . implode(', ', [
        q($r['title']), q($r['slug']), q($r['excerpt']), q($r['content']), "'image'",
        q($r['cover_image']), q($r['cover_alt']), q($r['cover_caption']), 'NULL',
        q('NatConnect Foundation'), 'c.`id`', "'published'", (string) $r['is_pick'],
        q($r['published_at']), q($r['published_at']), q($r['published_at']),
        '0', '0', (string) $r['read_time'], q($r['meta_title']), q($r['meta_desc']),
    ]);
    $sql[] = 'FROM `categories` c WHERE c.`slug` = ' . q($r['category'])
        . ' AND NOT EXISTS (SELECT 1 FROM `posts` p WHERE p.`slug` = ' . q($r['slug']) . ');';
    $sql[] = 'INSERT IGNORE INTO `post_tags` (`post_id`, `tag_id`)';
    $sql[] = 'SELECT p.`id`, t.`id` FROM `posts` p JOIN `tags` t ON t.`slug` IN ('
        . implode(', ', array_map('q', $r['tags'])) . ')';
    $sql[] = 'WHERE p.`slug` = ' . q($r['slug']) . ' AND p.`cover_image` = ' . q($r['cover_image']) . ';';
    $sql[] = '';
}
$sql[] = 'COMMIT;';
file_put_contents($outDir . '/import.sql', implode("\n", $sql) . "\n");

// ---------------------------------------------------------------------------
// 3. remove.sql — deletes only these exact posts (slug + cover path must match)
// ---------------------------------------------------------------------------
$rm   = [];
$rm[] = '-- Removes the ' . count($rows) . ' posts from this content pack, and any of the';
$rm[] = '-- pack\'s tags that end up with no posts and no cause/topic settings.';
$rm[] = '-- Note: this does NOT delete the image files. Deleting the posts from';
$rm[] = '-- Admin -> Posts instead also removes their image files from the server.';
$rm[] = '';
$rm[] = 'SET NAMES utf8mb4;';
$rm[] = 'START TRANSACTION;';
foreach ($rows as $r) {
    $rm[] = 'DELETE FROM `posts` WHERE `slug` = ' . q($r['slug']) . ' AND `cover_image` = ' . q($r['cover_image']) . ';';
}
$rm[] = 'DELETE t FROM `tags` t LEFT JOIN `post_tags` pt ON pt.`tag_id` = t.`id`';
$rm[] = 'WHERE pt.`tag_id` IS NULL AND t.`show_as_cause` = 0 AND t.`show_as_topic` = 0';
$rm[] = '  AND t.`description` IS NULL AND t.`image_path` IS NULL';
$rm[] = '  AND t.`slug` IN (' . implode(', ', array_map('q', array_keys($allTags))) . ');';
$rm[] = 'COMMIT;';
file_put_contents($outDir . '/remove.sql', implode("\n", $rm) . "\n");

// ---------------------------------------------------------------------------
// 4. uploads.zip — paths start at uploads/ so it extracts straight into public_html
// ---------------------------------------------------------------------------
$zip = new ZipArchive();
if ($zip->open($outDir . '/uploads.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "[ERROR] Could not create uploads.zip\n");
    exit(1);
}
$uploadsRoot = $stageDir . '/public_html/';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsRoot . 'uploads', FilesystemIterator::SKIP_DOTS));
$fileCount = 0;
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($uploadsRoot));
    if ($rel === 'uploads/index.html') {
        continue; // already present on every install
    }
    $zip->addFile($f->getPathname(), $rel);
    $fileCount++;
}
$zip->close();

// ---------------------------------------------------------------------------
// 5. titles.txt
// ---------------------------------------------------------------------------
$t = ["NatConnect Foundation — posts in this content pack (" . count($rows) . ")", ''];
$byCat = [];
foreach ($rows as $r) {
    $byCat[array_search($r['category'], $categories, true)][] = $r;
}
foreach ($byCat as $catName => $list) {
    $t[] = $catName;
    foreach ($list as $r) {
        $t[] = '  - ' . $r['title'] . ($r['is_pick'] ? "  [Editor's pick]" : '') . '  (/post/' . $r['slug'] . ')';
    }
    $t[] = '';
}
file_put_contents($outDir . '/titles.txt', implode("\n", $t));

rrmdir($stageDir);

echo "[OK] Built content pack in build/content-pack/\n";
echo "     " . count($rows) . " posts, " . count($allTags) . " tags, {$fileCount} image files in uploads.zip\n";
foreach (['import.sql', 'remove.sql', 'uploads.zip', 'titles.txt'] as $f) {
    printf("     %-12s %s\n", $f, number_format((int) filesize($outDir . '/' . $f) / 1024, 1) . ' KB');
}
