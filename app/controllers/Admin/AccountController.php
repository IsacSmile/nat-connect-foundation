<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\UserModel;

/**
 * NatConnect Foundation — Admin Account & Credentials Controller
 */
class AccountController
{
    /**
     * Display account profile and security settings.
     */
    public function show(): void
    {
        Auth::requireAuth('/admin/account');

        $userId = Auth::id();
        $user = $userId ? UserModel::findById($userId) : null;

        if (!$user) {
            Auth::logout();
            redirect('/admin/login');
        }

        View::render('admin/account', [
            'pageTitle' => 'Account Settings — NatConnect Foundation',
            'activeNav' => 'account',
            'user'      => $user,
        ], 'layouts/admin');
    }

    /**
     * Update admin profile name and email address.
     */
    public function updateProfile(): void
    {
        Auth::requireAuth('/admin/account');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security token invalid. Please try again.');
            redirect('/admin/account');
        }

        $userId = (int) Auth::id();
        $name   = trim((string) ($_POST['name'] ?? ''));
        $email  = trim((string) ($_POST['email'] ?? ''));

        if ($name === '' || strlen($name) > 150) {
            setFlash('error', 'Please provide a valid full name (maximum 150 characters).');
            redirect('/admin/account');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 191) {
            setFlash('error', 'Please provide a valid email address.');
            redirect('/admin/account');
        }

        if (UserModel::isEmailTakenByOther($email, $userId)) {
            setFlash('error', 'This email address is already in use by another account.');
            redirect('/admin/account');
        }

        UserModel::updateProfile($userId, $name, $email);

        // Synchronize authenticated session data
        if (!empty($_SESSION['auth_user_data'])) {
            $_SESSION['auth_user_data']['name']  = $name;
            $_SESSION['auth_user_data']['email'] = $email;
        }

        setFlash('success', 'Profile details updated successfully.');
        redirect('/admin/account');
    }

    /**
     * Change admin password with current password verification and 12-char minimum rule.
     */
    public function updatePassword(): void
    {
        Auth::requireAuth('/admin/account');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security token invalid. Please try again.');
            redirect('/admin/account');
        }

        $userId          = (int) Auth::id();
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword     = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        // 1. Password length constraint (min 12 characters)
        if (strlen($newPassword) < 12) {
            setFlash('error', 'New password must be at least 12 characters in length.');
            redirect('/admin/account');
        }

        // 2. Confirmation match
        if ($newPassword !== $confirmPassword) {
            setFlash('error', 'New password and confirmation do not match.');
            redirect('/admin/account');
        }

        // 3. Verify current password
        $user = UserModel::findById($userId);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            setFlash('error', 'The current password provided is incorrect.');
            redirect('/admin/account');
        }

        // 4. Update password hash in database
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        UserModel::updatePassword($userId, $newHash);

        // 5. Regenerate session after password change
        session_regenerate_id(true);

        setFlash('success', 'Password updated successfully.');
        redirect('/admin/account');
    }
}
