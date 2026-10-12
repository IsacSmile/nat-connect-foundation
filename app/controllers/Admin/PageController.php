<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\PageModel;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * NatConnect Foundation — Admin Pages Controller
 */
class PageController
{
    /**
     * Display pages list.
     */
    public function index(): void
    {
        Auth::requireAuth('/admin/pages');

        $pages = PageModel::getAll();
        $success = $_SESSION['flash_success'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        View::render('admin/pages', [
            'pageTitle' => 'Pages — NatConnect Foundation',
            'activeNav' => 'pages',
            'pages'     => $pages,
            'success'   => $success,
            'error'     => $error,
        ], 'layouts/admin');
    }

    /**
     * Display create page form.
     */
    public function create(): void
    {
        Auth::requireAuth('/admin/pages/new');

        $errors = $_SESSION['flash_errors'] ?? [];
        $old = $_SESSION['flash_old'] ?? [];
        unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

        View::render('admin/page-edit', [
            'pageTitle' => 'New Page — NatConnect Foundation',
            'activeNav' => 'pages',
            'isNew'     => true,
            'page'      => $old ?: [
                'id'               => 0,
                'title'            => '',
                'slug'             => '',
                'content'          => '',
                'meta_title'       => '',
                'meta_description' => '',
                'status'           => 'draft',
                'show_in_nav'      => 0,
                'nav_label'        => '',
                'nav_order'        => 0,
                'is_system'        => 0,
            ],
            'errors'    => $errors,
        ], 'layouts/admin');
    }

    /**
     * Store new page in database.
     */
    public function store(): void
    {
        Auth::requireAuth('/admin/pages/new');
        Csrf::verify($_POST['csrf_token'] ?? null);

        try {
            $id = PageModel::create($_POST);
            $_SESSION['flash_success'] = 'Page created successfully.';
            header('Location: /admin/pages');
            exit;
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_errors'] = ['general' => $e->getMessage()];
            $_SESSION['flash_old'] = $_POST;
            header('Location: /admin/pages/new');
            exit;
        } catch (Throwable $e) {
            $_SESSION['flash_errors'] = ['general' => 'Failed to save page. Please try again.'];
            $_SESSION['flash_old'] = $_POST;
            header('Location: /admin/pages/new');
            exit;
        }
    }

    /**
     * Display edit page form.
     */
    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/pages/' . $id . '/edit');

        $page = PageModel::findById($id);
        if (!$page) {
            $_SESSION['flash_error'] = 'Page not found.';
            header('Location: /admin/pages');
            exit;
        }

        $errors = $_SESSION['flash_errors'] ?? [];
        $old = $_SESSION['flash_old'] ?? [];
        unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

        if (!empty($old)) {
            $page = array_merge($page, $old);
        }

        View::render('admin/page-edit', [
            'pageTitle' => 'Edit ' . $page['title'] . ' — NatConnect Foundation',
            'activeNav' => 'pages',
            'isNew'     => false,
            'page'      => $page,
            'errors'    => $errors,
        ], 'layouts/admin');
    }

    /**
     * Update existing page in database.
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/pages/' . $id . '/edit');
        Csrf::verify($_POST['csrf_token'] ?? null);

        try {
            PageModel::update($id, $_POST);
            $_SESSION['flash_success'] = 'Page updated successfully.';
            header('Location: /admin/pages');
            exit;
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_errors'] = ['general' => $e->getMessage()];
            $_SESSION['flash_old'] = $_POST;
            header('Location: /admin/pages/' . $id . '/edit');
            exit;
        } catch (Throwable $e) {
            $_SESSION['flash_errors'] = ['general' => 'Failed to update page. Please try again.'];
            $_SESSION['flash_old'] = $_POST;
            header('Location: /admin/pages/' . $id . '/edit');
            exit;
        }
    }

    /**
     * Preview page as an admin.
     */
    public function preview(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/pages/' . $id . '/preview');

        $page = PageModel::findById($id);
        if (!$page) {
            http_response_code(404);
            echo 'Page not found';
            exit;
        }

        $siteName = setting('site_name', 'NatConnect Foundation');
        $pageTitle = "[Preview] " . (!empty($page['meta_title']) ? $page['meta_title'] : "{$page['title']} — {$siteName}");
        $metaDescription = !empty($page['meta_description']) ? $page['meta_description'] : snippet(strip_tags($page['content']), 160);

        View::render('public/page', [
            'pageTitle'       => $pageTitle,
            'metaDescription' => $metaDescription,
            'canonicalUrl'    => url('/' . $page['slug']),
            'page'            => $page,
            'isPreview'       => true,
            'noindex'         => true,
        ], 'layouts/public');
    }

    /**
     * Delete page.
     */
    public function delete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/pages');
        Csrf::verify($_POST['csrf_token'] ?? null);

        try {
            PageModel::delete($id);
            $_SESSION['flash_success'] = 'Page deleted successfully.';
        } catch (RuntimeException|InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        } catch (Throwable) {
            $_SESSION['flash_error'] = 'Failed to delete page.';
        }

        header('Location: /admin/pages');
        exit;
    }
}
