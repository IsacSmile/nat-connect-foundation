<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\SubscriberModel;

/**
 * NatConnect Foundation — Subscribers Admin Controller (Design Phase D1)
 */
class SubscriberController
{
    /**
     * List subscribers with search and pagination.
     */
    public function index(): void
    {
        Auth::requireAuth('/admin/subscribers');

        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        $data = SubscriberModel::getPaginated($search, $page, 25);
        $totalCount = SubscriberModel::getCount();

        View::render('admin/subscribers/index', [
            'pageTitle'   => 'Subscribers — NatConnect Foundation',
            'activeNav'   => 'subscribers',
            'subscribers' => $data['items'],
            'total'       => $data['total'],
            'totalCount'  => $totalCount,
            'page'        => $data['page'],
            'totalPages'  => $data['total_pages'],
            'search'      => $search,
        ], 'layouts/admin');
    }

    /**
     * Delete a subscriber.
     */
    public function delete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/subscribers');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_errors'] = ['csrf' => 'Invalid or expired session token. Please try again.'];
            header('Location: /admin/subscribers');
            exit;
        }

        SubscriberModel::delete($id);
        $_SESSION['flash_success'] = 'Subscriber removed from the list.';
        header('Location: /admin/subscribers');
        exit;
    }

    /**
     * Export all subscribers to CSV with formula injection prevention.
     */
    public function exportCsv(): void
    {
        Auth::requireAuth('/admin/subscribers');

        $data = SubscriberModel::getExportData();
        $filename = 'subscribers-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            exit;
        }

        // CSV Header
        fputcsv($output, ['Email', 'Consent Date', 'Source']);

        // Data rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['email'],
                $row['consent_date'],
                $row['source'],
            ]);
        }

        fclose($output);
        exit;
    }
}
