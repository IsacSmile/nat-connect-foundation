<?php
declare(strict_types=1);

// These checks use assert(). With zend.assertions=-1 (the default in many php.ini files)
// every assert() is compiled out and the suite would print PASS without checking anything.
if (ini_get('zend.assertions') !== '1') {
    fwrite(STDERR, "[ERROR] Assertions are disabled. Run: php -d zend.assertions=1 " . basename(__FILE__) . "\n");
    exit(1);
}

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
define('APP_PATH', ROOT_PATH . '/public_html/app');

if (file_exists(ROOT_PATH . '/public_html/vendor/autoload.php')) {
    require_once ROOT_PATH . '/public_html/vendor/autoload.php';
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

    $insertPost = function (string $title, string $slug, string $status, ?string $pubAt, ?string $content = null) use ($pdo, $catId, $tagId, &$createdPostIds) {
        $stmt = $pdo->prepare("
            INSERT INTO posts (title, slug, content, excerpt, category_id, status, published_at, author_name)
            VALUES (:title, :slug, :content, :excerpt, :catId, :status, :pubAt, 'Test Author')
        ");
        $stmt->execute([
            'title'   => $title,
            'slug'    => $slug,
            'content' => $content ?? "<p>Content for {$title} with literal %100_special token.</p>",
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
    $postE = $insertPost('Control Post No Percent Or Underscore', 'post-e-control-' . bin2hex(random_bytes(3)), 'published', date('Y-m-d H:i:s', $now - 10800), '<p>Control content with no special characters.</p>');
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
    $sliderPosts = PostModel::getSliderPosts(5);
    foreach ($sliderPosts as $p) {
        $shownIds[] = (int) $p['id'];
    }
    $mostRead = PostModel::getMostRead(3, $shownIds);
    foreach ($mostRead as $p) {
        $shownIds[] = (int) $p['id'];
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
        'sliderPosts'        => [],
        'mostReadPosts'      => [],
        'sliderAutoRotate'   => false,
        'heroHeadline'       => 'Headline',
        'heroIntro'          => 'Intro',
        'isDonateActive'     => false,
        'impactNumbers'      => [],
        'showGetInvolved'    => false,
        'getInvolvedTitle'   => 'Get involved',
        'getInvolvedCards'   => [],
        'showTopics'         => false,
        'topics'             => [],
        'titleTopics'        => 'Topics',
        'showLatest'         => false,
        'latestPosts'        => [],
        'titleLatest'        => 'Latest',
        'showAboutBand'      => false,
        'aboutBandPhoto'     => '',
        'aboutBandPhotoAlt'  => '',
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

    assert(!str_contains($emptyRenderHtml, 'id="hero-heading"'), "Hero section must not render when slider posts are absent");
    assert(!str_contains($emptyRenderHtml, 'id="impact-numbers"'), "Impact strip must not render when impact numbers are empty");
    assert(!str_contains($emptyRenderHtml, '<section id="get-involved"'), "Get involved section must not render when showGetInvolved is false");
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

    // --- Test 10b: Launch Check 'Security salt is properly configured' ---
    echo "--- Test 10b: Launch Check 'Security salt is properly configured' ---\n";
    assert(!LaunchCheck::isSaltValid('CHANGE_ME_generate_with_openssl_rand_hex_32'), "isSaltValid must fail for CHANGE_ME placeholder");
    assert(!LaunchCheck::isSaltValid('replace-with-a-random-64-char-string-in-config.local.php'), "isSaltValid must fail for template default");
    assert(!LaunchCheck::isSaltValid('short-salt'), "isSaltValid must fail for salt < 32 chars");
    assert(LaunchCheck::isSaltValid('c089d71c183cf99db4918e7e31ef408a99478f6916a0322bf20b411d7eb6bca3'), "isSaltValid must pass for 32+ char salt");

    $saltCheck = null;
    foreach ($allChecks['checks'] as $chk) {
        if ($chk['key'] === 'security_salt') {
            $saltCheck = $chk;
            break;
        }
    }
    assert($saltCheck !== null, "Security salt check must be included in LaunchCheck::runAll()");
    assert($saltCheck['passed'] === true, "Active test environment security salt must pass");
    echo "  [PASS] Security salt launch check rules and placeholder rejection verified.\n\n";

    // --- Test 11: Home Page Round 2 Assertions ---
    echo "--- Test 11: Home Page Round 2 Assertions (Hero Slider, Impact Strip, WhatsApp) ---\n";

    // 11.1 Slider uses picks with fallback (<2 picks => newest posts)
    echo "  - Subtest 11.1: Slider uses picks when >=2 exist, falls back to newest when <2\n";
    $stmtOrigPicks = $pdo->query("SELECT id FROM posts WHERE is_pick = 1");
    $origPickIds = $stmtOrigPicks->fetchAll(\PDO::FETCH_COLUMN);

    // Case A: 0 picks -> fallback to newest published posts
    $pdo->exec("UPDATE posts SET is_pick = 0");
    $fallbackPosts = PostModel::getSliderPosts(5);
    assert(!empty($fallbackPosts), "Fallback slider must return published posts");
    assert(count($fallbackPosts) <= 5, "Fallback slider respects limit of 5");
    for ($i = 0; $i < count($fallbackPosts) - 1; $i++) {
        $t1 = strtotime($fallbackPosts[$i]['published_at'] ?? $fallbackPosts[$i]['created_at']);
        $t2 = strtotime($fallbackPosts[$i + 1]['published_at'] ?? $fallbackPosts[$i + 1]['created_at']);
        assert($t1 >= $t2, "Fallback slider posts must be sorted newest first");
    }

    // Case B: Exactly 1 pick -> still <2 picks, so must fallback to newest posts
    $pdo->prepare("UPDATE posts SET is_pick = 1 WHERE id = :id")->execute(['id' => $postD]);
    $onePickSlider = PostModel::getSliderPosts(5);
    assert(!empty($onePickSlider), "Slider with 1 pick should fall back to newest posts");
    assert(count($onePickSlider) >= 2 || count($fallbackPosts) < 2, "Slider with 1 pick must not be restricted to only 1 slide if more posts exist");

    // Case C: >=2 picks -> must use editor picks
    $pdo->prepare("UPDATE posts SET is_pick = 1 WHERE id = :id")->execute(['id' => $postB]);
    $picksSlider = PostModel::getSliderPosts(5);
    $pickSliderIds = array_column($picksSlider, 'id');
    assert(in_array($postB, $pickSliderIds, true), "Slider must include pick post B");
    assert(in_array($postD, $pickSliderIds, true), "Slider must include pick post D");
    foreach ($picksSlider as $sp) {
        assert((int) $sp['is_pick'] === 1, "When >=2 picks exist, slider must contain only pick posts");
    }
    echo "    [PASS] Slider correctly uses picks when >=2 exist and falls back to newest posts when <2.\n";

    // Restore original picks
    $pdo->exec("UPDATE posts SET is_pick = 0");
    if (!empty($origPickIds)) {
        $restoreStmt = $pdo->prepare("UPDATE posts SET is_pick = 1 WHERE id = :id");
        foreach ($origPickIds as $pId) {
            $restoreStmt->execute(['id' => $pId]);
        }
    }

    // 11.2 No post repeats across strip / slider / most read / campaigns / RTI
    echo "  - Subtest 11.2: Strict no-repeat data allocation across all home sections\n";
    $round2ShownIds = [];
    $stripCheck = PostModel::getLatestPublished(4);
    foreach ($stripCheck as $p) {
        $round2ShownIds[] = (int) $p['id'];
    }
    $sliderCheck = PostModel::getSliderPosts(5, $round2ShownIds);
    foreach ($sliderCheck as $p) {
        $round2ShownIds[] = (int) $p['id'];
    }
    $mostReadCheck = PostModel::getMostRead(3, $round2ShownIds);
    foreach ($mostReadCheck as $p) {
        $round2ShownIds[] = (int) $p['id'];
    }
    if ($campCat) {
        $campCheck = PostModel::getPublishedForCategory((int) $campCat['id'], 3, $round2ShownIds);
        foreach ($campCheck as $p) {
            $round2ShownIds[] = (int) $p['id'];
        }
    }
    if ($rtiCat) {
        $rtiCheck = PostModel::getPublishedForCategory((int) $rtiCat['id'], 2, $round2ShownIds);
        foreach ($rtiCheck as $p) {
            $round2ShownIds[] = (int) $p['id'];
        }
    }
    assert(count($round2ShownIds) === count(array_unique($round2ShownIds)), "Strict no-repeat: post IDs must be unique across all 5 sections");
    echo "    [PASS] No post repeats across strip, slider, most read, campaigns, and RTI.\n";

    // 11.3 Impact strip hidden when empty, visible when populated
    echo "  - Subtest 11.3: Impact strip hidden when empty and visible when populated\n";
    ob_start();
    \App\Core\View::render('public/home', [
        'pageTitle'          => 'Test Home',
        'metaDescription'    => '',
        'shownPostIds'       => [],
        'showAnnouncement'   => false,
        'announcementText'   => '',
        'announcementLink'   => '',
        'sliderPosts'        => $sliderCheck,
        'mostReadPosts'      => [],
        'sliderAutoRotate'   => false,
        'heroHeadline'       => 'Headline',
        'heroIntro'          => 'Intro',
        'isDonateActive'     => false,
        'impactNumbers'      => [], // EMPTY
        'showGetInvolved'    => false,
        'getInvolvedTitle'   => 'Get involved',
        'getInvolvedCards'   => [],
        'showTopics'         => false,
        'topics'             => [],
        'titleTopics'        => 'Topics',
        'showLatest'         => false,
        'latestPosts'        => [],
        'titleLatest'        => 'Latest',
        'showAboutBand'      => false,
        'aboutBandPhoto'     => '',
        'aboutBandPhotoAlt'  => '',
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
    $htmlNoImpact = ob_get_clean();
    assert(!str_contains($htmlNoImpact, 'id="impact-numbers"'), "Impact numbers strip must NOT be rendered when empty");

    ob_start();
    \App\Core\View::render('public/home', [
        'pageTitle'          => 'Test Home',
        'metaDescription'    => '',
        'shownPostIds'       => [],
        'showAnnouncement'   => false,
        'announcementText'   => '',
        'announcementLink'   => '',
        'sliderPosts'        => $sliderCheck,
        'mostReadPosts'      => [],
        'sliderAutoRotate'   => false,
        'heroHeadline'       => 'Headline',
        'heroIntro'          => 'Intro',
        'isDonateActive'     => false,
        'impactNumbers'      => [
            ['number' => '10+', 'label' => 'Years of Advocacy'],
            ['number' => '500+', 'label' => 'RTI Applications'],
        ],
        'showGetInvolved'    => false,
        'getInvolvedTitle'   => 'Get involved',
        'getInvolvedCards'   => [],
        'showTopics'         => false,
        'topics'             => [],
        'titleTopics'        => 'Topics',
        'showLatest'         => false,
        'latestPosts'        => [],
        'titleLatest'        => 'Latest',
        'showAboutBand'      => false,
        'aboutBandPhoto'     => '',
        'aboutBandPhotoAlt'  => '',
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
    $htmlWithImpact = ob_get_clean();
    assert(str_contains($htmlWithImpact, 'id="impact-numbers"'), "Impact numbers strip MUST be rendered when populated");
    assert(str_contains($htmlWithImpact, '10+'), "Impact numbers must contain number text '10+'");
    assert(str_contains($htmlWithImpact, 'Years of Advocacy'), "Impact numbers must contain label 'Years of Advocacy'");
    echo "    [PASS] Impact strip correctly hidden when empty and rendered when populated.\n";

    // 11.4 WhatsApp URL builder with encoding
    echo "  - Subtest 11.4: WhatsApp URL builder and encoded message\n";
    $waUrl1 = buildWhatsAppUrl('+91 (987) 654-3210', 'Hello, I want to volunteer! & participate');
    assert($waUrl1 === 'https://wa.me/919876543210?text=Hello%2C%20I%20want%20to%20volunteer%21%20%26%20participate', "WhatsApp URL must strip non-digits and URL-encode message");

    $waUrl2 = buildWhatsAppUrl('9876543210');
    assert($waUrl2 === 'https://wa.me/9876543210', "WhatsApp URL with empty message must omit ?text parameter");

    $waUrl3 = buildWhatsAppUrl('', 'Test message');
    assert($waUrl3 === '/contact', "WhatsApp URL with empty phone must fall back to /contact");

    $origWaSetting = SettingModel::get('contact_whatsapp');
    SettingModel::set('contact_whatsapp', '+91 99999 88888');
    SettingModel::clearCache();

    $resolvedWa = resolveCardLink('whatsapp', 'Hi! I want to share a story.');
    assert(str_starts_with($resolvedWa, 'https://wa.me/919999988888?text='), "resolveCardLink('whatsapp') must build wa.me URL");
    assert(str_contains($resolvedWa, 'Hi%21%20I%20want%20to%20share%20a%20story.'), "resolveCardLink('whatsapp') must encode prefilled message");

    $resolvedPath = resolveCardLink('/contact');
    assert($resolvedPath === '/contact', "resolveCardLink should return path as-is");

    $resolvedExternal = resolveCardLink('https://example.org/volunteer');
    assert($resolvedExternal === 'https://example.org/volunteer', "resolveCardLink should return external URL as-is");

    // Restore original setting
    SettingModel::set('contact_whatsapp', $origWaSetting ?? '');
    SettingModel::clearCache();
    echo "    [PASS] WhatsApp URL builder produces valid wa.me URLs with URL-encoded messages and safe fallback.\n\n";

    // ---------------------------------------------------------
    // TEST 12: Causes Page, Navigation & Tag Archive Assertions
    // ---------------------------------------------------------
    echo "--- Test 12: Causes Page, Navigation & Tag Archive Assertions ---\n";

    // 12.1 Create cause and non-cause tags
    $randCause = bin2hex(random_bytes(3));
    $causeTag1Name = "Mangrove Protection {$randCause}";
    $causeTag1Slug = "mangrove-protection-{$randCause}";
    $causeTag1Desc = "Protecting critical coastal mangrove systems across the region.";
    $causeTag1Img  = "/uploads/2026/10/mangrove-test-1600.webp";

    $stmt = $pdo->prepare("
        INSERT INTO tags (name, slug, description, image_path, image_alt, show_as_topic, show_as_cause, sort_order)
        VALUES (:name, :slug, :description, :image_path, :image_alt, 0, 1, 10)
    ");
    $stmt->execute([
        'name'        => $causeTag1Name,
        'slug'        => $causeTag1Slug,
        'description' => $causeTag1Desc,
        'image_path'  => $causeTag1Img,
        'image_alt'   => 'Mangrove wetlands landscape',
    ]);
    $causeTag1Id = (int) $pdo->lastInsertId();
    $createdTagIds[] = $causeTag1Id;

    $causeTag2Name = "Wetland Conservation {$randCause}";
    $causeTag2Slug = "wetland-conservation-{$randCause}";
    $causeTag2Desc = "Preserving natural wetland habitats and migratory bird grounds.";
    $causeTag2Img  = "/uploads/2026/10/wetland-test-1600.webp";

    $stmt->execute([
        'name'        => $causeTag2Name,
        'slug'        => $causeTag2Slug,
        'description' => $causeTag2Desc,
        'image_path'  => $causeTag2Img,
        'image_alt'   => 'Wetlands aerial view',
    ]);
    $causeTag2Id = (int) $pdo->lastInsertId();
    $createdTagIds[] = $causeTag2Id;

    $nonCauseName = "General News Tag {$randCause}";
    $nonCauseSlug = "general-news-{$randCause}";
    $stmtNonCause = $pdo->prepare("
        INSERT INTO tags (name, slug, description, image_path, image_alt, show_as_topic, show_as_cause, sort_order)
        VALUES (:name, :slug, 'A general tag', NULL, NULL, 0, 0, 99)
    ");
    $stmtNonCause->execute([
        'name' => $nonCauseName,
        'slug' => $nonCauseSlug,
    ]);
    $nonCauseId = (int) $pdo->lastInsertId();
    $createdTagIds[] = $nonCauseId;

    // 12.2 /causes lists all causes and respects custom title and intro
    echo "  - Subtest 12.2: /causes route lists all causes with title and optional intro\n";
    $origCausesTitle = SettingModel::get('section_title_causes');
    $origCausesIntro = SettingModel::get('causes_intro');
    SettingModel::set('section_title_causes', "Environmental Causes We Champion {$randCause}");
    SettingModel::set('causes_intro', "Dedicated advocacy initiatives by NatConnect Foundation {$randCause}.");
    SettingModel::clearCache();

    $causesList = TagModel::getCauses();
    $causeIdsList = array_column($causesList, 'id');
    assert(in_array($causeTag1Id, $causeIdsList, true), "Cause Tag 1 must be present in TagModel::getCauses()");
    assert(in_array($causeTag2Id, $causeIdsList, true), "Cause Tag 2 must be present in TagModel::getCauses()");
    assert(!in_array($nonCauseId, $causeIdsList, true), "Non-cause tag must NOT be present in TagModel::getCauses()");

    // Verify ordering by sort_order
    for ($i = 0; $i < count($causesList) - 1; $i++) {
        assert((int) $causesList[$i]['sort_order'] <= (int) $causesList[$i + 1]['sort_order'], "Causes must be ordered by sort_order ASC");
    }

    ob_start();
    $publicCtrl->causes();
    $causesHtml = ob_get_clean();

    assert(str_contains($causesHtml, "Environmental Causes We Champion {$randCause}"), "Page heading must use section_title_causes setting");
    assert(str_contains($causesHtml, "Dedicated advocacy initiatives by NatConnect Foundation {$randCause}."), "Page must display optional causes_intro setting");
    assert(str_contains($causesHtml, $causeTag1Name), "Cause Tag 1 must be listed on /causes page");
    assert(str_contains($causesHtml, $causeTag2Name), "Cause Tag 2 must be listed on /causes page");
    assert(!str_contains($causesHtml, $nonCauseName), "Non-cause tag must NOT be listed on /causes page");
    echo "    [PASS] /causes correctly lists all causes, honors custom title and intro, and orders by sort_order.\n";

    // Restore original settings
    SettingModel::set('section_title_causes', $origCausesTitle ?? 'Causes we care about');
    SettingModel::set('causes_intro', $origCausesIntro ?? '');
    SettingModel::clearCache();

    // 12.3 Header Navigation: Causes links to /causes instead of /category/causes
    echo "  - Subtest 12.3: Header navigation links 'Causes' to /causes\n";
    ob_start();
    \App\Core\View::partial('partials/header');
    $headerHtml = ob_get_clean();
    assert(str_contains($headerHtml, 'href="/causes"'), "Header nav must link Causes to /causes");
    assert(!str_contains($headerHtml, 'href="/category/causes"'), "Header nav must NOT link to /category/causes");
    echo "    [PASS] Header navigation links 'Causes' to /causes instead of /category/causes.\n";

    // 12.4 Cause tag page: show cause image and description at the top, drafts never appear
    echo "  - Subtest 12.4: Cause tag page displays image & description; drafts and future posts are excluded\n";
    $causePubTitle = "Published Story for Cause {$randCause}";
    $causePubId = PostModel::create([
        'title'        => $causePubTitle,
        'slug'         => "cause-pub-{$randCause}",
        'category_id'  => $catId,
        'status'       => 'published',
        'published_at' => date('Y-m-d H:i:s', time() - 3600),
        'content'      => '<p>Published cause content</p>',
    ]);
    $createdPostIds[] = $causePubId;
    $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (:pid, :tid)")->execute(['pid' => $causePubId, 'tid' => $causeTag1Id]);

    $causeDraftTitle = "Draft Story for Cause {$randCause}";
    $causeDraftId = PostModel::create([
        'title'        => $causeDraftTitle,
        'slug'         => "cause-draft-{$randCause}",
        'category_id'  => $catId,
        'status'       => 'draft',
        'content'      => '<p>Draft cause content</p>',
    ]);
    $createdPostIds[] = $causeDraftId;
    $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (:pid, :tid)")->execute(['pid' => $causeDraftId, 'tid' => $causeTag1Id]);

    $causeFutureTitle = "Future Story for Cause {$randCause}";
    $causeFutureId = PostModel::create([
        'title'        => $causeFutureTitle,
        'slug'         => "cause-future-{$randCause}",
        'category_id'  => $catId,
        'status'       => 'scheduled',
        'published_at' => date('Y-m-d H:i:s', time() + 86400 * 3),
        'content'      => '<p>Future cause content</p>',
    ]);
    $createdPostIds[] = $causeFutureId;
    $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (:pid, :tid)")->execute(['pid' => $causeFutureId, 'tid' => $causeTag1Id]);

    ob_start();
    $publicCtrl->tag(['slug' => $causeTag1Slug]);
    $tagPageHtml = ob_get_clean();

    assert(str_contains($tagPageHtml, $causeTag1Name), "Cause tag page must display cause name");
    assert(str_contains($tagPageHtml, $causeTag1Desc), "Cause tag page must display cause description at top");
    assert(str_contains($tagPageHtml, 'mangrove-test-1600.webp') || str_contains($tagPageHtml, 'mangrove-test'), "Cause tag page must display cause image at top");
    assert(str_contains($tagPageHtml, $causePubTitle), "Published post must appear on cause tag page");
    assert(!str_contains($tagPageHtml, $causeDraftTitle), "Draft post must NEVER appear on cause tag page");
    assert(!str_contains($tagPageHtml, $causeFutureTitle), "Future scheduled post must NEVER appear on cause tag page");
    echo "    [PASS] Cause tag page displays cause image and description at the top; drafts & future posts are strictly excluded.\n\n";

    // ---------------------------------------------------------
    // TEST 13: Home Page Latest Strip Assertions
    // ---------------------------------------------------------
    echo "--- Test 13: Home Page Latest Strip Assertions ---\n";

    // 13.1 Strip shows the 4 newest effectively published posts
    echo "  - Subtest 13.1: Strip shows the 4 newest effectively published posts\n";
    $latest4 = PostModel::getLatestPublished(4);
    assert(count($latest4) <= 4, "Strip cannot contain more than 4 posts");
    assert(count($latest4) >= 2, "Strip requires at least 2 published posts to display");
    for ($i = 0; $i < count($latest4) - 1; $i++) {
        $t1 = strtotime($latest4[$i]['published_at'] ?? $latest4[$i]['created_at']);
        $t2 = strtotime($latest4[$i + 1]['published_at'] ?? $latest4[$i + 1]['created_at']);
        assert($t1 >= $t2, "Strip posts must be ordered newest first");
    }
    echo "    [PASS] Strip correctly returns the 4 newest effectively published posts.\n";

    // 13.2 Slider still has >= 2 slides when picks overlap the strip
    echo "  - Subtest 13.2: Slider has >= 2 slides when picks overlap the strip\n";
    // Mark the 4 strip posts as picks so all picks are in the strip!
    $stripIds = array_column($latest4, 'id');
    $pdo->exec("UPDATE posts SET is_pick = 0");
    $setPickStmt = $pdo->prepare("UPDATE posts SET is_pick = 1 WHERE id = :id");
    foreach ($stripIds as $sid) {
        $setPickStmt->execute(['id' => $sid]);
    }
    // Slider excluding strip must fall back to next newest published posts so it still has >= 2 slides
    $sliderAfterOverlap = PostModel::getSliderPosts(5, $stripIds);
    assert(count($sliderAfterOverlap) >= 2, "Slider must still have >= 2 slides when picks overlap the strip");
    $sliderOverlapIds = array_column($sliderAfterOverlap, 'id');
    assert(empty(array_intersect($sliderOverlapIds, $stripIds)), "Slider must NEVER repeat any post shown in the strip");
    echo "    [PASS] Slider maintains >= 2 slides without duplicating any strip post when picks overlap the strip.\n";

    // Restore picks
    $pdo->exec("UPDATE posts SET is_pick = 0");
    if (!empty($origPickIds)) {
        $restoreStmt = $pdo->prepare("UPDATE posts SET is_pick = 1 WHERE id = :id");
        foreach ($origPickIds as $pId) {
            $restoreStmt->execute(['id' => $pId]);
        }
    }

    // 13.3 Strict no-repeat data allocation: strip -> slider -> most read -> campaigns -> RTI
    echo "  - Subtest 13.3: Strict no-repeat allocation order: strip -> slider -> most read -> campaigns -> RTI\n";
    $allAllocatedIds = [];
    $stripAlloc = PostModel::getLatestPublished(4);
    foreach ($stripAlloc as $p) {
        $allAllocatedIds[] = (int) $p['id'];
    }
    $sliderAlloc = PostModel::getSliderPosts(5, $allAllocatedIds);
    foreach ($sliderAlloc as $p) {
        $allAllocatedIds[] = (int) $p['id'];
    }
    $mostReadAlloc = PostModel::getMostRead(3, $allAllocatedIds);
    foreach ($mostReadAlloc as $p) {
        $allAllocatedIds[] = (int) $p['id'];
    }
    if ($campCat) {
        $campAlloc = PostModel::getPublishedForCategory((int) $campCat['id'], 3, $allAllocatedIds);
        foreach ($campAlloc as $p) {
            $allAllocatedIds[] = (int) $p['id'];
        }
    }
    if ($rtiCat) {
        $rtiAlloc = PostModel::getPublishedForCategory((int) $rtiCat['id'], 2, $allAllocatedIds);
        foreach ($rtiAlloc as $p) {
            $allAllocatedIds[] = (int) $p['id'];
        }
    }
    assert(count($allAllocatedIds) === count(array_unique($allAllocatedIds)), "Zero repeats: post IDs must be strictly unique across strip, slider, most read, campaigns, RTI");
    echo "    [PASS] No post repeats across strip, slider, most read, campaigns, and RTI.\n";

    // 13.4 Strip hidden when its toggle is off and rendered when on
    echo "  - Subtest 13.4: Strip hidden when its toggle is off and rendered when on\n";
    // The admin toggle saves both keys; the home controller reads section_show_latest_strip first.
    $origToggle = SettingModel::get('section_show_latest');
    $origStripToggle = SettingModel::get('section_show_latest_strip');
    SettingModel::set('section_show_latest', '0');
    SettingModel::set('section_show_latest_strip', '0');
    SettingModel::clearCache();

    ob_start();
    $publicCtrl->home();
    $htmlStripOff = ob_get_clean();
    assert(!str_contains($htmlStripOff, 'aria-label="Latest stories"'), "Latest strip MUST be hidden when toggle is off");

    SettingModel::set('section_show_latest', '1');
    SettingModel::set('section_show_latest_strip', '1');
    SettingModel::clearCache();

    ob_start();
    $publicCtrl->home();
    $htmlStripOn = ob_get_clean();
    assert(str_contains($htmlStripOn, 'aria-label="Latest stories"'), "Latest strip MUST be visible when toggle is on and >=2 posts exist");

    echo "    [PASS] Latest strip is hidden when its toggle is off and rendered when on.\n";

    // Restore toggle
    SettingModel::set('section_show_latest', $origToggle ?? '1');
    SettingModel::set('section_show_latest_strip', $origStripToggle ?? '1');
    SettingModel::clearCache();
    // 14. Home Page: About Band & Get Involved Redesign Assertions
    echo "--- Test 14: About Band & Get Involved Redesign Assertions ---\n";
    
    // Subtest 14.1: Backgrounds, padding & eyebrow removal
    echo "  - Subtest 14.1: Backgrounds, vertical padding & eyebrow removal\n";
    ob_start();
    $publicCtrl->home();
    $homeHtml = ob_get_clean();

    assert(!str_contains($homeHtml, 'Take Action'), "Get Involved section must NOT contain hardcoded 'Take Action' eyebrow");
    assert(preg_match('/<section[^>]*id="get-involved"[^>]*class="[^"]*py-16 lg:py-20[^"]*"/', $homeHtml) === 1, "Get Involved must use py-16 lg:py-20 vertical padding");
    assert(preg_match('/<section[^>]*id="get-involved"[^>]*class="[^"]*bg-accent\/5[^"]*"/', $homeHtml) === 0, "Get Involved must use plain page background (never bg-accent/5)");
    assert(preg_match('/<section[^>]*class="[^"]*bg-accent\/5[^"]*py-16 lg:py-20[^"]*"[^>]*aria-labelledby="about-band-heading"/', $homeHtml) === 1, "About band must keep bg-accent/5 and py-16 lg:py-20");
    echo "    [PASS] Get involved uses plain background and py-16 lg:py-20 with no 'Take Action' eyebrow; About band keeps bg-accent/5.\n";

    // Subtest 14.2: Get Involved actions as stacked rows without filled primary buttons
    echo "  - Subtest 14.2: Get Involved actions as stacked rows without filled primary buttons\n";
    // Check that Get Involved section contains divide-y and no filled primary buttons
    $getInvolvedPos = strpos($homeHtml, '<section id="get-involved"');
    $aboutBandPos = strpos($homeHtml, 'aria-labelledby="about-band-heading"');
    assert($getInvolvedPos !== false && $aboutBandPos !== false, "Both Get Involved and About band sections must exist on home page");
    $getInvolvedHtml = substr($homeHtml, $getInvolvedPos, $aboutBandPos - $getInvolvedPos);

    assert(str_contains($getInvolvedHtml, 'divide-y divide-ink/10'), "Get Involved must feature hairline dividers between rows (divide-y divide-ink/10)");
    assert(str_contains($getInvolvedHtml, 'w-11 h-11 rounded-full bg-accent/10'), "Each action row must feature a 44px icon in a bg-accent/10 circle");
    assert(!str_contains($getInvolvedHtml, 'bg-primary hover:bg-primary-hover'), "No filled primary buttons in Get Involved section");
    assert(str_contains($getInvolvedHtml, 'text-accent-strong group-hover:text-accent-hover'), "Actions must use text link style CTAs");
    echo "    [PASS] Actions render as stacked rows with hairline dividers, 44px icon circles, and text CTAs with zero filled buttons.\n";

    // Subtest 14.3: Card texts avoid claims about investigating or editorial teams
    echo "  - Subtest 14.3: Plain copy card texts in database\n";
    $c1Text = SettingModel::get('home_card_1_text');
    $c2Text = SettingModel::get('home_card_2_text');
    $c3Text = SettingModel::get('home_card_3_text');
    assert(!str_contains(strtolower($c2Text), 'investigate'), "Card 2 must not make claims about investigating");
    assert(!str_contains(strtolower($c3Text), 'editorial team'), "Card 3 must not make claims about editorial teams");
    assert($c1Text === 'Give your time and skills to campaigns, drives and awareness work.', "Card 1 text must match updated plain copy");
    assert($c2Text === 'Seen damage to a wetland, mangrove or trees in your area? Tell us what you saw.', "Card 2 text must match updated plain copy");
    assert($c3Text === 'Have a local environmental story, photo or observation? Send it to us.', "Card 3 text must match updated plain copy");
    echo "    [PASS] Card texts strictly use plain copy with no claims about investigating or editorial teams.\n";

    // Subtest 14.4: About band text block hiding when empty
    echo "  - Subtest 14.4: About band empty text block suppression\n";
    $origFooterAbout = SettingModel::get('footer_about');
    SettingModel::set('footer_about', '');
    SettingModel::clearCache();

    ob_start();
    $publicCtrl->home();
    $homeHtmlNoAboutText = ob_get_clean();

    $aboutPos = strpos($homeHtmlNoAboutText, 'aria-labelledby="about-band-heading"');
    $causesPos = strpos($homeHtmlNoAboutText, 'aria-labelledby="causes-heading"');
    $aboutBandHtml = substr($homeHtmlNoAboutText, $aboutPos, ($causesPos !== false ? $causesPos : strlen($homeHtmlNoAboutText)) - $aboutPos);

    // Verify lone short line / fallback paragraph is NOT rendered
    assert(!str_contains($aboutBandHtml, 'Dedicated to grassroots environmental activism'), "When about text is empty, no fallback text block should be shown");
    echo "    [PASS] When about text is empty, right-side text block is cleanly omitted.\n\n";

    // Restore footer_about
    SettingModel::set('footer_about', $origFooterAbout ?? '');
    SettingModel::clearCache();

    // ---------------------------------------------------------
    // TEST 15: Fresh-Install Defaults & Footer Column Suppression
    // ---------------------------------------------------------
    echo "--- Test 15: Fresh-Install Defaults & Footer Column Suppression ---\n";

    // 15.1 Check seed.sql and install.sql for reviewed copy defaults
    echo "  - Subtest 15.1: seed.sql and install.sql contain exact reviewed default copy\n";
    $seedSqlPath = ROOT_PATH . '/public_html/database/seed.sql';
    $installSqlPath = ROOT_PATH . '/public_html/database/install.sql';

    assert(file_exists($seedSqlPath), "seed.sql must exist");
    assert(file_exists($installSqlPath), "install.sql must exist");

    $seedSql = file_get_contents($seedSqlPath);
    $installSql = file_get_contents($installSqlPath);

    $expectedNewsletterIntro = "Sign up to hear about our campaigns and stories.";
    $expectedCard1Text = "Give your time and skills to campaigns, drives and awareness work.";
    $expectedCard2Text = "Seen damage to a wetland, mangrove or trees in your area? Tell us what you saw.";
    $expectedCard3Text = "Have a local environmental story, photo or observation? Send it to us.";

    // Newsletter intro: must not promise email delivery or frequency
    assert(str_contains($seedSql, "'section_intro_newsletter', '{$expectedNewsletterIntro}'"), "seed.sql must contain exact newsletter intro");
    assert(str_contains($installSql, "'section_intro_newsletter', '{$expectedNewsletterIntro}'"), "install.sql must contain exact newsletter intro");
    assert(!str_contains(strtolower($expectedNewsletterIntro), 'email'), "Newsletter intro must not mention email");
    assert(!str_contains(strtolower($expectedNewsletterIntro), 'weekly') && !str_contains(strtolower($expectedNewsletterIntro), 'monthly'), "Newsletter intro must not promise delivery frequency");

    // Get involved card texts
    assert(str_contains($seedSql, "'home_card_1_text', '{$expectedCard1Text}'"), "seed.sql must contain exact home_card_1_text");
    assert(str_contains($installSql, "'home_card_1_text', '{$expectedCard1Text}'"), "install.sql must contain exact home_card_1_text");

    assert(str_contains($seedSql, "'home_card_2_text', '{$expectedCard2Text}'"), "seed.sql must contain exact home_card_2_text");
    assert(str_contains($installSql, "'home_card_2_text', '{$expectedCard2Text}'"), "install.sql must contain exact home_card_2_text");

    assert(str_contains($seedSql, "'home_card_3_text', '{$expectedCard3Text}'"), "seed.sql must contain exact home_card_3_text");
    assert(str_contains($installSql, "'home_card_3_text', '{$expectedCard3Text}'"), "install.sql must contain exact home_card_3_text");

    // Active DB settings check
    assert(SettingModel::get('section_intro_newsletter') === $expectedNewsletterIntro, "Active DB section_intro_newsletter must match reviewed copy");
    assert(SettingModel::get('home_card_1_text') === $expectedCard1Text, "Active DB home_card_1_text must match reviewed copy");
    assert(SettingModel::get('home_card_2_text') === $expectedCard2Text, "Active DB home_card_2_text must match reviewed copy");
    assert(SettingModel::get('home_card_3_text') === $expectedCard3Text, "Active DB home_card_3_text must match reviewed copy");
    echo "    [PASS] seed.sql, install.sql, and active DB match exact reviewed copy for newsletter intro and get-involved cards.\n";

    // 15.2 Check category sort_order: Causes 1, News 2, Campaigns 3, RTI Applications 4
    echo "  - Subtest 15.2: Default category sort_order: Causes 1, News 2, Campaigns 3, RTI Applications 4\n";
    assert(preg_match("/\((?:\d+,\s*)?'Causes',\s*'causes',[^)]*,\s*1\)/", $seedSql) === 1, "seed.sql must have Causes with sort_order 1");
    assert(preg_match("/\((?:\d+,\s*)?'News',\s*'news',[^)]*,\s*2\)/", $seedSql) === 1, "seed.sql must have News with sort_order 2");
    assert(preg_match("/\((?:\d+,\s*)?'Campaigns',\s*'campaigns',[^)]*,\s*3\)/", $seedSql) === 1, "seed.sql must have Campaigns with sort_order 3");
    assert(preg_match("/\((?:\d+,\s*)?'RTI Applications',\s*'rti-applications',[^)]*,\s*4\)/", $seedSql) === 1, "seed.sql must have RTI Applications with sort_order 4");

    assert(preg_match("/\((?:\d+,\s*)?'Causes',\s*'causes',[^)]*,\s*1\)/", $installSql) === 1, "install.sql must have Causes with sort_order 1");
    assert(preg_match("/\((?:\d+,\s*)?'News',\s*'news',[^)]*,\s*2\)/", $installSql) === 1, "install.sql must have News with sort_order 2");
    assert(preg_match("/\((?:\d+,\s*)?'Campaigns',\s*'campaigns',[^)]*,\s*3\)/", $installSql) === 1, "install.sql must have Campaigns with sort_order 3");
    assert(preg_match("/\((?:\d+,\s*)?'RTI Applications',\s*'rti-applications',[^)]*,\s*4\)/", $installSql) === 1, "install.sql must have RTI Applications with sort_order 4");

    $stmtCats = $pdo->query("SELECT slug, sort_order FROM categories WHERE slug IN ('causes', 'news', 'campaigns', 'rti-applications')");
    $catRows = $stmtCats->fetchAll(\PDO::FETCH_KEY_PAIR);
    assert((int) ($catRows['causes'] ?? 0) === 1, "Causes category sort_order must be 1 in DB");
    assert((int) ($catRows['news'] ?? 0) === 2, "News category sort_order must be 2 in DB");
    assert((int) ($catRows['campaigns'] ?? 0) === 3, "Campaigns category sort_order must be 3 in DB");
    assert((int) ($catRows['rti-applications'] ?? 0) === 4, "RTI Applications category sort_order must be 4 in DB");
    echo "    [PASS] Default category sort_order verified in seed.sql, install.sql, and active DB.\n";

    // 15.3 Footer column suppression: hide Contact column when contact details are empty
    echo "  - Subtest 15.3: Footer Contact column suppression and dynamic grid layout\n";
    $origEmail = SettingModel::get('contact_email');
    $origPhone = SettingModel::get('contact_phone');
    $origWa = SettingModel::get('contact_whatsapp');
    $origAddr = SettingModel::get('contact_address');

    // Case A: All contact fields empty -> Contact column hidden, grid-cols-1 md:grid-cols-3 lg:grid-cols-3
    SettingModel::set('contact_email', '');
    SettingModel::set('contact_phone', '');
    SettingModel::set('contact_whatsapp', '');
    SettingModel::set('contact_address', '');
    SettingModel::clearCache();

    ob_start();
    \App\Core\View::partial('partials/footer');
    $footerEmptyHtml = ob_get_clean();

    assert(!str_contains($footerEmptyHtml, '<h4 class="font-serif font-bold text-ink text-base mb-4">Contact</h4>'), "Footer must NOT render Contact heading when contact fields are empty");
    assert(str_contains($footerEmptyHtml, 'grid-cols-1 md:grid-cols-3 lg:grid-cols-3'), "Footer must use 3-column grid when Contact column is omitted");
    assert(!str_contains($footerEmptyHtml, 'lg:grid-cols-4'), "Footer must not use 4-column grid when Contact column is omitted");

    // Case B: Contact field populated -> Contact column visible, grid-cols-1 md:grid-cols-2 lg:grid-cols-4
    SettingModel::set('contact_email', 'info@natconnectfoundation.org');
    SettingModel::clearCache();

    ob_start();
    \App\Core\View::partial('partials/footer');
    $footerPopulatedHtml = ob_get_clean();

    assert(str_contains($footerPopulatedHtml, '<h4 class="font-serif font-bold text-ink text-base mb-4">Contact</h4>'), "Footer MUST render Contact heading when contact email is set");
    assert(str_contains($footerPopulatedHtml, 'info@natconnectfoundation.org'), "Footer must render contact email");
    assert(str_contains($footerPopulatedHtml, 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4'), "Footer must use 4-column grid when Contact column is visible");

    // Restore original contact settings
    SettingModel::set('contact_email', $origEmail ?? '');
    SettingModel::set('contact_phone', $origPhone ?? '');
    SettingModel::set('contact_whatsapp', $origWa ?? '');
    SettingModel::set('contact_address', $origAddr ?? '');
    SettingModel::clearCache();
    echo "    [PASS] Footer cleanly hides Contact column and adjusts to 3-col grid when empty; renders 4-col when populated.\n\n";

    echo "========================================================\n";
    echo "ALL PUBLIC, JSON-LD, D2 HOME & ROUND 2 ASSERTIONS PASSED!\n";
    echo "========================================================\n";

} catch (Throwable $e) {
    echo "\n[FAIL] Assertion / Test Error: " . $e->getMessage() . "\n";
    echo "In " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
