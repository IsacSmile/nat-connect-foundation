<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Editable Pages Test Suite
 *
 * Verifies:
 * 1. System pages cannot be deleted, system slug cannot be changed, Privacy cannot be unpublished.
 * 2. Reserved slugs are strictly rejected on save.
 * 3. Draft page triggers 404 publicly (preview works for admins).
 * 4. Changing published page slug creates 301 redirect in redirects table.
 * 5. Page content is sanitized with HTMLPurifier (scripts and foreign iframes stripped).
 * 6. Fixture cleanup.
 *
 * Usage: php tests/pages_check.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once ROOT_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/Sanitizer.php';
require_once APP_PATH . '/core/ImageUploader.php';
require_once APP_PATH . '/core/Turnstile.php';
require_once APP_PATH . '/core/View.php';
require_once APP_PATH . '/models/CommentModel.php';
require_once APP_PATH . '/models/RedirectModel.php';
require_once APP_PATH . '/models/SettingModel.php';
require_once APP_PATH . '/models/CategoryModel.php';
require_once APP_PATH . '/models/PostModel.php';
require_once APP_PATH . '/models/EngagementModel.php';
require_once APP_PATH . '/models/UserModel.php';
require_once APP_PATH . '/models/PageModel.php';
require_once APP_PATH . '/controllers/PublicController.php';

use App\Core\Auth;
use App\Core\Database;
use App\Controllers\PublicController;
use App\Models\PageModel;
use App\Models\RedirectModel;

echo "========================================================\n";
echo "NatConnect Foundation — Editable Pages Verification Suite\n";
echo "========================================================\n\n";

$pdo = Database::getConnection();
echo "[OK] Connected to database: " . config('db.name') . "\n";

$createdPageIds = [];
$createdRedirects = [];

// Cleanup handler
$cleanup = function () use ($pdo, &$createdPageIds, &$createdRedirects) {
    if (!empty($createdPageIds)) {
        $in = implode(',', array_map('intval', $createdPageIds));
        $pdo->exec("DELETE FROM pages WHERE id IN ({$in}) AND is_system = 0");
    }
    if (!empty($createdRedirects)) {
        foreach ($createdRedirects as $oldPath) {
            $stmt = $pdo->prepare("DELETE FROM redirects WHERE old_path = :old");
            $stmt->execute(['old' => $oldPath]);
        }
    }
};

try {
    // -------------------------------------------------------------
    // 1. System Pages Protection
    // -------------------------------------------------------------
    echo "\n--- 1. SYSTEM PAGES INTEGRITY ---\n";

    // 1.1 Cannot delete system page
    $aboutPage = PageModel::findBySlug('about');
    if (!$aboutPage || empty($aboutPage['is_system'])) {
        echo "[FAIL] System 'about' page is missing or not marked is_system=1.\n";
        exit(1);
    }

    $deleteBlocked = false;
    try {
        PageModel::delete((int) $aboutPage['id']);
    } catch (RuntimeException $e) {
        $deleteBlocked = true;
    }

    $stillExists = (bool) $pdo->query("SELECT 1 FROM pages WHERE id = {$aboutPage['id']}")->fetchColumn();
    if ($deleteBlocked && $stillExists) {
        echo "[PASS] System Page Gate: System page cannot be deleted (exception thrown and DB row preserved).\n";
    } else {
        echo "[FAIL] System page was deleted or did not throw exception!\n";
        exit(1);
    }

    // 1.2 System page slug cannot be changed
    $slugChangeBlocked = false;
    try {
        PageModel::update((int) $aboutPage['id'], [
            'title'   => 'About NatConnect',
            'slug'    => 'about-modified-slug',
            'content' => $aboutPage['content'],
            'status'  => 'published',
        ]);
    } catch (InvalidArgumentException $e) {
        $slugChangeBlocked = true;
    }

    if ($slugChangeBlocked) {
        echo "[PASS] System Slug Gate: Changing system page slug is rejected.\n";
    } else {
        echo "[FAIL] System page slug was modified without exception!\n";
        exit(1);
    }

    // 1.3 Privacy policy cannot be unpublished
    $privacyPage = PageModel::findBySlug('privacy');
    if (!$privacyPage || empty($privacyPage['is_system'])) {
        echo "[FAIL] System 'privacy' page is missing or not marked is_system=1.\n";
        exit(1);
    }

    $unpublishBlocked = false;
    try {
        PageModel::update((int) $privacyPage['id'], [
            'title'   => 'Privacy Policy',
            'slug'    => 'privacy',
            'content' => $privacyPage['content'],
            'status'  => 'draft', // Attempting to unpublish
        ]);
    } catch (InvalidArgumentException $e) {
        $unpublishBlocked = true;
    }

    if ($unpublishBlocked) {
        echo "[PASS] Privacy Status Gate: Privacy Policy cannot be unpublished.\n";
    } else {
        echo "[FAIL] Privacy Policy was unpublished without exception!\n";
        exit(1);
    }

    // -------------------------------------------------------------
    // 2. Reserved Slugs Validation
    // -------------------------------------------------------------
    echo "\n--- 2. RESERVED SLUGS VALIDATION ---\n";

    $reservedSlugs = PageModel::RESERVED_SLUGS;
    $allBlocked = true;

    foreach (['admin', 'api', 'gallery', 'donate', 'setup', 'index', 'causes'] as $resSlug) {
        $caught = false;
        try {
            PageModel::create([
                'title'   => "Test Reserved {$resSlug}",
                'slug'    => $resSlug,
                'content' => '<p>Content</p>',
                'status'  => 'draft',
            ]);
        } catch (InvalidArgumentException $e) {
            $caught = true;
        }

        if (!$caught) {
            $allBlocked = false;
            echo "[FAIL] Reserved slug '{$resSlug}' was accepted!\n";
            exit(1);
        }
    }

    if ($allBlocked) {
        echo "[PASS] Reserved Slugs: Core route slugs (admin, api, gallery, donate, setup, index, etc.) are strictly rejected.\n";
    }

    // -------------------------------------------------------------
    // 3. Draft Page Visibility & 404 Handling
    // -------------------------------------------------------------
    echo "\n--- 3. DRAFT PAGE 404 & ADMIN PREVIEW ---\n";

    $draftSlug = 'test-draft-page-' . bin2hex(random_bytes(4));
    $draftId = PageModel::create([
        'title'   => 'Upcoming Environmental Project',
        'slug'    => $draftSlug,
        'content' => '<p>Draft secret initiatives.</p>',
        'status'  => 'draft',
    ]);
    $createdPageIds[] = $draftId;

    // Simulate public non-logged-in guest request
    $_SESSION = []; // Clear session (no admin login)
    $controller = new PublicController();

    // Verify PublicController::showPage triggers notFound for draft
    ob_start();
    $controller->showPage(['slug' => $draftSlug]);
    $publicOutput = ob_get_clean();

    $is404 = (http_response_code() === 404) || str_contains($publicOutput, '404') || str_contains($publicOutput, 'Page Not Found');

    if ($is404) {
        echo "[PASS] Public Draft Gate: Draft page returns 404 Not Found for non-admin visitors.\n";
    } else {
        echo "[FAIL] Draft page did not trigger 404 for public visitor!\n";
        exit(1);
    }

    // Simulate logged-in admin previewing the draft
    $_SESSION['auth_user_id'] = 1; // Logged in as admin
    ob_start();
    $controller->showPage(['slug' => $draftSlug]);
    $adminOutput = ob_get_clean();

    if (str_contains($adminOutput, 'Upcoming Environmental Project') && str_contains($adminOutput, 'Preview: not published')) {
        echo "[PASS] Admin Preview: Logged-in admin can preview draft page with preview banner.\n";
    } else {
        echo "[FAIL] Admin could not preview draft page!\n";
        exit(1);
    }
    $_SESSION = []; // Reset

    // -------------------------------------------------------------
    // 4. Slug Change & 301 Redirect Generation
    // -------------------------------------------------------------
    echo "\n--- 4. SLUG CHANGE 301 REDIRECT CREATION ---\n";

    $origSlug = 'annual-wetland-survey-' . bin2hex(random_bytes(4));
    $newSlug  = 'mangrove-wetland-survey-' . bin2hex(random_bytes(4));

    $pubPageId = PageModel::create([
        'title'   => 'Annual Wetland Survey',
        'slug'    => $origSlug,
        'content' => '<p>Published survey content.</p>',
        'status'  => 'published',
    ]);
    $createdPageIds[] = $pubPageId;

    // Update published page with new slug
    PageModel::update($pubPageId, [
        'title'   => 'Annual Wetland Survey',
        'slug'    => $newSlug,
        'content' => '<p>Published survey content updated.</p>',
        'status'  => 'published',
    ]);

    $createdRedirects[] = '/' . $origSlug;

    // Verify 301 redirect row exists in redirects table
    $stmt = $pdo->prepare("SELECT new_path FROM redirects WHERE old_path = :old");
    $stmt->execute(['old' => '/' . $origSlug]);
    $targetPath = $stmt->fetchColumn();

    if ($targetPath === '/' . $newSlug) {
        echo "[PASS] 301 Redirect: Changing published page slug automatically created 301 redirect in redirects table.\n";
    } else {
        echo "[FAIL] Redirect was not created! Expected '/{$newSlug}', got " . var_export($targetPath, true) . "\n";
        exit(1);
    }

    // -------------------------------------------------------------
    // 5. HTMLPurifier Sanitization
    // -------------------------------------------------------------
    echo "\n--- 5. CONTENT SANITIZATION ---\n";

    $xssSlug = 'sanitization-test-' . bin2hex(random_bytes(4));
    $dirtyHtml = '<p>Safe intro</p><script>alert("XSS")</script><img src="test.jpg" onerror="alert(1)"><iframe src="https://attacker.com/evil"></iframe><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>';

    $cleanId = PageModel::create([
        'title'   => 'Sanitization Test Page',
        'slug'    => $xssSlug,
        'content' => $dirtyHtml,
        'status'  => 'published',
    ]);
    $createdPageIds[] = $cleanId;

    $savedPage = PageModel::findById($cleanId);
    $storedContent = $savedPage['content'];

    $scriptStripped = !str_contains($storedContent, '<script') && !str_contains($storedContent, 'alert("XSS")');
    $onerrorStripped = !str_contains($storedContent, 'onerror=');
    $foreignIframeStripped = !str_contains($storedContent, 'attacker.com');
    $safeYoutubePreserved = str_contains($storedContent, 'youtube.com/embed');

    if ($scriptStripped && $onerrorStripped && $foreignIframeStripped && $safeYoutubePreserved) {
        echo "[PASS] HTMLPurifier: Malicious scripts, onerror handlers, and untrusted iframes stripped; safe markup and YouTube embeds preserved.\n";
    } else {
        echo "[FAIL] HTMLPurifier sanitization failed! Content: {$storedContent}\n";
        exit(1);
    }

    // -------------------------------------------------------------
    // Clean up
    // -------------------------------------------------------------
    $cleanup();
    echo "\n[CLEANUP] Deleted test pages and test redirects.\n";

    echo "========================================================\n";
    echo "[SUCCESS] All Page Model & Public Routing Tests Passed!\n";
    echo "========================================================\n";

} catch (Throwable $e) {
    $cleanup();
    echo "\n[ERROR] Unhandled exception during pages test: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
