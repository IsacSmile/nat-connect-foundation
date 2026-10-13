<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\LaunchCheck;
use App\Core\View;
use App\Models\DashboardModel;

/**
 * NatConnect Foundation — Admin Dashboard & Module Controller
 */
class DashboardController
{
    /**
     * Render the main dashboard panel with live metrics and activity tables.
     */
    public function index(): void
    {
        Auth::requireAuth('/admin/dashboard');

        $stats           = DashboardModel::getStats();
        $topPosts        = DashboardModel::getTopPostsByViews(5);
        $recentPosts     = DashboardModel::getRecentlyUpdatedPosts(5);
        $launchChecklist = LaunchCheck::runAll();

        View::render('admin/dashboard', [
            'pageTitle'       => 'Dashboard — NatConnect Foundation',
            'activeNav'       => 'dashboard',
            'stats'           => $stats,
            'topPosts'        => $topPosts,
            'recentPosts'     => $recentPosts,
            'launchChecklist' => $launchChecklist,
        ], 'layouts/admin');
    }

    /**
     * Phase 3 Placeholder: Posts Index.
     */
    public function posts(): void
    {
        Auth::requireAuth('/admin/posts');

        View::render('admin/placeholder', [
            'pageTitle'    => 'Posts — NatConnect Foundation',
            'activeNav'    => 'posts',
            'featureTitle' => 'Posts',
            'featurePhase' => 'Phase 3: Post CRUD & Media Management',
            'description'  => 'Create, publish, edit, schedule, and organize posts.',
        ], 'layouts/admin');
    }

    /**
     * Phase 3 Placeholder: New Post Creation.
     */
    public function newPost(): void
    {
        Auth::requireAuth('/admin/posts/new');

        View::render('admin/placeholder', [
            'pageTitle'    => 'New Post — NatConnect Foundation',
            'activeNav'    => 'new-post',
            'featureTitle' => 'New Post',
            'featurePhase' => 'Phase 3: Post CRUD & Media Management',
            'description'  => 'Create and schedule new posts.',
        ], 'layouts/admin');
    }

    /**
     * Phase 3 Placeholder: Edit Post.
     */
    public function editPost(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth("/admin/posts/{$id}/edit");

        View::render('admin/placeholder', [
            'pageTitle'    => "Edit Post #{$id} — NatConnect Foundation",
            'activeNav'    => 'posts',
            'featureTitle' => "Edit Post #{$id}",
            'featurePhase' => 'Phase 3: Post CRUD & Media Management',
            'description'  => "Edit content and settings for post #{$id}.",
        ], 'layouts/admin');
    }

    /**
     * Phase 3 Placeholder: Categories.
     */
    public function categories(): void
    {
        Auth::requireAuth('/admin/categories');

        View::render('admin/placeholder', [
            'pageTitle'    => 'Categories — NatConnect Foundation',
            'activeNav'    => 'categories',
            'featureTitle' => 'Category Architecture',
            'featurePhase' => 'Phase 3: Post CRUD & Media Management',
            'description'  => 'Manage core environmental advocacy pillars (Causes, News, Campaigns, RTI Applications) and custom taxonomies.',
        ], 'layouts/admin');
    }

    /**
     * Phase 3 Placeholder: Comments Moderation Queue.
     */
    public function comments(): void
    {
        Auth::requireAuth('/admin/comments');

        View::render('admin/placeholder', [
            'pageTitle'    => 'Comments Moderation — NatConnect Foundation',
            'activeNav'    => 'comments',
            'featureTitle' => 'Comments Moderation Queue',
            'featurePhase' => 'Phase 5: Engagement & Interactivity',
            'description'  => 'Review reader submissions, approve verified feedback, and mark spam inquiries with Turnstile protection.',
        ], 'layouts/admin');
    }

    /**
     * Phase 3 Placeholder: Site Settings.
     */
    public function settings(): void
    {
        Auth::requireAuth('/admin/settings');

        View::render('admin/placeholder', [
            'pageTitle'    => 'Site Settings — NatConnect Foundation',
            'activeNav'    => 'settings',
            'featureTitle' => 'Site & NGO Settings',
            'featurePhase' => 'Phase 3: Post CRUD & Media Management',
            'description'  => 'Configure organization details, social profiles, contact information, and public announcement banners.',
        ], 'layouts/admin');
    }
}
