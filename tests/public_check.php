<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Phase 4A Public Queries & Browsing Verification
 *
 * Verifies:
 * 1. PostModel::sqlPublished() enforcement: Only 'published' and past 'scheduled' posts
 *    appear on Home, Category, Tag, and Search queries.
 * 2. Drafts and future scheduled posts NEVER appear in any public query.
 * 3. Search query properly escapes % and _ wildcards in user input.
 * 4. Donate feature activation rules.
 * 5. Complete fixture cleanup.
 *
 * Usage: php tests/public_check.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/Csrf.php';
require_once APP_PATH . '/core/Sanitizer.php';
require_once APP_PATH . '/core/View.php';
require_once APP_PATH . '/core/ImageUploader.php';
require_once APP_PATH . '/models/RedirectModel.php';
require_once APP_PATH . '/models/CategoryModel.php';
require_once APP_PATH . '/models/PostModel.php';
require_once APP_PATH . '/models/TagModel.php';
require_once APP_PATH . '/models/SettingModel.php';
require_once APP_PATH . '/models/PageModel.php';
require_once APP_PATH . '/models/GalleryModel.php';
require_once APP_PATH . '/models/SubscriberModel.php';
require_once APP_PATH . '/controllers/PublicController.php';
require_once APP_PATH . '/core/LaunchCheck.php';

use App\Core\Database;
use App\Core\ImageUploader;
use App\Core\LaunchCheck;
use App\Models\CategoryModel;
use App\Models\GalleryModel;
use App\Models\PostModel;
use App\Models\SettingModel;
use App\Models\SubscriberModel;
use App\Models\TagModel;
use App\Controllers\PublicController;

echo "========================================================\n";
echo "NatConnect Foundation — Phase 4A Public Browsing Tests\n";
echo "========================================================\n";

$pdo = Database::getConnection();
echo "[OK] Connected to database: " . config('db.name') . "\n\n";

$createdPostIds = [];
$createdCategoryIds = [];
$createdTagIds = [];

// Cleanup helper
$cleanup = function () use ($pdo, &$createdPostIds, &$createdCategoryIds, &$createdTagIds) {
    if (!empty($createdPostIds)) {
        $in = implode(',', array_map('intval', $createdPostIds));
        $pdo->exec("DELETE FROM post_tags WHERE post_id IN ({$in})");
        $pdo->exec("DELETE FROM posts WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test posts: {$in}\n";
    }
    if (!empty($createdCategoryIds)) {
        $in = implode(',', array_map('intval', $createdCategoryIds));
        $pdo->exec("DELETE FROM categories WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test categories: {$in}\n";
    }
    if (!empty($createdTagIds)) {
        $in = implode(',', array_map('intval', $createdTagIds));
        $pdo->exec("DELETE FROM tags WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test tags: {$in}\n";
    }
};

register_shutdown_function($cleanup);

try {
    // 1. Create a dedicated test category
    $catSlug = 'test-public-cat-' . bin2hex(random_bytes(3));
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :desc)");
    $stmt->execute([
        'name' => 'Public Test Category',
        'slug' => $catSlug,
        'desc' => 'Category for testing public query isolation.',
    ]);
    $catId = (int) $pdo->lastInsertId();
    $createdCategoryIds[] = $catId;

    // 2. Create a dedicated test tag
    $tagSlug = 'public-test-tag-' . bin2hex(random_bytes(3));
    $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (:name, :slug)");
    $stmt->execute([
        'name' => 'PublicTestTag',
        'slug' => $tagSlug,
    ]);
    $tagId = (int) $pdo->lastInsertId();
    $createdTagIds[] = $tagId;

    // 3. Create 4 test posts:
    //    Post A: Draft
    //    Post B: Past-scheduled (published_at = 1 hour ago) -> effectively PUBLISHED
    //    Post C: Future-scheduled (published_at = 2 days future) -> effectively SCHEDULED
    //    Post D: Published (published_at = 2 hours ago) -> PUBLISHED

    $now = time();
    $pastTime = date('Y-m-d H:i:s', $now - 3600);
    $olderPastTime = date('Y-m-d H:i:s', $now - 7200);
    $futureTime = date('Y-m-d H:i:s', $now + 172800);

    $insertPost = function (string $title, string $slug, string $status, ?string $pubAt) use ($pdo, $catId, $tagId, &$createdPostIds) {
        $stmt = $pdo->prepare("
            INSERT INTO posts (title, slug, content, excerpt, category_id, status, published_at, author_name)
            VALUES (:title, :slug, :content, :excerpt, :catId, :status, :pubAt, 'Test Author')
        ");
        $stmt->execute([
            'title'   => $title,
            'slug'    => $slug,
            'content' => "<p>Content for {$title} with literal %100_special token.</p>",
            'excerpt' => "Excerpt for {$title}",
            'catId'   => $catId,
            'status'  => $status,
            'pubAt'   => $pubAt,
        ]);
        $id = (int) $pdo->lastInsertId();
        $createdPostIds[] = $id;

        // Associate with test tag
        $stmtTag = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (:pid, :tid)");
        $stmtTag->execute(['pid' => $id, 'tid' => $tagId]);

        return $id;
    };

    $postASlug = 'post-a-draft-' . bin2hex(random_bytes(3));
    $postBSlug = 'post-b-past-' . bin2hex(random_bytes(3));
    $postCSlug = 'post-c-future-' . bin2hex(random_bytes(3));
    $postDSlug = 'post-d-published-' . bin2hex(random_bytes(3));

    $postA = $insertPost('Post A Draft %100_special', $postASlug, 'draft', null);
    $postB = $insertPost('Post B Past Scheduled %100_special', $postBSlug, 'scheduled', $pastTime);
    $postC = $insertPost('Post C Future Scheduled %100_special', $postCSlug, 'scheduled', $futureTime);
    $postD = $insertPost('Post D Published %100_special', $postDSlug, 'published', $olderPastTime);

    echo "--- Test Fixtures Created ---\n";
    echo "  - Category ID: {$catId} ({$catSlug})\n";
    echo "  - Tag ID:      {$tagId} ({$tagSlug})\n";
    echo "  - Post A (Draft):            ID {$postA}\n";
    echo "  - Post B (Past Scheduled):   ID {$postB}\n";
    echo "  - Post C (Future Scheduled): ID {$postC}\n";
    echo "  - Post D (Published):        ID {$postD}\n\n";

    // ---------------------------------------------------------
    // TEST 1: Category Query Isolation
    // ---------------------------------------------------------
    echo "--- Test 1: Category Archive Query ---\n";
    $catResults = PostModel::getPaginatedByCategory($catId, 1, 20);
    $catItemIds = array_column($catResults['items'], 'id');

    assert(in_array($postB, $catItemIds, true), "Post B (past scheduled) must appear in category archive");
    assert(in_array($postD, $catItemIds, true), "Post D (published) must appear in category archive");
    assert(!in_array($postA, $catItemIds, true), "Post A (draft) MUST NOT appear in category archive");
    assert(!in_array($postC, $catItemIds, true), "Post C (future scheduled) MUST NOT appear in category archive");
    echo "  [PASS] Only published and past-scheduled posts appear in category archive.\n";
    echo "  [PASS] Drafts and future-scheduled posts are strictly excluded.\n\n";

    // ---------------------------------------------------------
    // TEST 2: Tag Query Isolation
    // ---------------------------------------------------------
    echo "--- Test 2: Tag Archive Query ---\n";
    $tagResults = PostModel::getPaginatedByTag($tagId, 1, 20);
    $tagItemIds = array_column($tagResults['items'], 'id');

    assert(in_array($postB, $tagItemIds, true), "Post B (past scheduled) must appear in tag archive");
    assert(in_array($postD, $tagItemIds, true), "Post D (published) must appear in tag archive");
    assert(!in_array($postA, $tagItemIds, true), "Post A (draft) MUST NOT appear in tag archive");
    assert(!in_array($postC, $tagItemIds, true), "Post C (future scheduled) MUST NOT appear in tag archive");
    echo "  [PASS] Only published and past-scheduled posts appear in tag archive.\n";
    echo "  [PASS] Drafts and future-scheduled posts are strictly excluded.\n\n";

    // ---------------------------------------------------------
    // TEST 3: Home & Category Strip Query Isolation
    // ---------------------------------------------------------
    echo "--- Test 3: Home & Category Strip Query ---\n";
    $stripPosts = PostModel::getPublishedForCategory($catId, 10);
    $stripItemIds = array_column($stripPosts, 'id');

    assert(in_array($postB, $stripItemIds, true), "Post B must appear in category strip");
    assert(in_array($postD, $stripItemIds, true), "Post D must appear in category strip");
    assert(!in_array($postA, $stripItemIds, true), "Post A (draft) MUST NOT appear in category strip");
    assert(!in_array($postC, $stripItemIds, true), "Post C (future scheduled) MUST NOT appear in category strip");
    echo "  [PASS] Category strip displays only published and past-scheduled posts.\n\n";

    // ---------------------------------------------------------
    // TEST 4: Search Query & Wildcard Escaping (% and _)
    // ---------------------------------------------------------
    echo "--- Test 4: Search Query & Wildcard Escaping ---\n";

    // 4A: Normal search term for our test posts
    $search1 = PostModel::searchPublished('Post B Past Scheduled', 1, 10);
    $s1Ids = array_column($search1['items'], 'id');
    assert(in_array($postB, $s1Ids, true), "Search matches Post B");
    assert(!in_array($postA, $s1Ids, true), "Draft Post A excluded from search");
    assert(!in_array($postC, $s1Ids, true), "Future scheduled Post C excluded from search");
    echo "  [PASS] Standard text search returns only published content.\n";

    // 4B: Literal search for % and _ characters
    // User searches for literal '%100_special'
    $search2 = PostModel::searchPublished('%100_special', 1, 10);
    $s2Ids = array_column($search2['items'], 'id');
    assert(in_array($postB, $s2Ids, true), "Search with % and _ matches Post B containing literal token");
    assert(in_array($postD, $s2Ids, true), "Search with % and _ matches Post D containing literal token");
    assert(!in_array($postA, $s2Ids, true), "Draft Post A excluded from wildcard search");
    assert(!in_array($postC, $s2Ids, true), "Future scheduled Post C excluded from wildcard search");
    echo "  [PASS] Search literally matches % and _ without treating them as SQL wildcards.\n";

    // 4C: Single wildcard '%' search should NOT match all posts in the entire database
    // Create an un-matched control post without % in title
    $postE = $insertPost('Control Post No Percent Or Underscore', 'post-e-control-' . bin2hex(random_bytes(3)), 'published', $olderPastTime);
    $searchWild = PostModel::searchPublished('%', 1, 50);
    $wildIds = array_column($searchWild['items'], 'id');
    // Post E does NOT have a literal '%' so it must NOT be matched when searching for '%'
    assert(!in_array($postE, $wildIds, true), "Search for '%' must not act as a wildcard matching posts without a literal %");
    echo "  [PASS] Searching for '%' does NOT act as SQL wildcard match-all; only posts with literal '%' are matched.\n";

    // 4D: Single wildcard '_' search should NOT match arbitrary single characters
    $searchUnder = PostModel::searchPublished('_special', 1, 50);
    $underIds = array_column($searchUnder['items'], 'id');
    // Post E does not have '_special' so it must not be matched
    assert(!in_array($postE, $underIds, true), "Search for '_' must not act as single-character wildcard matching arbitrary text");
    echo "  [PASS] Searching for '_' does NOT act as single-character SQL wildcard.\n\n";

    // ---------------------------------------------------------
    // TEST 5: Donate Setting Activation
    // ---------------------------------------------------------
    echo "--- Test 5: Donate Setting Activation Logic ---\n";
    // Test SettingModel::isDonateActive()
    $origSettings = SettingModel::getAll();

    // Clear all donate settings
    SettingModel::setMany([
        'donate_intro'        => '',
        'donate_upi_id'       => '',
        'donate_upi_qr'       => '',
        'donate_bank_details' => '',
    ]);
    SettingModel::clearCache();
    assert(SettingModel::isDonateActive() === false, "Donate must be inactive when all donation fields are empty");
    echo "  [PASS] Donate correctly inactive when all donate fields are blank.\n";

    // Set one donate field
    SettingModel::set('donate_upi_id', 'test@upi');
    SettingModel::clearCache();
    assert(SettingModel::isDonateActive() === true, "Donate must be active when UPI ID is filled");
    echo "  [PASS] Donate correctly active when at least one donate field is filled.\n";

    // Restore original donate settings
    SettingModel::setMany([
        'donate_intro'        => $origSettings['donate_intro'] ?? '',
        'donate_upi_id'       => $origSettings['donate_upi_id'] ?? '',
        'donate_upi_qr'       => $origSettings['donate_upi_qr'] ?? '',
        'donate_bank_details' => $origSettings['donate_bank_details'] ?? '',
    ]);
    SettingModel::clearCache();
    echo "  [PASS] Original settings restored.\n\n";

    // ---------------------------------------------------------
    // TEST 6: Responsive Image Attributes Helper
    // ---------------------------------------------------------
    echo "--- Test 6: ImageUploader::getResponsiveAttributes ---\n";
    $testLocation = "/uploads/2026/10/0123456789abcdef0123456789abcdef-1600.webp";
    $attrs = ImageUploader::getResponsiveAttributes($testLocation);
    assert($attrs !== null, "Attributes must not be null");
    assert(str_contains($attrs['srcset'], '400w'), "srcset must contain 400w variant");
    assert(str_contains($attrs['srcset'], '800w'), "srcset must contain 800w variant");
    assert(str_contains($attrs['srcset'], '1600w'), "srcset must contain 1600w variant");
    assert(str_contains($attrs['src'], '800.webp'), "default src should be 800 width variant");
    echo "  [PASS] ImageUploader generates correct 400w, 800w, 1600w srcset attributes.\n\n";

    // ---------------------------------------------------------
    // TEST 7: Single Post (/post/{slug}) Isolation & Relationships
    // ---------------------------------------------------------
    echo "--- Test 7: Single Post (/post/{slug}) Isolation & Relationships ---\n";

    // 7A: Draft slug returns null (triggers 404)
    $postAResult = PostModel::getBySlugPublished($postASlug);
    assert($postAResult === null, "Draft slug must return null on public query (404)");
    echo "  [PASS] Draft slug returns null (triggers 404).\n";

    // 7B: Future scheduled slug returns null (triggers 404)
    $postCResult = PostModel::getBySlugPublished($postCSlug);
    assert($postCResult === null, "Future scheduled slug must return null on public query (404)");
    echo "  [PASS] Future scheduled slug returns null (triggers 404).\n";

    // 7C: Past scheduled slug returns post (returns 200)
    $postBResult = PostModel::getBySlugPublished($postBSlug);
    assert($postBResult !== null, "Past scheduled slug must return published post row (200)");
    assert((int) $postBResult['id'] === $postB, "Past scheduled post row ID must match fixture ID");
    echo "  [PASS] Past scheduled slug returns post successfully (200).\n";

    // 7D: Published slug returns post (returns 200)
    $postDResult = PostModel::getBySlugPublished($postDSlug);
    assert($postDResult !== null, "Published slug must return post row (200)");
    assert((int) $postDResult['id'] === $postD, "Published post row ID must match fixture ID");
    echo "  [PASS] Published slug returns post successfully (200).\n";

    // 7E: Related posts never include current post or unpublished posts
    $relatedPosts = PostModel::getRelatedPosts($postB, $catId, [$tagId], 3);
    $relatedIds = array_column($relatedPosts, 'id');
    assert(!in_array($postB, $relatedIds, true), "Related posts must NEVER include current post");
    assert(!in_array($postA, $relatedIds, true), "Related posts must NEVER include draft post");
    assert(!in_array($postC, $relatedIds, true), "Related posts must NEVER include future scheduled post");
    assert(in_array($postD, $relatedIds, true), "Related posts must include published post D");
    echo "  [PASS] Related posts strictly exclude current post and unpublished posts (drafts/future-scheduled).\n";

    // 7F: Popular posts never include current post or unpublished posts
    $popularPosts = PostModel::getPopularPosts($postB, 3);
    $popularIds = array_column($popularPosts, 'id');
    assert(!in_array($postB, $popularIds, true), "Popular posts must NEVER include current post");
    assert(!in_array($postA, $popularIds, true), "Popular posts must NEVER include draft post");
    assert(!in_array($postC, $popularIds, true), "Popular posts must NEVER include future scheduled post");
    echo "  [PASS] Popular posts strictly exclude current post and unpublished posts.\n";

    // 7G: Chronological prev/next queries return only published posts
    $prevPost = PostModel::getPreviousPublishedPost($pastTime, $postB);
    assert($prevPost !== null, "Previous post query must find older published post D");
    assert((int) $prevPost['id'] === $postD, "Previous post must be published post D");
    echo "  [PASS] Previous post navigation finds chronologically earlier published post.\n\n";

    // ---------------------------------------------------------
    // TEST 8: JSON-LD Article Schema XSS Protection & Escaping
    // ---------------------------------------------------------
    echo "--- Test 8: JSON-LD Article Schema XSS Protection ---\n";
    $xssTitle = 'Test </script><script>alert(1)</script>';
    $testLd = [
        '@context' => 'https://schema.org',
        '@type'    => 'Article',
        'headline' => $xssTitle,
    ];
    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    $encodedLd = json_encode($testLd, $flags);

    assert(!str_contains($encodedLd, '</script>'), "JSON-LD output must have NO literal '</script>' inside the block");
    assert(str_contains($encodedLd, '\u003C/script\u003E'), "Script tags must be safely converted to Unicode hexadecimal escape sequences");
    echo "  [PASS] Post titled '{$xssTitle}' produces JSON-LD with no literal '</script>' inside the block.\n";

    // Test template rendering with layout
    ob_start();
    $jsonLd = $testLd;
    $content = '';
    $pageTitle = 'XSS Test';
    include APP_PATH . '/views/layouts/public.php';
    $renderedHtml = ob_get_clean();

    // Extract content inside <script type="application/ld+json">...</script>
    if (preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $renderedHtml, $matches)) {
        $innerBlock = trim($matches[1]);
        assert(!str_contains($innerBlock, '</script>'), "Rendered template JSON-LD block must not contain literal '</script>'");
        assert(str_contains($innerBlock, '\u003C/script\u003E'), "Rendered template JSON-LD block must contain escaped tags");
        echo "  [PASS] Rendered public layout contains properly escaped JSON-LD script block without breakout vulnerabilities.\n\n";
    } else {
        throw new RuntimeException("Could not find <script type=\"application/ld+json\"> block in rendered layout.");
    }

    // ---------------------------------------------------------
    // TEST 9: Design Phase D2 Home Page Data & Rendering Assertions
    // ---------------------------------------------------------
    echo "--- Test 9: Design Phase D2 Home Page Data Rules & Assertions ---\n";
    $randD2 = bin2hex(random_bytes(4));

    // 9.1 Create draft and future scheduled posts
    $draftTitle = "D2 Draft Post {$randD2}";
    $draftId = PostModel::create([
        'title'        => $draftTitle,
        'slug'         => "d2-draft-{$randD2}",
        'category_id'  => $catId,
        'status'       => 'draft',
        'content'      => '<p>Draft content</p>',
    ]);
    $createdPostIds[] = $draftId;

    $futureTitle = "D2 Future Post {$randD2}";
    $futureId = PostModel::create([
        'title'        => $futureTitle,
        'slug'         => "d2-future-{$randD2}",
        'category_id'  => $catId,
        'status'       => 'scheduled',
        'published_at' => date('Y-m-d H:i:s', time() + 86400 * 5),
        'content'      => '<p>Future content</p>',
    ]);
    $createdPostIds[] = $futureId;

    // 9.2 Create published test posts in Campaigns and RTI
    $campTitle = "D2 Campaign Post {$randD2}";
    $campCat = CategoryModel::getBySlug('campaigns');
    $campCatId = $campCat ? (int) $campCat['id'] : $catId;
    $campId = PostModel::create([
        'title'        => $campTitle,
        'slug'         => "d2-camp-{$randD2}",
        'category_id'  => $campCatId,
        'status'       => 'published',
        'published_at' => date('Y-m-d H:i:s'),
        'content'      => '<p>Campaign content</p>',
    ]);
    $createdPostIds[] = $campId;

    $rtiTitle = "D2 RTI Post {$randD2}";
    $rtiCat = CategoryModel::getBySlug('rti-applications') 
        ?? CategoryModel::getBySlug('right-to-information') 
        ?? CategoryModel::getBySlug('rti');
    $rtiCatId = $rtiCat ? (int) $rtiCat['id'] : $catId;
    $rtiId = PostModel::create([
        'title'        => $rtiTitle,
        'slug'         => "d2-rti-{$randD2}",
        'category_id'  => $rtiCatId,
        'status'       => 'published',
        'published_at' => date('Y-m-d H:i:s'),
        'content'      => '<p>RTI content</p>',
    ]);
    $createdPostIds[] = $rtiId;

    // Render home page via PublicController
    $publicCtrl = new PublicController();
    ob_start();
    $publicCtrl->home();
    $homeHtml = ob_get_clean();

    // Assertion: Drafts and future scheduled never appear
    assert(!str_contains($homeHtml, $draftTitle), "Draft posts must NEVER appear on the home page");
    assert(!str_contains($homeHtml, $futureTitle), "Future scheduled posts must NEVER appear on the home page");
    echo "  [PASS] Drafts and future scheduled posts are strictly excluded from the home page.\n";

    // Assertion: No post ID appears twice in the home page data
    $shownIds = [];
    $hero = PostModel::getFeaturedPublished();
    if ($hero) {
        $shownIds[] = (int) $hero['id'];
        $picks = PostModel::getEditorsPicks(3, $shownIds);
        foreach ($picks as $p) {
            $shownIds[] = (int) $p['id'];
        }
    }
    $latest = PostModel::getLatestPublished(3, $shownIds);
    foreach ($latest as $p) {
        $shownIds[] = (int) $p['id'];
    }
    if ($campCat) {
        $cPosts = PostModel::getPublishedForCategory((int) $campCat['id'], 3, $shownIds);
        foreach ($cPosts as $p) {
            $shownIds[] = (int) $p['id'];
        }
    }
    if ($rtiCat) {
        $rPosts = PostModel::getPublishedForCategory((int) $rtiCat['id'], 2, $shownIds);
        foreach ($rPosts as $p) {
            $shownIds[] = (int) $p['id'];
        }
    }
    assert(count($shownIds) === count(array_unique($shownIds)), "No post ID may appear twice across home page sections");
    echo "  [PASS] Top-to-bottom allocation verified: no post ID appears twice in home page data.\n";

    // Assertion: Sections with no data or disabled toggles are not rendered
    ob_start();
    \App\Core\View::render('public/home', [
        'pageTitle'          => 'Test Home',
        'metaDescription'    => 'Test Meta',
        'shownPostIds'       => [],
        'showAnnouncement'   => false,
        'announcementText'   => '',
        'announcementLink'   => '',
        'heroPost'           => null,
        'editorPicks'        => [],
        'heroHeadline'       => 'Headline',
        'heroIntro'          => 'Intro',
        'showTopics'         => false,
        'topics'             => [],
        'titleTopics'        => 'Topics',
        'showLatest'         => false,
        'latestPosts'        => [],
        'titleLatest'        => 'Latest',
        'showAboutBand'      => false,
        'siteName'           => 'NatConnect',
        'missionStatement'   => '',
        'footerAbout'        => '',
        'showCauses'         => false,
        'causes'             => [],
        'titleCauses'        => 'Causes',
        'showCampaigns'      => false,
        'campaignPosts'      => [],
        'campaignCategory'   => null,
        'titleCampaigns'     => 'Campaigns',
        'showRti'            => false,
        'rtiPosts'           => [],
        'rtiCategory'        => null,
        'titleRti'           => 'RTI',
        'introRti'           => '',
        'showGallery'        => false,
        'galleryPhotos'      => [],
        'titleGallery'       => 'Gallery',
        'showNewsletter'     => false,
        'titleNewsletter'    => 'Newsletter',
        'introNewsletter'    => '',
        'showDonate'         => false,
        'titleDonate'        => 'Donate',
        'textDonate'         => '',
    ], 'layouts/public');
    $emptyRenderHtml = ob_get_clean();

    assert(!str_contains($emptyRenderHtml, 'id="hero-heading"'), "Hero section must not render when hero post is absent");
    assert(!str_contains($emptyRenderHtml, 'id="topics-heading"'), "Topics section must not render when topics are empty");
    assert(!str_contains($emptyRenderHtml, 'id="latest-heading"'), "Latest stories section must not render when empty");
    assert(!str_contains($emptyRenderHtml, 'id="about-band-heading"'), "About band must not render when mission statement is empty");
    assert(!str_contains($emptyRenderHtml, 'id="causes-heading"'), "Causes section must not render when causes are empty");
    assert(!str_contains($emptyRenderHtml, 'id="campaigns-heading"'), "Campaigns section must not render when campaign posts are empty");
    assert(!str_contains($emptyRenderHtml, 'id="rti-heading"'), "RTI section must not render when RTI posts are empty");
    assert(!str_contains($emptyRenderHtml, 'id="gallery-heading"'), "Gallery section must not render when gallery is empty");
    assert(!str_contains($emptyRenderHtml, 'id="donate-band-heading"'), "Donate band must not render when donate is inactive");
    echo "  [PASS] Sections with no data or disabled toggles are strictly excluded from rendering.\n\n";

    // --- Test 10: Launch Check 'Site address is set correctly' ---
    echo "--- Test 10: Launch Check 'Site address is set correctly' ---\n";
    assert(!LaunchCheck::isSiteAddressValid('http://127.0.0.1:8000'), "Site address must fail for 127.0.0.1 or http");
    assert(!LaunchCheck::isSiteAddressValid('http://natconnectfoundation.org'), "Site address must fail for non-https");
    assert(!LaunchCheck::isSiteAddressValid('https://localhost'), "Site address must fail for localhost");
    assert(!LaunchCheck::isSiteAddressValid('https://natconnectfoundation.org:8443'), "Site address must fail if port is present");
    assert(LaunchCheck::isSiteAddressValid('https://natconnectfoundation.org'), "Site address must pass for clean live https URL");

    $oldServerHost = $_SERVER['HTTP_HOST'] ?? null;
    $_SERVER['HTTP_HOST'] = 'natconnectfoundation.org';
    assert(LaunchCheck::isSiteAddressValid('https://natconnectfoundation.org'), "Host match check must pass when request host matches");
    $_SERVER['HTTP_HOST'] = 'otherdomain.org';
    assert(!LaunchCheck::isSiteAddressValid('https://natconnectfoundation.org'), "Host match check must fail when request host does not match");
    if ($oldServerHost !== null) {
        $_SERVER['HTTP_HOST'] = $oldServerHost;
    } else {
        unset($_SERVER['HTTP_HOST']);
    }

    $allChecks = LaunchCheck::runAll();
    $siteAddrCheck = null;
    foreach ($allChecks['checks'] as $chk) {
        if ($chk['key'] === 'site_address') {
            $siteAddrCheck = $chk;
            break;
        }
    }
    assert($siteAddrCheck !== null, "Site address check must be included in LaunchCheck::runAll()");
    assert(in_array("Set 'url' in config.local.php to the live domain", $siteAddrCheck['details'], true) || $siteAddrCheck['passed'], "Failed site address check must suggest fixing 'url' in config.local.php");
    echo "  [PASS] Site address launch check rules and fix recommendation verified.\n\n";

    echo "========================================================\n";
    echo "ALL PUBLIC, JSON-LD, D2 HOME & LAUNCH CHECK ASSERTIONS PASSED!\n";
    echo "========================================================\n";

} catch (Throwable $e) {
    echo "\n[FAIL] Assertion / Test Error: " . $e->getMessage() . "\n";
    echo "In " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
