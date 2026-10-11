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
- **Language & Runtime**: PHP 8.2+ without heavy frameworks.
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
│   ├── storage/
│   │   ├── installed.lock        # Created upon first-time admin setup (locks setup.php)
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
├── database/
│   ├── schema.sql                # Complete relational schema (InnoDB, utf8mb4)
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
├── run.sh                        # Convenience runner for local built-in PHP server
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

### Phase 2: Admin Authentication & Dashboard
- Admin login with rate limiting (`login_attempts` table, IP hash throttling).
- Password verification using `password_hash()` / `password_verify()`.
- Session expiration and secure logout.
- Admin dashboard layout and statistics (total posts, draft count, pending comments, view metrics).

### Phase 3: Post CRUD & Media Management
- Post management: Create, Edit, Delete, Draft, Schedule, Publish.
- Category & Tag assignment with transaction safety.
- Cover media picker (featured image upload or YouTube video URL).
- Media uploader with MIME type verification, extension whitelisting, image resizing/conversion, and filename sanitization.
- Clean text editor integration (accessible textarea / lightweight rich editor) sanitized via HTMLPurifier.

### Phase 4: Public Pages & Browsing
- Homepage featuring curated hero article, recent stories, and categorized sections.
- About NatConnect page and initiative spotlights.
- Category and Tag archive pages with pagination.
- Single Article page with Fraunces headings, optimized prose line-length (~68ch), cover media, read time, and social share links.
- RTI Applications & Environmental Campaigns dedicated index.

### Phase 5: Engagement & Interactivity
- Unique visitor tracking for views (`post_views`) and likes (`post_likes`) based on salted visitor hashes.
- Public commenting workflow with Cloudflare Turnstile CAPTCHA verification.
- Comment moderation queue in admin dashboard (approve, spam, delete).
- Related posts algorithm matching shared tags and categories.

### Phase 6: SEO, Migration & Syndication
- Dynamic XML Sitemap generator (`sitemap.xml`).
- RSS 2.0 Feed (`feed.xml` / `/feed`).
- Canonical URLs, Open Graph, and Twitter Card metadata generator.
- WordPress migration script importing legacy posts, categories, tags, and media.
- 301 Redirect table integration preventing broken backlinks from older URLs.

### Phase 7: Hardening, Performance & Deployment
- Production security review (Content Security Policy headers, upload folder lockdown).
- Asset optimization, Gzip / Brotli cache directives in `.htaccess`.
- Database indexing audit.
- Full release zip packaging and Hostinger deployment guide.

---

## 5. Architectural & Coding Rules
1. **Zero SQL Injection**: Every database query must use PDO prepared statements with parameterized inputs. Direct string interpolation or concatenation into SQL is strictly forbidden.
2. **Zero XSS**: Every variable rendered in templates must pass through `e()` (`htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`).
3. **CSRF Enforcement**: Every state-changing form (POST, PUT, DELETE) must include `Csrf::field()` and be verified via `Csrf::verify()` before processing.
4. **Defense in Depth for Uploads**:
   - Files are stored in `/public_html/uploads/`.
   - The uploads directory contains a LiteSpeed/Apache safe `.htaccess` that explicitly denies execution of script extensions:
     `<FilesMatch "\.(php|php\d|phtml|phar|pl|py|cgi|sh)$"> Require all denied </FilesMatch>`
     `Options -Indexes -ExecCGI`
   - Main `.htaccess` also enforces `RewriteRule ^uploads/.*\.(php|php\d|phtml|phar|pl|py|cgi|sh)$ - [F,L,NC]`.
   - Uploaded files are renamed using cryptographically random hashes (`bin2hex(random_bytes(16))`).
5. **Session Security**: Session cookies must have `httponly=true`, `samesite=Lax`, and `secure` enabled in production. Re-issue session IDs on login using `session_regenerate_id(true)`.
6. **No Inline Styling**: All visual styles must utilize the Tailwind CSS v3 token system.
7. **Accessibility (a11y)**: Semantic HTML tags (`<header>`, `<nav>`, `<main>`, `<article>`, `<aside>`, `<footer>`), valid `aria-*` tags for interactive elements, focus states (`focus-visible:ring-2`), descriptive `alt` attributes, minimum `text-sm` for footer body, and WCAG AA contrast.
8. **Client Copy Rule**: Never invent facts, statistics, names, or places about the client. Use `[CLIENT COPY: description]` placeholders until approved client text is provided.
9. **Lazy Publishing Rule (No Cron)**: Scheduled posts are published "lazily". Public queries treat `(status = 'published' OR (status = 'scheduled' AND published_at <= NOW()))` as published. This eliminates external cron dependencies on Hostinger shared hosting.
10. **Setup File Protocol**: `public_html/setup.php` is an ephemeral initial onboarding script. It strictly locks with HTTP 404 as soon as any user exists or `installed.lock` is present. **The deployer must delete `setup.php` immediately after creating the admin account.**
11. **Responsive Breakpoint Isolation**: The custom `'nav': '1100px'` breakpoint resides strictly under `theme.extend.screens`. Never combine `nav:` and `xl:` prefixes on the same property (keep responsive styling unambiguous and decoupled).
12. **Strict Color Palette & Tints**: Brand colors are single values; use opacity modifiers (bg-primary/10) for tints, never -50/-100 shade suffixes unless defined in config. Use only the designated brand palette tokens: **primary** (`#2F3A8F`), **accent** (`#3B8E3F`), **ink** (`#1A1D29`), **surface / background** (`#FAFAF7`), and **neutral grays / borders**. Do not introduce ad-hoc utility colors (such as amber, slate, etc.).
13. **Local Testing vs Server Directives**: PHP built-in server ignores `.htaccess`; all `.htaccess` behavior (URL rewrites, security headers, upload execution denials) must be verified on Hostinger (Apache/LiteSpeed).
