<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Photo Gallery Test (Phase 4C)
 *
 * Verifies:
 * 1. Upload test image through ImageUploader / GalleryModel.
 * 2. Assert WebP variants (1600, 800, 400) exist on disk.
 * 3. Assert alt text is REQUIRED and rejects filename patterns.
 * 4. Assert reordering with move up / move down.
 * 5. Assert deleting removes both the database row and all variant files from disk.
 *
 * Usage: php tests/gallery_check.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/public_html/app');

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/ImageUploader.php';
require_once APP_PATH . '/models/GalleryModel.php';

use App\Core\Database;
use App\Core\ImageUploader;
use App\Models\GalleryModel;

echo "========================================================\n";
echo "NatConnect Foundation — Phase 4C Photo Gallery Tests\n";
echo "========================================================\n";

$pdo = Database::getConnection();
echo "[OK] Connected to database: " . config('db.name') . "\n\n";

$tmpDir = sys_get_temp_dir() . '/natconnect_gallery_test_' . bin2hex(random_bytes(4));
mkdir($tmpDir, 0755, true);

$createdItemIds = [];
$uploadedPrimaryUrls = [];

$cleanup = function () use ($pdo, &$createdItemIds, &$uploadedPrimaryUrls, $tmpDir) {
    if (!empty($createdItemIds)) {
        $in = implode(',', array_map('intval', $createdItemIds));
        $pdo->exec("DELETE FROM gallery_items WHERE id IN ({$in})");
        echo "[CLEANUP] Deleted test gallery items: {$in}\n";
    }
    foreach ($uploadedPrimaryUrls as $url) {
        ImageUploader::deleteVariants($url);
    }
    // Remove temp test dir
    @array_map('unlink', glob($tmpDir . '/*'));
    @rmdir($tmpDir);
};

register_shutdown_function($cleanup);

try {
    // ---------------------------------------------------------
    // TEST 1: Alt Text Validation Enforcement
    // ---------------------------------------------------------
    echo "--- Test 1: Alt Text Validation ---\n";

    // 1A: Empty alt text must be rejected
    $emptyAltRejected = false;
    try {
        GalleryModel::validateAltText('');
    } catch (InvalidArgumentException $e) {
        $emptyAltRejected = true;
    }
    assert($emptyAltRejected, "Empty alt text must throw InvalidArgumentException");
    echo "  [PASS] Empty alt text is strictly rejected.\n";

    // 1B: Filename with extension must be rejected
    $filenameRejected = false;
    try {
        GalleryModel::validateAltText('mangroves_panje.jpg');
    } catch (InvalidArgumentException $e) {
        $filenameRejected = true;
    }
    assert($filenameRejected, "Alt text ending with .jpg must be rejected");
    echo "  [PASS] Alt text with filename extension (.jpg/.png/.webp) is strictly rejected.\n";

    // 1C: Short string without spaces must be rejected
    $shortSingleWordRejected = false;
    try {
        GalleryModel::validateAltText('wetland');
    } catch (InvalidArgumentException $e) {
        $shortSingleWordRejected = true;
    }
    assert($shortSingleWordRejected, "Short single-word alt text under 10 chars must be rejected");
    echo "  [PASS] Single-word alt text under 10 chars is rejected.\n";

    // 1D: Valid descriptive alt text accepted
    $validAlt = GalleryModel::validateAltText("Volunteers planting mangroves at Panje wetland.");
    assert($validAlt === "Volunteers planting mangroves at Panje wetland.", "Valid alt text must be returned cleanly");
    echo "  [PASS] Valid descriptive alt text is accepted.\n\n";

    // ---------------------------------------------------------
    // TEST 2: Image Upload & Variant Generation
    // ---------------------------------------------------------
    echo "--- Test 2: Upload Test Image & Variant Generation ---\n";

    // Create a real 600x450 JPEG image
    $img = imagecreatetruecolor(600, 450);
    $green = imagecolorallocate($img, 46, 125, 50);
    imagefill($img, 0, 0, $green);
    $testFilePath = $tmpDir . '/test_gallery.jpg';
    imagejpeg($img, $testFilePath, 85);
    imagedestroy($img);

    $filePayload = [
        'name'     => 'test_gallery.jpg',
        'type'     => 'image/jpeg',
        'tmp_name' => $testFilePath,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($testFilePath),
    ];

    $uploadResult = ImageUploader::upload($filePayload);
    $primaryUrl = $uploadResult['primary_url'];
    $uploadedPrimaryUrls[] = $primaryUrl;

    echo "  Uploaded primary URL: {$primaryUrl}\n";

    // Verify variants exist on disk
    $docRoot = ROOT_PATH . '/public_html';
    $var1600 = $docRoot . ImageUploader::variantUrl($primaryUrl, 1600);
    $var800  = $docRoot . ImageUploader::variantUrl($primaryUrl, 800);
    $var400  = $docRoot . ImageUploader::variantUrl($primaryUrl, 400);

    assert(file_exists($var1600), "Variant 1600 must exist on disk: {$var1600}");
    assert(file_exists($var800), "Variant 800 must exist on disk: {$var800}");
    assert(file_exists($var400), "Variant 400 must exist on disk: {$var400}");
    echo "  [PASS] All WebP variants (1600, 800, 400) exist on disk.\n\n";

    // ---------------------------------------------------------
    // TEST 3: Create Gallery Item through Model
    // ---------------------------------------------------------
    echo "--- Test 3: GalleryModel::create ---\n";
    $itemId = GalleryModel::create([
        'image_path' => $primaryUrl,
        'alt_text'   => 'Flamingos feeding at NRI wetland in Navi Mumbai',
        'caption'    => 'Annual winter flamingo migration documented by NatConnect.',
        'sort_order' => 0,
    ]);
    $createdItemIds[] = $itemId;

    assert($itemId > 0, "Gallery item ID must be greater than 0");
    $item = GalleryModel::getById($itemId);
    assert($item !== null, "Gallery item must be queryable by ID");
    assert($item['image_path'] === $primaryUrl, "image_path must match uploaded primary URL");
    assert($item['alt_text'] === 'Flamingos feeding at NRI wetland in Navi Mumbai', "alt_text must match");
    assert($item['caption'] === 'Annual winter flamingo migration documented by NatConnect.', "caption must match");
    echo "  [PASS] Gallery item created and retrieved successfully (ID #{$itemId}).\n\n";

    // ---------------------------------------------------------
    // TEST 4: Update Gallery Item
    // ---------------------------------------------------------
    echo "--- Test 4: GalleryModel::update ---\n";
    $updated = GalleryModel::update($itemId, [
        'alt_text' => 'Updated: Flamingos resting at Talawe wetland',
        'caption'  => 'Updated caption text.',
    ]);
    assert($updated === true, "GalleryModel::update must return true");
    $updatedItem = GalleryModel::getById($itemId);
    assert($updatedItem['alt_text'] === 'Updated: Flamingos resting at Talawe wetland', "alt_text must be updated");
    assert($updatedItem['caption'] === 'Updated caption text.', "caption must be updated");
    echo "  [PASS] Gallery item metadata updated successfully.\n\n";

    // ---------------------------------------------------------
    // TEST 5: Reordering (Move Up / Move Down)
    // ---------------------------------------------------------
    echo "--- Test 5: Reordering ---\n";
    // Create a second gallery item to test swapping positions
    $item2Id = GalleryModel::create([
        'image_path' => $primaryUrl,
        'alt_text'   => 'Second photo of mangrove wetlands at Panje',
        'caption'    => 'Second photo caption',
        'sort_order' => 1,
    ]);
    $createdItemIds[] = $item2Id;

    $allItemsBefore = GalleryModel::getAll();
    $firstIdBefore = (int) $allItemsBefore[0]['id'];

    // Move first item down
    $movedDown = GalleryModel::reorder($firstIdBefore, 'down');
    assert($movedDown === true, "Reorder down must succeed");

    $allItemsAfter = GalleryModel::getAll();
    $firstIdAfter = (int) $allItemsAfter[0]['id'];
    assert($firstIdBefore !== $firstIdAfter, "First item position must change after moving down");
    echo "  [PASS] Item reordered down successfully.\n";

    // Move it back up
    $movedUp = GalleryModel::reorder($firstIdBefore, 'up');
    assert($movedUp === true, "Reorder up must succeed");
    $allItemsRestored = GalleryModel::getAll();
    assert((int) $allItemsRestored[0]['id'] === $firstIdBefore, "First item must be restored to top position");
    echo "  [PASS] Item reordered up successfully.\n\n";

    // ---------------------------------------------------------
    // TEST 6: Delete Removes Database Row & Variant Files
    // ---------------------------------------------------------
    echo "--- Test 6: GalleryModel::delete File Cleanup ---\n";
    assert(file_exists($var1600), "Variant 1600 must exist prior to deletion");
    assert(file_exists($var800), "Variant 800 must exist prior to deletion");
    assert(file_exists($var400), "Variant 400 must exist prior to deletion");

    $deleted = GalleryModel::delete($itemId);
    assert($deleted === true, "GalleryModel::delete must return true");

    // Assert database row is gone
    $rowAfterDelete = GalleryModel::getById($itemId);
    assert($rowAfterDelete === null, "Database row must be deleted");
    echo "  [PASS] Database row successfully deleted.\n";

    // Assert files are deleted from disk
    assert(!file_exists($var1600), "Variant 1600 must be removed from disk");
    assert(!file_exists($var800), "Variant 800 must be removed from disk");
    assert(!file_exists($var400), "Variant 400 must be removed from disk");
    echo "  [PASS] All WebP image variants (1600, 800, 400) removed from disk.\n\n";

    // Remove item2
    GalleryModel::delete($item2Id);

    echo "========================================================\n";
    echo "ALL PHASE 4C GALLERY ASSERTIONS PASSED SUCCESSFULLY!\n";
    echo "========================================================\n";

} catch (Throwable $e) {
    echo "\n[FAIL] Assertion / Test Error: " . $e->getMessage() . "\n";
    echo "In " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
