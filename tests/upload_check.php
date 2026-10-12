<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Upload Security Verification Test
 *
 * Verifies ImageUploader security requirements:
 * 1. Clean real JPEG is accepted, re-encoded to WebP, EXIF metadata stripped.
 * 2. PHP script disguised as .jpg is rejected by finfo content detection.
 * 3. Polyglot JPEG with appended PHP code is re-encoded, stripping the malicious payload.
 * 4. Oversized image (>8 MB or >25 MP) is rejected.
 * 5. Directory protection: index.html created in uploads/YYYY/ and uploads/YYYY/MM/.
 * 6. Variant deletion: deleteVariants() cleans up all generated files.
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
require_once APP_PATH . '/core/ImageUploader.php';

use App\Core\ImageUploader;

$passes = 0;
$failures = 0;

function assertTest(string $description, bool $condition, string $details = ''): void {
    global $passes, $failures;
    if ($condition) {
        $passes++;
        echo "  [PASS] {$description}\n";
    } else {
        $failures++;
        echo "  [FAIL] {$description}" . ($details !== '' ? " — {$details}" : '') . "\n";
    }
}

echo "=== NatConnect Foundation: Image Upload Security Verification ===\n\n";

// Check GD Support
$gdSupport = ImageUploader::getGdSupport();
echo "PHP GD Environment Check:\n";
echo "  - GD Extension Loaded: " . ($gdSupport['gd_loaded'] ? "YES ({$gdSupport['version']})" : "NO") . "\n";
echo "  - WebP Support:        " . ($gdSupport['webp_support'] ? "YES" : "NO") . "\n";
echo "  - JPEG Support:        " . ($gdSupport['jpeg_support'] ? "YES" : "NO") . "\n";
echo "  - PNG Support:         " . ($gdSupport['png_support'] ? "YES" : "NO") . "\n\n";

if (!$gdSupport['gd_loaded']) {
    echo "[ERROR] PHP GD extension is NOT loaded. Cannot run GD re-encoding tests.\n";
    echo "Please install php-gd (e.g., sudo apt-get install php8.3-gd).\n\n";
    exit(1);
}

$tmpDir = sys_get_temp_dir() . '/natconnect_upload_tests_' . bin2hex(random_bytes(4));
mkdir($tmpDir, 0755, true);

try {
    // -------------------------------------------------------------
    // Test 1: Real clean JPEG with embedded EXIF metadata
    // -------------------------------------------------------------
    echo "Test 1: Real Clean JPEG with EXIF metadata...\n";

    // Create a 200x150 real JPEG using GD
    $img = imagecreatetruecolor(200, 150);
    $bg = imagecolorallocate($img, 46, 125, 50); // Nature green
    imagefill($img, 0, 0, $bg);
    $rawJpgPath = $tmpDir . '/clean_raw.jpg';
    imagejpeg($img, $rawJpgPath, 90);
    imagedestroy($img);

    // Embed an EXIF APP1 block containing secret GPS & Camera metadata
    $rawBytes = file_get_contents($rawJpgPath);
    $exifPayload = "Exif\x00\x00II*\x00\x08\x00\x00\x00GPS_COORDINATES_18.9876_73.0123_SECRET_ACTIVIST_LOCATION_TestCameraModel";
    $app1Marker = "\xFF\xE1" . pack('n', strlen($exifPayload) + 2) . $exifPayload;
    // Insert APP1 right after SOI (FF D8)
    $jpegWithExif = substr($rawBytes, 0, 2) . $app1Marker . substr($rawBytes, 2);
    $exifJpgPath = $tmpDir . '/clean_with_exif.jpg';
    file_put_contents($exifJpgPath, $jpegWithExif);

    assertTest("Source image contains test EXIF/GPS marker", str_contains(file_get_contents($exifJpgPath), 'GPS_COORDINATES'));

    $cleanResult = ImageUploader::processFile($exifJpgPath, 'nature_photo.jpg', (int) filesize($exifJpgPath));

    assertTest("Upload accepted with random hash", !empty($cleanResult['hash']) && strlen($cleanResult['hash']) === 32);
    assertTest("Primary location URL returned", str_starts_with($cleanResult['location'], '/uploads/'));

    // Check generated files on disk
    $webRoot = ROOT_PATH . '/public_html';
    $primaryDiskPath = $webRoot . $cleanResult['location'];
    assertTest("Primary variant file exists on disk", file_exists($primaryDiskPath));

    // Verify format is WebP (or fallback JPEG)
    $outputHeader = file_get_contents($primaryDiskPath, false, null, 0, 12);
    $isWebP = str_contains($outputHeader, 'WEBP') || str_starts_with($outputHeader, 'RIFF');
    assertTest("Output is re-encoded WebP format", $isWebP);

    // Verify EXIF metadata is completely stripped
    $outputContents = file_get_contents($primaryDiskPath);
    assertTest("EXIF GPS metadata is completely stripped from output", !str_contains($outputContents, 'GPS_COORDINATES') && !str_contains($outputContents, 'TestCameraModel'));

    // Verify responsive variants exist (1600, 800, 400)
    foreach ([1600, 800, 400] as $w) {
        $variantPath = $webRoot . $cleanResult['variants'][$w];
        assertTest("Variant width {$w} exists on disk", file_exists($variantPath));
    }

    // Verify index.html exists in upload directory (directory listing defense)
    $uploadDir = dirname($primaryDiskPath);
    assertTest("uploads/YYYY/MM/ contains protective index.html", file_exists($uploadDir . '/index.html'));
    assertTest("uploads/YYYY/ contains protective index.html", file_exists(dirname($uploadDir) . '/index.html'));

    // -------------------------------------------------------------
    // Test 2: PHP file disguised as .jpg
    // -------------------------------------------------------------
    echo "\nTest 2: PHP file disguised as .jpg...\n";
    $phpFakeJpg = $tmpDir . '/shell.jpg';
    file_put_contents($phpFakeJpg, '<?php phpinfo(); /* malicious code */ ?>');

    $rejected = false;
    $rejectReason = '';
    try {
        ImageUploader::processFile($phpFakeJpg, 'shell.jpg', (int) filesize($phpFakeJpg));
    } catch (\InvalidArgumentException $e) {
        $rejected = true;
        $rejectReason = $e->getMessage();
    }

    assertTest("PHP script disguised as .jpg is rejected", $rejected, "Message: {$rejectReason}");

    // -------------------------------------------------------------
    // Test 3: JPEG with PHP code appended (Polyglot / Trojan)
    // -------------------------------------------------------------
    echo "\nTest 3: JPEG with appended PHP code (Polyglot)...\n";
    $injectedJpg = $tmpDir . '/trojan.jpg';
    $payloadString = '<?php echo "MALICIOUS_BACKDOOR_EXECUTED"; system($_GET["cmd"]); ?>';
    file_put_contents($injectedJpg, $rawBytes . "\n" . $payloadString);

    assertTest("Trojan file contains embedded PHP payload", str_contains(file_get_contents($injectedJpg), 'MALICIOUS_BACKDOOR_EXECUTED'));

    $trojanResult = ImageUploader::processFile($injectedJpg, 'trojan.jpg', (int) filesize($injectedJpg));
    $trojanDiskPath = $webRoot . $trojanResult['location'];

    assertTest("Re-encoding completes", file_exists($trojanDiskPath));
    $sanitizedOutput = file_get_contents($trojanDiskPath);
    assertTest("Appended PHP code is completely eliminated in output", !str_contains($sanitizedOutput, 'MALICIOUS_BACKDOOR_EXECUTED') && !str_contains($sanitizedOutput, '<?php'));

    // -------------------------------------------------------------
    // Test 4: Oversized image (>8 MB and >25 Megapixels)
    // -------------------------------------------------------------
    echo "\nTest 4: Oversized images (>8 MB file size & >25 MP resolution)...\n";

    // 4a. Oversized file size (>8 MB)
    $largeFilePath = $tmpDir . '/oversized_8mb.jpg';
    $fh = fopen($largeFilePath, 'wb');
    fwrite($fh, "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00");
    fseek($fh, 9 * 1024 * 1024); // 9 Megabytes
    fwrite($fh, "\x00");
    fclose($fh);

    $sizeRejected = false;
    $sizeMsg = '';
    try {
        ImageUploader::processFile($largeFilePath, 'huge.jpg', (int) filesize($largeFilePath));
    } catch (\InvalidArgumentException $e) {
        $sizeRejected = true;
        $sizeMsg = $e->getMessage();
    }
    assertTest("Image exceeding 8 MB is rejected", $sizeRejected, "Message: {$sizeMsg}");

    // 4b. Oversized resolution (>25 Megapixels)
    // Construct JPEG SOF0 header indicating 6000x5000 = 30 Megapixels
    $largeResolutionPath = $tmpDir . '/oversized_30mp.jpg';
    $sof0Data = "\xFF\xD8\xFF\xC0\x00\x11\x08" . pack('n', 5000) . pack('n', 6000) . "\x03\x01\x11\x00\x02\x11\x01\x03\x11\x01\xFF\xD9";
    file_put_contents($largeResolutionPath, $sof0Data);

    $resRejected = false;
    $resMsg = '';
    try {
        ImageUploader::processFile($largeResolutionPath, 'panoramic.jpg', (int) filesize($largeResolutionPath));
    } catch (\InvalidArgumentException $e) {
        $resRejected = true;
        $resMsg = $e->getMessage();
    }
    assertTest("Image exceeding 25 Megapixels is rejected", $resRejected, "Message: {$resMsg}");
    assertTest("25MP error message matches requirement exactly", $resMsg === 'This photo is too large. Please resize it below 25 megapixels.');

    // 4c. Verify UPLOAD_ERR_INI_SIZE message format
    $iniErrorMsg = ImageUploader::getUploadErrorMessage(UPLOAD_ERR_INI_SIZE);
    assertTest("UPLOAD_ERR_INI_SIZE message contains real upload_max_filesize limit", str_starts_with($iniErrorMsg, 'This image is larger than the server allows ('), "Message: {$iniErrorMsg}");

    // -------------------------------------------------------------
    // Test 5: Cleanup on cover replacement / deletion
    // -------------------------------------------------------------
    echo "\nTest 5: Cover variants deletion cleanup...\n";
    $deletedCount = ImageUploader::deleteVariants($cleanResult['location']);
    assertTest("deleteVariants() deletes all generated variants", $deletedCount >= 3);
    assertTest("Primary file no longer exists after deleteVariants()", !file_exists($primaryDiskPath));

    ImageUploader::deleteVariants($trojanResult['location']);

} finally {
    // Cleanup temp directory
    array_map('unlink', glob("{$tmpDir}/*") ?: []);
    @rmdir($tmpDir);
}

echo "\n-------------------------------------------------------------\n";
echo "Results: {$passes} Passed, {$failures} Failed.\n";

if ($failures > 0) {
    exit(1);
}
echo "[SUCCESS] All ImageUploader security requirements verified.\n";
