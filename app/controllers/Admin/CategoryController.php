<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\CategoryModel;
use Throwable;

/**
 * NatConnect Foundation — Category Management Controller
 */
class CategoryController
{
    /**
     * Display categories list and creation form.
     */
    public function index(): void
    {
        Auth::requireAuth('/admin/categories');

        $categories = CategoryModel::getAll();

        View::render('admin/categories', [
            'pageTitle'  => 'Categories — NatConnect Foundation',
            'activeNav'  => 'categories',
            'categories' => $categories,
            'errors'     => $_SESSION['flash_errors'] ?? [],
            'old'        => $_SESSION['flash_old'] ?? [],
        ], 'layouts/admin');

        unset($_SESSION['flash_errors'], $_SESSION['flash_old']);
    }

    /**
     * Create a new category.
     */
    public function store(): void
    {
        Auth::requireAuth('/admin/categories');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_errors'] = ['csrf' => 'Invalid or expired session token. Please try again.'];
            header('Location: /admin/categories');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($slug === '') {
            $slug = slugify($name);
        } else {
            $slug = strtolower($slug);
        }

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Category name is required.';
        }
        if (!CategoryModel::isValidSlug($slug)) {
            $errors['slug'] = 'Slug must contain only lowercase letters, numbers, and hyphens.';
        } elseif (CategoryModel::isSlugTaken($slug)) {
            $errors['slug'] = "Slug '{$slug}' is already taken.";
        }

        if (!empty($errors)) {
            $_SESSION['flash_errors'] = $errors;
            $_SESSION['flash_old'] = $_POST;
            header('Location: /admin/categories');
            exit;
        }

        try {
            CategoryModel::create($name, $slug, $description !== '' ? $description : null);
            $_SESSION['flash_success'] = "Category '{$name}' created successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_errors'] = ['general' => $e->getMessage()];
            $_SESSION['flash_old'] = $_POST;
        }

        header('Location: /admin/categories');
        exit;
    }

    /**
     * Update an existing category.
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/categories');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_errors'] = ['csrf' => 'Invalid or expired session token. Please try again.'];
            header('Location: /admin/categories');
            exit;
        }

        $existing = CategoryModel::getById($id);
        if ($existing === null) {
            $_SESSION['flash_error'] = 'Category not found.';
            header('Location: /admin/categories');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($slug === '') {
            $slug = slugify($name);
        } else {
            $slug = strtolower($slug);
        }

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Category name is required.';
        }
        if (!CategoryModel::isValidSlug($slug)) {
            $errors['slug'] = 'Slug must contain only lowercase letters, numbers, and hyphens.';
        } elseif (CategoryModel::isSlugTaken($slug, $id)) {
            $errors['slug'] = "Slug '{$slug}' is already taken.";
        }

        if (!empty($errors)) {
            $_SESSION['flash_errors'] = $errors;
            $_SESSION['flash_old'] = $_POST;
            header('Location: /admin/categories');
            exit;
        }

        try {
            CategoryModel::update($id, $name, $slug, $description !== '' ? $description : null);
            $_SESSION['flash_success'] = "Category '{$name}' updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_errors'] = ['general' => $e->getMessage()];
        }

        header('Location: /admin/categories');
        exit;
    }

    /**
     * Delete a category (with post reassignment if posts exist).
     */
    public function delete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/categories');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Invalid session token.';
            header('Location: /admin/categories');
            exit;
        }

        $moveTo = isset($_POST['move_to_category_id']) && (int) $_POST['move_to_category_id'] > 0
            ? (int) $_POST['move_to_category_id']
            : null;

        try {
            CategoryModel::delete($id, $moveTo);
            $_SESSION['flash_success'] = 'Category deleted successfully.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: /admin/categories');
        exit;
    }
}
