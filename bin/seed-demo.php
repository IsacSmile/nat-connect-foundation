<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Local Demo Content Seeder
 *
 * Seeds realistic environmental-journal posts, taxonomies, gallery photos,
 * and demo home settings for UI and layout testing.
 * CLI only. Strictly restricted to local development environments.
 *
 * Usage:
 *   php bin/seed-demo.php          # Seed demo content
 *   php bin/seed-demo.php --clean  # Purge all demo content & restore previous settings
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI access only.\n";
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public_html');

// Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    if (!empty($parts)) {
        $parts[0] = strtolower($parts[0]);
    }
    $file = $baseDir . implode('/', $parts) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
date_default_timezone_set('Asia/Kolkata');

use App\Core\Database;
use App\Core\ImageUploader;
use App\Models\CategoryModel;
use App\Models\GalleryModel;
use App\Models\PostModel;
use App\Models\SettingModel;
use App\Models\TagModel;

// -----------------------------------------------------------------------------
// 1. Safety Checks (Must be CLI, debug=true, and url containing localhost/127.0.0.1)
// -----------------------------------------------------------------------------
$debug = (bool) config('app.debug', false);
$url = (string) (config('app.url') ?: config('url', ''));
$isLocal = str_contains($url, '127.0.0.1') || str_contains($url, 'localhost');

if (!$debug || !$isLocal) {
    fwrite(STDERR, "[ERROR] Refusing to run: seed-demo can only run when app.debug=true and config 'url' contains 127.0.0.1 or localhost.\n");
    exit(1);
}

$manifestPath = APP_PATH . '/storage/demo-manifest.json';
$isClean = in_array('--clean', $argv, true);
$pdo = Database::getConnection();

// -----------------------------------------------------------------------------
// 2. CLEANUP MODE (--clean)
// -----------------------------------------------------------------------------
if ($isClean) {
    echo "=== NatConnect Foundation — Demo Content Cleaner ===\n";
    if (!file_exists($manifestPath)) {
        echo "[INFO] No demo manifest found at app/storage/demo-manifest.json. Nothing to clean.\n";
        exit(0);
    }

    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    if (!is_array($manifest)) {
        fwrite(STDERR, "[ERROR] Corrupt demo manifest file at {$manifestPath}.\n");
        exit(1);
    }

    // A. Delete posts and relations
    $postIds = $manifest['posts'] ?? [];
    if (!empty($postIds)) {
        $inPosts = implode(',', array_map('intval', $postIds));
        $pdo->exec("DELETE FROM post_tags WHERE post_id IN ({$inPosts})");
        $pdo->exec("DELETE FROM comments WHERE post_id IN ({$inPosts})");
        $pdo->exec("DELETE FROM posts WHERE id IN ({$inPosts})");
        echo "[OK] Deleted " . count($postIds) . " demo post(s).\n";
    }

    // B. Delete tags
    $tagIds = $manifest['tags'] ?? [];
    if (!empty($tagIds)) {
        $inTags = implode(',', array_map('intval', $tagIds));
        $pdo->exec("DELETE FROM post_tags WHERE tag_id IN ({$inTags})");
        $pdo->exec("DELETE FROM tags WHERE id IN ({$inTags})");
        echo "[OK] Deleted " . count($tagIds) . " demo tag(s).\n";
    }

    // C. Delete gallery items
    $galleryIds = $manifest['gallery_items'] ?? [];
    if (!empty($galleryIds)) {
        $inGallery = implode(',', array_map('intval', $galleryIds));
        $pdo->exec("DELETE FROM gallery_items WHERE id IN ({$inGallery})");
        echo "[OK] Deleted " . count($galleryIds) . " demo gallery item(s).\n";
    }

    // D. Delete image variant files from disk
    $imageFiles = $manifest['image_files'] ?? [];
    $deletedFiles = 0;
    foreach ($imageFiles as $imgRel) {
        $deletedFiles += ImageUploader::deleteVariants($imgRel);
        $fullPath = PUBLIC_PATH . '/' . ltrim($imgRel, '/');
        if (file_exists($fullPath) && is_file($fullPath)) {
            if (@unlink($fullPath)) {
                $deletedFiles++;
            }
        }
    }
    echo "[OK] Removed {$deletedFiles} image file variant(s) from disk.\n";

    // E. Restore previous settings
    $previousSettings = $manifest['previous_settings'] ?? [];
    if (!empty($previousSettings)) {
        SettingModel::setMany($previousSettings);
        echo "[OK] Restored " . count($previousSettings) . " previous setting(s).\n";
    }

    // F. Remove manifest file
    @unlink($manifestPath);
    echo "[OK] Demo manifest removed. Cleanup complete.\n";
    exit(0);
}

// -----------------------------------------------------------------------------
// 3. SEEDING PREPARATION
// -----------------------------------------------------------------------------
if (file_exists($manifestPath)) {
    fwrite(STDERR, "[ERROR] Demo manifest already exists at app/storage/demo-manifest.json.\n" .
                   "To avoid duplicate content, please run 'php bin/seed-demo.php --clean' first.\n");
    exit(1);
}

echo "=== NatConnect Foundation — Demo Content Seeder ===\n";
echo "Starting demo content generation...\n\n";

$manifest = [
    'seeded_at'         => date('Y-m-d H:i:s'),
    'posts'             => [],
    'tags'              => [],
    'gallery_items'     => [],
    'image_files'       => [],
    'previous_settings' => [],
];

// Save existing settings values before modifying
$settingsKeysToSave = [
    'home_hero_headline',
    'home_hero_intro',
    'mission_statement',
    'section_intro_rti',
    'section_intro_newsletter',
    'section_intro_donate',
];
foreach ($settingsKeysToSave as $sKey) {
    $manifest['previous_settings'][$sKey] = SettingModel::get($sKey);
}

// -----------------------------------------------------------------------------
// 4. IMAGE DOWNLOAD & GENERATION HELPERS
// -----------------------------------------------------------------------------
function generateGradientFallback(string $targetFile, int $index): void
{
    $width = 1600;
    $height = 1000;
    $img = imagecreatetruecolor($width, $height);

    $palettes = [
        [[46, 125, 50], [130, 180, 140]],    // muted green
        [[47, 58, 143], [100, 149, 237]],    // navy to coastal blue
        [[194, 178, 128], [240, 230, 200]],  // warm sand / ecru
        [[40, 90, 80], [120, 170, 160]],     // muted sage / teal
        [[60, 110, 60], [180, 205, 170]],    // olive green
        [[70, 90, 130], [160, 185, 215]],    // slate coastal blue
    ];
    $palette = $palettes[$index % count($palettes)];
    [$r1, $g1, $b1] = $palette[0];
    [$r2, $g2, $b2] = $palette[1];

    for ($y = 0; $y < $height; $y++) {
        $ratio = $y / (float) $height;
        $r = (int) ($r1 + ($r2 - $r1) * $ratio);
        $g = (int) ($g1 + ($g2 - $g1) * $ratio);
        $b = (int) ($b1 + ($b2 - $b1) * $ratio);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $width, $y, $color);
    }

    imagejpeg($img, $targetFile, 90);
    imagedestroy($img);
}

function fetchOrGenerateImage(int $picsumId, int $fallbackIndex, string $label, array &$manifest): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'demo_fetch_');
    $downloaded = false;

    $url = "https://picsum.photos/id/{$picsumId}/1600/1000";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NatConnectDemoSeeder/1.0');
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && is_string($data) && strlen($data) > 5000) {
        file_put_contents($tmp, $data);
        $info = @getimagesize($tmp);
        if ($info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            $downloaded = true;
        }
    }

    if (!$downloaded) {
        echo "  [Fallback] Download failed for Picsum #{$picsumId} ({$label}). Generating GD gradient.\n";
        generateGradientFallback($tmp, $fallbackIndex);
    } else {
        echo "  [Picsum] Downloaded #{$picsumId} ({$label}).\n";
    }

    $result = ImageUploader::processFile($tmp, "demo_{$picsumId}.jpg", (int) filesize($tmp));
    @unlink($tmp);

    // Record variants in manifest for reliable cleanup
    if (!empty($result['variants'])) {
        foreach ($result['variants'] as $vUrl) {
            $manifest['image_files'][] = $vUrl;
        }
    } else {
        $manifest['image_files'][] = $result['location'];
    }

    return $result;
}

// -----------------------------------------------------------------------------
// 5. PROCESS 23 IMAGES (14 landscape nature, 3 causes, 6 gallery)
// -----------------------------------------------------------------------------
echo "--> Processing images through ImageUploader pipeline...\n";

// 14 Landscape nature photos
$processedPhotos = [];

// Post covers (9 image covers, Post 10 is YouTube)
$processedPhotos['post_cover_1'] = fetchOrGenerateImage(10, 0, 'Post 1 Cover', $manifest);
$processedPhotos['post_cover_2'] = fetchOrGenerateImage(11, 1, 'Post 2 Cover', $manifest);
$processedPhotos['post_cover_3'] = fetchOrGenerateImage(13, 2, 'Post 3 Cover', $manifest);
$processedPhotos['post_cover_4'] = fetchOrGenerateImage(14, 3, 'Post 4 Cover', $manifest);
$processedPhotos['post_cover_5'] = fetchOrGenerateImage(15, 4, 'Post 5 Cover', $manifest);
$processedPhotos['post_cover_6'] = fetchOrGenerateImage(16, 5, 'Post 6 Cover', $manifest);
$processedPhotos['post_cover_7'] = fetchOrGenerateImage(17, 6, 'Post 7 Cover', $manifest);
$processedPhotos['post_cover_8'] = fetchOrGenerateImage(18, 7, 'Post 8 Cover', $manifest);
$processedPhotos['post_cover_9'] = fetchOrGenerateImage(28, 8, 'Post 9 Cover', $manifest);

// 3 In-content images
$processedPhotos['content_img_1'] = fetchOrGenerateImage(29, 9, 'Post 1 In-content', $manifest);
$processedPhotos['content_img_2'] = fetchOrGenerateImage(54, 10, 'Post 4 In-content', $manifest);
$processedPhotos['content_img_3'] = fetchOrGenerateImage(57, 11, 'Post 6 In-content', $manifest);

// 2 Extra landscape nature photos (making 14 total)
$processedPhotos['extra_photo_1'] = fetchOrGenerateImage(58, 12, 'Landscape Photo 13', $manifest);
$processedPhotos['extra_photo_2'] = fetchOrGenerateImage(65, 13, 'Landscape Photo 14', $manifest);

// 3 Cause photos
$processedPhotos['cause_wetlands']     = fetchOrGenerateImage(110, 14, 'Cause Wetlands', $manifest);
$processedPhotos['cause_mangroves']    = fetchOrGenerateImage(116, 15, 'Cause Mangroves', $manifest);
$processedPhotos['cause_biodiversity'] = fetchOrGenerateImage(124, 16, 'Cause Biodiversity', $manifest);

// 6 Gallery photos
$galleryPhotos = [
    fetchOrGenerateImage(152, 17, 'Gallery 1', $manifest),
    fetchOrGenerateImage(164, 18, 'Gallery 2', $manifest),
    fetchOrGenerateImage(184, 19, 'Gallery 3', $manifest),
    fetchOrGenerateImage(193, 20, 'Gallery 4', $manifest),
    fetchOrGenerateImage(203, 21, 'Gallery 5', $manifest),
    fetchOrGenerateImage(211, 22, 'Gallery 6', $manifest),
];

echo "[OK] Successfully processed 23 images.\n\n";

// -----------------------------------------------------------------------------
// 6. SEED TAGS
// -----------------------------------------------------------------------------
echo "--> Seeding tags...\n";

$tagDefinitions = [
    [
        'name'          => 'Wetlands',
        'slug'          => 'wetlands',
        'show_as_cause' => 1,
        'show_as_topic' => 1,
        'sort_order'    => 1,
        'description'   => 'Coastal marshes, creek intertidal zones, and freshwater waterbodies serving as essential flood buffers and wildlife refuges.',
        'image_path'    => $processedPhotos['cause_wetlands']['location'],
        'image_alt'     => 'Expansive intertidal wetland with low-tide creek channels',
    ],
    [
        'name'          => 'Mangroves',
        'slug'          => 'mangroves',
        'show_as_cause' => 1,
        'show_as_topic' => 1,
        'sort_order'    => 2,
        'description'   => 'Tidal mangrove forests along estuarine mudflats that stabilize coastlines and sustain rich marine nurseries.',
        'image_path'    => $processedPhotos['cause_mangroves']['location'],
        'image_alt'     => 'Dense coastal mangrove thicket with exposed prop roots',
    ],
    [
        'name'          => 'Biodiversity',
        'slug'          => 'biodiversity',
        'show_as_cause' => 1,
        'show_as_topic' => 1,
        'sort_order'    => 3,
        'description'   => 'Flora, fauna, and fragile habitat corridors supporting migratory bird flyways and indigenous coastal species.',
        'image_path'    => $processedPhotos['cause_biodiversity']['location'],
        'image_alt'     => 'Diverse wetland habitat supporting coastal avian and aquatic species',
    ],
    [
        'name'          => 'Climate',
        'slug'          => 'climate',
        'show_as_cause' => 0,
        'show_as_topic' => 1,
        'sort_order'    => 4,
        'description'   => null,
        'image_path'    => null,
        'image_alt'     => null,
    ],
    [
        'name'          => 'Accountability',
        'slug'          => 'accountability',
        'show_as_cause' => 0,
        'show_as_topic' => 1,
        'sort_order'    => 5,
        'description'   => null,
        'image_path'    => null,
        'image_alt'     => null,
    ],
    [
        'name'          => 'Migratory birds',
        'slug'          => 'migratory-birds',
        'show_as_cause' => 0,
        'show_as_topic' => 0,
        'sort_order'    => 6,
        'description'   => null,
        'image_path'    => null,
        'image_alt'     => null,
    ],
];

$tagsByName = [];
foreach ($tagDefinitions as $tagDef) {
    $existing = TagModel::getById((int) ($pdo->query("SELECT id FROM tags WHERE slug = " . $pdo->quote($tagDef['slug']))->fetchColumn() ?: 0));
    if ($existing) {
        $tagId = (int) $existing['id'];
        TagModel::update($tagId, $tagDef);
    } else {
        $tagId = TagModel::create($tagDef);
    }
    $tagsByName[$tagDef['name']] = $tagId;
    $manifest['tags'][] = $tagId;
    echo "  [Tag] {$tagDef['name']} (ID {$tagId}, cause={$tagDef['show_as_cause']}, topic={$tagDef['show_as_topic']})\n";
}
echo "[OK] Tags configured.\n\n";

// -----------------------------------------------------------------------------
// 7. SEED GALLERY
// -----------------------------------------------------------------------------
echo "--> Seeding 6 gallery items...\n";

$galleryMetadata = [
    ['alt' => 'A tranquil lake surrounded by morning mist and pine forest', 'caption' => 'Dawn reflection along a sheltered freshwater lake.'],
    ['alt' => 'A shallow creek flowing through a green riparian corridor', 'caption' => 'Natural creek channel following seasonal monsoon recharge.'],
    ['alt' => 'Rocky shoreline where tidal waves meet coastal headlands', 'caption' => 'Intertidal rocky zone showing natural wave dispersion.'],
    ['alt' => 'Sunlight filtering through a dense woodland canopy', 'caption' => 'Canopy coverage in a protected contiguous coastal forest.'],
    ['alt' => 'Open grassland meadow with native shrubs and wild grasses', 'caption' => 'Seasonal grassland habitat adjacent to estuarine mudflats.'],
    ['alt' => 'Silhouetted trees against a glowing coastal sunset', 'caption' => 'Dusk over the tidal creek and surrounding buffer lands.'],
];

foreach ($galleryPhotos as $idx => $photo) {
    $meta = $galleryMetadata[$idx];
    $gId = GalleryModel::create([
        'image_path' => $photo['location'],
        'alt_text'   => $meta['alt'],
        'caption'    => $meta['caption'],
        'sort_order' => $idx + 1,
    ]);
    $manifest['gallery_items'][] = $gId;
    echo "  [Gallery] Photo #{$gId} - {$meta['caption']}\n";
}
echo "[OK] Gallery items created.\n\n";

// -----------------------------------------------------------------------------
// 8. UPDATE SETTINGS
// -----------------------------------------------------------------------------
echo "--> Updating demo home page settings...\n";

SettingModel::setMany([
    'home_hero_headline'       => 'Nature needs a voice. Stories make it heard.',
    'home_hero_intro'          => 'Reporting, perspectives and campaigns for the wetlands, forests and wildlife we all depend on.',
    'mission_statement'        => 'A connection to nature. A reason to speak up.',
    'section_intro_rti'        => 'Tracking public records, transparency disclosures, and environmental governance.',
    'section_intro_newsletter' => 'Monthly dispatches, conservation updates, and grassroots stories delivered to your inbox.',
    'section_intro_donate'     => 'Support independent environmental reporting and biodiversity protection initiatives.',
]);
echo "[OK] Settings updated.\n\n";

// -----------------------------------------------------------------------------
// 9. SEED 10 POSTS
// -----------------------------------------------------------------------------
echo "--> Seeding 10 editorial posts...\n";

$catRows = CategoryModel::getAll();
$catBySlug = [];
foreach ($catRows as $c) {
    $catBySlug[$c['slug']] = (int) $c['id'];
}

// Map category IDs safely
$newsCatId      = $catBySlug['news'] ?? 2;
$causesCatId    = $catBySlug['causes'] ?? 1;
$campaignsCatId = $catBySlug['campaigns'] ?? 3;
$rtiCatId       = $catBySlug['rti-applications'] ?? 4;

// Helper to assemble structured post body meeting 400-700 words
function buildPostBody(
    string $leadParagraph,
    string $h2Title1,
    array $h2Paragraphs1,
    string $h3Title,
    string $h3Intro,
    array $listItems,
    string $quoteText,
    ?string $inContentHtml,
    string $h2Title2,
    array $h2Paragraphs2
): string {
    $listHtml = '<ul>' . implode('', array_map(fn($item) => '<li>' . $item . '</li>', $listItems)) . '</ul>';
    $quoteHtml = '<blockquote><p>' . $quoteText . '</p><cite>— Demo text</cite></blockquote>';

    $parts = [
        '<p>' . $leadParagraph . '</p>',
        '<h2>' . $h2Title1 . '</h2>',
    ];
    foreach ($h2Paragraphs1 as $p) {
        $parts[] = '<p>' . $p . '</p>';
    }
    $parts[] = '<h3>' . $h3Title . '</h3>';
    $parts[] = '<p>' . $h3Intro . '</p>';
    $parts[] = $listHtml;
    $parts[] = $quoteHtml;

    if ($inContentHtml !== null) {
        $parts[] = $inContentHtml;
    }

    $parts[] = '<h2>' . $h2Title2 . '</h2>';
    foreach ($h2Paragraphs2 as $p) {
        $parts[] = '<p>' . $p . '</p>';
    }

    $fullBody = implode("\n\n", $parts);
    $wc = str_word_count(strip_tags($fullBody));
    if ($wc < 400 || $wc > 700) {
        throw new RuntimeException("Word count of post is {$wc}, which violates the 400–700 words requirement.");
    }

    return $fullBody;
}

$postsData = [
    // 1. News (Newest, 2 days ago, NOT pick, in-content image)
    [
        'title'        => 'Seasonal Migratory Shorebirds Return to Coastal Wetland Mudflats',
        'slug'         => 'demo-seasonal-migratory-shorebirds-return-mudflats',
        'category_id'  => $newsCatId,
        'days_ago'     => 2,
        'hours_offset' => 9,
        'is_pick'      => 0,
        'cover_type'   => 'image',
        'cover_image'  => $processedPhotos['post_cover_1']['location'],
        'cover_alt'    => 'Flock of shorebirds feeding on coastal wetland mudflats at dawn',
        'video_url'    => null,
        'excerpt'      => 'Observations across regional coastal mudflats indicate the steady arrival of seasonal shorebirds as high winter tides recede.',
        'tags'         => ['Wetlands', 'Migratory birds', 'Biodiversity'],
        'body'         => buildPostBody(
            'Demo content for layout testing. Coastal wetland mudflats represent a dynamic and delicate threshold between marine and terrestrial realms. Across diverse coastal landscapes, intertidal flats and estuarine watercourses provide foundational environmental services that protect inland human settlements from storm surges while offering critical feeding grounds for countless resident and migratory species. Over successive seasonal monitoring cycles, environmental observers have recorded significant shifts in water chemistry, soil moisture, and vegetative cover throughout these sheltered creek reaches. Understanding these baseline conditions provides indispensable insights into how natural coastal systems adapt to changing regional weather patterns.',
            'Ecological Functions of Natural Mudflat Buffers',
            [
                'When daily tidal cycles flow without artificial obstruction, coastal mudflats and mangrove fringes operate as highly efficient natural bio-filters. Rainwater runoff flowing from upper catchment areas is gradually slowed as it meanders through vegetated flats, allowing suspended sediments and silt to settle before the water enters open estuarine basins. This natural filtration process preserves water clarity, stabilizes nutrient cycles, and creates fertile micro-habitats that support benthic invertebrates upon which higher trophic levels depend.',
                'Furthermore, the complex underwater root architectures of coastal trees act as living revetments, absorbing kinetic wave energy and preventing catastrophic shoreline retreat during high-energy weather episodes. Beyond physical stabilization, these intertidal zones serve as vital carbon sinks, sequestering atmospheric carbon within deep anaerobic soils over centuries. When these habitats are degraded or fragmented, their natural capacity to recover diminishes rapidly, leaving surrounding areas increasingly vulnerable to inundation.',
                'Careful field assessments underscore that the physical continuity of these natural buffers cannot be substituted by artificial stone pitching or concrete retaining walls. While engineered embankments reflect wave shock onto neighboring unfortified banks, living coastal fringes dissipate hydraulic forces evenly throughout the intertidal matrix, preventing localized erosion hotspots.'
            ],
            'Key Observations Along the Tidal Reach',
            'Systematic observations conducted along multiple transects have highlighted critical ecological indicators that reflect overall habitat vitality. These core observations include the following key dynamics:',
            [
                'Gradual recolonization of native pioneer plants along gently sloping mud banks where tidal flushing remains unimpeded.',
                'Maintenance of active feeder channels providing essential daily water circulation across isolated interior lagoons.',
                'Noticeable stabilization of bank edges in zones where undisturbed riparian vegetation corridors are preserved.',
                'Regular congregations of mixed shorebird assemblages foraging during low tide periods on exposed intertidal mudflats.',
                'Absence of unauthorized non-biodegradable debris or construction spoil deposition within high-tide boundaries.'
            ],
            'Natural hydrological flow across tidal channels remains the single most critical factor in sustaining intertidal ecology and preventing irreversible habitat stagnation.',
            '<figure class="my-6"><img src="' . $processedPhotos['content_img_1']['url_800'] . '" alt="Field observation point monitoring water salinity in the intertidal zone" class="w-full rounded-lg" loading="lazy"><figcaption class="text-sm text-ink-muted mt-2">Field observation point monitoring seasonal salinity gradients in the intertidal zone.</figcaption></figure>',
            'Sustaining Long-Term Habitat Integrity and Community Vigilance',
            [
                'Protecting these natural ecosystems requires sustained observation, meticulous documentation, and an active commitment to environmental transparency. When local citizens, naturalists, and researchers collaborate to record water levels, species sightings, and shoreline alterations, an accurate historical baseline can be established. This documentation serves as a valuable public resource for evaluating ecosystem health over multi-year horizons.',
                'Through informed reporting and community stewardship, the fundamental importance of these coastal landscapes can be brought to the forefront of public consciousness. Preserving the ecological continuity of wetlands, creeks, and estuaries ultimately safeguards the biodiversity and climate resilience of the entire coastal region for future generations.'
            ]
        ),
    ],

    // 2. News (7 days ago, Editor's Pick)
    [
        'title'        => 'Documenting Native Mangrove Regeneration Along Estuarine Banks',
        'slug'         => 'demo-documenting-native-mangrove-regeneration',
        'category_id'  => $newsCatId,
        'days_ago'     => 7,
        'hours_offset' => 14,
        'is_pick'      => 1,
        'cover_type'   => 'image',
        'cover_image'  => $processedPhotos['post_cover_2']['location'],
        'cover_alt'    => 'Young mangrove seedlings emerging along muddy estuarine shoreline',
        'video_url'    => null,
        'excerpt'      => 'Field surveys along estuarine banks highlight natural sapling emergence in areas where tidal water flow remains uninhibited.',
        'tags'         => ['Mangroves', 'Biodiversity'],
        'body'         => buildPostBody(
            'Demo content for layout testing. Natural regeneration within estuarine mangrove forests demonstrates the remarkable resilience of coastal ecosystems when basic hydrological conditions are preserved. Over recent months, naturalists surveying tidal shorelines have documented vigorous sapling emergence along sheltered creek banks. Where tidal inundation cycles remain unimpeded by artificial embankments or debris, indigenous mangrove species re-establish viable root networks with remarkable speed. Understanding these natural recovery mechanisms is essential for designing non-intrusive coastal conservation strategies that work with natural estuarine processes.',
            'Hydrological Preconditions for Natural Forest Expansion',
            [
                'Mangrove propagules require specific hydrological conditions to anchor successfully in estuarine sediments. Gentle tidal currents carry floating seedlings into shallow intertidal depressions, where low-velocity water allows prop roots to establish firm purchase within rich silt beds. Once anchored, these seedlings grow rapidly, providing physical stability to soft embankments that would otherwise succumb to coastal erosion.',
                'In areas where artificial bunds have constricted natural water circulation, sapling survival rates decline markedly. Stagnant hypersaline pools prevent root respiration, underscoring the critical need to preserve unhindered tidal ingress and egress across all estuarine drainage channels. Uninhibited flushing also prevents the accumulation of toxic sulfides in the sediment, fostering favorable biochemical conditions for continued vegetative propagation.',
                'Furthermore, undisturbed mangrove root networks create complex underwater micro-habitats that attenuate current velocities, encouraging fine organic particles to settle and build up new soil horizons over time. These organic deposits nourish extensive communities of microscopic algae, which in turn feed juvenile crustaceans.'
            ],
            'Field Indicators of Healthy Mangrove Canopies',
            'Ecological surveys along selected coastal reaches track several vital signs of forest vigor. Observers evaluate the following essential metrics during periodic assessments:',
            [
                'Density of newly rooted seedlings per square meter within upper intertidal zones.',
                'Presence of diverse invertebrate populations, including fiddler crabs and mudskippers, among aerial roots.',
                'Absence of non-biodegradable debris entrapment along natural shoreline fringes.',
                'Continuous canopy connectivity providing shaded micro-climates for sensitive benthic organisms.',
                'Unobstructed flow of daily high-tide waters reaching interior creek nursery zones.'
            ],
            'Conserving the delicate tidal pulse that nourishes estuarine mudflats is far more effective than attempting artificial replanting in ecologically degraded zones.',
            null,
            'The Role of Participatory Shoreline Monitoring',
            [
                'Engaging coastal communities in long-term observational monitoring strengthens ecological awareness and local stewardship. By mapping tidal flow patterns and identifying localized obstructions early, citizens help ensure that natural forest regeneration proceeds without avoidable disruption. Local volunteer networks often provide the earliest notification when unauthorized blockages threaten juvenile stands.',
                'Detailed field records and environmental journalism play an essential role in highlighting these regenerative processes, reminding us that nature possesses an extraordinary capacity to heal when granted adequate space and protection. Continued documentation provides public evidence supporting the preservation of entire coastal creek corridors for generations to come.'
            ]
        ),
    ],

    // 3. News (13 days ago, NOT pick)
    [
        'title'        => 'Tidal Sluice Gate Maintenance and Creek Water Exchange Dynamics',
        'slug'         => 'demo-tidal-sluice-gate-maintenance-creek-dynamics',
        'category_id'  => $newsCatId,
        'days_ago'     => 13,
        'hours_offset' => 11,
        'is_pick'      => 0,
        'cover_type'   => 'image',
        'cover_image'  => $processedPhotos['post_cover_3']['location'],
        'cover_alt'    => 'Sluice gate canal connecting an estuarine creek to coastal marshes',
        'video_url'    => null,
        'excerpt'      => 'A look into how regular tidal gate operations sustain oxygenation and nutrient cycles across coastal creek systems.',
        'tags'         => ['Wetlands', 'Climate'],
        'body'         => buildPostBody(
            'Demo content for layout testing. Coastal creeks and interior brackish waterbodies depend heavily on regular tidal exchanges to maintain dissolved oxygen levels and sustain aquatic life. In coastal areas where historic sluice gates regulate water levels between tidal creeks and agricultural lowlands, operational protocols must balance flood control requirements with ecological flushing needs. Regular maintenance and transparent scheduling of gate operations form the backbone of sound estuarine water management.',
            'Balancing Drainage Management with Ecological Flushing',
            [
                'Sluice gates originally installed to prevent saline intrusion during high monsoon tides often alter natural creek hydrology if operated rigidly. Prolonged gate closures trap organic pollutants and stagnant runoff in interior channels, causing rapid eutrophication and fish mortality. Conversely, synchronized gate operations that permit regular low-tide drainage and high-tide influx preserve water quality throughout the estuarine network.',
                'Hydrological studies emphasize that maintaining seasonal flushing regimes flushes accumulated sediments, prevents bottom hypoxia, and allows juvenile aquatic fauna to transit between coastal nurseries and open marine environments. When tidal gates remain shuttered for prolonged durations, interior waters undergo rapid thermal stratification, severely depleting bottom dissolved oxygen.',
                'Field observations demonstrate that regular gate maintenance, including clearing accumulated marine encrustations and silt buildup from valve aprons, prevents mechanical jams during sudden monsoon cloudbursts. A well-maintained gate system protects inland residential pockets while respecting the natural tidal breathing of estuarine waterways.'
            ],
            'Operational Guidelines for Creek Flushing Cycles',
            'Field engineers and environmental observers have proposed key operating principles to optimize sluice gate performance across tidal channels:',
            [
                'Scheduled gate openings during spring tide cycles to maximize sediment scouring and channel deepening.',
                'Regular silt removal from culvert aprons to prevent physical blockages during emergency monsoon discharges.',
                'Installation of continuous water quality sensors measuring salinity and dissolved oxygen upstream of gates.',
                'Collaborative consultation with local fishing communities who possess generational knowledge of tidal currents.',
                'Timely public notices regarding seasonal gate adjustments to ensure agricultural and civic readiness.'
            ],
            'A creek system severed from its tidal rhythm becomes an ecological dead end; timely water exchange is the lifeline of estuarine vitality.',
            null,
            'Integrating Technical Infrastructure with Ecological Principles',
            [
                'Modern coastal management increasingly recognizes that water control infrastructure must adapt to ecological realities. Ensuring that engineering designs incorporate wildlife corridors and tidal flushing cycles bridges the gap between flood protection and habitat conservation. Long-term durability is achieved when civic works harmonize with coastal hydrodynamic forces.',
                'Continued transparency in infrastructure management protocols fosters trust among residents and ensures that coastal waterways remain functional, clean, and ecologically vibrant. Documenting and publishing operational schedules is a simple yet vital step toward accountable coastal governance.'
            ]
        ),
    ],

    // 4. Causes (19 days ago, Editor's Pick, in-content image)
    [
        'title'        => 'Safeguarding Fragile Intertidal Habitats from Unplanned Debris Dumping',
        'slug'         => 'demo-safeguarding-intertidal-habitats-debris-dumping',
        'category_id'  => $causesCatId,
        'days_ago'     => 19,
        'hours_offset' => 16,
        'is_pick'      => 1,
        'cover_type'   => 'image',
        'cover_image'  => $processedPhotos['post_cover_4']['location'],
        'cover_alt'    => 'Rocky intertidal coastline with clean water and natural coastal vegetation',
        'video_url'    => null,
        'excerpt'      => 'Intertidal zones require continuous monitoring and local vigilance to prevent debris accumulation that impedes marine life.',
        'tags'         => ['Wetlands', 'Biodiversity'],
        'body'         => buildPostBody(
            'Demo content for layout testing. Intertidal habitats represent some of the most productive yet vulnerable biological environments on our planet. Stretched along the margin where land and sea converge, these rocky ledges, sandy shores, and muddy shallows support complex trophic networks. However, rapid coastal infrastructure development often places immense pressure on these sensitive margins through indiscriminate disposal of excavation debris and urban waste. Protecting these zones requires vigilant monitoring and community engagement.',
            'The Physical and Chemical Toll of Debris Encroachment',
            [
                'When inert construction waste, construction rubble, or dredge spoils are dumped along intertidal margins, the physical structure of the shoreline is immediately compromised. Porous rock crevices and intertidal sediment layers that harbor specialized molluscs, crabs, and juvenile fish become smothered beneath dense layers of compacted silt and pulverized aggregate.',
                'In addition to direct physical smothering, the chemical leaching of heavy minerals and alkaline cement residues alters the delicate pH balance of tidal pools. Sensitive marine algae and benthic invertebrates that rely on clean water to reproduce experience steep mortality, triggering trophic collapses that ripple throughout the coastal food web. The natural self-healing capacity of the intertidal flat is overwhelmed when heavy waste compacts the mud layer.',
                'Furthermore, dumped debris alters local tidal currents, deflecting incoming waves toward vulnerable adjoining banks and exacerbating coastal erosion. Preserving intact intertidal topography ensures that incoming tidal surges expend their energy harmlessly against natural shoreline gradients.'
            ],
            'Community-Driven Strategies for Habitat Defense',
            'Protecting these irreplaceable intertidal landscapes requires a multifaceted approach combining on-ground vigilance with clear environmental accountability:',
            [
                'Continuous photographic baseline documentation of natural shoreline contours to record unauthorized alterations.',
                'Establishment of community watch groups that patrol coastal access roads during off-peak hours.',
                'Prompt submission of factual, geo-tagged reports to local environmental authorities when dumping occurs.',
                'Educational shoreline walks that connect neighborhood residents with the fascinating marine biodiversity at their doorstep.',
                'Clear demarcations of ecologically sensitive intertidal zones with informational signboards.'
            ],
            'An intertidal reef smothered beneath concrete rubble loses in hours what natural ecological forces took centuries to establish.',
            '<figure class="my-6"><img src="' . $processedPhotos['content_img_2']['url_800'] . '" alt="Natural shoreline embankment stabilized by deep native root systems" class="w-full rounded-lg" loading="lazy"><figcaption class="text-sm text-ink-muted mt-2">Natural shoreline embankment stabilized by resilient intertidal vegetation and deep root matrices.</figcaption></figure>',
            'Securing the Future of Our Coastal Commons',
            [
                'Preserving natural shoreline ecosystems is not merely an aesthetic concern; it is a critical investment in collective environmental resilience. Intact intertidal zones absorb storm impacts, prevent erosion, and sustain regional fisheries that support local livelihoods. The ecological dividends of undisturbed mudflats far outweigh the short-term convenience of cheap disposal sites.',
                'By advocating for strict zoning enforcement, responsible waste disposal logistics, and continuous habitat monitoring, citizens and civil society organizations play an irreplaceable role in keeping our coastal heritage vibrant and free from encroachment.'
            ]
        ),
    ],

    // 5. Causes (25 days ago, NOT pick)
    [
        'title'        => 'Why Urban Creeks Need Sustained Mangrove Buffer Corridors',
        'slug'         => 'demo-urban-creeks-mangrove-buffer-corridors',
        'category_id'  => $causesCatId,
        'days_ago'     => 25,
        'hours_offset' => 10,
        'is_pick'      => 0,
        'cover_type'   => 'image',
        'cover_image'  => $processedPhotos['post_cover_5']['location'],
        'cover_alt'    => 'Thick mangrove buffer corridor bordering calm creek waters',
        'video_url'    => null,
        'excerpt'      => 'Mangrove buffer strips act as essential natural filters, reducing siltation and absorbing runoff along urbanized shorelines.',
        'tags'         => ['Mangroves', 'Climate'],
        'body'         => buildPostBody(
            'Demo content for layout testing. In heavily populated coastal regions, natural creeks often function as the primary drainage arteries connecting urban watersheds to the sea. All too frequently, these vital waterways are treated as mere stormwater canals, their natural banks replaced by sheer concrete retaining walls. Yet scientific research consistently affirms that retaining contiguous mangrove buffer corridors along creek margins delivers indispensable ecological benefits. Protecting these buffers is crucial for sustainable urban resilience.',
            'Living Infrastructure Versus Concrete Canalization',
            [
                'Unlike rigid concrete embankments, which accelerate stormwater velocity and worsen downstream flooding, natural mangrove corridors act as responsive, living shock absorbers. Their intricate stilt roots and pneumatophores dissipate water energy, allowing suspended urban sediments to settle before reaching open coastal waters. This slowing of flow provides vital lag time during extreme monsoon cloudbursts.',
                'Furthermore, mangrove rhizosphere bacteria actively break down nitrogenous pollutants and organic compounds carried in urban storm runoff, effectively purifying the water before it discharges into the wider marine bay. This natural bio-remediation capability provides immense public health and ecological value at zero engineering maintenance cost. Without this vegetative cushion, urban runoff carries untreated contaminants directly into nearshore fisheries.',
                'Vegetated banks also stabilize soil temperatures along creek reaches, creating sheltered micro-climates that support diverse bird and insect populations. Channelizing creeks into bare concrete chutes destroys these micro-refuges, creating lifeless heat corridors through the city.'
            ],
            'Essential Elements of an Effective Creek Buffer',
            'To maintain functional ecological viability, urban creek corridors must fulfill several core structural criteria:',
            [
                'A continuous minimum horizontal buffer width of undisturbed native vegetation along both creek banks.',
                'Uninterrupted connection between high-tide wetland basins and the main central drainage channel.',
                'Strict prohibition of heavy vehicular access and unauthorized industrial discharge points.',
                'Establishment of vegetative windbreaks using indigenous coastal trees on adjacent landward boundaries.',
                'Periodic community cleanups to remove entangling plastics from mangrove aerial root matrices.'
            ],
            'A mangrove buffer along a city creek is not an underutilized vacant lot; it is a sophisticated, self-maintaining ecological water treatment plant.',
            null,
            'Reclaiming Natural Waterways for Future Urban Generations',
            [
                'Integrating ecological conservation into urban coastal planning requires visionary stewardship. Rather than isolating citizens behind concrete barricades, urban creek corridors can incorporate elevated boardwalks and observation lookouts that foster public appreciation for wetland biodiversity. Reconnecting city dwellers with water fosters a culture of shared environmental responsibility.',
                'When cities recognize their creeks as living natural assets rather than neglected back alleys, community pride and conservation action naturally flourish, creating healthier environments for all. Preserving these green corridors guarantees that urbanization does not come at the expense of our ecological life support systems.'
            ]
        ),
    ],

    // 6. Campaigns (31 days ago, Editor's Pick, in-content image)
    [
        'title'        => 'Community Tree Plantation and Wetland Boundary Stewardship',
        'slug'         => 'demo-community-tree-plantation-wetland-stewardship',
        'category_id'  => $campaignsCatId,
        'days_ago'     => 31,
        'hours_offset' => 15,
        'is_pick'      => 1,
        'cover_type'   => 'image',
        'cover_image'  => $processedPhotos['post_cover_6']['location'],
        'cover_alt'    => 'Community members tending young native saplings along a wetland edge',
        'video_url'    => null,
        'excerpt'      => 'Citizen-led mapping and planting initiatives demonstrate the power of local communities in safeguarding wetland boundaries.',
        'tags'         => ['Wetlands', 'Biodiversity', 'Climate'],
        'body'         => buildPostBody(
            'Demo content for layout testing. Grassroots community mobilization stands at the heart of meaningful environmental conservation. Across several coastal wetland peripheries, dedicated local residents, students, and conservation enthusiasts have launched participatory planting and boundary stewardship campaigns. By establishing green buffer zones using indigenous coastal saplings, these civic initiatives actively deter illegal encroachment while revitalizing degraded landward margins. Collective citizen action forms the bedrock of sustainable local conservation.',
            'The Strategic Value of Indigenous Green Buffers',
            [
                'Wetland margins are frequently subject to gradual, stealthy land reclamation when boundaries remain indistinct and untended. Planting native littoral trees along officially surveyed perimeter lines creates an unmistakable biological demarcation that deters unauthorized dumping and heavy machinery access. A thriving line of mature coastal trees visually asserts the public, protected status of the wetland expanse.',
                'Careful selection of indigenous species is paramount to the success of such campaigns. Choosing hardy coastal trees adapted to saline soils and seasonal monsoon inundation ensures high sapling survival rates without requiring artificial chemical fertilizers or intensive irrigation infrastructure. Native species also provide appropriate food and nesting resources for local wildlife, unlike non-native ornamental varieties.',
                'Furthermore, establishing dense root systems on wetland borders prevents topsoil runoff from neighboring construction areas from choking the delicate feeder channels of the wetland. Over time, these buffer plantings mature into lush biological corridors linking fragmented green pockets.'
            ],
            'Core Pillars of Successful Grassroots Campaigns',
            'Experienced community organizers highlight key operational steps that drive sustainable stewardship outcomes:',
            [
                'Thorough verification of land ownership records and official boundary coordinates before planting begins.',
                'Active engagement of local neighborhood youth and volunteer groups in regular weekend watering rosters.',
                'Detailed digital tagging and photographic tracking of every planted sapling to measure multi-season survival.',
                'Open public workshops explaining the ecological role of wetland buffers in local flood mitigation.',
                'Constructive liaison with municipal horticulture authorities to source certified indigenous sapling stock.'
            ],
            'Real conservation takes root when neighborhood citizens step forward to care for the public natural commons in their own backyards.',
            '<figure class="my-6"><img src="' . $processedPhotos['content_img_3']['url_800'] . '" alt="Community volunteers recording tidal waterline markers during low tide" class="w-full rounded-lg" loading="lazy"><figcaption class="text-sm text-ink-muted mt-2">Community volunteers recording high-tide markers to map dynamic wetland boundaries.</figcaption></figure>',
            'Building Lasting Momentum Through Collaborative Action',
            [
                'Community boundary stewardship campaigns accomplish far more than merely planting trees; they forge resilient social networks committed to environmental justice and transparent civic governance. When residents develop a personal relationship with local wetlands, they become fierce defenders of those ecosystems against opportunistic encroachment. Shared civic purpose unites neighborhoods around tangible conservation victories.',
                'By sharing campaign stories, documenting tangible progress, and fostering respectful partnerships with public authorities, citizen-led conservation proves that collective grassroots action can change the environmental trajectory of our cities. Sustained care transforms vulnerable boundary lines into flourishing sanctuaries.'
            ]
        ),
    ],

    // 7. Campaigns (38 days ago, NOT pick)
    [
        'title'        => 'Restoring Natural Inflow Channels to Prevent Coastal Waterlogging',
        'slug'         => 'demo-restoring-natural-inflow-channels',
        'category_id'  => $campaignsCatId,
        'days_ago'     => 38,
        'hours_offset' => 8,
        'is_pick'      => 0,
        'cover_type'   => 'image',
        'cover_image'  => $processedPhotos['post_cover_7']['location'],
        'cover_alt'    => 'Natural water channel flowing between lush coastal wetlands',
        'video_url'    => null,
        'excerpt'      => 'Clearing choked feeder channels ensures uninterrupted tidal cycles and reduces inland monsoon waterlogging risks.',
        'tags'         => ['Wetlands', 'Climate'],
        'body'         => buildPostBody(
            'Demo content for layout testing. During severe monsoon downpours, coastal urban settlements frequently face severe waterlogging that disrupts daily life and damages property. While public debate often focuses on mechanical pumping stations and concrete drains, environmental hydrologists consistently emphasize that restoring choked natural wetland feeder channels offers a vastly superior, sustainable solution to recurring inundation. Preserving natural drainage topography is an indispensable facet of climate resilience.',
            'How Choked Inflow Channels Amplify Urban Flood Risk',
            [
                'Wetland waterbodies function as natural retention basins designed by nature to absorb excess monsoon runoff. However, when connecting natural feeder streams become clogged with plastic refuse, construction silt, or illegal road crossings, stormwater cannot enter the retention basins and instead backs up into residential neighborhoods. A single choked culvert can flood dozens of upstream homes within an hour of intense rainfall.',
                'Restoring these natural conduits does not require heavy concrete reconstruction. Rather, it involves desilting choked bottleneck points, removing unauthorized culverts, and allowing the natural gradient of the terrain to guide floodwaters into designated wetland sponges. Unobstructed feeder streams distribute runoff smoothly, minimizing destructive peak velocities.',
                'In addition, natural channel beds lined with porous gravel and silt allow substantial volumes of runoff to infiltrate into shallow coastal aquifers, recharging the freshwater table and preventing saltwater intrusion into local wells during subsequent dry months. Natural banks also support emergent reeds that filter surface pollutants before water reaches the central basin.'
            ],
            'Practical Steps for Feeder Channel Restoration',
            'Field campaigns focused on hydrological channel rejuvenation prioritize targeted, ecologically sensitive interventions:',
            [
                'Comprehensive watershed elevation mapping using drone imagery and historical drainage surveys.',
                'Manual and mechanical clearance of non-biodegradable debris dams obstructing culverts and natural swales.',
                'Stabilization of channel banks with coir geotextiles and native emergent wetland reeds to prevent re-siltation.',
                'Establishment of community trash barriers at upstream storm drain discharge points.',
                'Regular pre-monsoon inspections conducted jointly by civic engineers and local residents.'
            ],
            'Allowing natural streams to flow freely toward wetland basins is the most cost-effective flood defense a coastal city can implement.',
            null,
            'A Call for Integrated Watershed Management',
            [
                'Addressing coastal waterlogging requires shifting from reactive emergency responses to proactive, year-round watershed stewardship. Protecting and maintaining feeder channels ensures that wetlands can perform their intended role as life-saving flood cushions during torrential downpours. Engineering solutions must work in harmony with natural drainage pathways.',
                'When municipal bodies and citizen groups unite around scientifically grounded channel restoration, urban resilience improves dramatically, demonstrating the enduring wisdom of working alongside nature rather than against it. Investing in natural drainage infrastructure safeguards communities against extreme weather impacts.'
            ]
        ),
    ],

    // 8. RTI Applications (45 days ago, NOT pick)
    [
        'title'        => 'Tracking Environmental Clearance Compliance for Coastal Projects',
        'slug'         => 'demo-tracking-environmental-clearance-compliance',
        'category_id'  => $rtiCatId,
        'days_ago'     => 45,
        'hours_offset' => 13,
        'is_pick'      => 0,
        'cover_type'   => 'image',
        'cover_image'  => $processedPhotos['post_cover_8']['location'],
        'cover_alt'    => 'Field survey map and documentation file on an office desk',
        'video_url'    => null,
        'excerpt'      => 'How public records requests help citizens track post-clearance monitoring reports and green buffer commitments.',
        'tags'         => ['Accountability'],
        'body'         => buildPostBody(
            'Demo content for layout testing. Statutory environmental clearances granted to major coastal infrastructure projects are accompanied by mandatory conditions designed to safeguard surrounding habitats. These typically include requirements to maintain ecological buffer zones, implement continuous effluent monitoring, and submit biannual compliance reports. In practice, however, these crucial post-clearance stipulations often go unscrutinized unless active citizens demand public disclosure. Rigorous public oversight ensures statutory commitments are upheld.',
            'The Power of Public Scrutiny in Post-Clearance Monitoring',
            [
                'The Right to Information framework provides citizens and researchers with a powerful democratic mechanism to inspect project compliance records. By requesting official copies of six-monthly environmental monitoring reports, third-party audit findings, and regulatory inspection notes, the public can verify whether statutory commitments are being faithfully executed on the ground.',
                'Public records frequently reveal significant gaps between project proposals and ground reality. Uncovering missing water quality data, truncated mangrove compensation plantings, or unauthorized alterations to project layout enables civil society to alert oversight agencies before irreversible ecological destruction occurs. Independent verification holds authorities and developers to high standards.',
                'Furthermore, documented compliance tracking creates an official paper trail that can be referenced in legal petitions or environmental tribunals. When citizens back their concerns with official clearance letters and audit logs, regulatory bodies are compelled to conduct formal site inspections and enforce corrective measures.'
            ],
            'Methodical Approaches to Statutory Information Requests',
            'Filing effective public information requests requires precision, clarity, and systematic follow-up. Successful transparency researchers adhere to the following best practices:',
            [
                'Citing specific project clearance reference numbers and relevant statutory clearance conditions.',
                'Requesting certified copies of raw monitoring data and certified lab test reports rather than generic summary statements.',
                'Tracking regulatory submission deadlines to identify overdue compliance filings promptly.',
                'Maintaining organized public digital archives of all received replies for comparative longitudinal analysis.',
                'Sharing verified factual disclosures with wider civic groups and local environmental journalists.'
            ],
            'Environmental governance thrives in daylight; public records requests ensure that regulatory conditions remain living commitments rather than forgotten paperwork.',
            null,
            'Democratizing Conservation Governance',
            [
                'Using statutory information tools to hold development accountable bridges the critical gap between environmental law and ground enforcement. When citizens exercise their right to know, public authorities and project proponents are held to higher standards of environmental responsibility. Transparency is the most reliable antidote to unchecked habitat degradation.',
                'By transforming complex administrative disclosures into accessible public reporting, environmental journalists and RTI activists strengthen democratic accountability and safeguard the public interest. Vigilant citizen engagement ensures our coastal commons remain protected by the rule of law.'
            ]
        ),
    ],

    // 9. RTI Applications (52 days ago, NOT pick)
    [
        'title'        => 'Accessing Public Records on Wetland Demarcation and Land Records',
        'slug'         => 'demo-accessing-public-records-wetland-demarcation',
        'category_id'  => $rtiCatId,
        'days_ago'     => 52,
        'hours_offset' => 11,
        'is_pick'      => 0,
        'cover_type'   => 'image',
        'cover_image'  => $processedPhotos['post_cover_9']['location'],
        'cover_alt'    => 'Open public register of land survey demarcations and wetland maps',
        'video_url'    => null,
        'excerpt'      => 'Understanding revenue land records and geo-referenced wetland boundaries through statutory transparency tools.',
        'tags'         => ['Accountability', 'Wetlands'],
        'body'         => buildPostBody(
            'Demo content for layout testing. A recurring challenge in wetland protection is the ambiguity that frequently clouds land revenue classifications. Across coastal districts, historic waterbodies and intertidal mudflats are occasionally recorded under archaic revenue categories that obscure their ecological identity, making them vulnerable to inappropriate land-use conversion. Unraveling these complex administrative classifications through statutory information requests is often the first crucial step in securing legal protection for threatened wetlands.',
            'Uncovering Historical Revenue Classifications and Village Maps',
            [
                'Revenue records, including cadastral village maps, property card registers, and historical survey settlements, contain the authoritative legal description of parcel boundaries. By accessing certified survey maps and mutation extracts, researchers can trace how land titles have evolved over decades. These historical documents demonstrate whether a piece of land was originally designated as a natural waterbody.',
                'Comparing historical maps with current satellite imagery frequently exposes unauthorized alterations where waterbodies were filled without statutory revenue sanctions. Armed with verified government records, conservation advocates can present undeniable evidence of encroachment to courts and administrative tribunals. Without documentary proof, on-ground visual claims can easily be disputed by commercial interests.',
                'Furthermore, understanding the administrative distinctions between revenue departments, coastal zone management bodies, and municipal planning authorities helps citizens direct their inquiries to the appropriate custodian of public records. Clear documentation forms an unshakeable evidentiary foundation.'
            ],
            'Key Documents to Request in Wetland Boundary Inquiries',
            'Researchers seeking to establish legal wetland boundaries should focus on specific public documents that establish legal status:',
            [
                'Historical village survey maps indicating original natural waterbody boundaries and drainage nalas.',
                'Certified extracts of revenue registers showing designated land classification codes.',
                'Minutes and site inspection reports from regional wetland grievance redressal committees.',
                'Official inter-departmental communications regarding proposed boundary demarcations and geo-referencing surveys.',
                'Satellite geo-referencing overlays prepared by state remote sensing agencies.'
            ],
            'Clear cadastral boundaries backed by certified public records are the strongest shield a threatened wetland can possess.',
            null,
            'Transparency as a Catalyst for Habitat Defense',
            [
                'Demystifying government land records empowers communities to defend their local natural heritage against speculative land grabbing. When citizens understand how to navigate administrative systems and demand public documentation, they transform from passive bystanders into proactive guardians of the environment. Knowledge of the law is a potent instrument for conservation.',
                'Open access to public data is the cornerstone of responsible environmental stewardship, ensuring that natural resources are preserved for future generations. Factual archival evidence leaves a lasting legacy of protection across our vulnerable wetlands.'
            ]
        ),
    ],

    // 10. News (1 any, 58 days ago, NOT pick, YouTube cover)
    [
        'title'        => 'Video Field Report: Avian Biodiversity in Coastal Riparian Zones',
        'slug'         => 'demo-video-field-report-avian-biodiversity',
        'category_id'  => $newsCatId,
        'days_ago'     => 58,
        'hours_offset' => 17,
        'is_pick'      => 0,
        'cover_type'   => 'video',
        'cover_image'  => null,
        'cover_alt'    => null,
        'video_url'    => 'https://www.youtube.com/watch?v=LXb3EKWsInQ',
        'excerpt'      => 'A field observation documenting bird feeding behaviors and nesting sanctuaries along sheltered coastal riparian belts.',
        'tags'         => ['Biodiversity', 'Migratory birds'],
        'body'         => buildPostBody(
            'Demo content for layout testing. Coastal riparian corridors provide critical refuge, nesting perches, and foraging habitats for dozens of bird species that navigate estuarine landscapes. In this video field observation, we document the dynamic feeding and roosting behaviors observed along a sheltered coastal riparian corridor where fresh creek water meets incoming tidal flows. High-definition footage reveals the intricate ecological interactions that unfold during early morning tidal transitions, highlighting the importance of vegetated creek fringes.',
            'Diverse Ecological Niches in Riparian Vegetated Belts',
            [
                'Riparian belts characterized by mixed native trees, reeds, and mangrove fringes offer varied micro-habitats suited to different avian feeding strategies. While wading birds such as egrets and herons stalk the shallow mud fringes, kingfishers utilize overhanging tree branches as strategic hunting perches to plunge for juvenile estuarine fish. The variety of plant strata allows multiple species to coexist without competing directly for food resources.',
                'The dense vegetative cover also shields nesting sites from terrestrial predators and severe coastal winds. Protecting the physical integrity of these vegetated buffer strips ensures that birds have undisturbed spaces to rest between intense foraging bouts during low tide. When human activity strips these banks of canopy cover, avian diversity declines precipitously as birds retreat to more remote, undisturbed reaches.',
                'Moreover, riparian vegetation stabilizes water temperatures and filters terrestrial sediment, maintaining clear water pockets where fish fry can congregate safely. These productive nursery areas sustain both avian predators and the wider estuarine food web throughout the seasons.'
            ],
            'Observational Findings from the Field Survey',
            'Our multi-hour video recording session documented several noteworthy behavioral patterns across the survey reach:',
            [
                'Coordinated flock feeding among small shorebirds along newly exposed intertidal mud banks.',
                'Territorial perching displays by resident raptors utilizing mature mangrove emergent crowns.',
                'Active utilization of reed beds as sheltered nocturnal roosting sanctuaries by mixed passerine flocks.',
                'Sensitivity of feeding waders to distant motorized watercraft, underscoring the need for quiet buffer zones.',
                'Regular flight corridors maintained along the central creek channel connecting roosting and foraging grounds.'
            ],
            'Capturing wildlife behavior on video transforms scientific data into an emotional, relatable visual experience that inspires conservation action.',
            null,
            'Visual Storytelling in Environmental Journalism',
            [
                'Video documentation serves as an invaluable tool for environmental education and public outreach. By capturing the grace, diversity, and vulnerability of coastal wildlife in high resolution, multimedia journalism bridges the distance between urban audiences and remote wetland ecosystems. Seeing animals in their natural habitats builds empathetic support for habitat preservation.',
                'We invite you to explore this video field report and join our ongoing community efforts to document and defend the irreplaceable coastal habitats that sustain our planet’s rich biodiversity. Protecting these riparian corridors ensures that the songs of migratory birds continue to echo along our shores.'
            ]
        ),
    ],
];

$createdPostsList = [];

foreach ($postsData as $pData) {
    $publishedAt = date('Y-m-d H:i:s', strtotime("-{$pData['days_ago']} days -{$pData['hours_offset']} hours"));

    $postId = PostModel::create([
        'title'             => $pData['title'],
        'slug'              => $pData['slug'],
        'excerpt'           => $pData['excerpt'],
        'content'           => $pData['body'],
        'category_id'       => $pData['category_id'],
        'author_name'       => 'NatConnect Foundation',
        'cover_type'        => $pData['cover_type'],
        'cover_image'       => $pData['cover_image'],
        'cover_alt'         => $pData['cover_alt'],
        'video_url'         => $pData['video_url'],
        'status'            => 'published',
        'is_pick'           => $pData['is_pick'],
        'published_at'      => $publishedAt,
        'read_time_minutes' => 3,
        'tags'              => $pData['tags'],
    ]);

    $manifest['posts'][] = $postId;

    $catName = 'Unknown';
    foreach ($catRows as $cr) {
        if ((int) $cr['id'] === $pData['category_id']) {
            $catName = $cr['name'];
            break;
        }
    }

    $createdPostsList[] = [
        'id'           => $postId,
        'title'        => $pData['title'],
        'category'     => $catName,
        'is_pick'      => $pData['is_pick'] ? 'Yes' : 'No',
        'published_at' => $publishedAt,
    ];

    echo "  [Post #{$postId}] {$pData['title']} ({$catName}, Pick: " . ($pData['is_pick'] ? 'Yes' : 'No') . ")\n";
}

echo "[OK] All 10 posts created successfully.\n\n";

// -----------------------------------------------------------------------------
// 10. WRITE MANIFEST FILE
// -----------------------------------------------------------------------------
$manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($manifestPath, $manifestJson);

echo "=== Seed Completed Successfully ===\n";
echo "Manifest saved to: {$manifestPath}\n\n";
echo "SUMMARY OF CREATED DEMO POSTS:\n";
echo str_repeat('-', 90) . "\n";
printf("%-4s | %-52s | %-16s | %-5s\n", "ID", "Title", "Category", "Pick");
echo str_repeat('-', 90) . "\n";
foreach ($createdPostsList as $cp) {
    printf("%-4d | %-52s | %-16s | %-5s\n", $cp['id'], mb_substr($cp['title'], 0, 50), $cp['category'], $cp['is_pick']);
}
echo str_repeat('-', 90) . "\n";
