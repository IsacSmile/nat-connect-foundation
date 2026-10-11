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

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\View;

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
        echo '<a href="/" class="inline-flex items-center px-5 py-2.5 bg-primary text-white text-sm font-medium rounded-lg">Return to Homepage</a>';
        echo '</div></body></html>';
        exit;
    });
}

// -------------------------------------------------------------
// 4. Initialize Secure Session
// -------------------------------------------------------------
Auth::startSession();

// -------------------------------------------------------------
// 5. Initialize Router & Define Core Routes
// -------------------------------------------------------------
$router = new Router();

// Phase 0/1: Temporary Home Route with Coming Soon section
$router->get('/', function () {
    View::render('public/home', [
        'pageTitle'       => 'NatConnect Foundation — Nature, Everything',
        'metaDescription' => 'NatConnect Foundation — Environmental advocacy and conservation platform.',
    ], 'layouts/public');
});

// Category Archives Preview Route
$router->get('/category/{slug}', function (array $params) {
    $categorySlug = $params['slug'] ?? '';
    View::render('public/home', [
        'pageTitle'       => ucfirst(str_replace('-', ' ', $categorySlug)) . ' — NatConnect Foundation',
        'metaDescription' => 'Archive listing for ' . e($categorySlug),
    ], 'layouts/public');
});

// Admin Login / Dashboard Stubs (Phase 2 anchors)
$router->get('/admin/login', function () {
    echo '<div style="font-family:sans-serif;padding:40px;text-align:center;"><h2>NatConnect Admin Portal</h2><p>Admin authentication module will be enabled in Phase 2.</p><a href="/">&larr; Return to Home</a></div>';
});

$router->get('/admin/dashboard', function () {
    View::render('admin/dashboard', [
        'pageTitle' => 'Admin Dashboard — NatConnect Foundation',
    ], 'layouts/admin');
});

// -------------------------------------------------------------
// 6. Dispatch Request
// -------------------------------------------------------------
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri    = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($requestMethod, $requestUri);
