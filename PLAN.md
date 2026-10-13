# NatConnect Foundation — Blog & CMS Architecture Plan

## 1. Project Summary
**NatConnect Foundation** is an environmental advocacy NGO in India focused on conservation, wetland protection, RTI activism, and environmental awareness. This custom blog and CMS platform delivers a lightweight, lightning-fast, and accessible web presence with robust editorial tooling, media handling, and archive management.

---

## 2. Technical & Hosting Constraints
- **Hosting Environment**: Hostinger Shared Hosting.
  - **No SSH access** on the target server.
  - **No Composer** on the server.
  - **No Node.js / NPM** on the server.
  - **Deployment Mechanism**: File upload via Hostinger File Manager or FTP/SFTP.
  - **Hostinger Server Facts (Verified on Staging, Apache Backend)**:
| Directive / Feature | Status on Hostinger | Behavior & Implementation Protocol |
|---|---|---|
| `.htaccess` Support | **Requires Activation** | May be disabled by default on PHP/HTML sites (served by LiteSpeed/hcdn without `.htaccess`). Must ask support / Agent to "enable .htaccess support / Apache backend". Verify with test: `RewriteRule [F]` must return 403. |
| `Options` (`-Indexes`, `-ExecCGI`, `+FollowSymLinks`) | **FORBIDDEN (HTTP 500)** | `AllowOverride` does not include `Options`. Any `Options` directive causes HTTP 500. Directory listing is prevented via empty `index.html` files (in `/uploads/` and all `uploads/YYYY/MM/` subdirectories). |
| `mod_headers` (`Header set ...`) | **NOT AVAILABLE** | Unwrapped `Header` directive triggers HTTP 500. Inside `<IfModule mod_headers.c>`, it is silently skipped. **All security headers are sent via PHP in `public_html/index.php`** before any output. `X-XSS-Protection` is removed everywhere. |
| `php_flag` / `php_value` | **FORBIDDEN** | Never write in `.htaccess` (can trigger HTTP 500 or be ignored). Configure limits via PHP code or hPanel PHP configuration. |
| `mod_rewrite` (`RewriteRule`, `RewriteCond`) | **WORKING (Verified)** | `RewriteRule` with `[F]` and front-controller routing (`!-f`, `!-d` -> `index.php`) work properly. |
| `<FilesMatch>` + `Require all denied` | **WORKING (Verified)** | Works correctly for denying script execution in `/uploads/` and blocking sensitive files (with `<IfModule !mod_authz_core.c>` fallback). |
| Static Asset Caching (`mod_expires`) | **UNVERIFIED (Harmless)** | Wrapped in `<IfModule mod_expires.c>`. Static asset caching relies on mod_expires (unverified) and the Hostinger CDN; verify on staging with `curl -I` on a CSS file after the first deploy. |
| Pre-Launch Staging Gate | **MANDATORY** | Before launch, upload the real `public_html/.htaccess` to staging and confirm no 500. |
- **Language & Runtime**: PHP 8.2+ without heavy frameworks.
  - **Hostinger PHP Configuration**: In Hostinger hPanel → PHP Configuration, set `upload_max_filesize=10M`, `post_max_size=12M`, and `memory_limit=256M`.
  - **Local Development Server**: Run the site locally only with `./bin/serve.sh` (matches Hostinger limits: `php -d upload_max_filesize=10M -d post_max_size=12M -d memory_limit=256M -S 127.0.0.1:8000 -t public_html`).
- **Database**: MySQL 8.x / MariaDB accessed strictly through **PDO** with prepared statements.
- **CSS Architecture**: **Tailwind CSS v3 (specifically v3.4.17 standalone CLI)**. All current and future development must strictly use Tailwind v3 syntax and `tailwind.config.js`. Do not use v4 `@theme` block directives. Only the compiled production CSS (`/public_html/assets/css/app.css`) is deployed.
- **Client-Side Interactivity**: **Alpine.js** self-hosted locally (`/public_html/assets/js/alpine.min.js`), no CDN dependencies.
- **Third-Party PHP Libraries**: Any external packages (e.g., HTMLPurifier) are installed locally via Composer and deployed via the uploaded `/vendor` directory.
- **Accessibility & UX**: Mobile-first responsive layout, semantic HTML5, visible focus indicators, alt text on all non-decorative imagery, WCAG AA color contrast (minimum `text-sm`, `text-ink/70` or darker for footer links and copy).

---

## 3. Directory Structure
```text
nat-connect-foundation/
├── app/
│   ├── config/
│   │   ├── config.php            # Core config loader (reads config.local.php)
│   │   ├── config.sample.php     # Template configuration file
│   │   └── config.local.php      # Local & production environment values (gitignored)
│   ├── controllers/              # Application controllers (Admin & Public)
│   ├── core/
│   │   ├── Auth.php              # Authentication & session helpers
│   │   ├── Csrf.php              # Anti-CSRF token lifecycle
│   │   ├── Database.php          # PDO singleton connection wrapper
│   │   ├── Router.php            # URI router with parameter parsing & 301 redirects
│   │   ├── View.php              # View rendering engine with layout management
│   │   └── helpers.php           # Core sanitization, URL, formatting helpers
│   ├── models/                   # Data access layer (Post, Category, Tag, Comment, etc.)
│   ├── storage/                  # Server-local runtime state (NEVER deployed or overwritten)
│   │   ├── installed.lock        # Created upon first-time admin setup (locks setup.php)
│   │   ├── cache/                # App cache directories (.gitkeep preserved)
│   │   │   └── htmlpurifier/     # HTMLPurifier Cache.SerializerPath (never in vendor/)
│   │   └── logs/                 # Daily / error logs (app/storage/logs/error.log)
│   └── views/
│       ├── admin/                # Admin panels & editorial views
│       ├── layouts/
│       │   ├── admin.php         # Admin shell layout
│       │   └── public.php        # Public shell layout
│       ├── partials/
│       │   ├── footer.php        # Global footer
│       │   └── header.php        # Navigation header with Alpine.js mobile drawer
│       └── public/               # Public page views (Home, Single Post, Archives, etc.)
├── bin/
│   ├── migrate.php               # Database migrations runner CLI
│   ├── seed.php                  # Database seed runner CLI
│   └── serve.sh                  # Local server script matching Hostinger upload limits
├── database/
│   ├── migrations/               # Numbered sequential migrations (001, 002, ...)
│   │   ├── 001_initial_schema.sql
│   │   └── 002_login_attempts_account_hash.sql
│   ├── schema.sql                # Archival reference schema (deprecated; use migrations)
│   └── seed.sql                  # Initial seed categories & placeholder settings (NO credentials)
├── public_html/                  # Document root exposed to the web
│   ├── .htaccess                 # Security headers, URL rewriting, cache-control, /uploads deny
│   ├── index.php                 # Front controller
│   ├── setup.php                 # First-time admin setup (returns 404 once locked; DELETE after use)
│   ├── assets/
│   │   ├── css/                  # Compiled app.css (Tailwind v3.4.17)
│   │   ├── fonts/                # Self-hosted woff2 fonts (Fraunces, Inter)
│   │   ├── img/                  # Brand assets (logo.png, logo-mark.png, favicon.png)
│   │   └── js/                   # Local libraries (alpine.min.js)
│   └── uploads/                  # User uploads (.htaccess forbids script execution)
├── tailwind/
│   ├── input.css                 # Source styles, @font-face, prose typography
│   ├── tailwind.config.js        # Design tokens & color palettes (v3 format)
│   └── tailwindcss               # Standalone binary (v3.4.17 Linux x64, gitignored)
├── .gitignore                    # Prevents leaks of credentials, logs, and binaries
├── PLAN.md                       # Master architectural blueprint
└── README.md                     # Setup instructions & developer runbook
```

---

## 4. Phase Breakdown

### Phase 0: Scaffold & Design System *(Completed)*
- Establish folder structure with security boundaries (`app/` outside document root).
- Define design tokens in `tailwind.config.js`:
  - **Primary Navy**: `#2F3A8F`
  - **Accent Green**: `#3B8E3F`
  - **Background Surface**: `#FAFAF7`
  - **Text Ink**: `#1A1D29`
  - **Muted Grays**: Balanced scales for borders, subtexts, and cards.
  - **Headings Font**: `Fraunces` (serif, self-hosted woff2).
  - **Body Font**: `Inter` (sans-serif, self-hosted woff2).
  - **Breakpoint**: Added `nav: 1100px` to cleanly collapse desktop navigation before wrapping.
- Standalone Tailwind CLI (v3.4.17) build pipeline and input CSS with `.prose` style (~68ch).
- Self-host Alpine.js, WOFF2 fonts, and brand assets (`logo-mark.png` in header, `logo.png` in footer, `favicon.png`).

### Phase 1: Core & Database Foundation *(Completed)*
- Database schema (`database/schema.sql`) and seed data (`database/seed.sql` with zero admin credentials).
- Initial secure admin initialization via `public_html/setup.php` with 404 lockout and `installed.lock`.
- Single PDO connection manager (`Database.php`) with UTF-8mb4 and strict exception handling.
- Router (`Router.php`) with dynamic route params, 404 handler, HEAD/GET mapping, and automatic 301 redirects check.
- CSRF protection library (`Csrf.php`) with timing-safe validation.
- Central helpers (`helpers.php`): `e()`, `slugify()`, `readTime()`, `formatDate()`, `url()`, `asset()`, `youtubeId()`.
- Secure session hardening (HttpOnly, Secure cookie, SameSite=Lax, strict mode).
- Authentication stub (`Auth.php`) with session regeneration.
- View rendering system (`View.php`) supporting layouts and partials (`header.php`, `footer.php`).
- Error handling pipeline: verbose in `debug=true`, logged to `app/storage/logs/` in production.
- Temporary preview route verifying fonts, palette, responsive header, and drawer without invented facts.

### Phase 2: Admin Authentication & Dashboard *(Completed)*
- **Rate-Limited & Timing-Safe Authentication**:
  - `GET /admin/login` & `POST /admin/login` with email, password, CSRF token, and generic error message ("Invalid email or password").
  - Dual rate limiting via `login_attempts` table: max 5 failed attempts per SHA-256 IP hash AND max 10 failed attempts per SHA-256 account email hash (`hash(email+salt)`) per 15-minute window with dynamic lockout countdown.
  - Timing-safe login: if email is not found, `password_verify()` still executes against a fixed dummy bcrypt hash to ensure constant response times and prevent account enumeration.
  - Opportunistic deletion of attempt logs older than 24 hours on each login submission.
  - Automatic `password_needs_rehash()` check and upgrade on successful authentication.
  - Clears recorded failed attempts for both IP and account upon successful login.
- **Hardened Session Management**:
  - `session_regenerate_id(true)` and CSRF token rotation on login.
  - Idle timeout: 2 hours (`auth.idle_timeout = 7200`).
  - Absolute session lifetime: 12 hours (`auth.absolute_timeout = 43200`).
  - Session expiration redirects to login with flash notice.
  - `POST /admin/logout` with CSRF validation (no GET logout).
- **Auth Middleware & Open Redirect Protection**:
  - All `/admin/*` routes (except `/admin/login`) enforce active authentication.
  - Open redirect prevention: validates `?next=` parameter (must start with `/admin`, forbidden `//`, backslashes, or URI schemes).
  - Admin privacy headers: `X-Robots-Tag: noindex, nofollow`, `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`.
  - Crawler exclusion: `public_html/robots.txt` disallows `/admin` and `/setup.php`.
- **Account Credentials Management**:
  - `/admin/account` allowing updates to administrator name and email (with uniqueness check).
  - Password change requiring current password, 12-character minimum, confirmation match, and session ID regeneration.
- **Live Admin Dashboard**:
  - 6 stat cards (`grid-cols-2 md:grid-cols-3 xl:grid-cols-6`): Total posts, Published, Drafts, Scheduled, Pending comments, Total views.
  - Post status metrics retrieved via **ONE query** with conditional `SUM/CASE`.
  - Numbers formatted using standard Indian numbering system (`1,00,000`) via helper `formatNumberIN()`.
  - Top 5 articles by views & 5 recently updated articles tables.
  - Prominent "New Article" CTA and clean empty states (zero sample data).
  - Phase 3 route placeholders (`/admin/posts`, `/admin/posts/new`, `/admin/posts/{id}/edit`, `/admin/categories`, `/admin/comments`, `/admin/settings`).
  - All DB access through `DashboardModel.php` and `UserModel.php`.
- **Mobile-First Admin Shell Layout**:
  - Desktop (`lg+`): fixed sidebar with all management modules, target="_blank" public site link, and POST logout button.
  - Mobile: top bar with 44px menu button and slide-over Alpine.js drawer with touch targets min 44px, backdrop dismiss, link click dismiss, and Escape key listener.
  - Active navigation item highlight (`bg-slate-800 text-white font-semibold`).
  - Dismissible flash messages banner (success, error, info).

### Phase 3: Post CRUD & Media Management

#### Phase 3A: Editorial Post & Category CRUD *(Completed)*
- **Categories Architecture (`/admin/categories`)**:
  - List all categories with associated post count.
  - Create & Edit with slug validation (lowercase, alphanumeric, hyphens only).
  - Deletion protection: categories with assigned posts cannot be deleted without reassigning posts to a chosen destination category in a transaction.
- **Posts Index (`/admin/posts`)**:
  - Desktop table and mobile stacked card view with responsive layouts.
  - Filters for status (`all`, `published`, `draft`, `scheduled`), category, and search by title with URL query persistence.
  - Pagination at 20 posts per page, sorted newest first (`COALESCE(published_at, created_at) DESC`).
  - Single deletion (POST + CSRF) and bulk checkbox deletion with confirmation modals.
  - Foreign key cascading deletion for comments, likes, views, and post tags.
- **Editorial Post Editor (`/admin/posts/new`, `/admin/posts/{id}/edit`)**:
  - Fields: title, auto-sync slug with manual edit override, excerpt (auto-generates ~160 chars from body if blank), TinyMCE content, category, comma-separated tags with autocomplete suggestions, author name (defaults to logged-in user), status (`draft`, `published`, `scheduled`), publication datetime, and SEO meta title & description with live character counters.
  - Self-hosted TinyMCE community package (`/assets/vendor/tinymce/`) configured with `license_key: 'gpl'` (no cloud dependencies/API keys). Toolbar includes H2, H3, bold, italic, link, YouTube video embed, blockquote, lists, undo/redo. Custom "YouTube" button asks for URL, extracts video ID via `youtubeId()`, and inserts responsive `youtube-nocookie` iframe.
  - Server-side content sanitization via `HTMLPurifier` whitelist with `Cache.SerializerPath` pinned to `app/storage/cache/htmlpurifier` (never inside `vendor/`), HTML5 `figure`/`figcaption` support, forced `rel="noopener noreferrer"` on `target=_blank` links, `HTML.SafeIframe = true`, and `URI.SafeIframeRegexp` strictly limited to `https://www.youtube.com/embed/` and `https://www.youtube-nocookie.com/embed/` (foreign domain iframes are completely stripped).
  - Reading time calculation via `readTime()` helper.
  - Dynamic slug uniqueness suffixing (`-2`, `-3`).
  - Automatic 301 redirect management in `redirects` table when a published post's slug is updated. Flattens redirect chains (updating any existing redirects where `new_path = old` to point to `new`, so $A \to B \to C$ leaves $A \to C$ and $B \to C$) and enforces loop prevention (deleting any redirect where `old_path` equals the post's current path).
  - Cover section placeholder clearly designated for Phase 3B.
- **Work Protection (Zero Work Loss)**:
  - Session keepalive heartbeat (`/admin/keepalive`) pinged every 5 minutes while editor is open to prevent idle timeout.
  - Automatic `localStorage` backup every 10 seconds with "Restore unsaved changes?" prompt on reload. Cleared upon successful submit.
  - Browser `beforeunload` warning when leaving editor with unsaved changes.

#### Phase 3B: Media Management & Cover Uploader *(Completed)*
- **Cover Media Component (`/admin/posts/new`, `/admin/posts/{id}/edit`)**:
  - Segmented toggle between Image and YouTube Video.
  - Image uploader with live preview, replacement, removal, and REQUIRED alternative text (`cover_alt`) enforced for accessibility and SEO.
  - Video uploader accepting `youtube.com/watch`, `youtu.be`, and `shorts` URLs, validated via `youtubeId()` and storing strictly the 11-character video ID with live `img.youtube.com/vi/{id}/hqdefault.jpg` thumbnail preview and direct watch link.
- **Upload Security (`ImageUploader`)**:
  - Whitelists only JPEG, PNG, and WebP via `finfo` byte inspection on file content; explicitly rejects SVG and GIF.
  - File size cap: 8 MB. Resolution cap: 25 megapixels (rejects memory exhaustion attacks on shared hosting).
  - Validation via `getimagesize()` followed by full re-encoding via GD to strip all EXIF metadata (including GPS location).
  - Produces WebP variants (quality ~80) in 3 responsive widths: 1600, 800, 400 (never upscaled). Falls back to JPEG if GD lacks WebP.
  - Cryptographically random filenames (`bin2hex(random_bytes(16))`) stored in `uploads/YYYY/MM/`. Original client filenames are never preserved on disk.
  - Rejection of `.htaccess` or any non-image filenames/extensions.
  - Directory listing protection: automatically creates an empty `index.html` in every newly created `uploads/YYYY/` and `uploads/YYYY/MM/` directory.
- **TinyMCE Image Integration (`POST /admin/media/upload`)**:
  - Dedicated endpoint verifying admin authentication and CSRF token (`X-CSRF-Token` header or POST param).
  - TinyMCE `images_upload_handler` uploads to `/admin/media/upload` and returns `{"location": "/uploads/YYYY/MM/{hash}-1600.webp"}`.
  - Inserted images trigger alt text prompts, and stored HTML embeds explicit `width` and `height` attributes to prevent Cumulative Layout Shift (CLS).
- **Cleanup & Known Limitations**:
  - When a cover image is replaced or a post is deleted (single or bulk delete), all associated cover variant files are deleted from disk via `ImageUploader::deleteVariants()`.
  - **Known limitation**: Inline images inserted into post content bodies via TinyMCE are not tracked or deleted upon post deletion (deletion is tracked only for post cover images).

### Phase 4: Public Pages & Browsing
- **Public Query Rule**: All public content queries (homepage, archives, single post detail, RSS, sitemap) MUST use canonical `PostModel::SQL_IS_PUBLISHED` / `PostModel::sqlPublished($alias)` so that scheduled posts whose timestamp has passed are automatically and immediately visible.
- **Phase 4A (Completed)**:
  - **Admin Polish**: Post editor sticky right sidebar (`lg:sticky lg:top-6`), mobile sticky bottom action bar, removed visible upload paths, standardized "post" terminology across all admin views, alt text filename-pattern rejection, `run.sh` removed.
  - **Admin Settings (`/admin/settings`)**: Settings table backed with per-request static cache. Covers Site info, Contact details, Social links (`https://` validated), and Donate details (intro text, optional UPI ID, UPI QR upload via `ImageUploader`, bank details).
  - **Public Foundation & Shared Layout**: Responsive layout with Rule 12 palette, Fraunces headings, Inter body, header with dynamic nav array (includes confirmed `/gallery`), footer with active contact & social links, single `<h1>` enforcement, unique SEO metadata and Open Graph tags.
  - **Reusable Post Card Partial**: `post-card.php` with 16:9 media, YouTube thumbnail support, responsive `srcset` (400w, 800w, 1600w), lazy loading (with high priority first card support), and neutral branded CSS placeholder fallback.
  - **Public Pages**:
    - Home (`/`): Featured hero story, latest 6 grid, categorized strips (3 each + "View all"), donate band (only if active).
    - Archives (`/category/{slug}`, `/tag/{slug}`): Description, post cards grid, 12 items/page pagination.
    - Search (`/search?q=`): Escaped SQL wildcards (`%`, `_`), max 100 char limit, 12 items/page pagination.
    - Static pages: `/about`, `/contact`, `/privacy`, `/donate` (404 when no donate fields filled), `/gallery` ("Coming soon" placeholder).
    - Error pages: Styled 404 (with search bar and 3 latest posts) and styled 500 (without sensitive technical details).
- **Phase 4B (Completed)**:
  - **Single Article Page (`/post/{slug}`)**: Enforces `PostModel::sqlPublished()` (drafts and future scheduled return 404). Hero media supports 16:9 responsive cover images (`srcset` 400w, 800w, 1600w, eager/fetchpriority) and YouTube Lite Embed (zero external scripts on page load, loads nocookie iframe on click). Category chip above Fraunces `<h1>` title, byline with author, publication date, read time, stored view count, and updated date badge (> 1 day). Readable prose (~68ch), tag links, share row (WhatsApp first, X, Facebook, LinkedIn, Alpine copy link), conditional donate CTA box, previous/next story navigation, desktop sticky sidebar with Related Stories (same category first then tags, max 3) and Popular Stories (last 30 days views via `post_views` with fallback to total views, max 3), mobile responsive sections, and clearly marked Phase 5 placeholders for Like button and comments. Full SEO metadata, Open Graph `article` tags, and JSON-LD Article structured data.
- **Phase 4C (Completed)**:
  - **Database Migration (`gallery_items`)**: Created and applied `004_create_gallery_items.sql` with schema `(id, image_path, alt_text NOT NULL, caption NULL, sort_order INT, created_at)`.
  - **Admin Photo Gallery (`/admin/gallery`)**: Added "Gallery" to admin sidebar navigation. Multi-file upload with queue processing, per-file upload progress bar, drag-and-drop dropzone, and reused `ImageUploader` (finfo verification, 8 MB / 25 MP caps, WebP variants at 1600/800/400w, and EXIF/GPS stripping). Photo card grid with REQUIRED alt text validation (strictly rejecting filename patterns and short words without spaces), optional caption, deletion with confirmation modal (purging database row and all disk variant files via `ImageUploader::deleteVariants()`), and sequential position reordering with "Move up" and "Move down" buttons. Plain copy per Rule 18.
  - **Public Photo Gallery (`/gallery`)**: Responsive grid (2 cols mobile, 3 cols tablet, 4 cols desktop) with 400w square thumbnails, `object-cover`, and lazy loading. Accessible Alpine.js Lightbox (no external libraries) displaying 1600w image, caption, next/previous buttons, close button, Escape key dismissal, Arrow left/right keys, mobile touch swipe gesture support, full focus trap and focus restoration to trigger element on close, and body scroll lock (`overflow-hidden`). Server-side pagination (24 photos per page) with progressive enhancement "Load more" link that works without JavaScript. Empty state handling when no photos exist. Unique SEO metadata (title, meta description) and `og:image` dynamically resolved to the first photo's 1600w variant.
  - **Automated Test Suite (`tests/gallery_check.php`)**: Comprehensive test asserting alt text validation rules, image upload & WebP variant generation, database creation/update, move up/down position reordering, and complete file cleanup upon deletion.


### Phase 5: Engagement & Interactivity *(Completed)*
- **Views (Accurate & Bot-Resistant)**:
  - Counted via small JS beacon after the post has been actively visible for 3 seconds (`POST /api/view/{postId}`). Bots and link previewers (WhatsApp, Facebook, Twitter) do not run JS and are excluded.
  - Server calculation: `visitor_hash = hash('sha256', ip . user_agent . salt)`. `INSERT IGNORE into post_views (post_id, visitor_hash, viewed_on=CURDATE())`; only increments `posts.views` if a new row was inserted.
  - Ignores requests if post is not effectively published, if the user is a logged-in admin, or if it is a preview.
  - Ignores requests whose user agent matches bot patterns (`/(bot|crawl|spider|slurp|preview|headless)/i`).
  - Opportunistic pruning of `post_views` rows older than 90 days (1% probability).
- **Likes (Anonymous, Honest Vanity Metric)**:
  - Interactive Alpine.js like button with heart icon + counter, toggles like/unlike with optimistic UI and error reversion.
  - Visitor identity: random 32-character ID in long-lived cookie (`nc_vid`, 1 year, HttpOnly, SameSite=Lax, Secure on HTTPS), stored server-side in `post_likes (post_id, visitor_hash)`.
  - Rate limited to max 60 like actions per hour per IP hash (`like_rate_limits` table with opportunistic 24h pruning).
  - Cross-site protection: `POST /api/like/{postId}` strictly verifies Origin/Referer header matches site host, returning 403 on mismatch.
  - **Limitation Note**: Likes can be gamed by clearing cookies; this is accepted (vanity metric).
- **Comments (Turnstile-Protected Reader Discussion)**:
  - Form under the post: name (required, max 80), email (required, valid, never shown publicly), comment (required, 3–2000 chars).
  - Plain text only: strips all HTML tags, stores raw text, escapes on output (`e()`), converts newlines to `<br>` (`nl2br(e($body))`). Links are NOT clickable.
  - Quadruple spam defense:
    1. Cloudflare Turnstile widget verified via siteverify API (rejects on failure or network error). Keys stored in `config.local.php` (`'turnstile.site_key'`, `'turnstile.secret_key'`), never in DB or repo. Local dev uses official always-pass test keys (`1x00000000000000000000AA`, `1x0000000000000000000000000000000AA`). Real keys required before launch.
    2. Honeypot field (`contact_fax`) hidden with CSS (not `type=hidden`). If filled, pretends success and discards.
    3. Minimum 3 seconds between form render and submit via hidden HMAC-signed timestamp (`comment_ts`, `comment_sig`).
    4. Rate limit: max 3 comments per 10 minutes per IP hash.
  - All new comments are created with status `'pending'` ("Thanks! Your comment will appear after review."). Form inputs are preserved in session on validation errors.
  - Approved comments display oldest first: author initial badge, name, date, plain text. Email is omitted.
  - Post meta row displays comment count (`X comments`).
  - Small privacy note under form with link to `/privacy`.
  - All `[PHASE 5 ...]` placeholder text removed from public views.
- **Admin Comments Moderation (`/admin/comments`)**:
  - Filter tabs: Pending (default), Approved, Spam with real-time status counts.
  - Moderation item: author name, email, formatted comment text, linked post title, date.
  - Actions: Approve, Mark as spam, Delete (with confirmation modal/prompt). Bulk selection bar with same actions.
  - Dashboard "To review" card links directly to `/admin/comments?status=pending`.
  - Plain copy per Rule 18, responsive desktop table and mobile cards.

### Phase 6: SEO, Syndication & Migration

#### Phase 6A: SEO Essentials *(Completed)*
- **Dynamic XML Sitemap (`/sitemap.xml`)**:
  - Automatically generates valid XML containing Home, published posts (`lastmod = updated_at`), categories, tags with posts, published pages, `/gallery`, `/contact`, and `/donate` (only when active).
  - Strictly enforces `PostModel::sqlPublished()` to guarantee drafts and future scheduled posts are completely excluded.
  - Generates escaped, absolute HTTPS URLs derived from configuration.
- **Dynamic Robots Exclusion (`/robots.txt`)**:
  - Serves via dynamic PHP route with Disallow `/admin` and Disallow `/setup.php`.
  - Appends `Sitemap: <site url>/sitemap.xml` generated dynamically from site configuration. Static file deleted.
- **RSS 2.0 Syndication Feed (`/feed`)**:
  - Provides latest 20 effectively published articles with title, permalink (`guid`), pubDate (RFC 2822 in IST), plain excerpt description, and cover image enclosure (`<enclosure url="..." type="image/webp" />`).
  - Added `<link rel="alternate" type="application/rss+xml">` discoverability in global public layout.
- **Default Share Image (`assets/img/og-default.png`)**:
  - 1200x630 branded Open Graph share image generated with GD, displaying the official logo against brand background `#FAFAF7`.
  - Used automatically for all pages without a dedicated cover image.
- **Favicon & Web App Manifest Suite**:
  - Generated from `assets/img/logo-mark.png`: `favicon.ico` (32px), `apple-touch-icon.png` (180px), `icon-192.png`, `icon-512.png`.
  - Minimal `site.webmanifest` configured with site name, theme color (`#2F3A8F`), and surface background (`#FAFAF7`).

#### Phase 6B: Content Import *(Future)*
- The old site (`natconnectfoundation.com`) is a custom PHP site, not WordPress.
- Import from the client's MySQL database export (`.sql`) + images folder.
- Preserve original publish dates, categories, and tags.
- Re-process legacy imagery through `ImageUploader` to generate modern responsive WebP variants (1600w, 800w, 400w) with stripped EXIF metadata.
- Automatically populate the `redirects` table with 301 redirects from every legacy URL to the corresponding new slug to preserve existing search rankings and backlinks.

### Phase 7: Hardening, Performance & Deployment

#### Phase 7A: Deployment Pipeline & Install Tooling *(Completed)*
- **Fresh Install File (`database/install.sql`)**:
  - Generated via `php bin/build-install-sql.php` directly from the local database structure (`SHOW CREATE TABLE` for all 17 tables, zero data dumps, all `AUTO_INCREMENT` values stripped from table options).
  - Appends `database/seed.sql` baseline content (categories, settings, system pages) and records all migrations in `schema_migrations`.
  - **Fresh install protocol**: Import `database/install.sql` only.
- **Admin Maintenance & Database Updates (`/admin/maintenance`)**:
  - Accessible via sidebar bottom. Lists all migrations from `database/migrations/` with real-time status (Applied / Pending) and applied timestamps.
  - "Apply pending updates" action: POST + CSRF + authenticated admin. Sequentially applies pending migrations with try/catch error handling, records each in `schema_migrations`, shows file-by-file results, and halts immediately at the first error showing the message plainly.
  - Prominent warning text directly above the action button: *"Back up the database in hPanel before applying updates."*
  - Migrations reside outside `public_html` in `<FTP root>/database/migrations/`.
- **GitHub Actions Automated Deployment (`.github/workflows/deploy.yml`)**:
  - Uses `SamKirkland/FTP-Deploy-Action@v4.3.5`.
  - Triggers on push to `staging` (using `STAGING_FTP_*` secrets) and `main` (using `PROD_FTP_*` secrets), plus `workflow_dispatch`.
  - Configurable protocol at top (`ftps` with one-line switch to `ftp`).
  - Remote layout: `<FTP root>/public_html/`, `<FTP root>/app/`, `<FTP root>/vendor/`, `<FTP root>/database/migrations/`.
  - `public_html` step: strictly excludes `uploads/**` (except `.htaccess` and `index.html`) and `.DS_Store`.
  - `app` step: strictly excludes `config/config.local.php` and `storage/**` (except `.gitkeep`).
  - `vendor` and `database/migrations` steps: uploaded as-is.
  - `dangerous-clean-slate` is strictly forbidden to protect user media and server runtime state.
  - Development tools (`tailwind/`, `tests/`, `bin/`, `.github/`, `*.md`, `.git*`, `database/seed*`, `database/install.sql`) are never deployed.

#### Deployment

##### Branch Flow
- **`dev` → `staging` → `main`**
  - **`dev`**: Active feature, design, and bugfix development branch. All local verification and test suites run here.
  - **`staging`**: Staging release branch. Merging `dev` into `staging` triggers GitHub Actions automated FTPS deployment to the Hostinger staging site using staging credentials.
  - **`main`**: Production release branch. Merging `staging` into `main` triggers GitHub Actions automated FTPS deployment to the live Hostinger site using production credentials.

##### FIRST INSTALL CHECKLIST
1. hPanel: confirm .htaccess support is enabled for the site.
2. hPanel → PHP Configuration: upload_max_filesize 10M, post_max_size 12M, memory_limit 256M.
3. hPanel → Databases: create DB + user; phpMyAdmin → import database/install.sql.
4. File Manager: delete default.php from public_html; create app/storage/logs, app/storage/cache/htmlpurifier (writable).
5. Upload app/config/config.local.php by hand: debug=false, real DB credentials, new random salt, url = the site's https address, Turnstile keys.
6. Visit /setup.php, create the admin, then DELETE setup.php.
7. Run the security checks: /uploads/test.php → 403; /setup.php → 404; random URL → styled 404; login lockout works; session cookie has the Secure flag.
8. Create ip.php test (REMOTE_ADDR vs real IP) to decide trusted_proxies, then delete it.
9. Dashboard launch checklist all green (except items intentionally pending).

##### Update Flow
1. **Merge**: Merge approved pull request into `staging` (or `main`).
2. **Wait for deploy**: Monitor GitHub Actions and verify the `Deploy to Hostinger FTP` workflow completes with green checkmarks across all four steps (`public_html`, `app`, `vendor`, `database/migrations`).
3. **Back up**: Log in to Hostinger hPanel and create a manual database backup before applying schema changes.
4. **Admin → Maintenance**: Log into the admin console, navigate to **Maintenance** at the bottom of the sidebar (`/admin/maintenance`).
5. **Apply updates**: Review the list of pending migrations and click **Apply pending updates**. Verify all migrations report successful completion.

- **Hostinger Deployment & .htaccess Rules**:
  - **Hostinger: enable .htaccess support per website (may be off by default for PHP/HTML sites)**. (Served by LiteSpeed/hcdn without `.htaccess` unless enabled; ask Hostinger support/Agent: 'enable .htaccess support / Apache backend'). Verify with a test: a RewriteRule `[F]` in `public_html/.htaccess` must return 403.
  - **Hostinger: Options directive in .htaccess = HTTP 500. Never use it.** (`AllowOverride` does not include `Options`).
  - **Hostinger: mod_headers is NOT available.** Unwrapped `Header` directive causes HTTP 500; inside `<IfModule mod_headers.c>` it is silently skipped. All security headers must be emitted from PHP in `public_html/index.php`. `X-XSS-Protection` is removed everywhere.
  - **Static asset caching relies on mod_expires (unverified) and the Hostinger CDN; verify on staging with curl -I on a CSS file after the first deploy.**
  - **Before launch, upload the real public_html/.htaccess to staging and confirm no 500.**
  - **Strictly forbidden**: `php_flag`, `php_value`, `Options`, and unwrapped `Header` anywhere.
  - **Directives status on Hostinger**:
    - **Verified working**: `RewriteRule [F]`, `<FilesMatch>` + `Require all denied` (with `Order/Deny` fallback).
    - **UNVERIFIED on Hostinger (harmless inside IfModule)**: `ExpiresByType` (`mod_expires` caching rules), `AddDefaultCharset`.
  - **Hostinger PHP Configuration**: In Hostinger hPanel → PHP Configuration, set `upload_max_filesize=10M`, `post_max_size=12M`, and `memory_limit=256M`.
  - **Storage Deployment Rule**: `app/storage/` holds server-local runtime state (`installed.lock`, logs, HTMLPurifier cache in `cache/htmlpurifier/`). `app/storage/` is strictly local and must **NEVER be deployed or overwritten** during code deployments or release uploads.


---

## 5. Architectural & Coding Rules
1. **Zero SQL Injection**: Every database query must use PDO prepared statements with parameterized inputs. Direct string interpolation or concatenation into SQL is strictly forbidden.
2. **Zero XSS**: Every variable rendered in templates must pass through `e()` (`htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`).
3. **CSRF Enforcement**: Every state-changing form (POST, PUT, DELETE) must include `Csrf::field()` and be verified via `Csrf::verify()` before processing.
4. **Defense in Depth for Uploads**:
   - Files are stored in `/public_html/uploads/`.
   - The uploads directory contains a hardened `.htaccess` that explicitly denies execution of script extensions:
     `<FilesMatch "\.(php|php\d|phtml|phar|pl|py|cgi|sh)$"> Require all denied </FilesMatch>` (with `mod_access_compat` fallback).
   - Main `.htaccess` also enforces `RewriteRule ^uploads/.*\.(php|php\d|phtml|phar|pl|py|cgi|sh)$ - [F,L,NC]`.
   - Directory listing prevention without `Options`: an empty `index.html` is placed in `/public_html/uploads/`, and `ImageUploader` (Phase 3B) automatically generates an empty `index.html` in every newly created `uploads/YYYY/` and `uploads/YYYY/MM/` directory.
   - `Options` directives (such as `Options -Indexes -ExecCGI`) are strictly forbidden because they trigger HTTP 500 on Hostinger (`AllowOverride` does not include `Options`).
   - Uploaded files are renamed using cryptographically random hashes (`bin2hex(random_bytes(16))`).
5. **Session Security**: Session cookies must have `httponly=true`, `samesite=Lax`, and `secure` enabled in production. Re-issue session IDs on login using `session_regenerate_id(true)`.
6. **No Inline Styling**: All visual styles must utilize the Tailwind CSS v3 token system.
7. **Accessibility (a11y)**: Semantic HTML tags (`<header>`, `<nav>`, `<main>`, `<article>`, `<aside>`, `<footer>`), valid `aria-*` tags for interactive elements, focus states (`focus-visible:ring-2`), descriptive `alt` attributes, minimum `text-sm` for footer body, and WCAG AA contrast.
8. **Client Copy Rule**: Never invent facts, statistics, names, or places about the client. Use `[CLIENT COPY: description]` placeholders until approved client text is provided.
9. **Effective Post Status & Lazy Publishing Rule (No Cron)**:
   - All code determining published posts (admin filters, status counts, dashboard summaries, and Phase 4 public queries) MUST use the single canonical SQL fragment defined in `PostModel::SQL_IS_PUBLISHED` / `PostModel::sqlPublished($alias)`: `(status = 'published' OR (status = 'scheduled' AND published_at <= NOW()))`.
   - Future scheduled posts MUST use `PostModel::SQL_IS_SCHEDULED` / `PostModel::sqlScheduled($alias)`: `(status = 'scheduled' AND published_at > NOW())`.
   - In PHP, the effective status of a post row MUST be resolved via `PostModel::effectiveStatus($post)` (returns `'published' | 'scheduled' | 'draft'`).
   - **Phase 4 public queries** (homepage, archives, single post detail, RSS, search) MUST use `PostModel::sqlPublished()` exclusively to query published content. Never check `status = 'published'` alone. This eliminates external cron dependencies on Hostinger shared hosting while ensuring scheduled posts instantly publish when their timestamp passes.
10. **Setup File Protocol**: `public_html/setup.php` is an ephemeral initial onboarding script. It strictly locks with HTTP 404 as soon as any user exists or `installed.lock` is present. **The deployer must delete `setup.php` immediately after creating the admin account.**
11. **Responsive Breakpoint Isolation**: The custom `'nav': '1100px'` breakpoint resides strictly under `theme.extend.screens`. Never combine `nav:` and `xl:` prefixes on the same property (keep responsive styling unambiguous and decoupled).
12. **Strict Color Palette, Roles & Contrast (WCAG AA Compliance)**:
    - **Single-value Brand Tokens**: Brand colors are defined as single values in `tailwind.config.js`; use opacity modifiers (e.g. `bg-primary/10`, `border-accent/20`) for tints, never `-50`/`-100` shade scale suffixes unless explicitly defined in config. Do not introduce ad-hoc colors outside the curated palette (**primary**, **accent**, **ink**, **surface**, **neutral grays/borders**).
    - **Contrast Rationale & Roles**:
      - Base **`accent`** (`#3B8E3F`) contrast against white is only ~4.1:1, failing WCAG AA (which requires 4.5:1 minimum for normal text). Therefore, `#3B8E3F` (`accent`) is **strictly decorative only**: icons, borders, `bg-accent/10` tints, and decorative indicator dots. **Never place white text on `#3B8E3F` and never render small text in it.**
      - **`accent-strong`** (`#2E7D32`) achieves ~5.1:1 contrast against white (passing WCAG AA). Used for all green text (links, eyebrow labels, "FOUNDATION" branding, footer action links) and solid green button backgrounds.
      - **`accent-hover`** (`#1B5E20`) is the darker hover state for green buttons and green links.
      - **`primary`** (`#2F3A8F`) and **`primary-hover`** (`#232C70`): Used for brand navy elements, headlines, and solid navy buttons. Header brand link hover uses `hover:text-primary-hover`.
    - **Solid Button Hover Rule**:
      - **Do NOT use opacity modifiers (e.g. `/90`) for hover states on solid buttons** — opacity lightens the button against light surroundings, degrading contrast and visual weight.
      - Solid green buttons (Donate CTA, setup submit): `bg-accent-strong hover:bg-accent-hover text-white`.
      - Solid navy buttons: `bg-primary hover:bg-primary-hover text-white`.
13. **Local Testing vs Server Directives & Hostinger Deployment Rules**:
    - PHP built-in server ignores `.htaccess`; all `.htaccess` behavior (URL rewrites, security headers, upload execution denials) must be verified on Hostinger (Apache backend).
    - **Hostinger: enable .htaccess support per website (may be off by default for PHP/HTML sites).** (Ask Hostinger support / Agent: 'enable .htaccess support / Apache backend'). Verify with a test: a RewriteRule `[F]` in `public_html/.htaccess` must return 403.
    - **Hostinger: Options directive in .htaccess = HTTP 500. Never use it.** On Hostinger's Apache backend, `AllowOverride` does not include `Options`. Any presence of `Options` (`Options -Indexes`, `-ExecCGI`, `+FollowSymLinks`, etc.) immediately causes HTTP 500.
    - **Hostinger: mod_headers is NOT available.** Unwrapped `Header` directive triggers HTTP 500; inside `<IfModule mod_headers.c>` it is silently skipped. All security headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()`, and `Strict-Transport-Security: max-age=31536000` when `Auth::isHttps()` is true) are sent via PHP in `public_html/index.php`. `X-XSS-Protection` is removed everywhere.
    - **Static asset caching relies on mod_expires (unverified) and the Hostinger CDN; verify on staging with curl -I on a CSS file after the first deploy.**
    - **Before launch, upload the real public_html/.htaccess to staging and confirm no 500.**
    - **Directives Protocol**:
      - Strictly forbidden: `php_flag`, `php_value`, `Options`, and unwrapped `Header` directives anywhere.
      - Verified working on Hostinger (Apache backend): `RewriteRule` with `[F]`, `<FilesMatch>` + `Require all denied` (with `Order/Deny` fallback).
      - UNVERIFIED on Hostinger (harmless inside IfModule): `ExpiresByType` (`mod_expires` caching rules), `AddDefaultCharset`.
    - Directory indexing without `Options`: Prevented by placing an empty `index.html` in `/public_html/uploads/`, and making `ImageUploader` (Phase 3B) create an empty `index.html` in every new `uploads/YYYY/` and `uploads/YYYY/MM/` directory.
14. **Manual Admin Password Reset & Lockout Recovery**: There is intentionally no automated "forgot password" feature or email recovery mechanism (single administrator, avoids unreliable mail/SMTP delivery issues on shared hosting). In the event of lost credentials:
    1. Generate a new password hash using PHP CLI:
       `php -r "echo password_hash('NEWPASS', PASSWORD_DEFAULT);"`
    2. Open Hostinger phpMyAdmin (or MySQL CLI) and run:
       `UPDATE users SET password_hash = '<GENERATED_HASH>' WHERE email = '<ADMIN_EMAIL>';`
    If the admin is locked out by failed attempts, run in phpMyAdmin: `DELETE FROM login_attempts;`
15. **Reverse Proxy & Client IP Verification on Hostinger**: `clientIp()` relies on `REMOTE_ADDR` exclusively by default to prevent IP spoofing attacks. On Hostinger, check what `$_SERVER['REMOTE_ADDR']` outputs; if Hostinger / Cloudflare CDN routes web traffic through a reverse proxy, configure `security.trusted_proxies` in `config.local.php` with the upstream proxy IPs/subnets. Otherwise, if left unconfigured on a reverse proxy, all visitors share one rate-limit bucket.
16. **Timezone Consistency (IST +05:30)**: All times are IST. PHP and MySQL sessions are both pinned to +05:30 (`date_default_timezone_set('Asia/Kolkata')` and PDO `SET time_zone = '+05:30'`). India has no DST, so a fixed offset is safe. Never mix in UTC.
17. **Database Migrations Protocol**:
    - Never edit an applied migration. Every schema change = a new numbered migration file in `database/migrations/`.
    - Local execution: run `php bin/migrate.php` via CLI.
    - Hostinger has no SSH: apply migrations manually in phpMyAdmin (Import the file, then `INSERT INTO schema_migrations (version) VALUES ('00X')`). Always back up the DB in hPanel first.
18. **Admin UI & Copy Protocol**: Admin copy rule: plain, short, no marketing language. One primary action per screen. Use "post", not "article", throughout the editorial admin. Stat card numbers use uniform `text-ink` sizing with neutral gray icons (accent-strong badge/border only for pending comments when count > 0). Sidebar is fixed `w-64 bg-white border-r border-border` with active navigation indicated by `bg-primary/10 text-primary font-semibold border-l-[3px] border-primary`.
19. **`app/storage/` Protocol & Deployment Integrity**:
    - `app/storage/` contains server-local runtime state (`installed.lock`, logs in `logs/`, and HTMLPurifier cache in `cache/htmlpurifier/`).
    - **`app/storage/` is strictly local and must NEVER be deployed or overwritten** during code deploys or release uploads.
    - HTMLPurifier serializer path must always point to `app/storage/cache/htmlpurifier` (never inside `vendor/`). The directory contains `.gitkeep`, is gitignored (`/app/storage/cache/htmlpurifier/*` except `.gitkeep`), and must remain server-writable (0775).
20. **Accounts & Credentials Security Rule**:
    - **Never create, seed, or reset user accounts or passwords in code, scripts, tests, or the database.**
    - Tests that require a user must create one with a random 32-character password and delete it during cleanup.
    - **Never write login credentials in reports.**
    - Run the site locally only with `./bin/serve.sh` (matches Hostinger limits).
21. **Local Demo Content Protocol**:
    - Demo content (`bin/seed-demo.php`) is strictly local-only for UI and design review; `bin/` is never deployed.
    - Always run `php bin/seed-demo.php --clean` before any export of the database or deployment to staging/production.
22. **Task Completion and Git Protocol**:
    - At the end of every task, run the test suites (`for t in tests/*_check.php; do php "$t"; done`).
    - Run `git add -A`.
    - Confirm that `config.local.php`, uploads images, `demo-manifest.json`, and logs are NOT staged.
    - Commit with a clear, descriptive message, and push to `origin dev`.
    - Show the real `git log --oneline -1` output in the report. Never claim a commit without showing it.

---

## 6. Pre-Launch Checklist
- [ ] **Automated Pre-Launch Verification**: Run `php bin/prelaunch-check.php`. Must pass with exit code 0. Validates:
  - No `[CLIENT COPY` placeholders remain in `app/views`, `pages` table, `settings` table, or `categories` table.
  - Cloudflare Turnstile keys are configured with real production keys (not empty and not Cloudflare test keys).
  - `app.debug` is set to `false`.
  - `public_html/setup.php` is deleted from disk.
  - Site address is set correctly (config `url` starts with `https://`, has no port, no `127.0.0.1`/`localhost`, and host matches request host).
- [ ] **Demo Content Purged**: Demo content is local-only; confirm `php bin/seed-demo.php --clean` has been executed before any export or deployment of the database.
- [ ] **Client Copy**: `grep -rn "CLIENT COPY" app/views` and database check must return nothing before launch. (Replace all `[CLIENT COPY: ...]` placeholders in pages, categories, and settings with verified client text).
- [ ] **Delete Setup**: Confirm `public_html/setup.php` has been deleted from the production server.
- [ ] **Apache .htaccess**: Upload real `public_html/.htaccess` to staging and confirm no HTTP 500 error occurs.
- [ ] **Security Headers**: Verify HTTP response headers via `curl -I` on production (`nosniff`, `SAMEORIGIN`, `strict-origin-when-cross-origin`, `Permissions-Policy`).
- [ ] **Cache Verification**: Verify static asset caching via `curl -I` on CSS and font assets.
- [ ] **Uploads Protection**: Test `RewriteRule` and script execution denial in `/uploads/` on Hostinger.
- [ ] **Cloudflare Turnstile Keys**: Real Turnstile site key and secret key must be set in `config.local.php` before production launch (replace test keys `1x00000000000000000000AA` / `1x0000000000000000000000000000000AA`).
- [ ] **Database Backup**: Ensure automated daily backups are enabled in Hostinger hPanel.
- [ ] **Google Search Console**: Submit `/sitemap.xml` in Google Search Console after launch.

