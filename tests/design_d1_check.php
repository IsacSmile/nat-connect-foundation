<?php
declare(strict_types=1);

// These checks use assert(). With zend.assertions=-1 (the default in many php.ini files)
// every assert() is compiled out and the suite would print PASS without checking anything.
if (ini_get('zend.assertions') !== '1') {
    fwrite(STDERR, "[ERROR] Assertions are disabled. Run: php -d zend.assertions=1 " . basename(__FILE__) . "\n");
    exit(1);
}

/**
 * NatConnect Foundation — Design Phase D1 Test Suite
 *
 * Verifies:
 * 1. Editor's picks flag saved (PostModel create/update/getEditorsPicks).
 * 2. Cause requires image + description (TagModel validation & CRUD).
 * 3. Subscribe rejects missing consent, honeypot, too fast, and 6th submission in an hour.
 * 4. Duplicate email gives the same response and no second row (anti-enumeration).
 * 5. CSV export escapes formula cells (=, +, -, @).
 * 6. /stories archive excludes unpublished posts (drafts and future scheduled).
 *
 * Usage: php tests/design_d1_check.php
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
require_once APP_PATH . '/core/Sanitizer.php';
require_once APP_PATH . '/core/ImageUploader.php';
require_once APP_PATH . '/core/View.php';
require_once APP_PATH . '/models/RedirectModel.php';
require_once APP_PATH . '/models/PostModel.php';
require_once APP_PATH . '/models/CategoryModel.php';
require_once APP_PATH . '/models/TagModel.php';
require_once APP_PATH . '/models/SettingModel.php';
require_once APP_PATH . '/models/PageModel.php';
require_once APP_PATH . '/models/SubscriberModel.php';
require_once APP_PATH . '/controllers/PublicController.php';

use App\Core\Database;
use App\Models\CategoryModel;
use App\Models\PostModel;
use App\Models\SubscriberModel;
use App\Models\TagModel;
use App\Controllers\PublicController;

echo "========================================================\n";
echo "NatConnect Foundation — Design Phase D1 Test Suite\n";
echo "========================================================\n\n";

$pdo = Database::getConnection();
echo "[OK] Connected to database: " . config('db.name') . "\n";

$rand = bin2hex(random_bytes(4));

$createdPostIds = [];
$createdTagIds = [];
$createdCategoryIds = [];
$createdSubscriberIds = [];
$testedIpHashes = [];

$cleanup = function () use ($pdo, &$createdPostIds, &$createdTagIds, &$createdCategoryIds, &$createdSubscriberIds, &$testedIpHashes) {
    if (!empty($createdPostIds)) {
        $in = implode(',', array_map('intval', $createdPostIds));
        $pdo->exec("DELETE FROM posts WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test posts: {$in}\n";
    }
    if (!empty($createdTagIds)) {
        $in = implode(',', array_map('intval', $createdTagIds));
        $pdo->exec("DELETE FROM tags WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test tags: {$in}\n";
    }
    if (!empty($createdCategoryIds)) {
        $in = implode(',', array_map('intval', $createdCategoryIds));
        $pdo->exec("DELETE FROM categories WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test categories: {$in}\n";
    }
    if (!empty($createdSubscriberIds)) {
        $in = implode(',', array_map('intval', $createdSubscriberIds));
        $pdo->exec("DELETE FROM subscribers WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test subscribers: {$in}\n";
    }
    if (!empty($testedIpHashes)) {
        foreach ($testedIpHashes as $h) {
            $pdo->prepare("DELETE FROM subscribe_rate_limits WHERE ip_hash = :h")->execute(['h' => $h]);
        }
    }
};

register_shutdown_function($cleanup);

// -------------------------------------------------------------
// Fixture: Category
// -------------------------------------------------------------
$stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, NOW())");
$catSlug = "d1-test-cat-{$rand}";
$stmt->execute(["D1 Test Category {$rand}", $catSlug, "Test category description"]);
$testCatId = (int) $pdo->lastInsertId();
$createdCategoryIds[] = $testCatId;

// =============================================================
// TEST 1: Editor's Picks Flag Saved & Queried
// =============================================================
echo "\n--- 1. EDITOR'S PICKS (posts.is_pick) ---\n";

// 1.1 Create post with is_pick = 1
$pickPostId = PostModel::create([
    'title'        => "Pick Post {$rand}",
    'slug'         => "pick-post-{$rand}",
    'category_id'  => $testCatId,
    'status'       => 'published',
    'is_pick'      => 1,
    'content'      => '<p>Test pick content</p>',
    'published_at' => date('Y-m-d H:i:s'),
]);
$createdPostIds[] = $pickPostId;

$postRow = PostModel::getById($pickPostId);
assert((int) $postRow['is_pick'] === 1, "is_pick must be 1 on create");
echo "[PASS] Editor's pick flag saved as 1 on post creation.\n";

// 1.2 Update post with is_pick = 0
PostModel::update($pickPostId, [
    'title'        => "Pick Post {$rand}",
    'slug'         => "pick-post-{$rand}",
    'category_id'  => $testCatId,
    'status'       => 'published',
    'is_pick'      => 0,
    'content'      => '<p>Test pick content</p>',
    'published_at' => date('Y-m-d H:i:s'),
]);
$postRow = PostModel::getById($pickPostId);
assert((int) $postRow['is_pick'] === 0, "is_pick must be 0 after update");
echo "[PASS] Editor's pick flag updated to 0.\n";

// 1.3 Update post back to is_pick = 1
PostModel::update($pickPostId, [
    'title'        => "Pick Post {$rand}",
    'slug'         => "pick-post-{$rand}",
    'category_id'  => $testCatId,
    'status'       => 'published',
    'is_pick'      => 1,
    'content'      => '<p>Test pick content</p>',
    'published_at' => date('Y-m-d H:i:s'),
]);
$postRow = PostModel::getById($pickPostId);
assert((int) $postRow['is_pick'] === 1, "is_pick must be 1 after second update");
echo "[PASS] Editor's pick flag updated back to 1.\n";

// 1.4 Helper getEditorsPicks returns latest pick posts
$picks = PostModel::getEditorsPicks(3);
$foundPick = false;
foreach ($picks as $p) {
    if ((int) $p['id'] === $pickPostId) {
        $foundPick = true;
        break;
    }
}
assert($foundPick, "PostModel::getEditorsPicks() must contain the published pick post");
echo "[PASS] PostModel::getEditorsPicks() correctly retrieves published editor picks.\n";


// =============================================================
// TEST 2: Cause Requires Image + Description
// =============================================================
echo "\n--- 2. TAG MANAGEMENT (Cause Validation) ---\n";

// 2.1 Cause missing description must throw InvalidArgumentException
$caught = false;
try {
    TagModel::validate([
        'name'          => "Cause Missing Desc {$rand}",
        'slug'          => "cause-no-desc-{$rand}",
        'description'   => '',
        'image_path'    => '/uploads/2026/10/test-1600.webp',
        'image_alt'     => 'A test cause image description',
        'show_as_cause' => 1,
    ]);
} catch (InvalidArgumentException $e) {
    $caught = true;
    assert(str_contains($e->getMessage(), 'description'), "Exception message must mention description");
}
assert($caught, "TagModel::validate must throw when cause lacks a description");
echo "[PASS] Cause without description is strictly rejected.\n";

// 2.2 Cause missing image must throw InvalidArgumentException
$caught = false;
try {
    TagModel::validate([
        'name'          => "Cause Missing Image {$rand}",
        'slug'          => "cause-no-img-{$rand}",
        'description'   => 'Valid description of our cause.',
        'image_path'    => '',
        'image_alt'     => '',
        'show_as_cause' => 1,
    ]);
} catch (InvalidArgumentException $e) {
    $caught = true;
    assert(str_contains($e->getMessage(), 'image'), "Exception message must mention image");
}
assert($caught, "TagModel::validate must throw when cause lacks an image");
echo "[PASS] Cause without image is strictly rejected.\n";

// 2.3 Cause with image missing alt text must throw InvalidArgumentException
$caught = false;
try {
    TagModel::validate([
        'name'          => "Cause Missing Alt {$rand}",
        'slug'          => "cause-no-alt-{$rand}",
        'description'   => 'Valid description of our cause.',
        'image_path'    => '/uploads/2026/10/test-1600.webp',
        'image_alt'     => '',
        'show_as_cause' => 1,
    ]);
} catch (InvalidArgumentException $e) {
    $caught = true;
}
assert($caught, "TagModel::validate must throw when cause image lacks alt text");
echo "[PASS] Cause with image missing alt text is strictly rejected.\n";

// 2.4 Cause with alt text ending with filename must throw
$caught = false;
try {
    TagModel::validate([
        'name'          => "Cause Filename Alt {$rand}",
        'slug'          => "cause-file-alt-{$rand}",
        'description'   => 'Valid description of our cause.',
        'image_path'    => '/uploads/2026/10/test-1600.webp',
        'image_alt'     => 'wetlands_photo.jpg',
        'show_as_cause' => 1,
    ]);
} catch (InvalidArgumentException $e) {
    $caught = true;
}
assert($caught, "TagModel::validate must reject filename-like alt text");
echo "[PASS] Cause with filename alt text is strictly rejected.\n";

// 2.5 Valid Cause succeeds
$validCauseId = TagModel::create([
    'name'          => "Valid Cause {$rand}",
    'slug'          => "valid-cause-{$rand}",
    'description'   => 'Protecting the flamingo migratory corridor in NRI wetland.',
    'image_path'    => '/uploads/2026/10/test-1600.webp',
    'image_alt'     => 'Flamingos wading in the NRI wetland at dawn',
    'show_as_cause' => 1,
    'show_as_topic' => 1,
    'sort_order'    => 1,
]);
$createdTagIds[] = $validCauseId;
assert($validCauseId > 0, "Valid cause must be successfully created");
$savedTag = TagModel::getById($validCauseId);
assert((int) $savedTag['show_as_cause'] === 1 && !empty($savedTag['image_path']), "Saved tag must have cause flag and image");
echo "[PASS] Valid cause with image, alt text, and description is successfully created.\n";

// 2.6 Delete removes tag links only, never posts
$testPostForTag = PostModel::create([
    'title'        => "Post Linked To Tag {$rand}",
    'slug'         => "post-tag-link-{$rand}",
    'category_id'  => $testCatId,
    'status'       => 'published',
    'tags'         => "Valid Cause {$rand}",
    'content'      => '<p>Content</p>',
    'published_at' => date('Y-m-d H:i:s'),
]);
$createdPostIds[] = $testPostForTag;
$tagLinksBefore = (int) $pdo->query("SELECT COUNT(*) FROM post_tags WHERE tag_id = {$validCauseId}")->fetchColumn();
assert($tagLinksBefore > 0, "Tag should be linked to post");

TagModel::delete($validCauseId);
$tagLinksAfter = (int) $pdo->query("SELECT COUNT(*) FROM post_tags WHERE tag_id = {$validCauseId}")->fetchColumn();
assert($tagLinksAfter === 0, "Deleting tag must remove post_tags links");
$postStillExists = PostModel::getById($testPostForTag);
assert($postStillExists !== null, "Deleting tag must NEVER delete posts");
echo "[PASS] Tag deletion removes post association links only; post remains intact.\n";


// =============================================================
// TEST 3: Subscriber Security & Validation (/subscribe)
// =============================================================
echo "\n--- 3. SUBSCRIBER VALIDATION & SECURITY ---\n";

$controller = new PublicController();
$salt = (string) config('security.salt', 'salt');
$testIp = '198.51.100.' . mt_rand(1, 250);
$testIpHash = hash('sha256', $testIp . $salt);
$testedIpHashes[] = $testIpHash;

$_SERVER['REMOTE_ADDR'] = $testIp;
$_SERVER['HTTP_ACCEPT'] = 'application/json'; // Request JSON response

// 3.1 Rejects missing consent
$_POST = [
    'email'    => "reader-{$rand}@example.com",
    'consent'  => '', // Missing consent
    'sub_ts'   => time() - 10,
    'sub_sig'  => hash_hmac('sha256', (string) (time() - 10), $salt),
    'sub_hp_7' => '',
];
ob_start();
$controller->subscribe();
$respJson = ob_get_clean();
$resp = json_decode($respJson, true);
assert(($resp['status_code'] ?? http_response_code()) === 422, "Missing consent must respond with 422");
assert(!empty($resp['error']) && str_contains($resp['error'], 'consent'), "Error must mention consent");
echo "[PASS] Missing consent is strictly rejected with 422.\n";

// 3.2 Rejects honeypot
$_POST = [
    'email'    => "bot-{$rand}@example.com",
    'consent'  => '1',
    'sub_ts'   => time() - 10,
    'sub_sig'  => hash_hmac('sha256', (string) (time() - 10), $salt),
    'sub_hp_7' => 'http://spam-site.com', // Filled honeypot
];
ob_start();
$controller->subscribe();
$respJson = ob_get_clean();
$resp = json_decode($respJson, true);
assert(($resp['status_code'] ?? http_response_code()) === 422, "Filled honeypot must respond with 422");
assert(!empty($resp['error']), "Error must be returned for honeypot");
echo "[PASS] Honeypot submission is strictly rejected with 422.\n";

// 3.3 Rejects too fast (< 3 seconds)
$_POST = [
    'email'    => "speed-{$rand}@example.com",
    'consent'  => '1',
    'sub_ts'   => time(), // Submitted immediately (0 seconds)
    'sub_sig'  => hash_hmac('sha256', (string) time(), $salt),
    'sub_hp_7' => '',
];
ob_start();
$controller->subscribe();
$respJson = ob_get_clean();
$resp = json_decode($respJson, true);
assert(($resp['status_code'] ?? http_response_code()) === 422, "Fast submission (< 3s) must respond with 422");
assert(!empty($resp['error']) && str_contains($resp['error'], 'fast'), "Error must indicate submission was too fast");
echo "[PASS] Fast submission under 3 seconds is strictly rejected with 422.\n";

// 3.4 Rate limit: Max 5 submissions per hour per IP hash; 6th rejected
$rateIp = '203.0.113.' . mt_rand(1, 250);
$rateIpHash = hash('sha256', $rateIp . $salt);
$testedIpHashes[] = $rateIpHash;
$_SERVER['REMOTE_ADDR'] = $rateIp;

// First 5 submissions succeed
for ($i = 1; $i <= 5; $i++) {
    $_POST = [
        'email'    => "rate-{$i}-{$rand}@example.com",
        'consent'  => '1',
        'sub_ts'   => time() - 10,
        'sub_sig'  => hash_hmac('sha256', (string) (time() - 10), $salt),
        'sub_hp_7' => '',
    ];
    ob_start();
    $controller->subscribe();
    $respJson = ob_get_clean();
    $resp = json_decode($respJson, true);
    assert(($resp['status_code'] ?? http_response_code()) === 200, "Submission #{$i} must succeed (200)");
}

// 6th submission in an hour from same IP must be rejected (429)
$_POST = [
    'email'    => "rate-6-{$rand}@example.com",
    'consent'  => '1',
    'sub_ts'   => time() - 10,
    'sub_sig'  => hash_hmac('sha256', (string) (time() - 10), $salt),
    'sub_hp_7' => '',
];
ob_start();
$controller->subscribe();
$respJson = ob_get_clean();
$resp = json_decode($respJson, true);
assert(($resp['status_code'] ?? http_response_code()) === 429, "6th submission in an hour must be rejected with 429");
assert(!empty($resp['error']) && str_contains($resp['error'], 'Too many'), "Error must indicate rate limit exceeded");
echo "[PASS] Rate limit enforced: 5 submissions allowed per hour; 6th is rejected with 429.\n";

// 3.5 IPv6 /64 Subscribe Rate Limiting
$ipv6SubA = '2001:db8:9999:aaaa:0000:0000:0000:0001';
$ipv6SubB = '2001:db8:9999:aaaa:ffff:ffff:ffff:ffff';
$ipv6SubHash = ipHash($ipv6SubA, $salt);
$testedIpHashes[] = $ipv6SubHash;

// First 5 submissions from address A
$_SERVER['REMOTE_ADDR'] = $ipv6SubA;
for ($i = 1; $i <= 5; $i++) {
    $_POST = [
        'email'    => "ipv6-sub-{$i}-{$rand}@example.com",
        'consent'  => '1',
        'sub_ts'   => time() - 10,
        'sub_sig'  => hash_hmac('sha256', (string) (time() - 10), $salt),
        'sub_hp_7' => '',
    ];
    ob_start();
    $controller->subscribe();
    $respJson = ob_get_clean();
    $resp = json_decode($respJson, true);
    assert(($resp['status_code'] ?? http_response_code()) === 200, "IPv6 submission #{$i} must succeed (200)");
}

// 6th submission from address B (same /64) must be rejected with 429
$_SERVER['REMOTE_ADDR'] = $ipv6SubB;
$_POST = [
    'email'    => "ipv6-sub-6-{$rand}@example.com",
    'consent'  => '1',
    'sub_ts'   => time() - 10,
    'sub_sig'  => hash_hmac('sha256', (string) (time() - 10), $salt),
    'sub_hp_7' => '',
];
ob_start();
$controller->subscribe();
$respJson = ob_get_clean();
$resp = json_decode($respJson, true);
assert(($resp['status_code'] ?? http_response_code()) === 429, "6th submission from same IPv6 /64 must be rejected with 429");
assert(!empty($resp['error']) && str_contains($resp['error'], 'Too many'), "Error must indicate rate limit exceeded for IPv6 /64");
echo "[PASS] IPv6 subscribe rate limit enforced across /64 network prefix (429).\n";


// =============================================================
// TEST 4: Duplicate Email Anti-Enumeration & No Second Row
// =============================================================
echo "\n--- 4. DUPLICATE EMAIL ANTI-ENUMERATION ---\n";

$freshIp = '198.51.100.' . mt_rand(1, 250);
$freshIpHash = hash('sha256', $freshIp . $salt);
$testedIpHashes[] = $freshIpHash;
$_SERVER['REMOTE_ADDR'] = $freshIp;

$dupEmail = "duplicate-test-{$rand}@example.com";

// First subscription
$_POST = [
    'email'    => $dupEmail,
    'consent'  => '1',
    'sub_ts'   => time() - 10,
    'sub_sig'  => hash_hmac('sha256', (string) (time() - 10), $salt),
    'sub_hp_7' => '',
];
ob_start();
$controller->subscribe();
$resp1 = json_decode(ob_get_clean(), true);

assert(($resp1['status_code'] ?? http_response_code()) === 200, "First subscription must respond with 200");
assert($resp1['message'] === "Thanks! You're on the list.", "First subscription must return standard message");

$stmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = :email");
$stmt->execute(['email' => $dupEmail]);
$subRow = $stmt->fetch(PDO::FETCH_ASSOC);
assert($subRow !== false, "Row must exist in subscribers table");
$createdSubscriberIds[] = (int) $subRow['id'];

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM subscribers WHERE email = :email");
$stmtCount->execute(['email' => $dupEmail]);
$countBefore = (int) $stmtCount->fetchColumn();
assert($countBefore === 1, "Exactly 1 row must exist");

// Second subscription with duplicate email (different IP to avoid rate limit)
$secondIp = '198.51.100.' . mt_rand(1, 250);
$testedIpHashes[] = hash('sha256', $secondIp . $salt);
$_SERVER['REMOTE_ADDR'] = $secondIp;

$_POST = [
    'email'    => $dupEmail,
    'consent'  => '1',
    'sub_ts'   => time() - 10,
    'sub_sig'  => hash_hmac('sha256', (string) (time() - 10), $salt),
    'sub_hp_7' => '',
];
ob_start();
$controller->subscribe();
$resp2 = json_decode(ob_get_clean(), true);

assert(($resp2['status_code'] ?? http_response_code()) === 200, "Duplicate subscription must respond with 200 (no error)");
assert($resp2['message'] === $resp1['message'], "Duplicate email must return the EXACT same response as a new email");

$stmtCount->execute(['email' => $dupEmail]);
$countAfter = (int) $stmtCount->fetchColumn();
assert($countAfter === 1, "Duplicate email must NOT insert a second row");
echo "[PASS] Duplicate email returns identical success message and creates no second row (anti-enumeration verified).\n";


// =============================================================
// TEST 5: CSV Export Escapes Formula Cells
// =============================================================
echo "\n--- 5. CSV EXPORT FORMULA INJECTION PREVENTION ---\n";

assert(SubscriberModel::escapeCsvCell('=CMD()') === "'=CMD()", "Cells starting with = must be prefixed with '");
assert(SubscriberModel::escapeCsvCell('+123') === "'+123", "Cells starting with + must be prefixed with '");
assert(SubscriberModel::escapeCsvCell('-SUM(A1)') === "'-SUM(A1)", "Cells starting with - must be prefixed with '");
assert(SubscriberModel::escapeCsvCell('@HYPERLINK()') === "'@HYPERLINK()", "Cells starting with @ must be prefixed with '");
assert(SubscriberModel::escapeCsvCell('regular@example.com') === "regular@example.com", "Normal cells must remain unchanged");

// Insert subscriber with formula-like email to test export
$formulaEmail = "=+123-test-{$rand}@example.com";
$pdo->prepare("INSERT INTO subscribers (email, consent_at, ip_hash, source, created_at) VALUES (:e, NOW(), 'test_hash', '=cmd', NOW())")
    ->execute(['e' => $formulaEmail]);
$formulaSubId = (int) $pdo->lastInsertId();
$createdSubscriberIds[] = $formulaSubId;

$exportRows = SubscriberModel::getExportData();
$foundFormulaSub = false;
foreach ($exportRows as $row) {
    if (str_contains($row['email'], "test-{$rand}@example.com")) {
        $foundFormulaSub = true;
        assert(str_starts_with($row['email'], "'"), "Exported formula email must start with single quote");
        assert(str_starts_with($row['source'], "'"), "Exported formula source must start with single quote");
        break;
    }
}
assert($foundFormulaSub, "Formula test subscriber must be present in export data");
echo "[PASS] CSV export rigorously prefixes cells starting with =, +, -, @ with a single quote.\n";


// =============================================================
// TEST 6: /stories Archive Excludes Unpublished Posts
// =============================================================
echo "\n--- 6. /STORIES ARCHIVE EXCLUDES UNPUBLISHED POSTS ---\n";

$pubSlug = "stories-pub-{$rand}";
$draftSlug = "stories-draft-{$rand}";
$futureSlug = "stories-future-{$rand}";

$pubTitle = "Published Story {$rand}";
$draftTitle = "Draft Story {$rand}";
$futureTitle = "Future Story {$rand}";

// 1. Published post
$pubId = PostModel::create([
    'title'        => $pubTitle,
    'slug'         => $pubSlug,
    'category_id'  => $testCatId,
    'status'       => 'published',
    'content'      => '<p>Published stories content</p>',
    'published_at' => date('Y-m-d H:i:s'),
]);
$createdPostIds[] = $pubId;

// 2. Draft post
$draftId = PostModel::create([
    'title'        => $draftTitle,
    'slug'         => $draftSlug,
    'category_id'  => $testCatId,
    'status'       => 'draft',
    'content'      => '<p>Draft stories content</p>',
]);
$createdPostIds[] = $draftId;

// 3. Future scheduled post
$futureId = PostModel::create([
    'title'        => $futureTitle,
    'slug'         => $futureSlug,
    'category_id'  => $testCatId,
    'status'       => 'scheduled',
    'published_at' => date('Y-m-d H:i:s', time() + 86400 * 10), // 10 days in future
    'content'      => '<p>Future stories content</p>',
]);
$createdPostIds[] = $futureId;

// Invoke stories archive
unset($_GET['category'], $_GET['page']);
ob_start();
$controller->stories();
$storiesHtml = ob_get_clean();

// Assert published post is present
assert(str_contains($storiesHtml, $pubTitle), "/stories must display effectively published stories");
echo "[PASS] Effectively published stories are included in /stories.\n";

// Assert draft post is EXCLUDED
assert(!str_contains($storiesHtml, $draftTitle), "/stories must strictly exclude draft posts");
assert(!str_contains($storiesHtml, $draftSlug), "/stories must strictly exclude draft slug");
echo "[PASS] Draft posts are strictly excluded from /stories.\n";

// Assert future scheduled post is EXCLUDED
assert(!str_contains($storiesHtml, $futureTitle), "/stories must strictly exclude future scheduled posts");
assert(!str_contains($storiesHtml, $futureSlug), "/stories must strictly exclude future scheduled slug");
echo "[PASS] Future scheduled posts are strictly excluded from /stories.\n";

// Test category filtering on /stories
$_GET['category'] = $catSlug;
ob_start();
$controller->stories();
$catFilteredHtml = ob_get_clean();
assert(str_contains($catFilteredHtml, $pubTitle), "/stories?category= must display stories in category");
assert(!str_contains($catFilteredHtml, $draftTitle), "/stories?category= must exclude drafts");
echo "[PASS] Category filter on /stories works and respects publication status.\n";

echo "\n========================================================\n";
echo "[SUCCESS] All Design Phase D1 Tests Passed (100% OK)!\n";
echo "========================================================\n";
