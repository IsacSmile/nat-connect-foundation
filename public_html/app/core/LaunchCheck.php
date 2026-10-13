<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

require_once __DIR__ . '/Turnstile.php';

/**
 * NatConnect Foundation — Launch Checklist & Pre-Launch Readiness Service
 *
 * Evaluates core launch readiness criteria shared between the CLI pre-launch tool
 * and the administrative dashboard:
 * 1. Placeholder copy in pages (with direct links to edit each page)
 * 2. Placeholder copy in settings
 * 3. Placeholder copy in categories
 * 4. Placeholder copy in template views
 * 5. Production Cloudflare Turnstile keys configured
 * 6. Application debug mode disabled
 * 7. public_html/setup.php removed
 * 8. HTTPS connection active
 * 9. At least one published post live
 */
class LaunchCheck
{
    /**
     * Run all launch readiness checks.
     *
     * @return array{
     *   checks: array<int, array{
     *     key: string,
     *     name: string,
     *     passed: bool,
     *     message: string,
     *     fix_url: ?string,
     *     fix_label: ?string,
     *     details: array<string>
     *   }>,
     *   allPassed: bool,
     *   failCount: int
     * }
     */
    public static function runAll(): array
    {
        $checks = [];
        $db = null;

        try {
            $db = Database::getConnection();
        } catch (Throwable $e) {
            $db = null;
        }

        // 1. Pages table placeholder check
        if ($db) {
            try {
                $stmt = $db->query("SELECT id, title, slug FROM pages WHERE content LIKE '%[CLIENT COPY%' OR title LIKE '%[CLIENT COPY%' ORDER BY id ASC");
                $pagesWithCopy = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($pagesWithCopy)) {
                    foreach ($pagesWithCopy as $p) {
                        $checks[] = [
                            'key'       => 'page_placeholder_' . $p['id'],
                            'name'      => "Page: {$p['title']}",
                            'passed'    => false,
                            'message'   => "{$p['title']} page still has placeholder text",
                            'fix_url'   => "/admin/pages/edit/{$p['id']}",
                            'fix_label' => 'Edit',
                            'details'   => ["Page #{$p['id']}: '{$p['title']}' (/{$p['slug']})"],
                        ];
                    }
                } else {
                    $checks[] = [
                        'key'       => 'pages_placeholder',
                        'name'      => 'Pages copy',
                        'passed'    => true,
                        'message'   => 'Pages do not have placeholder text',
                        'fix_url'   => null,
                        'fix_label' => null,
                        'details'   => [],
                    ];
                }
            } catch (Throwable $e) {
                $checks[] = [
                    'key'       => 'pages_placeholder',
                    'name'      => 'Pages copy',
                    'passed'    => false,
                    'message'   => 'Could not inspect pages table: ' . $e->getMessage(),
                    'fix_url'   => '/admin/pages',
                    'fix_label' => 'Pages',
                    'details'   => [],
                ];
            }
        }

        // 2. Settings table placeholder check
        if ($db) {
            try {
                $stmt = $db->query("SELECT `key`, `value` FROM settings WHERE `value` LIKE '%[CLIENT COPY%'");
                $settingsWithCopy = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($settingsWithCopy)) {
                    $details = [];
                    foreach ($settingsWithCopy as $s) {
                        $details[] = "Setting '{$s['key']}': " . substr($s['value'], 0, 50) . '...';
                    }
                    $checks[] = [
                        'key'       => 'settings_placeholder',
                        'name'      => 'Settings copy',
                        'passed'    => false,
                        'message'   => 'Settings still have placeholder text (e.g. mission statement)',
                        'fix_url'   => '/admin/settings',
                        'fix_label' => 'Edit',
                        'details'   => $details,
                    ];
                } else {
                    $checks[] = [
                        'key'       => 'settings_placeholder',
                        'name'      => 'Settings copy',
                        'passed'    => true,
                        'message'   => 'Settings do not have placeholder text',
                        'fix_url'   => null,
                        'fix_label' => null,
                        'details'   => [],
                    ];
                }
            } catch (Throwable $e) {
                $checks[] = [
                    'key'       => 'settings_placeholder',
                    'name'      => 'Settings copy',
                    'passed'    => false,
                    'message'   => 'Could not inspect settings table',
                    'fix_url'   => '/admin/settings',
                    'fix_label' => 'Edit',
                    'details'   => [],
                ];
            }
        }

        // 3. Categories table placeholder check
        if ($db) {
            try {
                $stmt = $db->query("SELECT id, name FROM categories WHERE name LIKE '%[CLIENT COPY%' OR description LIKE '%[CLIENT COPY%'");
                $categoriesWithCopy = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($categoriesWithCopy)) {
                    $details = [];
                    foreach ($categoriesWithCopy as $c) {
                        $details[] = "Category #{$c['id']}: '{$c['name']}'";
                    }
                    $checks[] = [
                        'key'       => 'categories_placeholder',
                        'name'      => 'Categories copy',
                        'passed'    => false,
                        'message'   => 'Categories still have placeholder text',
                        'fix_url'   => '/admin/categories',
                        'fix_label' => 'Edit',
                        'details'   => $details,
                    ];
                } else {
                    $checks[] = [
                        'key'       => 'categories_placeholder',
                        'name'      => 'Categories copy',
                        'passed'    => true,
                        'message'   => 'Categories do not have placeholder text',
                        'fix_url'   => null,
                        'fix_label' => null,
                        'details'   => [],
                    ];
                }
            } catch (Throwable $e) {
                $checks[] = [
                    'key'       => 'categories_placeholder',
                    'name'      => 'Categories copy',
                    'passed'    => false,
                    'message'   => 'Could not inspect categories table',
                    'fix_url'   => '/admin/categories',
                    'fix_label' => 'Edit',
                    'details'   => [],
                ];
            }
        }

        // 4. Template views placeholder check
        $viewsWithCopy = self::checkViewsForPlaceholders();
        if (!empty($viewsWithCopy)) {
            $checks[] = [
                'key'       => 'views_placeholder',
                'name'      => 'Template views copy',
                'passed'    => false,
                'message'   => 'Template views still contain placeholder text',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => $viewsWithCopy,
            ];
        } else {
            $checks[] = [
                'key'       => 'views_placeholder',
                'name'      => 'Template views copy',
                'passed'    => true,
                'message'   => 'Template views do not have placeholder text',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => [],
            ];
        }

        // 5. Cloudflare Turnstile keys check
        $siteKey   = trim((string) config('turnstile.site_key', ''));
        $secretKey = trim((string) config('turnstile.secret_key', ''));

        if ($siteKey === '' || $secretKey === '') {
            $checks[] = [
                'key'       => 'turnstile_keys',
                'name'      => 'Turnstile keys',
                'passed'    => false,
                'message'   => 'Turnstile keys are empty (production requires real keys)',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => ['Set turnstile.site_key and turnstile.secret_key in config.local.php'],
            ];
        } elseif (Turnstile::isTestKey($siteKey) || Turnstile::isTestKey($secretKey)) {
            $checks[] = [
                'key'       => 'turnstile_keys',
                'name'      => 'Turnstile keys',
                'passed'    => false,
                'message'   => 'Turnstile is using Cloudflare test keys (production requires real keys)',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => ['Replace Cloudflare test keys with real production keys in config.local.php'],
            ];
        } else {
            $checks[] = [
                'key'       => 'turnstile_keys',
                'name'      => 'Turnstile keys',
                'passed'    => true,
                'message'   => 'Turnstile is configured with production keys',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => [],
            ];
        }

        // 6. Debug mode check
        $debug = (bool) config('app.debug', false);
        if ($debug) {
            $checks[] = [
                'key'       => 'app_debug',
                'name'      => 'Debug mode',
                'passed'    => false,
                'message'   => 'Debug mode is on (must be disabled for launch)',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => ["Set 'debug' => false in config.local.php"],
            ];
        } else {
            $checks[] = [
                'key'       => 'app_debug',
                'name'      => 'Debug mode',
                'passed'    => true,
                'message'   => 'Debug mode is off',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => [],
            ];
        }

        // 7. setup.php presence check
        $setupPath = defined('PUBLIC_PATH') 
            ? PUBLIC_PATH . '/setup.php' 
            : (defined('ROOT_PATH') && file_exists(ROOT_PATH . '/public_html/setup.php') 
                ? ROOT_PATH . '/public_html/setup.php' 
                : dirname(__DIR__, 2) . '/setup.php');
        if (file_exists($setupPath)) {
            $checks[] = [
                'key'       => 'setup_file',
                'name'      => 'setup.php file',
                'passed'    => false,
                'message'   => 'setup.php is still present in public_html (delete before launch)',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => ['Delete public_html/setup.php from disk'],
            ];
        } else {
            $checks[] = [
                'key'       => 'setup_file',
                'name'      => 'setup.php file',
                'passed'    => true,
                'message'   => 'setup.php has been deleted',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => [],
            ];
        }

        // 8. HTTPS active check
        $httpsActive = self::isHttpsActive();
        if (!$httpsActive) {
            $checks[] = [
                'key'       => 'https_active',
                'name'      => 'HTTPS active',
                'passed'    => false,
                'message'   => 'HTTPS is not active',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => ['Ensure SSL certificate is active and site is served over HTTPS'],
            ];
        } else {
            $checks[] = [
                'key'       => 'https_active',
                'name'      => 'HTTPS active',
                'passed'    => true,
                'message'   => 'HTTPS is active',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => [],
            ];
        }

        // 9. Site address check
        $siteAddressValid = self::isSiteAddressValid();
        if ($siteAddressValid) {
            $checks[] = [
                'key'       => 'site_address',
                'name'      => 'Site address',
                'passed'    => true,
                'message'   => 'Site address is set correctly',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => [],
            ];
        } else {
            $checks[] = [
                'key'       => 'site_address',
                'name'      => 'Site address',
                'passed'    => false,
                'message'   => 'Site address is not set correctly',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => ["Set 'url' in config.local.php to the live domain"],
            ];
        }

        // 9b. Security salt check
        $saltValid = self::isSaltValid();
        if ($saltValid) {
            $checks[] = [
                'key'       => 'security_salt',
                'name'      => 'Security salt',
                'passed'    => true,
                'message'   => 'Security salt is properly configured',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => [],
            ];
        } else {
            $checks[] = [
                'key'       => 'security_salt',
                'name'      => 'Security salt',
                'passed'    => false,
                'message'   => 'Security salt is using placeholder or is shorter than 32 characters',
                'fix_url'   => null,
                'fix_label' => null,
                'details'   => ["Generate a random 32+ character salt (e.g. openssl rand -hex 32) and set 'security.salt' in config.local.php"],
            ];
        }

        // 10. At least one published post
        if ($db) {
            try {
                $pubCount = (int) $db->query("SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at <= NOW()")->fetchColumn();
                if ($pubCount < 1) {
                    $checks[] = [
                        'key'       => 'published_posts',
                        'name'      => 'Published posts',
                        'passed'    => false,
                        'message'   => 'No published posts yet',
                        'fix_url'   => '/admin/posts/new',
                        'fix_label' => 'New post',
                        'details'   => ['Create and publish at least one post before launch'],
                    ];
                } else {
                    $checks[] = [
                        'key'       => 'published_posts',
                        'name'      => 'Published posts',
                        'passed'    => true,
                        'message'   => "At least one published post is live ({$pubCount} published)",
                        'fix_url'   => null,
                        'fix_label' => null,
                        'details'   => [],
                    ];
                }
            } catch (Throwable $e) {
                $checks[] = [
                    'key'       => 'published_posts',
                    'name'      => 'Published posts',
                    'passed'    => false,
                    'message'   => 'Could not inspect posts table: ' . $e->getMessage(),
                    'fix_url'   => '/admin/posts/new',
                    'fix_label' => 'New post',
                    'details'   => [],
                ];
            }
        }

        // 11. Protected folders security self-test check
        $storageDir = defined('APP_PATH') ? APP_PATH . '/storage' : dirname(__DIR__) . '/storage';
        $selfTestFile = $storageDir . '/self_test.json';
        if (!file_exists($selfTestFile)) {
            $checks[] = [
                'key'       => 'protected_folders',
                'name'      => 'Protected folders',
                'passed'    => false,
                'message'   => 'Protected folders are not reachable',
                'fix_url'   => '/admin/maintenance',
                'fix_label' => 'Run self-test',
                'details'   => ['Run security self-test from Admin → Maintenance after each deploy.'],
            ];
        } else {
            $testData = json_decode((string) file_get_contents($selfTestFile), true);
            $isPassed = !empty($testData['passed']);
            if ($isPassed) {
                $testedAt = !empty($testData['tested_at']) ? date('M j, Y H:i', strtotime((string) $testData['tested_at'])) : 'recently';
                $checks[] = [
                    'key'       => 'protected_folders',
                    'name'      => 'Protected folders',
                    'passed'    => true,
                    'message'   => 'Protected folders are not reachable',
                    'fix_url'   => null,
                    'fix_label' => null,
                    'details'   => ["Verified unreachable via security self-test ({$testedAt})"],
                ];
            } else {
                $checks[] = [
                    'key'       => 'protected_folders',
                    'name'      => 'Protected folders',
                    'passed'    => false,
                    'message'   => 'Protected folders are not reachable',
                    'fix_url'   => '/admin/maintenance',
                    'fix_label' => 'Fix & re-run',
                    'details'   => ['Security self-test detected accessible protected folders. Check .htaccess configuration.'],
                ];
            }
        }

        $failCount = 0;
        foreach ($checks as $c) {
            if (!$c['passed']) {
                $failCount++;
            }
        }

        return [
            'checks'    => $checks,
            'allPassed' => ($failCount === 0),
            'failCount' => $failCount,
        ];
    }

    /**
     * Check whether HTTPS is active.
     */
    public static function isHttpsActive(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }
        $appUrl = (string) config('app.url', '');
        return parse_url($appUrl, PHP_URL_SCHEME) === 'https';
    }

    /**
     * Scan app/views for [CLIENT COPY placeholders.
     *
     * @return array<string>
     */
    public static function checkViewsForPlaceholders(): array
    {
        $viewsDir = defined('APP_PATH') ? APP_PATH . '/views' : dirname(__DIR__) . '/views';
        $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        $viewFilesWithCopy = [];

        if (is_dir($viewsDir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    if ($content !== false && str_contains($content, '[CLIENT COPY')) {
                        $relPath = str_replace([$rootPath . '/', $viewsDir . '/'], ['', 'app/views/'], $file->getPathname());
                        $viewFilesWithCopy[] = $relPath;
                    }
                }
            }
        }

        return $viewFilesWithCopy;
    }

    /**
     * Check whether config 'url' is set correctly for production:
     * - must start with https://
     * - must not contain 127.0.0.1, localhost, or a port
     * - its host must match current request host (if available)
     */
    public static function isSiteAddressValid(?string $url = null): bool
    {
        if ($url === null) {
            $url = (string) (config('url') ?? config('app.url') ?? '');
        }
        $url = trim($url);
        if ($url === '' || !str_starts_with($url, 'https://')) {
            return false;
        }
        if (str_contains($url, '127.0.0.1') || str_contains($url, 'localhost')) {
            return false;
        }
        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['host'])) {
            return false;
        }
        // Must not contain a port
        if (!empty($parsed['port']) || preg_match('/:\d+/', $url)) {
            return false;
        }
        // If a request host is present, host must match
        $reqHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        if (!empty($reqHost)) {
            $reqHostClean = explode(':', $reqHost)[0];
            if (strcasecmp($parsed['host'], $reqHostClean) !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check whether config 'security.salt' is properly configured:
     * - must not equal placeholder 'CHANGE_ME_generate_with_openssl_rand_hex_32'
     * - must not be shorter than 32 characters
     * - must not equal template default 'replace-with-a-random-64-char-string-in-config.local.php'
     */
    public static function isSaltValid(?string $salt = null): bool
    {
        if ($salt === null) {
            $salt = (string) config('security.salt', '');
        }
        $placeholder = 'CHANGE_ME_generate_with_openssl_rand_hex_32';
        $defaultConfig = 'replace-with-a-random-64-char-string-in-config.local.php';
        if ($salt === $placeholder || $salt === $defaultConfig || strlen($salt) < 32) {
            return false;
        }
        return true;
    }
}
