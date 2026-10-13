<?php
declare(strict_types=1);

/**
 * NatConnect Foundation — Initial Installation & Admin Account Setup
 *
 * This file allows creating the very first administrator account.
 * For security:
 * 1. Returns HTTP 404 if app/storage/installed.lock exists.
 * 2. Returns HTTP 404 if the `users` table already contains any accounts.
 * 3. Enforces a minimum 12-character password hashed with password_hash(PASSWORD_DEFAULT).
 * 4. Locks itself by writing app/storage/installed.lock upon completion.
 *
 * MUST BE DELETED AFTER FIRST USE.
 */

define('PUBLIC_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('ROOT_PATH', is_dir(dirname(__DIR__) . '/public_html') ? dirname(__DIR__) : __DIR__);

// PSR-4 Autoloader
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

require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/helpers.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;

// 1. Check lock file existence
$storageDir = APP_PATH . '/storage';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0755, true);
}
$lockFile = $storageDir . '/installed.lock';

if (file_exists($lockFile)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="en"><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1></body></html>';
    exit;
}

// 2. Check if users table already has records
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT COUNT(*) FROM `users`");
    $userCount = (int) $stmt->fetchColumn();

    if ($userCount > 0) {
        // Lock already implied by presence of users
        @file_put_contents($lockFile, "Existing users detected at " . date('c'));
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="en"><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1></body></html>';
        exit;
    }
} catch (Throwable $e) {
    // Database connection or table missing
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Database Setup Required — NatConnect Foundation</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="/assets/css/app.css">
    </head>
    <body class="bg-surface text-ink min-h-screen flex items-center justify-center p-6">
      <div class="max-w-lg bg-white p-8 rounded-xl border border-border shadow-soft">
        <h1 class="font-serif text-2xl font-bold text-primary mb-3">Database Connection Error</h1>
        <p class="text-sm text-ink-muted mb-4">
          The database tables could not be verified. Please ensure MySQL is running, credentials in <code class="bg-slate-100 px-1 py-0.5 rounded text-xs">app/config/config.local.php</code> are correct, and <code class="bg-slate-100 px-1 py-0.5 rounded text-xs">database/schema.sql</code> has been imported.
        </p>
        <div class="bg-slate-50 p-3 rounded-lg text-xs font-mono text-ink-soft overflow-x-auto">
          <?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') ?>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Start secure session for CSRF handling
Auth::startSession();

$errors = [];
$success = false;

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify()) {
        $errors[] = 'Security token expired or invalid. Please refresh and try again.';
    } else {
        $name            = trim((string) ($_POST['name'] ?? ''));
        $email           = trim((string) ($_POST['email'] ?? ''));
        $password        = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($name === '') {
            $errors[] = 'Full name is required.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if (strlen($password) < 12) {
            $errors[] = 'Password must be at least 12 characters long.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $db->prepare("
                    INSERT INTO `users` (`name`, `email`, `password_hash`, `role`)
                    VALUES (:name, :email, :hash, 'admin')
                ");
                $insert->execute([
                    ':name'  => $name,
                    ':email' => $email,
                    ':hash'  => $hash,
                ]);

                // Create installation lock file
                @file_put_contents($lockFile, "Installed on " . date('c') . " by " . $email . PHP_EOL);

                $success = true;
            } catch (Throwable $e) {
                $errors[] = 'Failed to create administrator account: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup Administrator — NatConnect Foundation</title>
  <link rel="icon" type="image/png" href="<?= asset('assets/img/logo.png') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="bg-surface text-ink min-h-screen flex items-center justify-center p-4 sm:p-6 antialiased">
  <div class="max-w-md w-full bg-white rounded-2xl border border-border p-8 shadow-soft">
    
    <!-- Brand Header -->
    <div class="text-center mb-8">
      <img src="<?= asset('assets/img/logo.png') ?>" alt="NatConnect Foundation" class="h-16 w-auto mx-auto object-contain mb-3">
      <h1 class="font-serif text-2xl font-bold text-primary">NatConnect Setup</h1>
      <p class="text-xs text-ink-muted mt-1">Configure the primary administrator account</p>
    </div>

    <?php if ($success): ?>
      <div class="space-y-4">
        <div class="p-4 rounded-xl bg-accent/10 border border-accent/20 text-ink">
          <div class="flex items-center gap-2 text-accent-strong font-semibold text-sm mb-1">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Setup Complete!</span>
          </div>
          <p class="text-xs text-ink-muted leading-relaxed">
            Administrator account created successfully and installation locked (<code class="bg-white px-1 py-0.5 rounded text-[11px]">installed.lock</code> written).
          </p>
        </div>

        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
          <strong>Critical Security Notice:</strong>
          <p class="mt-1">
            Please delete <code class="font-mono bg-white px-1 py-0.5 rounded text-[11px]">public_html/setup.php</code> from your server immediately.
          </p>
        </div>

        <a href="/" class="block w-full text-center py-2.5 px-4 bg-primary hover:bg-primary-hover text-white text-sm font-semibold rounded-xl transition-colors shadow-soft-sm">
          Go to Homepage
        </a>
      </div>

    <?php else: ?>

      <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
          <?php foreach ($errors as $error): ?>
            <p>• <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form action="/setup.php" method="POST" class="space-y-4">
        <?= Csrf::field() ?>

        <div>
          <label for="name" class="block text-xs font-semibold text-ink-soft mb-1">Full Name</label>
          <input 
            type="text" 
            id="name" 
            name="name" 
            value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
            required 
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
            placeholder="e.g. Administrator"
          />
        </div>

        <div>
          <label for="email" class="block text-xs font-semibold text-ink-soft mb-1">Email Address</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
            required 
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
            placeholder="admin@example.org"
          />
        </div>

        <div>
          <label for="password" class="block text-xs font-semibold text-ink-soft mb-1">Password (Min 12 characters)</label>
          <input 
            type="password" 
            id="password" 
            name="password" 
            minlength="12" 
            required 
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
            placeholder=""
          />
        </div>

        <div>
          <label for="confirm_password" class="block text-xs font-semibold text-ink-soft mb-1">Confirm Password</label>
          <input 
            type="password" 
            id="confirm_password" 
            name="confirm_password" 
            minlength="12" 
            required 
            class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm text-ink focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
            placeholder=""
          />
        </div>

        <button 
          type="submit" 
          class="w-full py-2.5 px-4 bg-accent-strong hover:bg-accent-hover text-white text-sm font-semibold rounded-xl shadow-soft transition-colors mt-2"
        >
          Create Admin Account
        </button>
      </form>

    <?php endif; ?>

  </div>
</body>
</html>
