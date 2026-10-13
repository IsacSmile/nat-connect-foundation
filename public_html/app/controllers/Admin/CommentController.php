<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\CommentModel;

/**
 * NatConnect Foundation — Comments Moderation Controller
 */
class CommentController
{
    /**
     * Display comments moderation list segmented by tab status.
     */
    public function index(): void
    {
        Auth::requireAuth('/admin/comments');

        $status = (string) ($_GET['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'approved', 'spam'], true)) {
            $status = 'pending';
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $counts = CommentModel::getStatusCounts();
        $total = $counts[$status] ?? 0;
        $totalPages = max(1, (int) ceil($total / $perPage));

        $comments = CommentModel::getByStatus($status, $perPage, $offset);

        View::render('admin/comments', [
            'pageTitle'   => 'Comments — NatConnect Foundation',
            'activeNav'   => 'comments',
            'currentTab'  => $status,
            'counts'      => $counts,
            'comments'    => $comments,
            'pagination'  => [
                'current' => $page,
                'total'   => $totalPages,
                'count'   => $total,
            ],
            'success'     => getFlash('success'),
            'error'       => getFlash('error'),
        ], 'layouts/admin');
    }

    /**
     * Approve single comment.
     */
    public function approve(array $params): void
    {
        Auth::requireAuth('/admin/comments');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security check failed. Please try again.');
            $returnStatus = $_POST['return_status'] ?? 'pending';
            redirect('/admin/comments?status=' . urlencode($returnStatus));
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id > 0) {
            CommentModel::updateStatus($id, 'approved');
            setFlash('success', 'Comment approved.');
        }

        $returnStatus = $_POST['return_status'] ?? 'pending';
        redirect('/admin/comments?status=' . urlencode($returnStatus));
    }

    /**
     * Mark single comment as spam.
     */
    public function spam(array $params): void
    {
        Auth::requireAuth('/admin/comments');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security check failed. Please try again.');
            $returnStatus = $_POST['return_status'] ?? 'pending';
            redirect('/admin/comments?status=' . urlencode($returnStatus));
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id > 0) {
            CommentModel::updateStatus($id, 'spam');
            setFlash('success', 'Comment marked as spam.');
        }

        $returnStatus = $_POST['return_status'] ?? 'pending';
        redirect('/admin/comments?status=' . urlencode($returnStatus));
    }

    /**
     * Delete single comment.
     */
    public function delete(array $params): void
    {
        Auth::requireAuth('/admin/comments');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security check failed. Please try again.');
            $returnStatus = $_POST['return_status'] ?? 'pending';
            redirect('/admin/comments?status=' . urlencode($returnStatus));
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id > 0) {
            CommentModel::delete($id);
            setFlash('success', 'Comment deleted.');
        }

        $returnStatus = $_POST['return_status'] ?? 'pending';
        redirect('/admin/comments?status=' . urlencode($returnStatus));
    }

    /**
     * Bulk actions for comments (approve, spam, delete).
     */
    public function bulk(): void
    {
        Auth::requireAuth('/admin/comments');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            setFlash('error', 'Security check failed. Please try again.');
            $returnStatus = $_POST['return_status'] ?? 'pending';
            redirect('/admin/comments?status=' . urlencode($returnStatus));
            return;
        }

        $action = trim((string) ($_POST['action'] ?? ''));
        $ids = (array) ($_POST['ids'] ?? []);
        $returnStatus = $_POST['return_status'] ?? 'pending';

        if (empty($ids)) {
            setFlash('error', 'No comments selected.');
            redirect('/admin/comments?status=' . urlencode($returnStatus));
            return;
        }

        if ($action === 'approve') {
            $updated = CommentModel::bulkUpdateStatus($ids, 'approved');
            setFlash('success', "{$updated} comment(s) approved.");
        } elseif ($action === 'spam') {
            $updated = CommentModel::bulkUpdateStatus($ids, 'spam');
            setFlash('success', "{$updated} comment(s) marked as spam.");
        } elseif ($action === 'delete') {
            $deleted = CommentModel::bulkDelete($ids);
            setFlash('success', "{$deleted} comment(s) deleted.");
        } else {
            setFlash('error', 'Invalid bulk action.');
        }

        redirect('/admin/comments?status=' . urlencode($returnStatus));
    }
}
