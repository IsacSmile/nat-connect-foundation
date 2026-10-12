<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Phase 3A Editorial Post & Category Assertions
 *
 * Usage: php tests/posts_check.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once ROOT_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Sanitizer.php';
require_once APP_PATH . '/models/CategoryModel.php';
require_once APP_PATH . '/models/TagModel.php';
require_once APP_PATH . '/models/RedirectModel.php';
require_once APP_PATH . '/models/PostModel.php';
require_once APP_PATH . '/models/DashboardModel.php';

use App\Core\Database;
use App\Core\Sanitizer;
use App\Models\CategoryModel;
use App\Models\DashboardModel;
use App\Models\PostModel;
use App\Models\RedirectModel;
use App\Models\TagModel;

echo "========================================================\n";
echo "NatConnect Foundation — Phase 3A Posts & Categories Tests\n";
echo "========================================================\n";

$pdo = Database::getConnection();
echo "[OK] Connected to database: " . config('db.name') . "\n\n";

$createdPostIds = [];
$createdCategoryIds = [];
$createdRedirectPaths = [];

try {
    // ---------------------------------------------------------
    // Setup: Create 2 Test Categories
    // ---------------------------------------------------------
    $cat1Slug = 'test-cat-env-' . bin2hex(random_bytes(3));
    $cat1Id = CategoryModel::create('Test Conservation Category', $cat1Slug, 'Test Description');
    $createdCategoryIds[] = $cat1Id;

    $cat2Slug = 'test-cat-rti-' . bin2hex(random_bytes(3));
    $cat2Id = CategoryModel::create('Test RTI Category', $cat2Slug, 'Destination Category');
    $createdCategoryIds[] = $cat2Id;

    echo "[PASS] Test categories created successfully (#{$cat1Id}, #{$cat2Id})\n";

    // ---------------------------------------------------------
    // Test 1: HTMLPurifier XSS Sanitization
    // ---------------------------------------------------------
    echo "\n--- Test 1: HTMLPurifier Sanitization ---\n";
    $dirtyContent = '
        <h2>Navi Mumbai Mangrove Update</h2>
        <p>This is safe content with a <a href="https://example.org" target="_blank">valid external link</a>.</p>
        <script>alert("XSS Attack!");</script>
        <img src="test.jpg" alt="Wetland" onerror="evilXssFunction()" />
        <p><a href="javascript:alert(\'stolen-cookie\')">Click here for free funds</a></p>
        <figure><img src="crz.jpg" alt="CRZ violation"><figcaption>CRZ Line</figcaption></figure>
    ';

    $post1Id = PostModel::create([
        'title'       => 'Wetland Protection Alert',
        'category_id' => $cat1Id,
        'content'     => $dirtyContent,
        'status'      => 'published',
        'tags'        => 'Wetlands, Navi Mumbai, Test Tag'
    ]);
    $createdPostIds[] = $post1Id;

    $post1 = PostModel::getById($post1Id);
    $cleanHtml = $post1['content'] ?? '';

    // Assert <script> is stripped
    assert(!str_contains($cleanHtml, '<script>'), "Assertion Failed: <script> was not stripped!");
    assert(!str_contains($cleanHtml, 'alert("XSS Attack!")'), "Assertion Failed: script payload still present!");
    echo "[PASS] <script> tags and payloads stripped.\n";

    // Assert onerror= is stripped
    assert(!str_contains($cleanHtml, 'onerror'), "Assertion Failed: onerror attribute was not stripped!");
    assert(!str_contains($cleanHtml, 'evilXssFunction'), "Assertion Failed: evil handler still present!");
    echo "[PASS] onerror= event attributes stripped from <img>.\n";

    // Assert javascript: link scheme is stripped
    assert(!str_contains($cleanHtml, 'javascript:'), "Assertion Failed: javascript: URL scheme was not stripped!");
    echo "[PASS] javascript: URL scheme stripped from links.\n";

    // Assert noopener / noreferrer enforced on target=_blank
    assert(str_contains($cleanHtml, 'rel="noreferrer noopener"') || str_contains($cleanHtml, 'rel="noopener noreferrer"'), "Assertion Failed: noopener/noreferrer not enforced on target=_blank link!");
    echo "[PASS] rel=\"noopener noreferrer\" forced on target=_blank links.\n";

    // Assert HTML5 figure and figcaption are preserved
    assert(str_contains($cleanHtml, '<figure>') && str_contains($cleanHtml, '<figcaption>'), "Assertion Failed: figure/figcaption not preserved!");
    echo "[PASS] Safe HTML elements (h2, p, a, figure, figcaption) preserved correctly.\n";

    // YouTube iframe embed survival vs foreign iframe stripping
    echo "\n--- Test 1B: YouTube Embed Sanitization ---\n";
    $embedContent = '
        <p>Documentary Video:</p>
        <iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" title="YouTube video player" frameborder="0" allowfullscreen class="w-full aspect-video rounded-xl my-4"></iframe>
        <iframe width="560" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="YouTube video player standard"></iframe>
        <iframe width="560" height="315" src="https://player.vimeo.com/video/76979871" title="Vimeo player"></iframe>
        <iframe src="https://malicious-site.com/exploit.html"></iframe>
    ';
    $cleanEmbed = Sanitizer::clean($embedContent);

    // YouTube iframes survive
    assert(str_contains($cleanEmbed, 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'), "Assertion Failed: youtube-nocookie iframe stripped!");
    assert(str_contains($cleanEmbed, 'https://www.youtube.com/embed/dQw4w9WgXcQ'), "Assertion Failed: youtube standard iframe stripped!");
    assert(str_contains($cleanEmbed, '<iframe'), "Assertion Failed: YouTube iframe tags missing!");
    echo "[PASS] YouTube and youtube-nocookie embed iframes survive HTMLPurifier sanitizing.\n";

    // Non-YouTube iframes stripped
    assert(!str_contains($cleanEmbed, 'vimeo.com'), "Assertion Failed: Vimeo iframe survived!");
    assert(!str_contains($cleanEmbed, 'malicious-site.com'), "Assertion Failed: Malicious iframe survived!");
    $iframeMatches = [];
    preg_match_all('#<iframe#i', $cleanEmbed, $iframeMatches);
    assert(count($iframeMatches[0]) === 2, "Assertion Failed: Expected exactly 2 YouTube iframes, got " . count($iframeMatches[0]));
    echo "[PASS] Foreign domain iframes (vimeo, malicious sites) are completely stripped.\n";

    // ---------------------------------------------------------
    // Test 2: Slug Uniqueness & Auto-Suffixing (-2, -3)
    // ---------------------------------------------------------
    echo "\n--- Test 2: Slug Uniqueness & Suffixing ---\n";
    $sameTitle = 'Wetland Protection Alert';

    $post2Id = PostModel::create([
        'title'       => $sameTitle,
        'category_id' => $cat1Id,
        'content'     => '<p>Second post with identical title</p>',
        'status'      => 'draft',
    ]);
    $createdPostIds[] = $post2Id;
    $post2 = PostModel::getById($post2Id);

    $post3Id = PostModel::create([
        'title'       => $sameTitle,
        'category_id' => $cat1Id,
        'content'     => '<p>Third post with identical title</p>',
        'status'      => 'draft',
    ]);
    $createdPostIds[] = $post3Id;
    $post3 = PostModel::getById($post3Id);

    echo "Post 1 Slug: {$post1['slug']}\n";
    echo "Post 2 Slug: {$post2['slug']}\n";
    echo "Post 3 Slug: {$post3['slug']}\n";

    assert($post1['slug'] === 'wetland-protection-alert', "Assertion Failed: Base slug is {$post1['slug']}");
    assert($post2['slug'] === 'wetland-protection-alert-2', "Assertion Failed: Post 2 slug expected suffix -2, got {$post2['slug']}");
    assert($post3['slug'] === 'wetland-protection-alert-3', "Assertion Failed: Post 3 slug expected suffix -3, got {$post3['slug']}");
    echo "[PASS] Duplicate titles automatically receive -2, -3 suffixes for unique slugs.\n";

    // ---------------------------------------------------------
    // Test 3: 301 Redirect Created on Published Post Slug Change
    // ---------------------------------------------------------
    echo "\n--- Test 3: 301 Redirect on Published Slug Change ---\n";
    $oldSlug = $post1['slug'];
    $newSlug = 'mangrove-wetland-protection-final-report';

    // Update published Post 1 with new title & slug
    PostModel::update($post1Id, [
        'title'       => 'Mangrove Wetland Protection Final Report',
        'slug'        => $newSlug,
        'category_id' => $cat1Id,
        'content'     => $post1['content'],
        'status'      => 'published',
    ]);

    $updatedPost1 = PostModel::getById($post1Id);
    assert($updatedPost1['slug'] === $newSlug, "Assertion Failed: Post slug did not update to {$newSlug}");

    // Check redirects table
    $expectedOldPath = '/post/' . $oldSlug;
    $expectedNewPath = '/post/' . $newSlug;
    $createdRedirectPaths[] = $expectedOldPath;

    $stmt = $pdo->prepare("SELECT new_path FROM redirects WHERE old_path = :old LIMIT 1");
    $stmt->execute(['old' => $expectedOldPath]);
    $redirectTarget = $stmt->fetchColumn();

    assert($redirectTarget === $expectedNewPath, "Assertion Failed: Redirect row missing or incorrect! Expected {$expectedNewPath}, got {$redirectTarget}");
    echo "[PASS] 301 redirect created in redirects table: {$expectedOldPath} -> {$expectedNewPath}\n";

    // Verify draft post slug change does NOT create redirect
    $oldDraftSlug = $post2['slug'];
    $newDraftSlug = 'draft-slug-updated';
    PostModel::update($post2Id, [
        'title'       => 'Draft Slug Updated',
        'slug'        => $newDraftSlug,
        'category_id' => $cat1Id,
        'content'     => '<p>Draft</p>',
        'status'      => 'draft',
    ]);
    $stmtDraft = $pdo->prepare("SELECT 1 FROM redirects WHERE old_path = :old LIMIT 1");
    $stmtDraft->execute(['old' => '/post/' . $oldDraftSlug]);
    assert(!$stmtDraft->fetchColumn(), "Assertion Failed: Draft post should NOT generate 301 redirects!");
    echo "[PASS] Draft slug changes correctly skip generating redirects.\n";

    // ---------------------------------------------------------
    // Test 3B: Redirect Chain Flattening (A->B->C leaves A->C and B->C) & Loop Prevention
    // ---------------------------------------------------------
    echo "\n--- Test 3B: Redirect Chain Flattening (A->B->C) & Loop Prevention ---\n";
    $pathA = '/test-path-a-' . bin2hex(random_bytes(3));
    $pathB = '/test-path-b-' . bin2hex(random_bytes(3));
    $pathC = '/test-path-c-' . bin2hex(random_bytes(3));
    $createdRedirectPaths[] = $pathA;
    $createdRedirectPaths[] = $pathB;
    $createdRedirectPaths[] = $pathC;

    // Step 1: Insert redirect A -> B
    RedirectModel::createOrUpdate($pathA, $pathB);
    $stmtChain = $pdo->prepare("SELECT new_path FROM redirects WHERE old_path = ?");
    $stmtChain->execute([$pathA]);
    assert($stmtChain->fetchColumn() === $pathB, "Assertion Failed: Initial redirect A->B was not created!");

    // Step 2: Insert redirect B -> C
    // Chain rule requirement: Must also UPDATE any existing redirects whose new_path = B to point to C.
    // Result: A->B becomes A->C, and B->C is inserted. So both A->C and B->C exist.
    RedirectModel::createOrUpdate($pathB, $pathC);

    $stmtChain->execute([$pathA]);
    $newTargetA = $stmtChain->fetchColumn();
    assert($newTargetA === $pathC, "Assertion Failed: Chain flattening failed for A! Expected {$pathC}, got {$newTargetA}");

    $stmtChain->execute([$pathB]);
    $newTargetB = $stmtChain->fetchColumn();
    assert($newTargetB === $pathC, "Assertion Failed: Redirect B->C not found! Expected {$pathC}, got {$newTargetB}");
    echo "[PASS] Redirect chain flattened successfully: A->B->C leaves A->C and B->C.\n";

    // Step 3: Loop prevention rule
    // If the post slug is later renamed back to A: C -> A
    // Must delete any redirect where old_path equals post's current path (pathA), preventing loop A->C->A.
    // And flatten existing B->C to B->A.
    RedirectModel::createOrUpdate($pathC, $pathA);

    $stmtChain->execute([$pathA]);
    $targetAAfterLoop = $stmtChain->fetchColumn();
    assert($targetAAfterLoop === false, "Assertion Failed: Redirect with old_path = current path (A) was not deleted to prevent loops!");

    $stmtChain->execute([$pathB]);
    $newTargetBAfterLoop = $stmtChain->fetchColumn();
    assert($newTargetBAfterLoop === $pathA, "Assertion Failed: B was not flattened to current path A! Got {$newTargetBAfterLoop}");

    $stmtChain->execute([$pathC]);
    $newTargetCAfterLoop = $stmtChain->fetchColumn();
    assert($newTargetCAfterLoop === $pathA, "Assertion Failed: C->A redirect missing! Got {$newTargetCAfterLoop}");
    echo "[PASS] Loop prevention verified: old_path matching current path deleted, chains retargeted to current path.\n";

    // ---------------------------------------------------------
    // Test 3C: Effective Status & Lazy Publishing (Past vs Future Scheduled)
    // ---------------------------------------------------------
    echo "\n--- Test 3C: Effective Status (Past vs Future Scheduled) ---\n";
    $countsBefore = PostModel::getStatusCounts();

    $pastScheduledId = PostModel::create([
        'title'        => 'Past Scheduled Post ' . bin2hex(random_bytes(2)),
        'category_id'  => $cat1Id,
        'content'      => '<p>Past scheduled content</p>',
        'status'       => 'scheduled',
        'published_at' => date('Y-m-d H:i:s', time() - 3600), // 1 hour ago
    ]);
    $createdPostIds[] = $pastScheduledId;

    $futureScheduledId = PostModel::create([
        'title'        => 'Future Scheduled Post ' . bin2hex(random_bytes(2)),
        'category_id'  => $cat1Id,
        'content'      => '<p>Future scheduled content</p>',
        'status'       => 'scheduled',
        'published_at' => date('Y-m-d H:i:s', time() + 86400), // 24 hours in future
    ]);
    $createdPostIds[] = $futureScheduledId;

    $pastPost = PostModel::getById($pastScheduledId);
    $futurePost = PostModel::getById($futureScheduledId);

    // 1. Verify badges / effectiveStatus()
    $pastEff = PostModel::effectiveStatus($pastPost);
    $futureEff = PostModel::effectiveStatus($futurePost);
    assert($pastEff === 'published', "Assertion Failed: Scheduled post with past date must have effective status 'published', got: {$pastEff}");
    assert($pastPost['effective_status'] === 'published', "Assertion Failed: post array effective_status missing or wrong for past scheduled post");
    assert($futureEff === 'scheduled', "Assertion Failed: Scheduled post with future date must have effective status 'scheduled', got: {$futureEff}");
    assert($futurePost['effective_status'] === 'scheduled', "Assertion Failed: post array effective_status missing or wrong for future scheduled post");
    echo "[PASS] Badges & PHP helper: scheduled post with past date evaluates to 'published', future date evaluates to 'scheduled'.\n";

    // 2. Verify Published filter includes past scheduled, excludes future scheduled
    $pubList = PostModel::getPaginated(['status' => 'published'], 1, 100);
    $pubIds = array_column($pubList['items'], 'id');
    assert(in_array($pastScheduledId, $pubIds, true), "Assertion Failed: Published filter must include scheduled post with past date!");
    assert(!in_array($futureScheduledId, $pubIds, true), "Assertion Failed: Published filter must NOT include scheduled post with future date!");
    echo "[PASS] 'Published' filter correctly includes scheduled post with past date and excludes future scheduled post.\n";

    // 3. Verify Scheduled filter excludes past scheduled, includes future scheduled
    $schedList = PostModel::getPaginated(['status' => 'scheduled'], 1, 100);
    $schedIds = array_column($schedList['items'], 'id');
    assert(!in_array($pastScheduledId, $schedIds, true), "Assertion Failed: Scheduled filter must NOT include scheduled post with past date!");
    assert(in_array($futureScheduledId, $schedIds, true), "Assertion Failed: Scheduled filter must include scheduled post with future date!");
    echo "[PASS] 'Scheduled' filter correctly excludes scheduled post with past date and includes future scheduled post.\n";

    // 4. Verify getStatusCounts() and Dashboard counts
    $countsAfter = PostModel::getStatusCounts();
    assert($countsAfter['published'] === $countsBefore['published'] + 1, "Assertion Failed: Past scheduled post should increment published count by 1");
    assert($countsAfter['scheduled'] === $countsBefore['scheduled'] + 1, "Assertion Failed: Future scheduled post should increment scheduled count by 1");
    echo "[PASS] Status counts reflect effective status: past scheduled increments published count, future scheduled increments scheduled count.\n";

    $stats = DashboardModel::getStats();
    assert($stats['published_posts'] === $countsAfter['published'], "Assertion Failed: Dashboard published_posts count does not match PostModel published count!");
    assert($stats['scheduled_posts'] === $countsAfter['scheduled'], "Assertion Failed: Dashboard scheduled_posts count does not match PostModel scheduled count!");
    echo "[PASS] Dashboard counts match PostModel effective status counts identically.\n";

    // ---------------------------------------------------------
    // Test 4: Category Deletion Protection & Reassignment
    // ---------------------------------------------------------
    echo "\n--- Test 4: Category Deletion Protection ---\n";
    // Category 1 currently has posts ($post1Id, $post2Id, $post3Id)
    $cat1PostCount = CategoryModel::getPostCount($cat1Id);
    assert($cat1PostCount > 0, "Assertion Failed: Category 1 should have posts");

    $caughtException = false;
    try {
        CategoryModel::delete($cat1Id); // No target category specified
    } catch (\Throwable $e) {
        $caughtException = true;
    }
    assert($caughtException, "Assertion Failed: Deleting category with posts without destination category did not throw!");
    echo "[PASS] Blocked deletion of category with {$cat1PostCount} posts when no destination category provided.\n";

    // Now delete with valid reassignment to Category 2
    CategoryModel::delete($cat1Id, $cat2Id);
    $cat1After = CategoryModel::getById($cat1Id);
    assert($cat1After === null, "Assertion Failed: Category 1 was not deleted after reassignment!");

    $post1AfterReassign = PostModel::getById($post1Id);
    assert((int) $post1AfterReassign['category_id'] === $cat2Id, "Assertion Failed: Posts were not reassigned to Category 2!");
    echo "[PASS] Category deleted and posts cleanly reassigned to destination category (#{$cat2Id}).\n";

    // ---------------------------------------------------------
    // Test 5: Post Deletion Cascade
    // ---------------------------------------------------------
    echo "\n--- Test 5: Post Deletion Cascade Integrity ---\n";
    // Add mock comment, like, and view to Post 3
    $pdo->prepare("INSERT INTO comments (post_id, name, email, body, status, ip_hash) VALUES (?, 'Visitor', 'v@ex.org', 'Great article', 'approved', 'hash123')")->execute([$post3Id]);
    $pdo->prepare("INSERT INTO post_likes (post_id, visitor_hash) VALUES (?, 'visitor123')")->execute([$post3Id]);
    $pdo->prepare("INSERT INTO post_views (post_id, visitor_hash, viewed_on) VALUES (?, 'visitor123', CURDATE())")->execute([$post3Id]);
    TagModel::syncTagsForPost($post3Id, ['Wetland', 'Mangrove']);

    // Delete post 3
    PostModel::delete($post3Id);

    // Verify cascade
    $commCount = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE post_id = {$post3Id}")->fetchColumn();
    $likeCount = (int) $pdo->query("SELECT COUNT(*) FROM post_likes WHERE post_id = {$post3Id}")->fetchColumn();
    $viewCount = (int) $pdo->query("SELECT COUNT(*) FROM post_views WHERE post_id = {$post3Id}")->fetchColumn();
    $tagCount  = (int) $pdo->query("SELECT COUNT(*) FROM post_tags WHERE post_id = {$post3Id}")->fetchColumn();

    assert($commCount === 0, "Assertion Failed: Comments not cascaded!");
    assert($likeCount === 0, "Assertion Failed: Likes not cascaded!");
    assert($viewCount === 0, "Assertion Failed: Views not cascaded!");
    assert($tagCount === 0, "Assertion Failed: Post tags not cascaded!");
    echo "[PASS] Deleting a post cascades to comments, likes, views, and post_tags cleanly.\n";

} finally {
    // ---------------------------------------------------------
    // Cleanup All Test Fixtures
    // ---------------------------------------------------------
    echo "\n--- Cleaning up test fixtures ---\n";
    if (!empty($createdPostIds)) {
        $in = implode(',', array_map('intval', $createdPostIds));
        $pdo->exec("DELETE FROM posts WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test posts: {$in}\n";
    }
    if (!empty($createdCategoryIds)) {
        $in = implode(',', array_map('intval', $createdCategoryIds));
        $pdo->exec("DELETE FROM categories WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test categories: {$in}\n";
    }
    if (!empty($createdRedirectPaths)) {
        foreach ($createdRedirectPaths as $p) {
            $pdo->prepare("DELETE FROM redirects WHERE old_path = ?")->execute([$p]);
        }
        echo "[CLEANUP] Deleted test redirects.\n";
    }
    // Delete orphan test tags
    $pdo->exec("DELETE FROM tags WHERE name IN ('Wetlands', 'Navi Mumbai', 'Test Tag', 'Wetland', 'Mangrove')");
    echo "[CLEANUP] Deleted test tags.\n";
}

echo "\n========================================================\n";
echo "ALL PHASE 3A ASSERTIONS PASSED SUCCESSFULLY!\n";
echo "========================================================\n";
