<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Homepage Pack Builder (CLI only, never deployed)
 *
 * Builds build/homepage-pack/ from content/homepage/homepage.json:
 *   update.sql   Get involved intro + photo, cause cards, topic chips, and updates to
 *                launch posts that were already imported (content, slug + 301 redirect, tags)
 *   uploads.zip  WebP variants for the Get involved photo and cause images
 *
 * The SQL never overwrites anything an admin has already filled in: the intro and photo
 * are only set when empty, and a cause is only set up when that tag has no image yet.
 *
 * Usage: php bin/build-homepage-pack.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI access only.\n";
    exit(1);
}

$repoRoot = dirname(__DIR__);
$outDir   = $repoRoot . '/build/homepage-pack';
$stageDir = $outDir . '/stage';

define('ROOT_PATH', $stageDir);
define('PUBLIC_PATH', $repoRoot . '/public_html');
define('APP_PATH', PUBLIC_PATH . '/app');

require_once __DIR__ . '/lib/pack-helpers.php';

use App\Core\Sanitizer;

$hpDir    = $repoRoot . '/content/homepage';
$hp       = json_decode((string) @file_get_contents($hpDir . '/homepage.json'), true);
$posts    = json_decode((string) @file_get_contents($repoRoot . '/content/launch-posts/posts.json'), true);
if (!is_array($hp) || !is_array($posts)) {
    fwrite(STDERR, "[ERROR] Could not read content/homepage/homepage.json or content/launch-posts/posts.json\n");
    exit(1);
}
$postsBySlug = array_column($posts, null, 'slug');

rrmdir($outDir);
mkdir($stageDir . '/public_html/uploads', 0775, true);

$errors = [];
$sql = [
    '-- NatConnect Foundation homepage pack',
    '-- Generated ' . date('Y-m-d H:i') . ' IST by bin/build-homepage-pack.php',
    '-- Import in phpMyAdmin AFTER backing up the database. Safe to run twice.',
    '-- Never overwrites text or images an admin has already set.',
    '',
    'SET NAMES utf8mb4;',
    "SET time_zone = '+05:30';",
    'START TRANSACTION;',
    '',
];

// ---------------------------------------------------------------------------
// 1. Get involved: intro and photo (only when empty)
// ---------------------------------------------------------------------------
$intro = trim((string) ($hp['settings']['get_involved_intro'] ?? ''));
$photo = $hp['settings']['get_involved_photo'] ?? null;
$sql[] = '-- Get involved section';
if ($intro !== '') {
    $sql[] = "INSERT IGNORE INTO `settings` (`key`, `value`) VALUES ('get_involved_intro', '');";
    $sql[] = 'UPDATE `settings` SET `value` = ' . q($intro)
        . " WHERE `key` = 'get_involved_intro' AND (`value` IS NULL OR `value` = '');";
}
if (is_array($photo)) {
    $alt = trim((string) ($photo['alt'] ?? ''));
    if (!packAltOk($alt)) {
        $errors[] = 'get_involved_photo: alt text must be a descriptive sentence';
    }
    try {
        $loc = packProcessImage($hpDir . '/' . (string) ($photo['image'] ?? ''));
        $sql[] = "INSERT IGNORE INTO `settings` (`key`, `value`) VALUES ('get_involved_photo', ''), ('get_involved_photo_alt', '');";
        // Set photo and alt together, only if no photo has been uploaded yet.
        $sql[] = "UPDATE `settings` s JOIN `settings` a ON a.`key` = 'get_involved_photo_alt'";
        $sql[] = '   SET s.`value` = ' . q($loc) . ', a.`value` = ' . q($alt);
        $sql[] = " WHERE s.`key` = 'get_involved_photo' AND (s.`value` IS NULL OR s.`value` = '');";
    } catch (Throwable $e) {
        $errors[] = 'get_involved_photo: ' . $e->getMessage();
    }
}
$sql[] = '';

// ---------------------------------------------------------------------------
// 2. Causes (tags with image + description), only for tags without an image yet
// ---------------------------------------------------------------------------
$sql[] = '-- Causes we care about';
foreach ((array) ($hp['causes'] ?? []) as $c) {
    $slug = (string) ($c['slug'] ?? '');
    $name = trim((string) ($c['name'] ?? ''));
    $desc = trim((string) ($c['description'] ?? ''));
    $alt  = trim((string) ($c['image_alt'] ?? ''));
    if ($slug === '' || $name === '' || $desc === '' || !packAltOk($alt)) {
        $errors[] = "cause '{$slug}': needs slug, name, description and a descriptive image_alt";
        continue;
    }
    try {
        $loc = packProcessImage($hpDir . '/' . (string) ($c['image'] ?? ''));
    } catch (Throwable $e) {
        $errors[] = "cause '{$slug}': " . $e->getMessage();
        continue;
    }
    $sql[] = 'INSERT IGNORE INTO `tags` (`name`, `slug`) VALUES (' . q($name) . ', ' . q($slug) . ');';
    $sql[] = 'UPDATE `tags` SET `show_as_cause` = 1, `description` = ' . q($desc) . ', `image_path` = ' . q($loc)
        . ', `image_alt` = ' . q($alt) . ', `sort_order` = ' . (int) ($c['sort_order'] ?? 0)
        . ' WHERE `slug` = ' . q($slug) . " AND (`image_path` IS NULL OR `image_path` = '');";
}
$sql[] = '';

// ---------------------------------------------------------------------------
// 3. Topics (chips row)
// ---------------------------------------------------------------------------
$topics = array_values(array_filter((array) ($hp['topics'] ?? []), 'is_string'));
if ($topics !== []) {
    $sql[] = '-- Explore by topic';
    // Chips are ordered by sort_order: keep the listed order, after any cause numbering (1-9).
    foreach ($topics as $i => $t) {
        $sql[] = 'UPDATE `tags` SET `show_as_topic` = 1, `sort_order` = IF(`sort_order` = 0 OR `show_as_cause` = 0, ' . (10 + $i)
            . ', `sort_order`) WHERE `slug` = ' . q($t) . ';';
    }
    $sql[] = '';
}

// ---------------------------------------------------------------------------
// 4. Updates to launch posts that were already imported
// ---------------------------------------------------------------------------
foreach ((array) ($hp['post_updates'] ?? []) as $u) {
    $old = (string) ($u['old_slug'] ?? '');
    $new = (string) ($u['slug'] ?? '');
    $p   = $postsBySlug[$new] ?? null;
    if ($old === '' || $p === null) {
        $errors[] = "post update '{$old}' -> '{$new}': slug not found in posts.json";
        continue;
    }
    $clean = Sanitizer::clean((string) $p['content_html']);
    $sql[] = '-- Update: ' . $p['title'];
    $sql[] = 'UPDATE `posts` SET `title` = ' . q($p['title']) . ', `slug` = ' . q($new)
        . ', `excerpt` = ' . q($p['excerpt']) . ', `content` = ' . q($clean)
        . ', `read_time_minutes` = ' . readTime($clean)
        . ', `meta_title` = ' . q($p['meta_title']) . ', `meta_description` = ' . q($p['meta_description'])
        . ', `cover_alt` = ' . q($p['cover_alt']) . ', `cover_caption` = ' . q($p['cover_caption'])
        . ', `updated_at` = NOW()'
        . ' WHERE `slug` = ' . q($old) . ';';
    if ($old !== $new) {
        // Same behaviour as the admin editor: 301 from the old URL, chains flattened.
        $sql[] = 'UPDATE `redirects` SET `new_path` = ' . q('/post/' . $new) . ' WHERE `new_path` = ' . q('/post/' . $old) . ';';
        $sql[] = 'DELETE FROM `redirects` WHERE `old_path` = ' . q('/post/' . $new) . ';';
        $sql[] = 'INSERT IGNORE INTO `redirects` (`old_path`, `new_path`) VALUES (' . q('/post/' . $old) . ', ' . q('/post/' . $new) . ');';
    }
    $tagSlugs = array_column((array) $p['tags'], 'slug');
    $tagRows  = array_map(fn($t) => '(' . q($t['name']) . ', ' . q($t['slug']) . ')', (array) $p['tags']);
    $sql[] = 'INSERT IGNORE INTO `tags` (`name`, `slug`) VALUES ' . implode(', ', $tagRows) . ';';
    $sql[] = 'DELETE pt FROM `post_tags` pt JOIN `posts` p ON p.`id` = pt.`post_id` JOIN `tags` t ON t.`id` = pt.`tag_id`'
        . ' WHERE p.`slug` = ' . q($new) . ' AND t.`slug` NOT IN (' . implode(', ', array_map('q', $tagSlugs)) . ');';
    $sql[] = 'INSERT IGNORE INTO `post_tags` (`post_id`, `tag_id`) SELECT p.`id`, t.`id` FROM `posts` p JOIN `tags` t'
        . ' ON t.`slug` IN (' . implode(', ', array_map('q', $tagSlugs)) . ') WHERE p.`slug` = ' . q($new) . ';';
    $sql[] = '';
}

// ---------------------------------------------------------------------------
// 5. Retired tags: removed only if no post uses them and nothing was configured
// ---------------------------------------------------------------------------
$retired = array_values(array_filter((array) ($hp['retired_tags'] ?? []), 'is_string'));
if ($retired !== []) {
    $sql[] = '-- Retired tags';
    $sql[] = 'DELETE t FROM `tags` t LEFT JOIN `post_tags` pt ON pt.`tag_id` = t.`id`';
    $sql[] = 'WHERE pt.`tag_id` IS NULL AND t.`show_as_cause` = 0 AND t.`show_as_topic` = 0';
    $sql[] = '  AND t.`description` IS NULL AND t.`image_path` IS NULL';
    $sql[] = '  AND t.`slug` IN (' . implode(', ', array_map('q', $retired)) . ');';
    $sql[] = '';
}
$sql[] = 'COMMIT;';

if ($errors !== []) {
    fwrite(STDERR, "[ERROR] Pack not built:\n  - " . implode("\n  - ", $errors) . "\n");
    rrmdir($outDir);
    exit(1);
}

file_put_contents($outDir . '/update.sql', implode("\n", $sql) . "\n");
$fileCount = packZipUploads($stageDir, $outDir . '/uploads.zip');
rrmdir($stageDir);

echo "[OK] Built homepage pack in build/homepage-pack/\n";
echo "     " . count((array) ($hp['causes'] ?? [])) . " causes, " . count($topics) . " topics, "
    . count((array) ($hp['post_updates'] ?? [])) . " post updates, {$fileCount} image files in uploads.zip\n";
foreach (['update.sql', 'uploads.zip'] as $f) {
    printf("     %-12s %s\n", $f, number_format((int) filesize($outDir . '/' . $f) / 1024, 1) . ' KB');
}
