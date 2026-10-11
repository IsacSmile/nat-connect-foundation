<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\UserModel;

/**
 * NatConnect Foundation — Admin Authentication Controller
 */
class AuthController
{
    /**
     * Show the admin sign-in form.
     */
    public function showLogin(): void
    {
        Auth::sendAdminHeaders();

        if (Auth::check()) {
            redirect('/admin/dashboard');
        }

        $nextParam = (string) ($_GET['next'] ?? '');
        $next = Auth::isValidAdminRedirect($nextParam) ? $nextParam : '';

        View::render('admin/login', [
            'pageTitle' => 'Admin Sign In — NatConnect Foundation',
            'next'      => $next,
            'errors'    => [],
        ], null); // Render standalone full-page view
    }

    /**
     * Process authentication submission.
     */
    public function login(): void
    {
        Auth::sendAdminHeaders();

        // 1. Verify CSRF
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security token expired or invalid. Please try again.');
            redirect('/admin/login');
        }

        // 2. Opportunistic cleanup of stale attempts older than 24h
        UserModel::purgeOldAttempts(24);

        // 3. Compute client IP hash
        $ip = clientIp();
        $salt = (string) config('security.salt', '');
        $ipHash = hash('sha256', $ip . $salt);

        // 4. Rate limiting: Check failed attempts in the last 15 minutes
        $attemptsData = UserModel::getFailedAttempts($ipHash, 15);
        $maxAttempts = (int) config('auth.max_attempts', 5);

        if ($attemptsData['count'] >= $maxAttempts) {
            $oldestTime = strtotime($attemptsData['oldest_attempt'] ?? 'now');
            $secondsRemaining = max(60, ($oldestTime + (15 * 60)) - time());
            $minutesRemaining = max(1, (int) ceil($secondsRemaining / 60));

            $errorMsg = sprintf(
                'Too many failed login attempts. Please try again in %d minute%s.',
                $minutesRemaining,
                $minutesRemaining === 1 ? '' : 's'
            );

            View::render('admin/login', [
                'pageTitle' => 'Admin Sign In — NatConnect Foundation',
                'next'      => Auth::isValidAdminRedirect($_POST['next'] ?? null) ? (string) $_POST['next'] : '',
                'errors'    => [$errorMsg],
                'email'     => (string) ($_POST['email'] ?? ''),
            ], null);
            return;
        }

        // 5. Read inputs
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $next     = Auth::isValidAdminRedirect($_POST['next'] ?? null) ? (string) $_POST['next'] : '/admin/dashboard';

        // 6. Generic failure response
        $genericError = 'Invalid email or password.';

        if ($email === '' || $password === '') {
            UserModel::recordFailedLogin($ipHash);
            View::render('admin/login', [
                'pageTitle' => 'Admin Sign In — NatConnect Foundation',
                'next'      => $next,
                'errors'    => [$genericError],
                'email'     => $email,
            ], null);
            return;
        }

        // 7. Verify credentials
        $user = UserModel::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            UserModel::recordFailedLogin($ipHash);
            View::render('admin/login', [
                'pageTitle' => 'Admin Sign In — NatConnect Foundation',
                'next'      => $next,
                'errors'    => [$genericError],
                'email'     => $email,
            ], null);
            return;
        }

        // 8. Rehash password if algorithm/cost parameters updated
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            UserModel::updatePassword((int) $user['id'], $newHash);
        }

        // 9. Clear recorded failed attempts for this IP on success
        UserModel::clearFailedAttempts($ipHash);

        // 10. Authenticate session with regeneration
        Auth::login($user);

        // 11. Redirect to requested admin route or dashboard
        redirect($next);
    }

    /**
     * Process logout via POST with CSRF verification only.
     */
    public function logout(): void
    {
        Auth::sendAdminHeaders();

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security token invalid.');
            redirect('/admin/dashboard');
        }

        Auth::logout();
        setFlash('info', 'You have been signed out successfully.');
        redirect('/admin/login');
    }
}
