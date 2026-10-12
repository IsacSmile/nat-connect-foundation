<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\ImageUploader;
use App\Core\View;
use App\Models\CategoryModel;
use App\Models\PostModel;
use App\Models\TagModel;
use InvalidArgumentException;
use Throwable;

/**
 * NatConnect Foundation — Editorial Post Controller
 */
class PostController
{
    /**
     * List posts with status, category, and search filters and pagination.
     */
    public function index(): void
    {
        Auth::requireAuth('/admin/posts');

        $status     = trim((string) ($_GET['status'] ?? 'all'));
        $categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
        $search     = trim((string) ($_GET['search'] ?? ''));
        $page       = max(1, (int) ($_GET['page'] ?? 1));

        $filters = [
            'status'      => $status !== 'all' ? $status : null,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'search'      => $search !== '' ? $search : null,
        ];

        $paginated = PostModel::getPaginated($filters, $page, 20);
        $categories = CategoryModel::getAll();
        $statusCounts = PostModel::getStatusCounts();

        View::render('admin/posts/index', [
            'pageTitle'       => 'Posts — NatConnect Foundation',
            'activeNav'       => 'posts',
            'posts'           => $paginated['items'],
            'total'           => $paginated['total'],
            'page'            => $paginated['page'],
            'perPage'         => $paginated['per_page'],
            'totalPages'      => $paginated['total_pages'],
            'categories'      => $categories,
            'currentStatus'   => $status,
            'currentCategory' => $categoryId,
            'currentSearch'   => $search,
            'statusCounts'    => $statusCounts,
        ], 'layouts/admin');
    }

    /**
     * Display new post editor.
     */
    public function create(): void
    {
        Auth::requireAuth('/admin/posts/new');

        $user = Auth::user();
        $defaultAuthor = $user['name'] ?? 'NatConnect Foundation';

        $categories = CategoryModel::getAll();
        $tagSuggestions = TagModel::getSuggestions();

        $errors = $_SESSION['flash_errors'] ?? [];
        $old = $_SESSION['flash_old'] ?? [];
        unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

        View::render('admin/posts/form', [
            'pageTitle'      => 'New Post — NatConnect Foundation',
            'activeNav'      => 'new-post',
            'isEdit'         => false,
            'post'           => null,
            'defaultAuthor'  => $defaultAuthor,
            'categories'     => $categories,
            'tagSuggestions' => $tagSuggestions,
            'errors'         => $errors,
            'old'            => $old,
        ], 'layouts/admin');
    }

    /**
     * Store a newly created post.
     */
    public function store(): void
    {
        Auth::requireAuth('/admin/posts/new');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_errors'] = ['csrf' => 'Invalid or expired session token. Please try again.'];
            $_SESSION['flash_old'] = $_POST;
            header('Location: /admin/posts/new');
            exit;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'draft'));
        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));

        $errors = [];
        if ($title === '') {
            $errors['title'] = 'Post title is required.';
        }
        if ($categoryId <= 0) {
            $errors['category_id'] = 'Please select a category.';
        }

        $now = date('Y-m-d H:i:s');
        if ($status === 'scheduled') {
            if ($publishedAt === '') {
                $errors['published_at'] = 'A future publication date and time is required for scheduled posts.';
            } elseif (strtotime($publishedAt) <= strtotime($now)) {
                $errors['published_at'] = 'Scheduled publication time must be in the future.';
            }
        }

        // Cover media validation
        $coverType = trim((string) ($_POST['cover_type'] ?? 'image'));
        if ($coverType === 'image') {
            $coverImage = trim((string) ($_POST['cover_image'] ?? ''));
            $coverAlt = trim((string) ($_POST['cover_alt'] ?? ''));
            if ($coverImage !== '') {
                if ($coverAlt === '') {
                    $errors['cover_alt'] = "Please describe what's in the photo.";
                } elseif (preg_match('/\.(jpe?g|png|webp|gif|svg|avif|bmp|tiff)$/i', $coverAlt) || (!str_contains($coverAlt, ' ') && mb_strlen($coverAlt) < 10)) {
                    $errors['cover_alt'] = "Please describe what's in the photo instead of using a filename.";
                }
            }
        } elseif ($coverType === 'video') {
            $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
            if ($videoUrl !== '' && youtubeId($videoUrl) === null) {
                $errors['video_url'] = 'Please enter a valid YouTube video URL (watch, youtu.be, or shorts) or 11-character video ID.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['flash_errors'] = $errors;
            $_SESSION['flash_old'] = $_POST;
            header('Location: /admin/posts/new');
            exit;
        }

        try {
            $postId = PostModel::create($_POST);
            $_SESSION['flash_success'] = 'Post created successfully.';
            if (!empty($_POST['redirect_to_preview'])) {
                header("Location: /admin/posts/{$postId}/preview");
                exit;
            }
            header("Location: /admin/posts/{$postId}/edit");
            exit;
        } catch (Throwable $e) {
            $_SESSION['flash_errors'] = ['general' => $e->getMessage()];
            $_SESSION['flash_old'] = $_POST;
            header('Location: /admin/posts/new');
            exit;
        }
    }

    /**
     * Display edit form for existing post.
     */
    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth("/admin/posts/{$id}/edit");

        $post = PostModel::getById($id);
        if ($post === null) {
            $_SESSION['flash_error'] = 'Post not found.';
            header('Location: /admin/posts');
            exit;
        }

        $categories = CategoryModel::getAll();
        $tagSuggestions = TagModel::getSuggestions();

        $errors = $_SESSION['flash_errors'] ?? [];
        $old = $_SESSION['flash_old'] ?? [];
        unset($_SESSION['flash_errors'], $_SESSION['flash_old']);

        View::render('admin/posts/form', [
            'pageTitle'      => "Edit Post #{$id} — NatConnect Foundation",
            'activeNav'      => 'posts',
            'isEdit'         => true,
            'post'           => $post,
            'defaultAuthor'  => $post['author_name'] ?? 'NatConnect Foundation',
            'categories'     => $categories,
            'tagSuggestions' => $tagSuggestions,
            'errors'         => $errors,
            'old'            => $old,
        ], 'layouts/admin');
    }

    /**
     * Update an existing post.
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth("/admin/posts/{$id}/edit");

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_errors'] = ['csrf' => 'Invalid or expired session token. Please try again.'];
            $_SESSION['flash_old'] = $_POST;
            header("Location: /admin/posts/{$id}/edit");
            exit;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'draft'));
        $publishedAt = trim((string) ($_POST['published_at'] ?? ''));

        $errors = [];
        if ($title === '') {
            $errors['title'] = 'Post title is required.';
        }
        if ($categoryId <= 0) {
            $errors['category_id'] = 'Please select a category.';
        }

        $now = date('Y-m-d H:i:s');
        if ($status === 'scheduled') {
            if ($publishedAt === '') {
                $errors['published_at'] = 'A future publication date and time is required for scheduled posts.';
            } elseif (strtotime($publishedAt) <= strtotime($now)) {
                $errors['published_at'] = 'Scheduled publication time must be in the future.';
            }
        }

        // Cover media validation
        $coverType = trim((string) ($_POST['cover_type'] ?? 'image'));
        if ($coverType === 'image') {
            $coverImage = trim((string) ($_POST['cover_image'] ?? ''));
            $coverAlt = trim((string) ($_POST['cover_alt'] ?? ''));
            if ($coverImage !== '') {
                if ($coverAlt === '') {
                    $errors['cover_alt'] = "Please describe what's in the photo.";
                } elseif (preg_match('/\.(jpe?g|png|webp|gif|svg|avif|bmp|tiff)$/i', $coverAlt) || (!str_contains($coverAlt, ' ') && mb_strlen($coverAlt) < 10)) {
                    $errors['cover_alt'] = "Please describe what's in the photo instead of using a filename.";
                }
            }
        } elseif ($coverType === 'video') {
            $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
            if ($videoUrl !== '' && youtubeId($videoUrl) === null) {
                $errors['video_url'] = 'Please enter a valid YouTube video URL (watch, youtu.be, or shorts) or 11-character video ID.';
            }
        }

        if (!empty($errors)) {
            $_SESSION['flash_errors'] = $errors;
            $_SESSION['flash_old'] = $_POST;
            header("Location: /admin/posts/{$id}/edit");
            exit;
        }

        try {
            PostModel::update($id, $_POST);
            $_SESSION['flash_success'] = 'Post updated successfully.';
            if (!empty($_POST['redirect_to_preview'])) {
                header("Location: /admin/posts/{$id}/preview");
                exit;
            }
        } catch (Throwable $e) {
            $_SESSION['flash_errors'] = ['general' => $e->getMessage()];
            $_SESSION['flash_old'] = $_POST;
        }

        header("Location: /admin/posts/{$id}/edit");
        exit;
    }

    /**
     * Preview post in public layout for any status (/admin/posts/{id}/preview).
     * Enforces admin authentication, renders fixed preview banner and noindex.
     */
    public function preview(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth("/admin/posts/{$id}/preview");

        $post = PostModel::getById($id);
        if ($post === null) {
            $_SESSION['flash_error'] = 'Post not found.';
            header('Location: /admin/posts');
            exit;
        }

        $tags = TagModel::getTagsForPost($id);
        $tagIds = array_column($tags, 'id');

        $relatedPosts = PostModel::getRelatedPosts($id, $post['category_id'] !== null ? (int) $post['category_id'] : null, $tagIds, 3);
        $popularPosts = PostModel::getPopularPosts($id, 3);
        $prevPost = PostModel::getPreviousPublishedPost($post['published_at'], $id);
        $nextPost = PostModel::getNextPublishedPost($post['published_at'], $id);

        $siteName = setting('site_name', 'NatConnect Foundation');
        $pageTitle = "[Preview] " . (!empty($post['meta_title']) ? $post['meta_title'] : "{$post['title']} — {$siteName}");
        $metaDescription = !empty($post['meta_description']) 
            ? $post['meta_description'] 
            : (!empty($post['excerpt']) ? $post['excerpt'] : excerpt($post['content'], 160));

        $canonicalUrl = url('/post/' . ($post['slug'] ?: 'preview'));

        if (!empty($post['cover_image'])) {
            $ogImage = ImageUploader::variantUrl($post['cover_image'], 1600);
            if (!str_starts_with($ogImage, 'http://') && !str_starts_with($ogImage, 'https://')) {
                $ogImage = url($ogImage);
            }
        } elseif (!empty($post['video_url']) && youtubeId($post['video_url'])) {
            $ogImage = 'https://img.youtube.com/vi/' . youtubeId($post['video_url']) . '/hqdefault.jpg';
        } else {
            $ogImage = asset('assets/img/logo.png');
            if (!str_starts_with($ogImage, 'http://') && !str_starts_with($ogImage, 'https://')) {
                $ogImage = url($ogImage);
            }
        }

        $pubTime = !empty($post['published_at']) ? date('c', strtotime((string) $post['published_at'])) : null;
        $modTime = !empty($post['updated_at']) ? date('c', strtotime((string) $post['updated_at'])) : $pubTime;

        $jsonLd = [
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => $post['title'],
            'image'            => [$ogImage],
            'datePublished'    => $pubTime,
            'dateModified'     => $modTime,
            'author'           => [
                '@type' => 'Person',
                'name'  => $post['author_name'] ?? $siteName,
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => $siteName,
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => url('/assets/img/logo.png'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $canonicalUrl,
            ],
        ];

        View::render('public/post', [
            'pageTitle'            => $pageTitle,
            'metaDescription'      => $metaDescription,
            'canonicalUrl'         => $canonicalUrl,
            'ogType'               => 'article',
            'ogTitle'              => $post['meta_title'] ?: $post['title'],
            'ogDescription'        => $metaDescription,
            'ogImage'              => $ogImage,
            'articlePublishedTime' => $pubTime,
            'articleModifiedTime'  => $modTime,
            'articleAuthor'        => $post['author_name'] ?? $siteName,
            'jsonLd'               => $jsonLd,
            'post'                 => $post,
            'tags'                 => $tags,
            'relatedPosts'         => $relatedPosts,
            'popularPosts'         => $popularPosts,
            'prevPost'             => $prevPost,
            'nextPost'             => $nextPost,
            'isPreview'            => true,
            'noindex'              => true,
        ], 'layouts/public');
    }

    /**
     * Delete a single post.
     */
    public function delete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        Auth::requireAuth('/admin/posts');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Invalid session token.';
            header('Location: /admin/posts');
            exit;
        }

        try {
            PostModel::delete($id);
            $_SESSION['flash_success'] = 'Post deleted successfully.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to delete post: ' . $e->getMessage();
        }

        header('Location: /admin/posts');
        exit;
    }

    /**
     * Bulk delete posts selected via checkboxes.
     */
    public function bulkDelete(): void
    {
        Auth::requireAuth('/admin/posts');

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Invalid session token.';
            header('Location: /admin/posts');
            exit;
        }

        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            $_SESSION['flash_error'] = 'No posts were selected for bulk deletion.';
            header('Location: /admin/posts');
            exit;
        }

        try {
            $count = PostModel::bulkDelete($ids);
            $_SESSION['flash_success'] = "{$count} posts deleted successfully.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Bulk delete failed: ' . $e->getMessage();
        }

        header('Location: /admin/posts');
        exit;
    }

    /**
     * Keepalive heartbeat endpoint called every 5 minutes while editor is open
     * to keep admin session alive without idling out.
     */
    public function keepalive(): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['status' => 'unauthorized']);
            exit;
        }

        $_SESSION['auth_last_activity'] = time();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'timestamp' => time()]);
        exit;
    }
}
