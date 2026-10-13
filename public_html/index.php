<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Front Controller
 *
 * All public web requests route through this entrypoint.
 * App source code resides outside public_html in /app.
 */

// Define application root constants
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// -------------------------------------------------------------
// 1. PSR-4 Class Autoloader & Vendor Autoloader
// -------------------------------------------------------------
spl_autoload_register(function (string $class): void {
    // Prefix for application namespace
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    // Convert namespace separators to directory separators and lowercase first folder
    $parts = explode('\\', $relativeClass);
    if (!empty($parts)) {
        $parts[0] = strtolower($parts[0]); // e.g. Core -> core, Controllers -> controllers
    }

    $file = $baseDir . implode('/', $parts) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Load local Composer vendor autoloader if installed
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// -------------------------------------------------------------
// 2. Load Core Configuration & Helpers
// -------------------------------------------------------------
require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';
date_default_timezone_set('Asia/Kolkata');

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\View;

// -------------------------------------------------------------
// 2b. Global Security Response Headers
// Note: Hostinger mod_headers is unavailable (unwrapped causes 500,
// wrapped is skipped). All security headers are sent via PHP.
// -------------------------------------------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

if (Auth::isHttps()) {
    header('Strict-Transport-Security: max-age=31536000');
}

// -------------------------------------------------------------
// 3. Robust Error & Exception Handling
// -------------------------------------------------------------
$isDebug = (bool) config('app.debug', false);

if ($isDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);

    $logFile = APP_PATH . '/storage/logs/error.log';
    ini_set('error_log', $logFile);

    // Global Exception Handler for Production
    set_exception_handler(function (Throwable $e) use ($logFile): void {
        $msg = sprintf(
            "[%s] Uncaught Exception: %s in %s:%d\nStack Trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        @file_put_contents($logFile, $msg, FILE_APPEND);

        http_response_code(500);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>500 Server Error — NatConnect Foundation</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<link rel="stylesheet" href="/assets/css/app.css">';
        echo '</head><body class="bg-surface text-ink min-h-screen flex items-center justify-center p-6">';
        echo '<div class="text-center max-w-md bg-white p-8 rounded-xl border border-gray-200 shadow-sm">';
        echo '<span class="inline-block px-3 py-1 bg-rose-100 text-rose-800 font-semibold text-xs rounded-full uppercase tracking-wider mb-3">System Notice</span>';
        echo '<h1 class="font-serif text-2xl font-bold text-primary mb-3">Something Went Wrong</h1>';
        echo '<p class="text-muted text-sm mb-6">Our technical team has logged this incident and is addressing it. Please try again shortly.</p>';
        echo '<a href="/" class="inline-flex items-center px-5 py-2.5 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded-lg transition-colors">Return to Homepage</a>';
        echo '</div></body></html>';
        exit;
    });
}

// -------------------------------------------------------------
// 4. Initialize Secure Session
// -------------------------------------------------------------
Auth::startSession();

// Detect when post_max_size is exceeded (empty $_POST and $_FILES while CONTENT_LENGTH > 0)
if (
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' &&
    empty($_POST) &&
    empty($_FILES) &&
    (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
) {
    $requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
    $requestPath = '/' . trim((string) parse_url($requestUri, PHP_URL_PATH), '/');

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')
        || ($requestPath === '/admin/media/upload');

    if ($isAjax) {
        http_response_code(413);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Upload too large']);
        exit;
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? '/admin';
    $_SESSION['flash_errors'] = ['general' => 'Upload too large'];
    $_SESSION['flash_error'] = 'Upload too large';
    header('Location: ' . $referer);
    exit;
}

// -------------------------------------------------------------
// 5. Initialize Router & Define Core Routes
// -------------------------------------------------------------
$router = new Router();

// Phase 4A & 4B: Public Routes
$router->get('/', [App\Controllers\PublicController::class, 'home']);
$router->get('/post/{slug}', [App\Controllers\PublicController::class, 'post']);
$router->get('/category/{slug}', [App\Controllers\PublicController::class, 'category']);
$router->get('/tag/{slug}', [App\Controllers\PublicController::class, 'tag']);
$router->get('/search', [App\Controllers\PublicController::class, 'search']);
$router->get('/about', [App\Controllers\PublicController::class, 'about']);
$router->get('/contact', [App\Controllers\PublicController::class, 'contact']);
$router->get('/privacy', [App\Controllers\PublicController::class, 'privacy']);
$router->get('/donate', [App\Controllers\PublicController::class, 'donate']);
$router->get('/gallery', [App\Controllers\PublicController::class, 'gallery']);

// Design Phase D1: All Stories Archive & Subscribe
$router->get('/stories', [App\Controllers\PublicController::class, 'stories']);
$router->get('/causes', [App\Controllers\PublicController::class, 'causes']);
$router->post('/subscribe', [App\Controllers\PublicController::class, 'subscribe']);

// Phase 6A: SEO & Syndication Routes
$router->get('/sitemap.xml', [App\Controllers\PublicController::class, 'sitemapXml']);
$router->get('/robots.txt', [App\Controllers\PublicController::class, 'robotsTxt']);
$router->get('/feed', [App\Controllers\PublicController::class, 'rssFeed']);
$router->get('/feed.xml', [App\Controllers\PublicController::class, 'rssFeed']);

// Phase 5: Engagement API Routes
$router->post('/api/view/{id}', [App\Controllers\PublicController::class, 'recordView']);
$router->post('/api/view/{postId}', [App\Controllers\PublicController::class, 'recordView']);
$router->post('/api/like/{id}', [App\Controllers\PublicController::class, 'toggleLike']);
$router->post('/api/like/{postId}', [App\Controllers\PublicController::class, 'toggleLike']);
$router->post('/post/{slug}/comment', [App\Controllers\PublicController::class, 'submitComment']);

// Custom 404 Handler
$router->setNotFound([App\Controllers\PublicController::class, 'notFound']);

// -------------------------------------------------------------
// Phase 2: Admin Authentication Routes
// -------------------------------------------------------------
$router->get('/admin/login', [App\Controllers\Admin\AuthController::class, 'showLogin']);
$router->post('/admin/login', [App\Controllers\Admin\AuthController::class, 'login']);
$router->post('/admin/logout', [App\Controllers\Admin\AuthController::class, 'logout']);

// -------------------------------------------------------------
// Phase 2: Admin Dashboard & Account Management Routes
// -------------------------------------------------------------
$router->get('/admin', [App\Controllers\Admin\DashboardController::class, 'index']);
$router->get('/admin/dashboard', [App\Controllers\Admin\DashboardController::class, 'index']);
$router->get('/admin/account', [App\Controllers\Admin\AccountController::class, 'show']);
$router->post('/admin/account/profile', [App\Controllers\Admin\AccountController::class, 'updateProfile']);
$router->post('/admin/account/password', [App\Controllers\Admin\AccountController::class, 'updatePassword']);

// -------------------------------------------------------------
// Phase 3A: Categories Routes
// -------------------------------------------------------------
$router->get('/admin/categories', [App\Controllers\Admin\CategoryController::class, 'index']);
$router->post('/admin/categories', [App\Controllers\Admin\CategoryController::class, 'store']);
$router->post('/admin/categories/{id}/edit', [App\Controllers\Admin\CategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [App\Controllers\Admin\CategoryController::class, 'delete']);
$router->post('/admin/categories/{id}/move', [App\Controllers\Admin\CategoryController::class, 'reorder']);

// Design Phase D1: Tags Management Routes
$router->get('/admin/tags', [App\Controllers\Admin\TagController::class, 'index']);
$router->post('/admin/tags', [App\Controllers\Admin\TagController::class, 'store']);
$router->get('/admin/tags/{id}/edit', [App\Controllers\Admin\TagController::class, 'edit']);
$router->post('/admin/tags/{id}/edit', [App\Controllers\Admin\TagController::class, 'update']);
$router->post('/admin/tags/{id}/delete', [App\Controllers\Admin\TagController::class, 'delete']);

// -------------------------------------------------------------
// Phase 3A: Posts Editorial Routes
// -------------------------------------------------------------
$router->get('/admin/posts', [App\Controllers\Admin\PostController::class, 'index']);
$router->get('/admin/posts/new', [App\Controllers\Admin\PostController::class, 'create']);
$router->post('/admin/posts/new', [App\Controllers\Admin\PostController::class, 'store']);
$router->get('/admin/posts/{id}/edit', [App\Controllers\Admin\PostController::class, 'edit']);
$router->post('/admin/posts/{id}/edit', [App\Controllers\Admin\PostController::class, 'update']);
$router->get('/admin/posts/{id}/preview', [App\Controllers\Admin\PostController::class, 'preview']);
$router->post('/admin/posts/{id}/delete', [App\Controllers\Admin\PostController::class, 'delete']);
$router->post('/admin/posts/bulk-delete', [App\Controllers\Admin\PostController::class, 'bulkDelete']);

// Phase 3A: Keepalive Heartbeat
$router->get('/admin/keepalive', [App\Controllers\Admin\PostController::class, 'keepalive']);
$router->post('/admin/keepalive', [App\Controllers\Admin\PostController::class, 'keepalive']);

// Phase 3B: Media Upload
$router->post('/admin/media/upload', [App\Controllers\Admin\MediaController::class, 'upload']);

// Phase 4C: Admin Gallery Routes
$router->get('/admin/gallery', [App\Controllers\Admin\GalleryController::class, 'index']);
$router->post('/admin/gallery/upload', [App\Controllers\Admin\GalleryController::class, 'upload']);
$router->post('/admin/gallery/{id}/edit', [App\Controllers\Admin\GalleryController::class, 'update']);
$router->post('/admin/gallery/{id}/delete', [App\Controllers\Admin\GalleryController::class, 'delete']);
$router->post('/admin/gallery/{id}/move', [App\Controllers\Admin\GalleryController::class, 'reorder']);

// Phase 5: Admin Comments Routes
$router->get('/admin/comments', [App\Controllers\Admin\CommentController::class, 'index']);
$router->post('/admin/comments/bulk', [App\Controllers\Admin\CommentController::class, 'bulk']);
$router->post('/admin/comments/{id}/approve', [App\Controllers\Admin\CommentController::class, 'approve']);
$router->post('/admin/comments/{id}/spam', [App\Controllers\Admin\CommentController::class, 'spam']);
$router->post('/admin/comments/{id}/delete', [App\Controllers\Admin\CommentController::class, 'delete']);

// Design Phase D1: Admin Subscribers Routes
$router->get('/admin/subscribers', [App\Controllers\Admin\SubscriberController::class, 'index']);
$router->get('/admin/subscribers/export', [App\Controllers\Admin\SubscriberController::class, 'exportCsv']);
$router->post('/admin/subscribers/{id}/delete', [App\Controllers\Admin\SubscriberController::class, 'delete']);

$router->get('/admin/settings', [App\Controllers\Admin\SettingsController::class, 'show']);
$router->post('/admin/settings', [App\Controllers\Admin\SettingsController::class, 'save']);

// Phase 6: Admin Pages Routes
$router->get('/admin/pages', [App\Controllers\Admin\PageController::class, 'index']);
$router->get('/admin/pages/new', [App\Controllers\Admin\PageController::class, 'create']);
$router->post('/admin/pages/new', [App\Controllers\Admin\PageController::class, 'store']);
$router->get('/admin/pages/{id}/edit', [App\Controllers\Admin\PageController::class, 'edit']);
$router->post('/admin/pages/{id}/edit', [App\Controllers\Admin\PageController::class, 'update']);
$router->get('/admin/pages/{id}/preview', [App\Controllers\Admin\PageController::class, 'preview']);
$router->post('/admin/pages/{id}/delete', [App\Controllers\Admin\PageController::class, 'delete']);

// Dynamic Public Pages Fallback (checked after all fixed routes, before 404)
$router->get('/{slug}', [App\Controllers\PublicController::class, 'showPage']);

// -------------------------------------------------------------
// 6. Admin Auth Middleware & Request Dispatch
// -------------------------------------------------------------
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri    = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath   = '/' . trim((string) parse_url($requestUri, PHP_URL_PATH), '/');

// Enforce Auth Middleware on all /admin/* routes except /admin/login
if (str_starts_with($requestPath, '/admin')) {
    Auth::sendAdminHeaders();

    if ($requestPath !== '/admin/login') {
        Auth::requireAuth($requestUri);
    }
}

$router->dispatch($requestMethod, $requestUri);
