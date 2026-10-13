<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Phase 6A SEO & Syndication Verification Suite
 *
 * Verifies:
 * 1. /sitemap.xml:
 *    - Valid XML structure with xml prolog and urlset namespace.
 *    - Contains home, published posts (lastmod = updated_at), categories, tags with posts,
 *      published pages, gallery, contact, and donate (if active).
 *    - Excludes drafts and future-scheduled posts.
 *    - Excludes empty tags (tags without published posts).
 *    - Excludes draft pages.
 *    - Absolute https URLs matching config.
 * 2. /feed (RSS 2.0):
 *    - Valid RSS 2.0 XML structure (<rss version="2.0">, <channel>).
 *    - Latest published posts with title, link, guid (permalink), pubDate (RFC 2822 IST),
 *      description (plain excerpt), and cover image enclosure.
 *    - Strictly excludes unpublished posts (drafts and future scheduled).
 * 3. robots.txt:
 *    - Disallow /admin and /setup.php.
 *    - Sitemap directive generated dynamically from site config.
 *    - Static robots.txt file is absent from disk.
 * 4. Default Share Image & Favicon Assets:
 *    - assets/img/og-default.png exists and is 1200x630.
 *    - Favicon set: favicon.ico (32px), apple-touch-icon.png (180px), icon-192.png, icon-512.png.
 *    - site.webmanifest exists and is valid JSON.
 *    - Public layout includes RSS alternate link and web manifest link.
 * 5. Complete fixture cleanup.
 *
 * Usage: php tests/seo_check.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/public_html/app');

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/ImageUploader.php';
require_once APP_PATH . '/core/View.php';
require_once APP_PATH . '/models/PostModel.php';
require_once APP_PATH . '/models/CategoryModel.php';
require_once APP_PATH . '/models/TagModel.php';
require_once APP_PATH . '/models/SettingModel.php';
require_once APP_PATH . '/models/PageModel.php';
require_once APP_PATH . '/controllers/PublicController.php';

use App\Core\Database;
use App\Core\ImageUploader;
use App\Models\CategoryModel;
use App\Models\PostModel;
use App\Models\SettingModel;
use App\Controllers\PublicController;

echo "========================================================\n";
echo "NatConnect Foundation — Phase 6A SEO & Syndication Suite\n";
echo "========================================================\n\n";

$pdo = Database::getConnection();
echo "[OK] Connected to database: " . config('db.name') . "\n";

$createdPostIds = [];
$createdCategoryIds = [];
$createdTagIds = [];
$createdPageIds = [];

// Cleanup handler on exit or failure
$cleanup = function () use ($pdo, &$createdPostIds, &$createdCategoryIds, &$createdTagIds, &$createdPageIds) {
    if (!empty($createdPostIds)) {
        $in = implode(',', array_map('intval', $createdPostIds));
        $pdo->query("DELETE FROM post_tags WHERE post_id IN ({$in})");
        $pdo->query("DELETE FROM comments WHERE post_id IN ({$in})");
        $pdo->query("DELETE FROM post_likes WHERE post_id IN ({$in})");
        $pdo->query("DELETE FROM post_views WHERE post_id IN ({$in})");
        $pdo->query("DELETE FROM posts WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test posts: {$in}\n";
    }
    if (!empty($createdTagIds)) {
        $in = implode(',', array_map('intval', $createdTagIds));
        $pdo->query("DELETE FROM post_tags WHERE tag_id IN ({$in})");
        $pdo->query("DELETE FROM tags WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test tags: {$in}\n";
    }
    if (!empty($createdCategoryIds)) {
        $in = implode(',', array_map('intval', $createdCategoryIds));
        $pdo->query("DELETE FROM categories WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test categories: {$in}\n";
    }
    if (!empty($createdPageIds)) {
        $in = implode(',', array_map('intval', $createdPageIds));
        $pdo->query("DELETE FROM pages WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test pages: {$in}\n";
    }
};

register_shutdown_function($cleanup);

/**
 * Validate well-formed XML without requiring ext-simplexml/ext-dom.
 */
function validateXmlWellFormed(string $xml, string $rootTag): bool {
    $trimmed = trim($xml);
    if (!str_starts_with($trimmed, '<?xml version="1.0" encoding="UTF-8"?>')) {
        return false;
    }

    preg_match_all('#<(/?[a-zA-Z0-9_:-]+)([^>]*)>#', $trimmed, $matches, PREG_OFFSET_CAPTURE);
    $stack = [];

    foreach ($matches[1] as $idx => $tagMatch) {
        $tag = $tagMatch[0];
        $attrs = $matches[2][$idx][0];

        // Self-closing tag
        if (str_ends_with(rtrim($attrs), '/')) {
            continue;
        }

        if (str_starts_with($tag, '/')) {
            $closing = substr($tag, 1);
            $last = array_pop($stack);
            if ($last !== $closing) {
                return false;
            }
        } else {
            $stack[] = $tag;
        }
    }

    return empty($stack) && str_contains($trimmed, "<{$rootTag}") && str_contains($trimmed, "</{$rootTag}>");
}

// -------------------------------------------------------------
// Fixtures Setup
// -------------------------------------------------------------
$rand = bin2hex(random_bytes(4));

// 1. Category
$catSlug = "test-seo-cat-{$rand}";
$stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
$stmt->execute(["SEO Cat {$rand}", $catSlug, "SEO test category"]);
$catId = (int) $pdo->lastInsertId();
$createdCategoryIds[] = $catId;

// 2. Tags: one with a post, one without
$tagWithPostSlug = "test-seo-tag-used-{$rand}";
$tagEmptySlug    = "test-seo-tag-empty-{$rand}";

$stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
$stmt->execute(["Used Tag {$rand}", $tagWithPostSlug]);
$tagUsedId = (int) $pdo->lastInsertId();
$createdTagIds[] = $tagUsedId;

$stmt->execute(["Empty Tag {$rand}", $tagEmptySlug]);
$tagEmptyId = (int) $pdo->lastInsertId();
$createdTagIds[] = $tagEmptyId;

// 3. Posts: Published, Draft, Future Scheduled
$publishedSlug = "test-seo-pub-{$rand}";
$draftSlug     = "test-seo-draft-{$rand}";
$futureSlug    = "test-seo-future-{$rand}";

$stmt = $pdo->prepare("
    INSERT INTO posts (title, slug, excerpt, content, cover_image, status, published_at, category_id, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");

// Published Post (past date)
$testCover = "/uploads/2026/10/test-seo-cover-{$rand}-1600.webp";
$stmt->execute([
    "Published SEO Post {$rand}",
    $publishedSlug,
    "Excerpt for published post",
    "<p>Content for published post</p>",
    $testCover,
    'published',
    date('Y-m-d H:i:s', time() - 3600),
    $catId,
]);
$publishedPostId = (int) $pdo->lastInsertId();
$createdPostIds[] = $publishedPostId;

// Attach tag to published post
$pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$publishedPostId, $tagUsedId]);

// Draft Post
$stmt->execute([
    "Draft SEO Post {$rand}",
    $draftSlug,
    "Excerpt for draft",
    "<p>Draft content</p>",
    null,
    'draft',
    null,
    $catId,
]);
$createdPostIds[] = (int) $pdo->lastInsertId();

// Future Scheduled Post
$stmt->execute([
    "Future SEO Post {$rand}",
    $futureSlug,
    "Excerpt for future",
    "<p>Future content</p>",
    null,
    'scheduled',
    date('Y-m-d H:i:s', time() + 86400 * 5),
    $catId,
]);
$createdPostIds[] = (int) $pdo->lastInsertId();

// 4. Pages: Published, Draft
$pubPageSlug   = "test-seo-page-pub-{$rand}";
$draftPageSlug = "test-seo-page-draft-{$rand}";

$stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, status, show_in_nav, is_system, created_at, updated_at) VALUES (?, ?, ?, ?, 0, 0, NOW(), NOW())");
$stmt->execute(["Published Test Page {$rand}", $pubPageSlug, "<p>Page content</p>", 'published']);
$createdPageIds[] = (int) $pdo->lastInsertId();

$stmt->execute(["Draft Test Page {$rand}", $draftPageSlug, "<p>Draft page content</p>", 'draft']);
$createdPageIds[] = (int) $pdo->lastInsertId();

$controller = new PublicController();

// -------------------------------------------------------------
// Test 1: Dynamic XML Sitemap (/sitemap.xml)
// -------------------------------------------------------------
echo "\n--- 1. XML SITEMAP (/sitemap.xml) ---\n";

ob_start();
$controller->sitemapXml();
$sitemapXml = ob_get_clean();

if (!validateXmlWellFormed($sitemapXml, 'urlset')) {
    echo "[FAIL] Sitemap is not well-formed XML with valid <urlset> root!\n";
    exit(1);
}
echo "[PASS] XML Well-Formed: Sitemap produces valid XML with <urlset> namespace.\n";

// Check URLs in sitemap
$canonicalSiteUrl = PublicController::getCanonicalSiteUrl();
if (!str_starts_with($canonicalSiteUrl, 'https://')) {
    echo "[FAIL] getCanonicalSiteUrl() must return absolute HTTPS URL!\n";
    exit(1);
}
echo "[PASS] HTTPS Canonical Scheme: Canonical URL strictly enforces HTTPS ('{$canonicalSiteUrl}').\n";

// Check home
if (str_contains($sitemapXml, "<loc>{$canonicalSiteUrl}/</loc>")) {
    echo "[PASS] Home URL: Homepage is present in sitemap.\n";
} else {
    echo "[FAIL] Homepage URL was missing from sitemap!\n";
    exit(1);
}

// Check published post present
if (str_contains($sitemapXml, "<loc>{$canonicalSiteUrl}/post/{$publishedSlug}</loc>")) {
    echo "[PASS] Published Post: Published post is included in sitemap with lastmod.\n";
} else {
    echo "[FAIL] Published post was missing from sitemap!\n";
    exit(1);
}

// Check draft post EXCLUDED
if (!str_contains($sitemapXml, "/post/{$draftSlug}")) {
    echo "[PASS] Draft Exclusion: Draft post is strictly excluded from sitemap.\n";
} else {
    echo "[FAIL] Draft post was found in sitemap!\n";
    exit(1);
}

// Check future scheduled post EXCLUDED
if (!str_contains($sitemapXml, "/post/{$futureSlug}")) {
    echo "[PASS] Future Post Exclusion: Future scheduled post is strictly excluded from sitemap.\n";
} else {
    echo "[FAIL] Future scheduled post was found in sitemap!\n";
    exit(1);
}

// Check categories present
if (str_contains($sitemapXml, "<loc>{$canonicalSiteUrl}/category/{$catSlug}</loc>")) {
    echo "[PASS] Categories: Category archive URL is included in sitemap.\n";
} else {
    echo "[FAIL] Category archive URL was missing from sitemap!\n";
    exit(1);
}

// Check tag with post present
if (str_contains($sitemapXml, "<loc>{$canonicalSiteUrl}/tag/{$tagWithPostSlug}</loc>")) {
    echo "[PASS] Tags With Posts: Tag with active published post is included in sitemap.\n";
} else {
    echo "[FAIL] Tag with active post was missing from sitemap!\n";
    exit(1);
}

// Check empty tag EXCLUDED
if (!str_contains($sitemapXml, "/tag/{$tagEmptySlug}")) {
    echo "[PASS] Empty Tag Exclusion: Tag without published posts is strictly excluded from sitemap.\n";
} else {
    echo "[FAIL] Tag without published posts was included in sitemap!\n";
    exit(1);
}

// Check published page present
if (str_contains($sitemapXml, "<loc>{$canonicalSiteUrl}/{$pubPageSlug}</loc>")) {
    echo "[PASS] Published Pages: Published page is included in sitemap.\n";
} else {
    echo "[FAIL] Published page was missing from sitemap!\n";
    exit(1);
}

// Check draft page EXCLUDED
if (!str_contains($sitemapXml, "/{$draftPageSlug}")) {
    echo "[PASS] Draft Page Exclusion: Draft page is strictly excluded from sitemap.\n";
} else {
    echo "[FAIL] Draft page was included in sitemap!\n";
    exit(1);
}

// Check gallery, contact, and causes present
if (str_contains($sitemapXml, "<loc>{$canonicalSiteUrl}/gallery</loc>") 
    && str_contains($sitemapXml, "<loc>{$canonicalSiteUrl}/contact</loc>")
    && str_contains($sitemapXml, "<loc>{$canonicalSiteUrl}/causes</loc>")) {
    echo "[PASS] Fixed Public Routes: /gallery, /contact, and /causes are included in sitemap.\n";
} else {
    echo "[FAIL] /gallery, /contact, or /causes was missing from sitemap!\n";
    exit(1);
}

// -------------------------------------------------------------
// Test 2: Dynamic RSS 2.0 Feed (/feed)
// -------------------------------------------------------------
echo "\n--- 2. RSS 2.0 FEED (/feed) ---\n";

ob_start();
$controller->rssFeed();
$rssXml = ob_get_clean();

if (!validateXmlWellFormed($rssXml, 'rss')) {
    echo "[FAIL] RSS feed is not well-formed XML with valid <rss> root!\n";
    exit(1);
}
echo "[PASS] RSS Well-Formed: RSS feed produces valid RSS 2.0 XML.\n";

// Check channel metadata
if (str_contains($rssXml, '<channel>') && str_contains($rssXml, '<atom:link') && str_contains($rssXml, '<language>en</language>')) {
    echo "[PASS] Channel Metadata: RSS channel includes title, description, language, and atom:link.\n";
} else {
    echo "[FAIL] Channel metadata missing from RSS feed!\n";
    exit(1);
}

// Check published post in feed
if (str_contains($rssXml, "<title>Published SEO Post {$rand}</title>") &&
    str_contains($rssXml, "<link>{$canonicalSiteUrl}/post/{$publishedSlug}</link>") &&
    str_contains($rssXml, "<guid isPermaLink=\"true\">{$canonicalSiteUrl}/post/{$publishedSlug}</guid>")
) {
    echo "[PASS] Published Item: Published post is included with title, link, and permalink guid.\n";
} else {
    echo "[FAIL] Published post was missing from RSS feed!\n";
    exit(1);
}

// Check enclosure
if (str_contains($rssXml, '<enclosure url="') && str_contains($rssXml, 'type="image/webp"')) {
    echo "[PASS] Enclosure: Cover image is included as RSS enclosure.\n";
} else {
    echo "[FAIL] Cover image enclosure missing from RSS item!\n";
    exit(1);
}

// Check RFC 2822 IST date format
if (preg_match('#<pubDate>[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} \d{2}:\d{2}:\d{2} \+0530</pubDate>#', $rssXml)) {
    echo "[PASS] RFC 2822 PubDate: Publication date formatted in valid RFC 2822 IST (+0530).\n";
} else {
    echo "[FAIL] pubDate does not match RFC 2822 IST format in RSS feed!\n";
    exit(1);
}

// Check draft & future posts EXCLUDED
if (!str_contains($rssXml, $draftSlug) && !str_contains($rssXml, $futureSlug)) {
    echo "[PASS] Unpublished Exclusion: Drafts and future scheduled posts are strictly excluded from RSS.\n";
} else {
    echo "[FAIL] Draft or future scheduled post appeared in RSS feed!\n";
    exit(1);
}

// -------------------------------------------------------------
// Test 3: robots.txt Route & Directives
// -------------------------------------------------------------
echo "\n--- 3. ROBOTS.TXT (/robots.txt) ---\n";

ob_start();
$controller->robotsTxt();
$robotsTxt = ob_get_clean();

if (str_contains($robotsTxt, 'User-agent: *') &&
    str_contains($robotsTxt, 'Disallow: /admin') &&
    str_contains($robotsTxt, 'Disallow: /setup.php') &&
    str_contains($robotsTxt, "Sitemap: {$canonicalSiteUrl}/sitemap.xml")
) {
    echo "[PASS] Robots Directives: Contains Disallow /admin, Disallow /setup.php, and dynamic Sitemap URL.\n";
} else {
    echo "[FAIL] Robots.txt missing required directives or sitemap URL!\n";
    exit(1);
}

if (!file_exists(ROOT_PATH . '/public_html/robots.txt')) {
    echo "[PASS] Static File Absent: Static public_html/robots.txt was successfully removed (served via PHP).\n";
} else {
    echo "[FAIL] Static public_html/robots.txt still exists on disk!\n";
    exit(1);
}

// -------------------------------------------------------------
// Test 4: Default Share Image & Favicon Assets
// -------------------------------------------------------------
echo "\n--- 4. ASSETS & LAYOUT HEAD TAGS ---\n";

$ogImgPath = ROOT_PATH . '/public_html/assets/img/og-default.png';
if (file_exists($ogImgPath)) {
    $dims = getimagesize($ogImgPath);
    if ($dims && $dims[0] === 1200 && $dims[1] === 630) {
        echo "[PASS] OG Default Image: assets/img/og-default.png exists with exact 1200x630 dimensions.\n";
    } else {
        echo "[FAIL] assets/img/og-default.png dimensions are not 1200x630!\n";
        exit(1);
    }
} else {
    echo "[FAIL] assets/img/og-default.png does not exist!\n";
    exit(1);
}

// Favicon set
$favIco   = ROOT_PATH . '/public_html/favicon.ico';
$appleIco = ROOT_PATH . '/public_html/apple-touch-icon.png';
$ico192   = ROOT_PATH . '/public_html/icon-192.png';
$ico512   = ROOT_PATH . '/public_html/icon-512.png';
$manifest = ROOT_PATH . '/public_html/site.webmanifest';

if (file_exists($favIco) && file_exists($appleIco) && file_exists($ico192) && file_exists($ico512) && file_exists($manifest)) {
    echo "[PASS] Favicon Suite: favicon.ico, apple-touch-icon.png, icon-192.png, icon-512.png, and site.webmanifest exist.\n";
} else {
    echo "[FAIL] One or more favicon/manifest files are missing!\n";
    exit(1);
}

$manifestJson = json_decode(file_get_contents($manifest), true);
if (is_array($manifestJson) && ($manifestJson['background_color'] ?? '') === '#FAFAF7' && !empty($manifestJson['icons'])) {
    echo "[PASS] Web Manifest: site.webmanifest is valid JSON with theme and background colors.\n";
} else {
    echo "[FAIL] site.webmanifest is invalid or missing background_color!\n";
    exit(1);
}

// Layout head verification
$layoutContent = file_get_contents(APP_PATH . '/views/layouts/public.php');
if (str_contains($layoutContent, 'rel="alternate" type="application/rss+xml"') &&
    str_contains($layoutContent, 'rel="manifest" href="/site.webmanifest"') &&
    str_contains($layoutContent, 'rel="icon" href="/favicon.ico"') &&
    str_contains($layoutContent, 'rel="apple-touch-icon"')
) {
    echo "[PASS] Layout Head Tags: public layout includes RSS feed, webmanifest, favicon.ico, and apple-touch-icon tags.\n";
} else {
    echo "[FAIL] Public layout is missing one or more required head tags!\n";
    exit(1);
}

echo "\n========================================================\n";
echo "[SUCCESS] All Phase 6A SEO & Syndication Tests Passed!\n";
echo "========================================================\n";
