<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ImageUploader;
use App\Core\Turnstile;
use App\Core\View;
use App\Models\CategoryModel;
use App\Models\CommentModel;
use App\Models\EngagementModel;
use App\Models\GalleryModel;
use App\Models\PageModel;
use App\Models\PostModel;
use App\Models\RedirectModel;
use App\Models\SettingModel;
use App\Models\SubscriberModel;
use App\Models\TagModel;
use App\Models\UserModel;

class PublicController
{
    /**
     * Helper to obtain or set a persistent 32-character random visitor ID in cookie
     */
    public static function getOrCreateVisitorId(): string
    {
        $vid = (string) ($_COOKIE['nc_vid'] ?? '');
        if (!preg_match('/^[a-f0-9]{32}$/', $vid)) {
            $vid = bin2hex(random_bytes(16));
            $isSecure = Auth::isHttps();
            if (!headers_sent()) {
                setcookie('nc_vid', $vid, [
                    'expires'  => time() + (365 * 86400),
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
            $_COOKIE['nc_vid'] = $vid;
        }
        return $vid;
    }

    /**
     * Opportunistically prune stale like rate limits and login attempts older than 24h (1% of requests).
     */
    public static function opportunisticPruning(): void
    {
        if (mt_rand(1, 100) === 1) {
            EngagementModel::pruneOldLikeRateLimits(24);
            UserModel::purgeOldAttempts(24);
        }
    }

    /**
     * Single Post (/post/{slug})
     */
    public function post(array $params): void
    {
        $slug = trim((string) ($params['slug'] ?? ''));
        if ($slug === '') {
            self::notFound();
            return;
        }

        // Must enforce PostModel::sqlPublished()
        $post = PostModel::getBySlugPublished($slug);

        if (!$post) {
            // Check if there is an active 301 redirect for this legacy or modified slug
            $redirectTarget = RedirectModel::getTarget('/post/' . $slug);
            if ($redirectTarget) {
                header('Location: ' . $redirectTarget, true, 301);
                exit;
            }

            self::notFound();
            return;
        }

        $postId = (int) $post['id'];
        $tags = TagModel::getTagsForPost($postId);
        $tagIds = array_column($tags, 'id');

        $relatedPosts = PostModel::getRelatedPosts($postId, $post['category_id'] !== null ? (int) $post['category_id'] : null, $tagIds, 3);
        $popularPosts = PostModel::getPopularPosts($postId, 3);
        $prevPost = PostModel::getPreviousPublishedPost($post['published_at'], $postId);
        $nextPost = PostModel::getNextPublishedPost($post['published_at'], $postId);

        $siteName = setting('site_name', 'NatConnect Foundation');

        $pageTitle = !empty($post['meta_title']) ? $post['meta_title'] : "{$post['title']} — {$siteName}";
        $metaDescription = !empty($post['meta_description']) 
            ? $post['meta_description'] 
            : (!empty($post['excerpt']) ? $post['excerpt'] : excerpt($post['content'], 160));

        $canonicalUrl = url('/post/' . $post['slug']);

        // Determine OG image (cover 1600 or YouTube hqdefault or site logo)
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

        // Structured Data: Schema.org Article
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

        // Phase 5 Engagement State
        $visitorId = self::getOrCreateVisitorId();
        $userLiked = EngagementModel::hasUserLiked($postId, $visitorId);
        $comments = CommentModel::getApprovedForPost($postId);
        $commentCount = count($comments);
        $commentsAvailable = Turnstile::canAcceptComments();
        self::opportunisticPruning();

        // Signed timestamp for comment submission (min 3s check)
        $commentTs = time();
        $salt = (string) config('security.salt', 'salt');
        $commentSig = hash_hmac('sha256', (string) $commentTs, $salt);
        $turnstileSiteKey = (string) config('turnstile.site_key', '1x00000000000000000000AA');

        // Form validation feedback from session
        $commentErrors = $_SESSION['comment_errors'] ?? [];
        $commentForm = $_SESSION['comment_form'] ?? [];
        unset($_SESSION['comment_errors'], $_SESSION['comment_form']);

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
            'userLiked'            => $userLiked,
            'comments'             => $comments,
            'commentCount'         => $commentCount,
            'commentsAvailable'    => $commentsAvailable,
            'commentTs'            => $commentTs,
            'commentSig'           => $commentSig,
            'turnstileSiteKey'     => $turnstileSiteKey,
            'commentErrors'        => $commentErrors,
            'commentForm'          => $commentForm,
            'commentSuccess'       => getFlash('comment_success'),
            'isPreview'            => false,
            'noindex'              => false,
        ], 'layouts/public');
    }

    /**
     * JS View Beacon (/api/view/{id})
     */
    public function recordView(array $params): void
    {
        $postId = (int) ($params['id'] ?? $params['postId'] ?? 0);
        if ($postId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Invalid post ID']);
            return;
        }

        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        // Ignore bot user agents
        if ($ua === '' || preg_match('/(bot|crawl|spider|slurp|preview|headless|facebookexternalhit|whatsapp)/i', $ua)) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(['status' => 'ignored', 'reason' => 'bot']);
            return;
        }

        // Ignore preview requests
        if (!empty($_GET['preview']) || !empty($_POST['preview'])) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(['status' => 'ignored', 'reason' => 'preview']);
            return;
        }

        // Ignore logged-in admins
        if (Auth::check()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(['status' => 'ignored', 'reason' => 'admin']);
            return;
        }

        // Must be effectively published post
        $post = PostModel::getByIdPublished($postId);
        if (!$post) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(['status' => 'ignored', 'reason' => 'not_published']);
            return;
        }

        $ip = clientIp();
        $salt = (string) config('security.salt', 'salt');
        $visitorHash = hash('sha256', $ip . $ua . $salt);

        $recorded = EngagementModel::recordView($postId, $visitorHash);

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode(['success' => true, 'counted' => $recorded]);
    }

    /**
     * Like / Unlike Toggle (/api/like/{id})
     */
    public function toggleLike(array $params): void
    {
        $postId = (int) ($params['id'] ?? $params['postId'] ?? 0);
        if ($postId <= 0) {
            http_response_code(400);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(['error' => 'Invalid post ID']);
            return;
        }

        // Verify Origin / Referer matches site host (reject cross-site)
        $appUrlHost = (string) parse_url((string) config('app.url', 'http://127.0.0.1:8000'), PHP_URL_HOST);
        $currentHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $currentHostName = explode(':', $currentHost)[0];

        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');

        $sourceHost = '';
        if ($origin !== '') {
            $sourceHost = (string) parse_url($origin, PHP_URL_HOST);
        } elseif ($referer !== '') {
            $sourceHost = (string) parse_url($referer, PHP_URL_HOST);
        }

        if ($sourceHost === '' || ($sourceHost !== $appUrlHost && $sourceHost !== $currentHostName)) {
            @http_response_code(403);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(['error' => 'Forbidden: Cross-site request rejected']);
            return;
        }

        // Post must exist and be effectively published
        $post = PostModel::getByIdPublished($postId);
        if (!$post) {
            http_response_code(404);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(['error' => 'Post not found']);
            return;
        }

        $ip = clientIp();
        $salt = (string) config('security.salt', 'salt');
        $ipHash = hash('sha256', $ip . $salt);

        // Rate limit: 60 like actions per hour per IP hash
        if (!EngagementModel::checkLikeRateLimit($ipHash, 60, 1)) {
            http_response_code(429);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
            return;
        }

        EngagementModel::recordLikeAttempt($ipHash);

        $visitorHash = self::getOrCreateVisitorId();
        $result = EngagementModel::toggleLike($postId, $visitorHash);

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode([
            'liked' => $result['liked'],
            'count' => $result['count'],
        ]);
    }

    /**
     * Public Reader Comment Submission (/post/{slug}/comment)
     */
    public function submitComment(array $params): void
    {
        $slug = trim((string) ($params['slug'] ?? ''));
        $post = PostModel::getBySlugPublished($slug);
        if (!$post) {
            self::notFound();
            return;
        }

        $postId = (int) $post['id'];
        $redirectUrl = '/post/' . $slug . '#comments';

        self::opportunisticPruning();

        // Turnstile production protection: Never accept comments without a real key in production
        if (!Turnstile::canAcceptComments()) {
            $_SESSION['comment_errors'] = ['Comments are temporarily unavailable.'];
            if (!headers_sent()) {
                header('Location: ' . $redirectUrl);
            }
            if (PHP_SAPI !== 'cli') {
                exit;
            }
            return;
        }

        // Honeypot check: hidden with CSS, not type=hidden. If filled, pretend success and discard.
        $honeypot = trim((string) ($_POST['nc_hp_7'] ?? $_POST['website_url'] ?? $_POST['contact_fax'] ?? ''));
        if ($honeypot !== '') {
            setFlash('comment_success', 'Thanks! Your comment will appear after review.');
            if (!headers_sent()) {
                header('Location: ' . $redirectUrl);
            }
            if (PHP_SAPI !== 'cli') {
                exit;
            }
            return;
        }

        $errors = [];
        $salt = (string) config('security.salt', 'salt');
        $ip = clientIp();
        $ipHash = hash('sha256', $ip . $salt);

        // 1. Signed timestamp check (minimum 3 seconds between render and submit)
        $ts = (int) ($_POST['comment_ts'] ?? 0);
        $sig = (string) ($_POST['comment_sig'] ?? '');
        $expectedSig = hash_hmac('sha256', (string) $ts, $salt);

        if (!hash_equals($expectedSig, $sig) || (time() - $ts) < 3) {
            $errors['speed'] = 'Submission too fast. Please take a moment before submitting.';
        }

        // 2. Rate limit: max 3 comments per 10 minutes per IP hash
        if (CommentModel::countRecentByIpHash($ipHash, 10) >= 3) {
            $errors['rate'] = 'You have submitted too many comments. Please try again in 10 minutes.';
        }

        // 3. Cloudflare Turnstile verification
        $turnstileToken = $_POST['cf-turnstile-response'] ?? '';
        if (!Turnstile::verify($turnstileToken, $ip)) {
            $errors['turnstile'] = 'Security verification failed. Please try again.';
        }

        // 4. Form inputs validation
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $rawComment = (string) ($_POST['comment'] ?? '');
        // Plain text only: strip all HTML; store raw text, escape on output, convert newlines to <br>
        $cleanComment = trim(strip_tags($rawComment));

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen($name) > 80) {
            $errors['name'] = 'Name must be 80 characters or fewer.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (mb_strlen($cleanComment) < 3) {
            $errors['comment'] = 'Comment must be at least 3 characters.';
        } elseif (mb_strlen($cleanComment) > 2000) {
            $errors['comment'] = 'Comment must be 2000 characters or fewer.';
        }

        if (!empty($errors)) {
            $_SESSION['comment_form'] = [
                'name'    => $name,
                'email'   => $email,
                'comment' => $cleanComment,
            ];
            $_SESSION['comment_errors'] = $errors;
            if (!headers_sent()) {
                header('Location: ' . $redirectUrl);
            }
            if (PHP_SAPI !== 'cli') {
                exit;
            }
            return;
        }

        // Passed validation: All new comments are status 'pending'
        CommentModel::create([
            'post_id' => $postId,
            'name'    => $name,
            'email'   => $email,
            'body'    => $cleanComment,
            'status'  => 'pending',
            'ip_hash' => $ipHash,
        ]);

        setFlash('comment_success', 'Thanks! Your comment will appear after review.');
        if (!headers_sent()) {
            header('Location: ' . $redirectUrl);
        }
        if (PHP_SAPI !== 'cli') {
            exit;
        }
        return;
    }

    /**
     * Homepage (/)
     */
    public function home(): void
    {
        $shownIds = [];

        // 1. Announcement bar
        $showAnnouncement = setting('section_show_announcement', '0') === '1';
        $announcementText = trim((string) setting('announcement_text', ''));
        $announcementLink = trim((string) setting('announcement_link', ''));

        // 2. Hero Section: newest published post + editor's picks
        $showHero = setting('section_show_hero', '1') !== '0';
        $heroPost = $showHero ? PostModel::getFeaturedPublished() : null;
        $editorPicks = [];

        if ($heroPost) {
            $shownIds[] = (int) $heroPost['id'];
            $editorPicks = PostModel::getEditorsPicks(3, $shownIds);
            foreach ($editorPicks as $pick) {
                $shownIds[] = (int) $pick['id'];
            }
        }

        $heroHeadline = trim((string) setting('home_hero_headline', 'Championing Nature, Defending Tomorrow'));
        $heroIntro = trim((string) setting('home_hero_intro', 'NatConnect Foundation drives grassroots environmental action, RTI-backed wetland conservation, and coastal ecosystem protection across India.'));

        // 3. Explore by topic (tags with show_as_topic)
        $showTopics = setting('section_show_topics', '1') === '1';
        $topics = $showTopics ? TagModel::getTopics(30) : [];

        // 4. Latest stories (3 newest remaining published posts)
        $showLatest = setting('section_show_latest', '1') === '1';
        $latestPosts = [];
        if ($showLatest) {
            $latestPosts = PostModel::getLatestPublished(3, $shownIds);
            foreach ($latestPosts as $p) {
                $shownIds[] = (int) $p['id'];
            }
        }

        // 5. About band
        $siteName = setting('site_name', 'NatConnect Foundation');
        $siteTagline = setting('site_tagline', '');
        $missionStatement = trim((string) setting('mission_statement', ''));
        $footerAbout = trim((string) setting('footer_about', ''));

        // 6. Causes we care about (tags with show_as_cause)
        $showCauses = setting('section_show_causes', '1') === '1';
        $causes = $showCauses ? TagModel::getCauses(20) : [];

        // 7. Campaigns category posts
        $showCampaigns = setting('section_show_campaigns', '1') === '1';
        $campaignPosts = [];
        $campaignCategory = null;
        if ($showCampaigns) {
            $campaignCategory = CategoryModel::getBySlug('campaigns');
            if (!$campaignCategory) {
                $allCats = CategoryModel::getAll();
                foreach ($allCats as $c) {
                    if (str_contains(strtolower($c['slug']), 'campaign') || str_contains(strtolower($c['name']), 'campaign')) {
                        $campaignCategory = $c;
                        break;
                    }
                }
            }
            if ($campaignCategory) {
                $campaignPosts = PostModel::getPublishedForCategory((int) $campaignCategory['id'], 3, $shownIds);
                foreach ($campaignPosts as $p) {
                    $shownIds[] = (int) $p['id'];
                }
            }
        }

        // 8. Right to Information category posts
        $showRti = setting('section_show_rti', '1') === '1';
        $rtiPosts = [];
        $rtiCategory = null;
        if ($showRti) {
            $rtiCategory = CategoryModel::getBySlug('rti-applications') 
                ?? CategoryModel::getBySlug('right-to-information') 
                ?? CategoryModel::getBySlug('rti');
            if (!$rtiCategory) {
                $allCats = CategoryModel::getAll();
                foreach ($allCats as $c) {
                    if (str_contains(strtolower($c['slug']), 'rti') || str_contains(strtolower($c['name']), 'rti')) {
                        $rtiCategory = $c;
                        break;
                    }
                }
            }
            if ($rtiCategory) {
                $rtiPosts = PostModel::getPublishedForCategory((int) $rtiCategory['id'], 2, $shownIds);
                foreach ($rtiPosts as $p) {
                    $shownIds[] = (int) $p['id'];
                }
            }
        }

        // 9. From the gallery (3 latest gallery photos)
        $showGallery = setting('section_show_gallery', '1') === '1';
        $galleryPhotos = $showGallery ? GalleryModel::getLatest(3) : [];

        // 10. Stay informed (newsletter subscription)
        $showNewsletter = setting('section_show_newsletter', '1') === '1';

        // 11. Donate band (only if active)
        $showDonate = setting('section_show_donate', '1') === '1';
        $isDonateActive = SettingModel::isDonateActive();

        $metaDescription = !empty($footerAbout) ? $footerAbout : "{$siteName} — Environmental advocacy and conservation platform.";

        View::render('public/home', [
            'pageTitle'          => !empty($siteTagline) ? "{$siteName} — {$siteTagline}" : $siteName,
            'metaDescription'    => $metaDescription,
            'shownPostIds'       => $shownIds,
            // Section 1: Announcement
            'showAnnouncement'   => $showAnnouncement && ($announcementText !== ''),
            'announcementText'   => $announcementText,
            'announcementLink'   => $announcementLink,
            // Section 2: Hero
            'heroPost'           => $heroPost,
            'editorPicks'        => $editorPicks,
            'heroHeadline'       => $heroHeadline,
            'heroIntro'          => $heroIntro,
            // Section 3: Topics
            'showTopics'         => $showTopics && !empty($topics),
            'topics'             => $topics,
            'titleTopics'        => setting('section_title_topics', 'Explore by topic'),
            'subtitleTopics'     => setting('section_subtitle_topics', ''),
            // Section 4: Latest stories
            'showLatest'         => $showLatest && !empty($latestPosts),
            'latestPosts'        => $latestPosts,
            'titleLatest'        => setting('section_title_latest', 'Latest stories'),
            'subtitleLatest'     => setting('section_subtitle_latest', ''),
            // Section 5: About band
            'showAboutBand'      => ($missionStatement !== ''),
            'siteName'           => $siteName,
            'missionStatement'   => $missionStatement,
            'footerAbout'        => $footerAbout,
            // Section 6: Causes
            'showCauses'         => $showCauses && !empty($causes),
            'causes'             => $causes,
            'titleCauses'        => setting('section_title_causes', 'Causes we care about'),
            'subtitleCauses'     => setting('section_subtitle_causes', ''),
            // Section 7: Campaigns
            'showCampaigns'      => $showCampaigns && !empty($campaignPosts),
            'campaignPosts'      => $campaignPosts,
            'campaignCategory'   => $campaignCategory,
            'titleCampaigns'     => setting('section_title_campaigns', 'Campaigns'),
            'subtitleCampaigns'  => setting('section_subtitle_campaigns', ''),
            // Section 8: Right to Information
            'showRti'            => $showRti && !empty($rtiPosts),
            'rtiPosts'           => $rtiPosts,
            'rtiCategory'        => $rtiCategory,
            'titleRti'           => setting('section_title_rti', 'Right to Information'),
            'subtitleRti'        => setting('section_subtitle_rti', ''),
            'introRti'           => setting('section_intro_rti', 'Our ongoing investigations and RTI appeals uncovering wetland encroachments and environmental governance issues.'),
            // Section 9: Gallery
            'showGallery'        => $showGallery && !empty($galleryPhotos),
            'galleryPhotos'      => $galleryPhotos,
            'titleGallery'       => setting('section_title_gallery', 'From the gallery'),
            'subtitleGallery'    => setting('section_subtitle_gallery', ''),
            // Section 10: Stay informed
            'showNewsletter'     => $showNewsletter,
            'titleNewsletter'    => setting('section_title_newsletter', 'Stay informed'),
            'subtitleNewsletter' => setting('section_subtitle_newsletter', ''),
            'introNewsletter'    => setting('section_intro_newsletter', 'Grassroots investigations, campaign updates, and environmental governance research from NatConnect Foundation.'),
            // Section 11: Donate band
            'showDonate'         => $showDonate && $isDonateActive,
            'titleDonate'        => setting('section_title_donate', 'Support our work'),
            'textDonate'         => setting('section_intro_donate') ?: (setting('donate_intro') ?: setting('home_text_donate', '')),
        ], 'layouts/public');
    }

    /**
     * Category Archive (/category/{slug})
     */
    public function category(array $params): void
    {
        $slug = trim((string) ($params['slug'] ?? ''));
        $category = CategoryModel::getBySlug($slug);

        if (!$category) {
            self::notFound();
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $data = PostModel::getPaginatedByCategory((int) $category['id'], $page, 12);

        $siteName = setting('site_name', 'NatConnect Foundation');
        $catDesc = !empty($category['description'])
            ? $category['description']
            : "Browse {$category['name']} articles, RTI filings, and conservation updates.";

        View::render('public/category', [
            'pageTitle'       => "{$category['name']} — {$siteName}",
            'metaDescription' => $catDesc,
            'category'        => $category,
            'posts'           => $data['items'],
            'pagination'      => [
                'current' => $data['page'],
                'total'   => $data['total_pages'],
                'count'   => $data['total'],
                'baseUrl' => "/category/{$category['slug']}",
            ],
        ], 'layouts/public');
    }

    /**
     * Tag Archive (/tag/{slug})
     */
    public function tag(array $params): void
    {
        $slug = trim((string) ($params['slug'] ?? ''));
        $tag = TagModel::getBySlug($slug);

        if (!$tag) {
            self::notFound();
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $data = PostModel::getPaginatedByTag((int) $tag['id'], $page, 12);

        $siteName = setting('site_name', 'NatConnect Foundation');

        View::render('public/tag', [
            'pageTitle'       => "#{$tag['name']} — {$siteName}",
            'metaDescription' => "Environmental reports and updates tagged with #{$tag['name']}.",
            'tag'             => $tag,
            'posts'           => $data['items'],
            'pagination'      => [
                'current' => $data['page'],
                'total'   => $data['total_pages'],
                'count'   => $data['total'],
                'baseUrl' => "/tag/{$tag['slug']}",
            ],
        ], 'layouts/public');
    }

    /**
     * Search (/search?q=)
     */
    public function search(): void
    {
        $rawQuery = (string) ($_GET['q'] ?? '');
        $query = mb_substr(trim($rawQuery), 0, 100);
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $data = PostModel::searchPublished($query, $page, 12);
        $siteName = setting('site_name', 'NatConnect Foundation');

        $title = $query !== ''
            ? "Search results for \"{$query}\" — {$siteName}"
            : "Search — {$siteName}";

        View::render('public/search', [
            'pageTitle'       => $title,
            'metaDescription' => "Search conservation news, RTI reports, and wetland advocacy.",
            'query'           => $query,
            'posts'           => $data['items'],
            'pagination'      => [
                'current' => $data['page'],
                'total'   => $data['total_pages'],
                'count'   => $data['total'],
                'baseUrl' => "/search?q=" . urlencode($query),
            ],
        ], 'layouts/public');
    }

    /**
     * Render an editable page by slug (/about, /privacy, /{slug})
     */
    public function showPage(array $params = []): void
    {
        $slug = trim((string) ($params['slug'] ?? ''));
        if ($slug === '') {
            self::notFound();
            return;
        }

        self::opportunisticPruning();

        // 1. Look up page by slug
        $page = PageModel::findBySlug($slug);

        if (!$page) {
            // Check if there is an active 301 redirect for this legacy or modified slug
            $redirectTarget = RedirectModel::getTarget('/' . $slug);
            if ($redirectTarget) {
                header('Location: ' . $redirectTarget, true, 301);
                exit;
            }

            self::notFound();
            return;
        }

        $isAdmin = Auth::check();
        $isPreview = $isAdmin && (!empty($_GET['preview']) || $page['status'] === 'draft');

        // Drafts = 404 for non-admins
        if ($page['status'] === 'draft' && !$isAdmin) {
            self::notFound();
            return;
        }

        $siteName = setting('site_name', 'NatConnect Foundation');
        $pageTitle = !empty($page['meta_title']) ? $page['meta_title'] : "{$page['title']} — {$siteName}";
        if ($isPreview) {
            $pageTitle = "[Preview] " . $pageTitle;
        }
        $metaDescription = !empty($page['meta_description'])
            ? $page['meta_description']
            : snippet(strip_tags($page['content']), 160);

        View::render('public/page', [
            'pageTitle'       => $pageTitle,
            'metaDescription' => $metaDescription,
            'canonicalUrl'    => url('/' . $page['slug']),
            'page'            => $page,
            'isPreview'       => $isPreview,
            'noindex'         => ($page['status'] === 'draft'),
        ], 'layouts/public');
    }

    /**
     * About NatConnect (/about)
     */
    public function about(): void
    {
        $this->showPage(['slug' => 'about']);
    }

    /**
     * Contact Us (/contact)
     */
    public function contact(): void
    {
        $siteName = setting('site_name', 'NatConnect Foundation');
        $settings = SettingModel::getAll();

        View::render('public/contact', [
            'pageTitle'       => "Contact Us — {$siteName}",
            'metaDescription' => "Get in touch with NatConnect Foundation for environmental advocacy, media inquiries, or reporting ecological damage.",
            'settings'        => $settings,
        ], 'layouts/public');
    }

    /**
     * Privacy Policy (/privacy)
     */
    public function privacy(): void
    {
        $this->showPage(['slug' => 'privacy']);
    }

    /**
     * Donate (/donate)
     */
    public function donate(): void
    {
        if (!SettingModel::isDonateActive()) {
            self::notFound();
            return;
        }

        $siteName = setting('site_name', 'NatConnect Foundation');
        $settings = SettingModel::getAll();

        View::render('public/donate', [
            'pageTitle'       => "Support Our Cause — {$siteName}",
            'metaDescription' => "Support NatConnect Foundation's grassroots legal advocacy, RTI investigations, and environmental defense.",
            'settings'        => $settings,
        ], 'layouts/public');
    }

    /**
     * Photo Gallery (/gallery)
     */
    public function gallery(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $data = GalleryModel::getPaginated($page, 24);

        $siteName = setting('site_name', 'NatConnect Foundation');
        $pageTitle = "Photo Gallery — {$siteName}";
        $metaDescription = "Explore grassroots conservation photography, wetland biodiversity, and environmental advocacy documented by NatConnect Foundation.";

        // og:image is the first photo (1600w variant) or default logo
        $firstPhoto = $data['items'][0] ?? null;
        if ($firstPhoto && !empty($firstPhoto['image_path'])) {
            $ogImage = ImageUploader::variantUrl($firstPhoto['image_path'], 1600);
            if (!str_starts_with($ogImage, 'http://') && !str_starts_with($ogImage, 'https://')) {
                $ogImage = url($ogImage);
            }
        } else {
            $ogImage = asset('assets/img/logo.png');
            if (!str_starts_with($ogImage, 'http://') && !str_starts_with($ogImage, 'https://')) {
                $ogImage = url($ogImage);
            }
        }

        View::render('public/gallery', [
            'pageTitle'       => $pageTitle,
            'metaDescription' => $metaDescription,
            'ogImage'         => $ogImage,
            'galleryIntro'    => setting('gallery_intro', ''),
            'photos'          => $data['items'],
            'pagination'      => [
                'current' => $data['page'],
                'total'   => $data['total_pages'],
                'count'   => $data['total'],
                'baseUrl' => '/gallery',
            ],
        ], 'layouts/public');
    }

    /**
     * Styled 404 Not Found Page
     */
    public static function notFound(): void
    {
        @http_response_code(404);
        $siteName = setting('site_name', 'NatConnect Foundation');
        $latestPosts = PostModel::getLatestPublishedPosts(3);

        View::render('public/404', [
            'pageTitle'       => "Page Not Found (404) — {$siteName}",
            'metaDescription' => "The page you requested could not be located.",
            'latestPosts'     => $latestPosts,
        ], 'layouts/public');
        if (PHP_SAPI !== 'cli') {
            exit;
        }
    }

    /**
     * Styled 500 Server Error Page
     */
    public static function serverError(): void
    {
        http_response_code(500);
        $siteName = setting('site_name', 'NatConnect Foundation');

        View::render('public/500', [
            'pageTitle'       => "Server Error (500) — {$siteName}",
            'metaDescription' => "A server error occurred. Please try again later.",
        ], 'layouts/public');
        exit;
    }

    /**
     * Get absolute canonical site URL with HTTPS scheme from configuration.
     */
    public static function getCanonicalSiteUrl(): string
    {
        $url = (string) (config('url') ?: config('app.url') ?: 'https://natconnectfoundation.com');
        $url = rtrim($url, '/');
        if (str_starts_with($url, 'http://')) {
            $url = 'https://' . substr($url, 7);
        } elseif (!str_starts_with($url, 'https://')) {
            $url = 'https://' . ltrim($url, '/');
        }
        return $url;
    }

    /**
     * Dynamic XML Sitemap (/sitemap.xml)
     */
    public function sitemapXml(): void
    {
        $siteUrl = self::getCanonicalSiteUrl();
        $urls = [];

        // 1. Home
        $urls[] = [
            'loc'     => "{$siteUrl}/",
            'lastmod' => date('Y-m-d'),
        ];

        // 2. Published Posts (lastmod = updated_at)
        $sqlPub = PostModel::sqlPublished('p');
        $stmt = \App\Core\Database::pdo()->query("
            SELECT slug, updated_at, published_at 
            FROM posts p 
            WHERE {$sqlPub} 
            ORDER BY published_at DESC
        ");
        while ($post = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $ts = $post['updated_at'] ?: $post['published_at'];
            $lastmod = !empty($ts) ? date('Y-m-d', strtotime($ts)) : null;
            $urls[] = [
                'loc'     => "{$siteUrl}/post/{$post['slug']}",
                'lastmod' => $lastmod,
            ];
        }

        // 3. Categories
        $stmt = \App\Core\Database::pdo()->query("SELECT slug, created_at FROM categories ORDER BY name ASC");
        while ($cat = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $lastmod = !empty($cat['created_at']) ? date('Y-m-d', strtotime($cat['created_at'])) : null;
            $urls[] = [
                'loc'     => "{$siteUrl}/category/{$cat['slug']}",
                'lastmod' => $lastmod,
            ];
        }

        // 4. Tags with posts
        $stmt = \App\Core\Database::pdo()->query("
            SELECT DISTINCT t.slug 
            FROM tags t 
            JOIN post_tags pt ON pt.tag_id = t.id 
            JOIN posts p ON p.id = pt.post_id 
            WHERE {$sqlPub} 
            ORDER BY t.name ASC
        ");
        while ($tag = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $urls[] = [
                'loc'     => "{$siteUrl}/tag/{$tag['slug']}",
                'lastmod' => null,
            ];
        }

        // 5. Published Pages
        $stmt = \App\Core\Database::pdo()->query("SELECT slug, updated_at FROM pages WHERE status = 'published' ORDER BY id ASC");
        while ($page = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $lastmod = !empty($page['updated_at']) ? date('Y-m-d', strtotime($page['updated_at'])) : null;
            $urls[] = [
                'loc'     => "{$siteUrl}/{$page['slug']}",
                'lastmod' => $lastmod,
            ];
        }

        // 6. Stories Archive
        $urls[] = [
            'loc'     => "{$siteUrl}/stories",
            'lastmod' => date('Y-m-d'),
        ];

        // 7. Gallery
        $urls[] = [
            'loc'     => "{$siteUrl}/gallery",
            'lastmod' => null,
        ];

        // 8. Contact
        $urls[] = [
            'loc'     => "{$siteUrl}/contact",
            'lastmod' => null,
        ];

        // 8. Donate (if active)
        if (SettingModel::isDonateActive()) {
            $urls[] = [
                'loc'     => "{$siteUrl}/donate",
                'lastmod' => null,
            ];
        }

        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
        }
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls as $u) {
            echo "  <url>\n";
            echo "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if (!empty($u['lastmod'])) {
                echo "    <lastmod>" . htmlspecialchars($u['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
            }
            echo "  </url>\n";
        }
        echo "</urlset>\n";
    }

    /**
     * Dynamic robots.txt
     */
    public function robotsTxt(): void
    {
        $siteUrl = self::getCanonicalSiteUrl();

        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo "User-agent: *\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /setup.php\n\n";
        echo "Sitemap: {$siteUrl}/sitemap.xml\n";
    }

    /**
     * Dynamic RSS 2.0 Feed (/feed)
     */
    public function rssFeed(): void
    {
        $siteUrl = self::getCanonicalSiteUrl();
        $siteName = (string) (setting('site_name') ?: config('app.name', 'NatConnect Foundation'));
        $siteTagline = (string) (setting('site_tagline') ?: config('app.tagline', 'Nature, Everything'));

        $sqlPub = PostModel::sqlPublished('p');
        $stmt = \App\Core\Database::pdo()->query("
            SELECT p.*, c.name AS category_name 
            FROM posts p 
            LEFT JOIN categories c ON c.id = p.category_id 
            WHERE {$sqlPub} 
            ORDER BY p.published_at DESC 
            LIMIT 20
        ");
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!headers_sent()) {
            header('Content-Type: application/rss+xml; charset=utf-8');
        }
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
        echo "  <channel>\n";
        echo "    <title>" . htmlspecialchars($siteName, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</title>\n";
        echo "    <link>" . htmlspecialchars($siteUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</link>\n";
        echo "    <description>" . htmlspecialchars($siteTagline, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</description>\n";
        echo "    <language>en</language>\n";
        echo "    <lastBuildDate>" . date(DATE_RFC2822) . "</lastBuildDate>\n";
        echo "    <atom:link href=\"" . htmlspecialchars($siteUrl . '/feed', ENT_XML1 | ENT_QUOTES, 'UTF-8') . "\" rel=\"self\" type=\"application/rss+xml\" />\n";

        foreach ($posts as $post) {
            $postLink = "{$siteUrl}/post/{$post['slug']}";
            $pubDate = !empty($post['published_at']) ? date(DATE_RFC2822, strtotime($post['published_at'])) : date(DATE_RFC2822);
            $descText = excerpt($post['excerpt'] ?: $post['content'], 250);

            echo "    <item>\n";
            echo "      <title>" . htmlspecialchars($post['title'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</title>\n";
            echo "      <link>" . htmlspecialchars($postLink, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</link>\n";
            echo "      <guid isPermaLink=\"true\">" . htmlspecialchars($postLink, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</guid>\n";
            echo "      <pubDate>" . $pubDate . "</pubDate>\n";
            echo "      <description>" . htmlspecialchars($descText, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</description>\n";

            if (!empty($post['category_name'])) {
                echo "      <category>" . htmlspecialchars($post['category_name'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</category>\n";
            }

            if (!empty($post['cover_image'])) {
                $imgRel = ImageUploader::getWebpUrl($post['cover_image'], 1600);
                $imgUrl = $siteUrl . $imgRel;
                $publicRoot = dirname(__DIR__, 2) . '/public_html';
                $diskFile = $publicRoot . $imgRel;
                $length = file_exists($diskFile) ? (int) filesize($diskFile) : 0;
                $mime = str_ends_with($imgRel, '.webp') ? 'image/webp' : 'image/jpeg';
                echo "      <enclosure url=\"" . htmlspecialchars($imgUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "\" length=\"{$length}\" type=\"{$mime}\" />\n";
            }

            echo "    </item>\n";
        }

        echo "  </channel>\n";
        echo "</rss>\n";
    }

    /**
     * All Stories Archive (/stories).
     * Displays all published posts (PostModel::sqlPublished()), newest first, 12 per page,
     * with category filter chips at the top.
     */
    public function stories(): void
    {
        $categorySlug = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $categories = CategoryModel::getAll();
        $selectedCategory = null;
        if ($categorySlug !== null && $categorySlug !== '') {
            $selectedCategory = CategoryModel::getBySlug($categorySlug);
        }

        $pdo = \App\Core\Database::pdo();
        $sqlPub = PostModel::sqlPublished('p');

        $whereConditions = [$sqlPub];
        $params = [];

        if ($selectedCategory !== null) {
            $whereConditions[] = "p.category_id = :category_id";
            $params['category_id'] = (int) $selectedCategory['id'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Count total matching published posts
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE {$whereClause}");
        $countStmt->execute($params);
        $totalPosts = (int) $countStmt->fetchColumn();

        // Query published posts
        $querySql = "
            SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM posts p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE {$whereClause}
            ORDER BY COALESCE(p.published_at, p.created_at) DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($querySql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalPages = max(1, (int) ceil($totalPosts / $perPage));

        $pageTitle = $selectedCategory
            ? "{$selectedCategory['name']} Stories — NatConnect Foundation"
            : "All Stories — NatConnect Foundation";
        $metaDescription = $selectedCategory && !empty($selectedCategory['description'])
            ? excerpt($selectedCategory['description'], 160)
            : "Explore our complete archive of published environmental dispatches, RTI investigations, and conservation stories.";

        View::render('public/stories', [
            'pageTitle'        => $pageTitle,
            'metaDescription'  => $metaDescription,
            'posts'            => $posts,
            'categories'       => $categories,
            'selectedCategory' => $selectedCategory,
            'currentPage'      => $page,
            'totalPages'       => $totalPages,
            'totalPosts'       => $totalPosts,
        ], 'layouts/public');
    }

    /**
     * Handle public newsletter subscription.
     * Enforces:
     * - Valid email, lowercase, max 254
     * - Mandatory explicit consent checkbox
     * - Honeypot rejection
     * - 3-second signed timestamp (sub_ts, sub_sig)
     * - Rate limit max 5 submissions per hour per IP hash
     * - Anti-enumeration: Identical response for new and duplicate emails
     * - Dual response: JSON for Alpine/AJAX, redirect back with flash message for plain HTML
     */
    public function subscribe(): void
    {
        $salt = (string) config('security.salt', 'salt');
        $ip = clientIp();
        $ipHash = hash('sha256', $ip . $salt);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

        $respond = function (int $statusCode, bool $success, string $message) use ($isAjax): void {
            if ($isAjax) {
                if (!headers_sent()) {
                    http_response_code($statusCode);
                    header('Content-Type: application/json; charset=UTF-8');
                }
                echo json_encode([
                    'status_code' => $statusCode,
                    'success'     => $success,
                    $success ? 'message' : 'error' => $message,
                ]);
                if (PHP_SAPI !== 'cli') {
                    exit;
                }
                return;
            }

            if ($success) {
                $_SESSION['flash_sub_success'] = $message;
            } else {
                $_SESSION['flash_sub_error'] = $message;
            }

            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            if (!headers_sent()) {
                header('Location: ' . $referer);
            }
            if (PHP_SAPI !== 'cli') {
                exit;
            }
            return;
        };

        // 1. Honeypot check
        $honeypot = trim((string) ($_POST['sub_hp_7'] ?? ($_POST['website'] ?? '')));
        if ($honeypot !== '') {
            $respond(422, false, 'Spam submission rejected.');
            return;
        }

        // 2. Signed timestamp check (minimum 3 seconds between render and submit)
        $ts = (int) ($_POST['sub_ts'] ?? 0);
        $sig = (string) ($_POST['sub_sig'] ?? '');
        $expectedSig = hash_hmac('sha256', (string) $ts, $salt);

        if (!hash_equals($expectedSig, $sig) || (time() - $ts) < 3) {
            $respond(422, false, 'Submission too fast. Please take a moment before submitting.');
            return;
        }

        // 3. Rate limit check (max 5 submissions per hour per IP hash)
        if (!SubscriberModel::checkRateLimit($ipHash, 5)) {
            $respond(429, false, 'Too many subscription attempts. Please try again in an hour.');
            return;
        }

        // 4. Consent check (required)
        $consent = !empty($_POST['consent']);
        if (!$consent) {
            $respond(422, false, 'Please confirm your consent to receive updates.');
            return;
        }

        // 5. Email check
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if ($email === '' || mb_strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $respond(422, false, 'Please enter a valid email address.');
            return;
        }

        // 6. Record subscriber
        $source = trim((string) ($_POST['source'] ?? 'website'));
        try {
            $result = SubscriberModel::subscribe($email, true, $ipHash, $source);
            $respond(200, true, $result['message']);
            return;
        } catch (\Throwable $e) {
            $respond(422, false, $e->getMessage());
            return;
        }
    }
}
