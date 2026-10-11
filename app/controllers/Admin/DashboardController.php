<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
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

        $stats       = DashboardModel::getStats();
        $topPosts    = DashboardModel::getTopPostsByViews(5);
        $recentPosts = DashboardModel::getRecentlyUpdatedPosts(5);

        View::render('admin/dashboard', [
            'pageTitle'   => 'Admin Dashboard — NatConnect Foundation',
            'activeNav'   => 'dashboard',
            'stats'       => $stats,
            'topPosts'    => $topPosts,
            'recentPosts' => $recentPosts,
        ], 'layouts/admin');
    }

    /**
     * Phase 3 Placeholder: Articles & Posts Index.
     */
    public function posts(): void
    {
        Auth::requireAuth('/admin/posts');

        View::render('admin/placeholder', [
            'pageTitle'    => 'Articles & Posts — NatConnect Foundation',
            'activeNav'    => 'posts',
            'featureTitle' => 'Articles & Posts Management',
            'featurePhase' => 'Phase 3: Post CRUD & Media Management',
            'description'  => 'Create, publish, edit, schedule, and organize environmental articles and reports. Rich content editing and media uploads will be unlocked in Phase 3.',
        ], 'layouts/admin');
    }

    /**
     * Phase 3 Placeholder: New Post Creation.
     */
    public function newPost(): void
    {
        Auth::requireAuth('/admin/posts/new');

        View::render('admin/placeholder', [
            'pageTitle'    => 'New Article — NatConnect Foundation',
            'activeNav'    => 'new-post',
            'featureTitle' => 'New Article Composer',
            'featurePhase' => 'Phase 3: Post CRUD & Media Management',
            'description'  => 'The editorial drafting screen, featured cover upload, YouTube embedding, and HTMLPurifier sanitization pipeline will arrive in Phase 3.',
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
            'pageTitle'    => "Edit Article #{$id} — NatConnect Foundation",
            'activeNav'    => 'posts',
            'featureTitle' => "Edit Article #{$id}",
            'featurePhase' => 'Phase 3: Post CRUD & Media Management',
            'description'  => "Article editor for record #{$id} will be functional in Phase 3 with revision tracking, status transitions, and tag management.",
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
