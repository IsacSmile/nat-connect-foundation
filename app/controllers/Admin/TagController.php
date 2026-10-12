<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ImageUploader;
use App\Core\View;
use App\Models\TagModel;
use InvalidArgumentException;
use Throwable;

/**
 * NatConnect Foundation — Tag Management Controller (Design Phase D1)
 */
class TagController
{
    /**
     * Display tags list.
     */
    public function index(): void
    {
        Auth::requireAuth('/admin/tags');

        $tags = TagModel::getAllWithCounts();

        $errors = $_SESSION['flash_errors'] ?? [];
        $old = $_SESSION['flash_old'] ?? [];
        unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

        View::render('admin/tags/index', [
            'pageTitle' => 'Tags — NatConnect Foundation',
            'activeNav' => 'tags',
            'tags'      => $tags,
            'errors'    => $errors,
            'old'       => $old,
        ], 'layouts/admin');
    }

    /**
     * Display edit form for a tag.
     */
    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth("/admin/tags/{$id}/edit");

        $tag = TagModel::getById($id);
        if ($tag === null) {
            $_SESSION['flash_error'] = 'Tag not found.';
            header('Location: /admin/tags');
            exit;
        }

        $errors = $_SESSION['flash_errors'] ?? [];
        $old = $_SESSION['flash_old'] ?? [];
        unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

        View::render('admin/tags/edit', [
            'pageTitle' => "Edit Tag: {$tag['name']} — NatConnect Foundation",
            'activeNav' => 'tags',
            'tag'       => $tag,
            'errors'    => $errors,
            'old'       => $old,
        ], 'layouts/admin');
    }

    /**
     * Update an existing tag.
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth("/admin/tags/{$id}/edit");

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_errors'] = ['csrf' => 'Invalid or expired session token. Please try again.'];
            header("Location: /admin/tags/{$id}/edit");
            exit;
        }

        $tag = TagModel::getById($id);
        if ($tag === null) {
            $_SESSION['flash_error'] = 'Tag not found.';
            header('Location: /admin/tags');
            exit;
        }

        $errors = [];
        $imagePath = $tag['image_path'];
        $imageAlt = trim((string) ($_POST['image_alt'] ?? $tag['image_alt'] ?? ''));

        // Handle image removal
        if (!empty($_POST['remove_image'])) {
            $imagePath = null;
            $imageAlt = null;
        }

        // Handle image upload
        if (isset($_FILES['image']) && (int) $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadRes = ImageUploader::upload($_FILES['image']);
                $imagePath = $uploadRes['location'];
            } catch (Throwable $e) {
                $errors['image'] = $e->getMessage();
            }
        }

        $data = [
            'name'          => trim((string) ($_POST['name'] ?? '')),
            'slug'          => trim((string) ($_POST['slug'] ?? '')),
            'description'   => trim((string) ($_POST['description'] ?? '')),
            'image_path'    => $imagePath,
            'image_alt'     => $imageAlt !== '' ? $imageAlt : null,
            'show_as_topic' => !empty($_POST['show_as_topic']) ? 1 : 0,
            'show_as_cause' => !empty($_POST['show_as_cause']) ? 1 : 0,
            'sort_order'    => (int) ($_POST['sort_order'] ?? 0),
        ];

        if (!empty($errors)) {
            $_SESSION['flash_errors'] = $errors;
            $_SESSION['flash_old'] = $_POST;
            header("Location: /admin/tags/{$id}/edit");
            exit;
        }

        try {
            TagModel::update($id, $data);
            $_SESSION['flash_success'] = "Tag '{$data['name']}' updated successfully.";
            header('Location: /admin/tags');
            exit;
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_errors'] = ['general' => $e->getMessage()];
            $_SESSION['flash_old'] = $_POST;
            header("Location: /admin/tags/{$id}/edit");
            exit;
        } catch (Throwable $e) {
            $_SESSION['flash_errors'] = ['general' => 'Failed to update tag: ' . $e->getMessage()];
            $_SESSION['flash_old'] = $_POST;
            header("Location: /admin/tags/{$id}/edit");
            exit;
        }
    }

    /**
     * Create a new tag from admin.
     */
    public function store(): void
    {
        Auth::requireAuth('/admin/tags');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_errors'] = ['csrf' => 'Invalid or expired session token. Please try again.'];
            header('Location: /admin/tags');
            exit;
        }

        $imagePath = null;
        $imageAlt = trim((string) ($_POST['image_alt'] ?? ''));

        if (isset($_FILES['image']) && (int) $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadRes = ImageUploader::upload($_FILES['image']);
                $imagePath = $uploadRes['location'];
            } catch (Throwable $e) {
                $_SESSION['flash_errors'] = ['image' => $e->getMessage()];
                $_SESSION['flash_old'] = $_POST;
                header('Location: /admin/tags');
                exit;
            }
        }

        $data = [
            'name'          => trim((string) ($_POST['name'] ?? '')),
            'slug'          => trim((string) ($_POST['slug'] ?? '')),
            'description'   => trim((string) ($_POST['description'] ?? '')),
            'image_path'    => $imagePath,
            'image_alt'     => $imageAlt !== '' ? $imageAlt : null,
            'show_as_topic' => !empty($_POST['show_as_topic']) ? 1 : 0,
            'show_as_cause' => !empty($_POST['show_as_cause']) ? 1 : 0,
            'sort_order'    => (int) ($_POST['sort_order'] ?? 0),
        ];

        try {
            TagModel::create($data);
            $_SESSION['flash_success'] = "Tag '{$data['name']}' created successfully.";
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_errors'] = ['general' => $e->getMessage()];
            $_SESSION['flash_old'] = $_POST;
        } catch (Throwable $e) {
            $_SESSION['flash_errors'] = ['general' => 'Failed to create tag: ' . $e->getMessage()];
            $_SESSION['flash_old'] = $_POST;
        }

        header('Location: /admin/tags');
        exit;
    }

    /**
     * Delete a tag (removes tag links only, never posts).
     */
    public function delete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/tags');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_errors'] = ['csrf' => 'Invalid or expired session token. Please try again.'];
            header('Location: /admin/tags');
            exit;
        }

        $tag = TagModel::getById($id);
        if ($tag === null) {
            $_SESSION['flash_error'] = 'Tag not found.';
            header('Location: /admin/tags');
            exit;
        }

        TagModel::delete($id);
        $_SESSION['flash_success'] = "Tag '{$tag['name']}' deleted successfully. Posts remain unchanged.";
        header('Location: /admin/tags');
        exit;
    }
}
