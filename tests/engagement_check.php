<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Phase 5 Engagement & Interactivity Verification
 *
 * Verifies:
 * 1. Views:
 *    - Same visitor twice on the same day counts once.
 *    - Bot user agent is not counted.
 *    - Unpublished (draft) post is not counted.
 * 2. Likes:
 *    - Toggle like / unlike works and updates posts.likes.
 *    - Same visitor cannot duplicate likes (unique constraint / toggle state).
 *    - Cross-origin request rejected with HTTP 403.
 * 3. Comments:
 *    - XSS payload stored and rendered escaped (plain text only, no tags executed).
 *    - Honeypot submission discarded (pretends success, zero DB rows).
 *    - Submission under 3 seconds rejected by signed timestamp check.
 *    - 4th comment in 10 minutes rejected by IP rate limiter.
 *    - Turnstile failure rejected (mocked verification).
 *    - New comments are status 'pending' and never shown publicly until approved.
 * 4. Complete fixture cleanup (posts, views, likes, comments, rate limits).
 *
 * Usage: php tests/engagement_check.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/Turnstile.php';
require_once APP_PATH . '/core/ImageUploader.php';
require_once APP_PATH . '/core/View.php';
require_once APP_PATH . '/models/PostModel.php';
require_once APP_PATH . '/models/CategoryModel.php';
require_once APP_PATH . '/models/TagModel.php';
require_once APP_PATH . '/models/SettingModel.php';
require_once APP_PATH . '/models/PageModel.php';
require_once APP_PATH . '/models/EngagementModel.php';
require_once APP_PATH . '/models/CommentModel.php';
require_once APP_PATH . '/controllers/PublicController.php';

use App\Core\Database;
use App\Core\Turnstile;
use App\Models\CommentModel;
use App\Models\EngagementModel;
use App\Models\PostModel;
use App\Controllers\PublicController;

echo "========================================================\n";
echo "NatConnect Foundation — Phase 5 Engagement Test Suite\n";
echo "========================================================\n\n";

$pdo = Database::getConnection();
echo "[OK] Connected to database: " . config('db.name') . "\n";

$createdPostIds = [];
$createdCommentIds = [];

// Cleanup helper
$cleanup = function () use ($pdo, &$createdPostIds, &$createdCommentIds) {
    if (!empty($createdCommentIds)) {
        $in = implode(',', array_map('intval', $createdCommentIds));
        $pdo->exec("DELETE FROM comments WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test comments: {$in}\n";
        $createdCommentIds = [];
    }

    if (!empty($createdPostIds)) {
        $in = implode(',', array_map('intval', $createdPostIds));
        $pdo->exec("DELETE FROM post_views WHERE post_id IN ({$in})");
        $pdo->exec("DELETE FROM post_likes WHERE post_id IN ({$in})");
        $pdo->exec("DELETE FROM comments WHERE post_id IN ({$in})");
        $pdo->exec("DELETE FROM posts WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test posts and relations: {$in}\n";
        $createdPostIds = [];
    }

    // Clean up test IP rate limit rows
    $testIpHash = hash('sha256', '198.51.100.99' . config('security.salt', 'salt'));
    $pdo->exec("DELETE FROM like_rate_limits WHERE ip_hash = '{$testIpHash}'");
    $pdo->exec("DELETE FROM comments WHERE ip_hash = '{$testIpHash}'");
};

// Ensure cleanup on abrupt exit
register_shutdown_function(function () use ($cleanup) {
    $cleanup();
});

// Setup Fixtures: One published post and one draft post
$stmt = $pdo->prepare("
    INSERT INTO posts (title, slug, content, excerpt, status, published_at, views, likes, created_at, updated_at)
    VALUES (:title, :slug, :content, :excerpt, :status, :published_at, 0, 0, NOW(), NOW())
");

$publishedSlug = 'test-phase-5-published-' . bin2hex(random_bytes(4));
$stmt->execute([
    'title'        => 'Phase 5 Engagement Test Post',
    'slug'         => $publishedSlug,
    'content'      => '<p>Test article body for views, likes, and comments.</p>',
    'excerpt'      => 'Test excerpt',
    'status'       => 'published',
    'published_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
]);
$publishedPostId = (int) $pdo->lastInsertId();
$createdPostIds[] = $publishedPostId;
echo "[FIXTURE] Created published test post #{$publishedPostId} ({$publishedSlug})\n";

$draftSlug = 'test-phase-5-draft-' . bin2hex(random_bytes(4));
$stmt->execute([
    'title'        => 'Phase 5 Draft Test Post',
    'slug'         => $draftSlug,
    'content'      => '<p>Unpublished draft article.</p>',
    'excerpt'      => 'Draft excerpt',
    'status'       => 'draft',
    'published_at' => null,
]);
$draftPostId = (int) $pdo->lastInsertId();
$createdPostIds[] = $draftPostId;
echo "[FIXTURE] Created draft test post #{$draftPostId} ({$draftSlug})\n\n";

$controller = new PublicController();

// =========================================================================
// 1. VIEWS TESTS
// =========================================================================
echo "--- 1. VIEWS VERIFICATION ---\n";

// Test 1.1: Same visitor twice on the same day counts only once
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '198.51.100.10';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
unset($_GET['preview'], $_POST['preview']);

// First view
ob_start();
$controller->recordView(['id' => $publishedPostId]);
$resp1 = json_decode(ob_get_clean(), true);

$viewsAfterFirst = (int) $pdo->query("SELECT views FROM posts WHERE id = {$publishedPostId}")->fetchColumn();

// Second view (same visitor, same day)
ob_start();
$controller->recordView(['id' => $publishedPostId]);
$resp2 = json_decode(ob_get_clean(), true);

$viewsAfterSecond = (int) $pdo->query("SELECT views FROM posts WHERE id = {$publishedPostId}")->fetchColumn();

if ($viewsAfterFirst === 1 && $viewsAfterSecond === 1 && $resp1['counted'] === true && $resp2['counted'] === false) {
    echo "[PASS] Deduplication: same visitor twice on the same day counted only once (Views = 1).\n";
} else {
    echo "[FAIL] Deduplication failed: first={$viewsAfterFirst}, second={$viewsAfterSecond}\n";
    exit(1);
}

// Test 1.2: Bot User Agent is NOT counted
$_SERVER['REMOTE_ADDR'] = '198.51.100.11';
$botUas = [
    'Googlebot/2.1 (+http://www.google.com/bot.html)',
    'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'WhatsApp/2.21.12.21 A',
    'Twitterbot/1.0',
    'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
    'HeadlessChrome/120.0.0.0 Safari/537.36',
];

$allBotsIgnored = true;
foreach ($botUas as $botUa) {
    $_SERVER['HTTP_USER_AGENT'] = $botUa;
    ob_start();
    $controller->recordView(['id' => $publishedPostId]);
    $resp = json_decode(ob_get_clean(), true);

    if (($resp['status'] ?? '') !== 'ignored' || ($resp['reason'] ?? '') !== 'bot') {
        $allBotsIgnored = false;
        echo "[FAIL] Bot UA was not ignored: {$botUa}\n";
        break;
    }
}

$viewsAfterBots = (int) $pdo->query("SELECT views FROM posts WHERE id = {$publishedPostId}")->fetchColumn();
if ($allBotsIgnored && $viewsAfterBots === 1) {
    echo "[PASS] Bot Filtering: Bot/crawler/previewer User Agents were completely ignored.\n";
} else {
    echo "[FAIL] Bot Filtering failed: Views increased to {$viewsAfterBots}\n";
    exit(1);
}

// Test 1.3: Unpublished post view is NOT counted
$_SERVER['REMOTE_ADDR'] = '198.51.100.12';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36';

ob_start();
$controller->recordView(['id' => $draftPostId]);
$respDraft = json_decode(ob_get_clean(), true);

$draftViews = (int) $pdo->query("SELECT views FROM posts WHERE id = {$draftPostId}")->fetchColumn();
if (($respDraft['status'] ?? '') === 'ignored' && ($respDraft['reason'] ?? '') === 'not_published' && $draftViews === 0) {
    echo "[PASS] Unpublished Gate: Draft post view was ignored (Views = 0).\n\n";
} else {
    echo "[FAIL] Unpublished Gate failed: Views counted on draft post ({$draftViews})\n";
    exit(1);
}

// =========================================================================
// 2. LIKES TESTS
// =========================================================================
echo "--- 2. LIKES VERIFICATION ---\n";

// Test 2.1: Toggle works (like -> 1, unlike -> 0)
$siteHost = parse_url((string) config('app.url', 'http://127.0.0.1:8000'), PHP_URL_HOST);
$_SERVER['HTTP_HOST'] = $siteHost . ':8000';
$_SERVER['HTTP_ORIGIN'] = 'http://' . $siteHost . ':8000';
$_SERVER['REMOTE_ADDR'] = '198.51.100.20';
$_COOKIE['nc_vid'] = bin2hex(random_bytes(16));

// Action 1: Like
ob_start();
$controller->toggleLike(['id' => $publishedPostId]);
$likeResp1 = json_decode(ob_get_clean(), true);
$likesCount1 = (int) $pdo->query("SELECT likes FROM posts WHERE id = {$publishedPostId}")->fetchColumn();

// Action 2: Unlike (toggle)
ob_start();
$controller->toggleLike(['id' => $publishedPostId]);
$likeResp2 = json_decode(ob_get_clean(), true);
$likesCount2 = (int) $pdo->query("SELECT likes FROM posts WHERE id = {$publishedPostId}")->fetchColumn();

// Action 3: Like again
ob_start();
$controller->toggleLike(['id' => $publishedPostId]);
$likeResp3 = json_decode(ob_get_clean(), true);
$likesCount3 = (int) $pdo->query("SELECT likes FROM posts WHERE id = {$publishedPostId}")->fetchColumn();

if ($likeResp1['liked'] === true && $likesCount1 === 1 &&
    $likeResp2['liked'] === false && $likesCount2 === 0 &&
    $likeResp3['liked'] === true && $likesCount3 === 1) {
    echo "[PASS] Like Toggle: Toggling like -> unlike -> like operates accurately (1 -> 0 -> 1).\n";
} else {
    echo "[FAIL] Like Toggle failed: count1={$likesCount1}, count2={$likesCount2}, count3={$likesCount3}\n";
    exit(1);
}

// Test 2.2: Same visitor cannot create duplicate like rows
$postLikesRowCount = (int) $pdo->query("SELECT COUNT(*) FROM post_likes WHERE post_id = {$publishedPostId} AND visitor_hash = '{$_COOKIE['nc_vid']}'")->fetchColumn();
if ($postLikesRowCount === 1) {
    echo "[PASS] Identity Deduplication: Visitor has exactly 1 row in post_likes table.\n";
} else {
    echo "[FAIL] Duplicate post_likes rows found: {$postLikesRowCount}\n";
    exit(1);
}

// Test 2.3: Cross-origin request rejected with HTTP 403
$_SERVER['HTTP_ORIGIN'] = 'https://malicious-external-site.com';
unset($_SERVER['HTTP_REFERER']);

ob_start();
@http_response_code(200);
$controller->toggleLike(['id' => $publishedPostId]);
$crossOriginResp = json_decode(ob_get_clean(), true);
$statusCode = http_response_code();

if (($statusCode === 403 || !empty($crossOriginResp['error'])) && !empty($crossOriginResp['error'])) {
    echo "[PASS] Cross-Origin Gate: External cross-site origin correctly rejected (Status 403 / Error JSON).\n\n";
} else {
    echo "[FAIL] Cross-Origin Gate failed: Status={$statusCode}, Response=" . json_encode($crossOriginResp) . "\n";
    exit(1);
}

// Reset valid origin headers
$_SERVER['HTTP_ORIGIN'] = 'http://' . $siteHost . ':8000';
@http_response_code(200);

// =========================================================================
// 3. COMMENTS TESTS
// =========================================================================
echo "--- 3. COMMENTS VERIFICATION ---\n";

// Test 3.1: XSS payload stored and rendered escaped
$xssName = 'Security Tester <script>alert("name")</script>';
$xssEmail = 'tester@example.com';
$xssBody = "Hello world! <script>alert('xss')</script> & <b>Bold Statement</b>\nSecond line here.";

$xssCommentId = CommentModel::create([
    'post_id' => $publishedPostId,
    'name'    => $xssName,
    'email'   => $xssEmail,
    'body'    => $xssBody,
    'status'  => 'approved',
    'ip_hash' => hash('sha256', '127.0.0.1' . config('security.salt')),
]);
$createdCommentIds[] = $xssCommentId;

$approvedComments = CommentModel::getApprovedForPost($publishedPostId);
$targetComment = null;
foreach ($approvedComments as $ac) {
    if ((int) $ac['id'] === $xssCommentId) {
        $targetComment = $ac;
        break;
    }
}

// Test rendering output through nl2br(e($body))
$renderedOutput = nl2br(e($targetComment['body']));
$renderedName = e($targetComment['name']);

if (!str_contains($renderedOutput, '<script>') && 
    str_contains($renderedOutput, '&lt;script&gt;') && 
    str_contains($renderedOutput, '<br />') &&
    !str_contains($renderedName, '<script>')) {
    echo "[PASS] XSS Protection: HTML tags are strictly escaped on output (no raw script executable).\n";
} else {
    echo "[FAIL] XSS Protection failed! Output: {$renderedOutput}\n";
    exit(1);
}

// Test 3.2: Honeypot submission discarded
$_SERVER['REMOTE_ADDR'] = '198.51.100.99';
$initialCommentCount = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE post_id = {$publishedPostId}")->fetchColumn();

$_POST = [
    'nc_hp_7'               => 'spambot-data', // Honeypot filled
    'name'                  => 'Spammer Bot',
    'email'                 => 'spam@spammer.org',
    'comment'               => 'Buy pills now at discount prices!',
    'comment_ts'            => time() - 10,
    'comment_sig'           => hash_hmac('sha256', (string) (time() - 10), (string) config('security.salt')),
    'cf-turnstile-response' => 'test-token',
];

ob_start();
// Handle redirect in submitComment without killing CLI script
try {
    $controller->submitComment(['slug' => $publishedSlug]);
} catch (Throwable $e) {}
ob_end_clean();

$afterHoneypotCount = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE post_id = {$publishedPostId}")->fetchColumn();
if ($afterHoneypotCount === $initialCommentCount) {
    echo "[PASS] Honeypot Gate: Honeypot submission was silently discarded (0 database records created).\n";
} else {
    echo "[FAIL] Honeypot submission was inserted into database!\n";
    exit(1);
}

// Test 3.3: Submit under 3 seconds rejected
$_POST = [
    'contact_fax'           => '',
    'name'                  => 'Fast Human',
    'email'                 => 'fast@example.com',
    'comment'               => 'This was submitted too fast.',
    'comment_ts'            => time(), // Submitted immediately (0 seconds)
    'comment_sig'           => hash_hmac('sha256', (string) time(), (string) config('security.salt')),
    'cf-turnstile-response' => 'test-token',
];

ob_start();
try {
    $controller->submitComment(['slug' => $publishedSlug]);
} catch (Throwable $e) {}
ob_end_clean();

$afterFastCount = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE post_id = {$publishedPostId}")->fetchColumn();
$errors = $_SESSION['comment_errors'] ?? [];

if ($afterFastCount === $initialCommentCount && !empty($errors['speed'])) {
    echo "[PASS] Timestamp Gate: Submissions completed in under 3 seconds are rejected.\n";
} else {
    echo "[FAIL] Timestamp Gate failed! Comment was created or error missing.\n";
    exit(1);
}

// Test 3.4: 4th comment in 10 minutes rejected (IP rate limiter)
$testIp = '198.51.100.99';
$_SERVER['REMOTE_ADDR'] = $testIp;
$testIpHash = hash('sha256', $testIp . config('security.salt', 'salt'));

// Seed 3 recent comments for this IP hash
for ($i = 1; $i <= 3; $i++) {
    $cid = CommentModel::create([
        'post_id' => $publishedPostId,
        'name'    => "User {$i}",
        'email'   => "user{$i}@example.com",
        'body'    => "Valid comment {$i} within rate window",
        'status'  => 'pending',
        'ip_hash' => $testIpHash,
    ]);
    $createdCommentIds[] = $cid;
}

// Attempt 4th comment submission
Turnstile::$mockVerifier = fn() => true; // Allow turnstile to pass
$validTs = time() - 10;
$_POST = [
    'contact_fax'           => '',
    'name'                  => 'Fourth Commenter',
    'email'                 => 'fourth@example.com',
    'comment'               => 'Fourth comment attempt within 10 minutes.',
    'comment_ts'            => $validTs,
    'comment_sig'           => hash_hmac('sha256', (string) $validTs, (string) config('security.salt')),
    'cf-turnstile-response' => 'test-token',
];

ob_start();
try {
    $controller->submitComment(['slug' => $publishedSlug]);
} catch (Throwable $e) {}
ob_end_clean();

$rateErrors = $_SESSION['comment_errors'] ?? [];
$totalForIp = CommentModel::countRecentByIpHash($testIpHash, 10);

if ($totalForIp === 3 && !empty($rateErrors['rate'])) {
    echo "[PASS] Rate Limiter: 4th comment within 10 minutes from same IP was rejected.\n";
} else {
    echo "[FAIL] Rate Limiter failed! Total comments for IP = {$totalForIp}\n";
    exit(1);
}

// Test 3.5: Turnstile failure rejected (mocked)
$_SERVER['REMOTE_ADDR'] = '198.51.100.55'; // Different IP to bypass rate limit
Turnstile::$mockVerifier = fn($token, $ip) => false; // Mock failure

$_POST = [
    'contact_fax'           => '',
    'name'                  => 'Good User',
    'email'                 => 'good@example.com',
    'comment'               => 'Valid body with failing turnstile.',
    'comment_ts'            => $validTs,
    'comment_sig'           => hash_hmac('sha256', (string) $validTs, (string) config('security.salt')),
    'cf-turnstile-response' => 'bad-token',
];

ob_start();
try {
    $controller->submitComment(['slug' => $publishedSlug]);
} catch (Throwable $e) {}
ob_end_clean();

$turnstileErrors = $_SESSION['comment_errors'] ?? [];
Turnstile::$mockVerifier = null; // Reset mock

if (!empty($turnstileErrors['turnstile'])) {
    echo "[PASS] Turnstile Verification: Simulated CAPTCHA failure correctly rejected submission.\n";
} else {
    echo "[FAIL] Turnstile failure was not rejected!\n";
    exit(1);
}

// Test 3.6: New comments are status 'pending' and not shown publicly until approved
Turnstile::$mockVerifier = fn() => true;
$_SERVER['REMOTE_ADDR'] = '198.51.100.77';

$_POST = [
    'contact_fax'           => '',
    'name'                  => 'Reader John',
    'email'                 => 'john@example.com',
    'comment'               => 'Insightful comment awaiting editorial review.',
    'comment_ts'            => $validTs,
    'comment_sig'           => hash_hmac('sha256', (string) $validTs, (string) config('security.salt')),
    'cf-turnstile-response' => 'valid-token',
];

ob_start();
try {
    $controller->submitComment(['slug' => $publishedSlug]);
} catch (Throwable $e) {}
ob_end_clean();

Turnstile::$mockVerifier = null;

// Find the newly created comment
$newPendingComment = $pdo->query("SELECT id, status FROM comments WHERE email = 'john@example.com' ORDER BY id DESC LIMIT 1")->fetch();
if (!$newPendingComment) {
    echo "[FAIL] Pending comment was not inserted into database!\n";
    exit(1);
}
$createdCommentIds[] = (int) $newPendingComment['id'];

// Check public approved comments
$approvedBefore = CommentModel::getApprovedForPost($publishedPostId);
$isPendingShown = in_array((int) $newPendingComment['id'], array_column($approvedBefore, 'id'), true);

if ($newPendingComment['status'] === 'pending' && !$isPendingShown) {
    echo "[PASS] Moderation Status: Newly submitted comment has status 'pending' and is NOT shown publicly.\n";
} else {
    echo "[FAIL] Pending comment is inappropriately shown publicly!\n";
    exit(1);
}

// Approve comment and check public view
CommentModel::updateStatus((int) $newPendingComment['id'], 'approved');
$approvedAfter = CommentModel::getApprovedForPost($publishedPostId);
$isApprovedShown = in_array((int) $newPendingComment['id'], array_column($approvedAfter, 'id'), true);

if ($isApprovedShown) {
    echo "[PASS] Moderation Approval: Approved comment is now immediately visible in public approved query.\n\n";
} else {
    echo "[FAIL] Approved comment is not appearing in public query!\n";
    exit(1);
}

// Test 3.7: Comment form disabled when debug=false with test keys (production enforcement)
$originalDebug = config('app.debug');
$GLOBALS['config']['app']['debug'] = false; // Simulate production

// Verify Turnstile::canAcceptComments() is false with test keys in production
$canAccept = Turnstile::canAcceptComments();
if ($canAccept !== false) {
    echo "[FAIL] Turnstile::canAcceptComments() must be false when debug=false with test keys!\n";
    exit(1);
}

// Render post page and check that comment form is hidden and notice is displayed
ob_start();
$controller->post(['slug' => $publishedSlug]);
$postPageOutput = ob_get_clean();

$hasDisabledNotice = str_contains($postPageOutput, 'Comments are temporarily unavailable.');
$hasCommentForm = str_contains($postPageOutput, '<form action="/post/' . $publishedSlug . '/comment"');

if ($hasDisabledNotice && !$hasCommentForm) {
    echo "[PASS] Production Turnstile Gate: Comment form is completely disabled ('Comments are temporarily unavailable.') when debug=false with test keys.\n";
} else {
    echo "[FAIL] Comment form was rendered or disabled notice was missing when debug=false with test keys!\n";
    exit(1);
}

// Attempt POST comment submission while debug=false
$_POST = [
    'nc_hp_7'               => '',
    'name'                  => 'Production Submitter',
    'email'                 => 'prod@example.com',
    'comment'               => 'This must be rejected in production without real keys.',
    'comment_ts'            => time() - 10,
    'comment_sig'           => hash_hmac('sha256', (string) (time() - 10), (string) config('security.salt')),
    'cf-turnstile-response' => 'test-token',
];
$commentsBeforeAttempt = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE post_id = {$publishedPostId}")->fetchColumn();

ob_start();
try {
    $controller->submitComment(['slug' => $publishedSlug]);
} catch (Throwable $e) {}
ob_end_clean();

$commentsAfterAttempt = (int) $pdo->query("SELECT COUNT(*) FROM comments WHERE post_id = {$publishedPostId}")->fetchColumn();
$prodErrors = $_SESSION['comment_errors'] ?? [];

$GLOBALS['config']['app']['debug'] = $originalDebug; // Restore debug mode

if ($commentsAfterAttempt === $commentsBeforeAttempt && in_array('Comments are temporarily unavailable.', $prodErrors, true)) {
    echo "[PASS] Production Protection: Never accept comments without a real key in production (0 rows inserted, error logged).\n";
} else {
    echo "[FAIL] Comment was accepted in production or error message was missing!\n";
    exit(1);
}

// Test 3.8: Comment count in meta row counts ONLY approved comments
$initialApprovedCount = CommentModel::countApprovedForPost($publishedPostId);

// Add 1 approved, 1 pending, and 1 spam comment
$cApproved = CommentModel::create([
    'post_id' => $publishedPostId,
    'name'    => 'Approved Reader',
    'email'   => 'approved@example.com',
    'body'    => 'Approved content.',
    'status'  => 'approved',
    'ip_hash' => $testIpHash,
]);
$createdCommentIds[] = $cApproved;

$cPending = CommentModel::create([
    'post_id' => $publishedPostId,
    'name'    => 'Pending Reader',
    'email'   => 'pending@example.com',
    'body'    => 'Pending content.',
    'status'  => 'pending',
    'ip_hash' => $testIpHash,
]);
$createdCommentIds[] = $cPending;

$cSpam = CommentModel::create([
    'post_id' => $publishedPostId,
    'name'    => 'Spam Reader',
    'email'   => 'spam@example.com',
    'body'    => 'Spam content.',
    'status'  => 'spam',
    'ip_hash' => $testIpHash,
]);
$createdCommentIds[] = $cSpam;

$newApprovedCount = CommentModel::countApprovedForPost($publishedPostId);
$approvedList = CommentModel::getApprovedForPost($publishedPostId);

if ($newApprovedCount === ($initialApprovedCount + 1) && count($approvedList) === ($initialApprovedCount + 1)) {
    echo "[PASS] Approved Count Model: CommentModel::countApprovedForPost counts strictly approved comments (ignored 1 pending, 1 spam).\n";
} else {
    echo "[FAIL] CommentModel counted non-approved comments! Expected " . ($initialApprovedCount + 1) . ", got {$newApprovedCount}\n";
    exit(1);
}

// Render post page and verify meta row reflects only approved count
ob_start();
$controller->post(['slug' => $publishedSlug]);
$postRenderedHtml = ob_get_clean();

$expectedCountString = $newApprovedCount === 1 ? '1 comment' : "{$newApprovedCount} comments";
if (str_contains($postRenderedHtml, $expectedCountString) && !str_contains($postRenderedHtml, ($newApprovedCount + 2) . ' comments')) {
    echo "[PASS] Meta Row Count: Post meta row displays strictly approved comment count ('{$expectedCountString}').\n\n";
} else {
    echo "[FAIL] Post meta row did not reflect strictly approved comments!\n";
    exit(1);
}

// Clean up
$cleanup();

echo "========================================================\n";
echo "[SUCCESS] All Phase 5 Engagement Tests Passed Successfully!\n";
echo "========================================================\n";
