-- =====================================================================
-- NatConnect Foundation — Fresh Installation Schema & Baseline Seed
-- =====================================================================
-- FRESH INSTALL PROTOCOL: Import database/install.sql only.
-- Generated: 2026-10-13 14:16:40 (IST)
-- Contains:
--   1. Full database table structure (clean DDL, no auto-increment values)
--   2. Seed categories, site settings, and default pages
--   3. Recorded schema_migrations baseline (all current migrations marked applied)
-- Excludes demo content, test data, and user credentials entirely.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Table Structures
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `cover_type` enum('image','video') NOT NULL DEFAULT 'image',
  `cover_image` varchar(255) DEFAULT NULL,
  `cover_alt` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `author_name` varchar(150) DEFAULT 'NatConnect Foundation',
  `category_id` int(10) unsigned DEFAULT NULL,
  `status` enum('draft','published','scheduled') NOT NULL DEFAULT 'draft',
  `is_pick` tinyint(4) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `likes` int(10) unsigned NOT NULL DEFAULT 0,
  `read_time_minutes` int(10) unsigned NOT NULL DEFAULT 1,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `legacy_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_posts_status_published` (`status`,`published_at`),
  KEY `idx_posts_category_id` (`category_id`),
  CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','approved','spam') NOT NULL DEFAULT 'pending',
  `ip_hash` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comments_post_status` (`post_id`,`status`),
  CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `gallery_items`;
CREATE TABLE `gallery_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gallery_sort` (`sort_order`,`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `like_rate_limits`;
CREATE TABLE `like_rate_limits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_hash` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_like_rate_limits_ip_created` (`ip_hash`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_hash` varchar(64) NOT NULL,
  `account_hash` varchar(64) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_ip_time` (`ip_hash`,`attempted_at`),
  KEY `idx_login_attempts_account_time` (`account_hash`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `show_in_nav` tinyint(4) NOT NULL DEFAULT 0,
  `nav_label` varchar(40) DEFAULT NULL,
  `nav_order` int(11) NOT NULL DEFAULT 0,
  `is_system` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_pages_status_nav` (`status`,`show_in_nav`,`nav_order`),
  KEY `idx_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `post_likes`;
CREATE TABLE `post_likes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `visitor_hash` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post_visitor_like` (`post_id`,`visitor_hash`),
  CONSTRAINT `fk_post_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `image_alt` varchar(255) DEFAULT NULL,
  `show_as_topic` tinyint(4) NOT NULL DEFAULT 0,
  `show_as_cause` tinyint(4) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `slug` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `post_tags`;
CREATE TABLE `post_tags` (
  `post_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `idx_post_tags_tag` (`tag_id`),
  CONSTRAINT `fk_post_tags_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_post_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `post_views`;
CREATE TABLE `post_views` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `visitor_hash` varchar(64) NOT NULL,
  `viewed_on` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post_visitor_view` (`post_id`,`visitor_hash`,`viewed_on`),
  KEY `idx_post_views_post` (`post_id`),
  KEY `idx_post_views_viewed_on` (`viewed_on`),
  CONSTRAINT `fk_post_views_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `redirects`;
CREATE TABLE `redirects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `old_path` varchar(255) NOT NULL,
  `new_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `old_path` (`old_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE `schema_migrations` (
  `version` varchar(50) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` longtext DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `subscribe_rate_limits`;
CREATE TABLE `subscribe_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_hash` char(64) NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sub_rate_ip_time` (`ip_hash`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `subscribers`;
CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(254) NOT NULL,
  `consent_at` datetime NOT NULL,
  `ip_hash` char(64) NOT NULL,
  `source` varchar(30) NOT NULL DEFAULT 'website',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_subscribers_created` (`created_at`),
  KEY `idx_subscribers_ip` (`ip_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','editor') NOT NULL DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Baseline Seed Data (from database/seed.sql)
-- ---------------------------------------------------------------------

-- NatConnect Foundation Initial Seed Data
-- =====================================================================
-- Notice: No default admin user or credentials are seeded here.
-- Run public_html/setup.php on first install to configure the initial
-- administrator account securely, then delete setup.php.
-- Safe to run multiple times (uses INSERT IGNORE).
-- =====================================================================

SET NAMES utf8mb4;

-- Seed Default Categories
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `description`)
VALUES
  (1, 'Causes', 'causes', NULL),
  (2, 'News', 'news', NULL),
  (3, 'Campaigns', 'campaigns', NULL),
  (4, 'RTI Applications', 'rti-applications', NULL);

-- Seed Default Site Settings
INSERT IGNORE INTO `settings` (`key`, `value`)
VALUES
  ('site_name', 'NatConnect Foundation'),
  ('site_tagline', 'Nature, Everything'),
  ('mission_statement', '[CLIENT COPY] Mission statement'),
  ('contact_email', ''),
  ('contact_phone', ''),
  ('contact_address', ''),
  ('contact_whatsapp', ''),
  ('footer_about', ''),
  ('announcement_text', ''),
  ('announcement_link', ''),
  ('home_hero_headline', '[CLIENT COPY] Championing Nature, Defending Tomorrow'),
  ('home_hero_intro', '[CLIENT COPY] NatConnect Foundation drives grassroots environmental action, RTI-backed wetland conservation, and coastal ecosystem protection across India.'),
  ('section_title_latest', 'Latest stories'),
  ('section_title_topics', 'Explore by topic'),
  ('section_title_causes', 'Causes we care about'),
  ('section_title_campaigns', 'Campaigns'),
  ('section_title_rti', 'Right to Information'),
  ('section_title_gallery', 'From the gallery'),
  ('section_title_newsletter', 'Stay informed'),
  ('section_title_donate', 'Support our work'),
  ('section_title_get_involved', 'Get involved'),
  ('section_intro_rti', 'Our ongoing investigations and RTI appeals uncovering wetland encroachments and environmental governance issues.'),
  ('section_intro_newsletter', 'Get our latest research, campaign dispatches, and RTI findings delivered straight to your inbox.'),
  ('section_intro_donate', 'Support independent environmental reporting and grassroots conservation action across India.'),
  ('section_show_latest', '1'),
  ('section_show_latest_strip', '1'),
  ('section_show_topics', '1'),
  ('section_show_causes', '1'),
  ('section_show_campaigns', '1'),
  ('section_show_rti', '1'),
  ('section_show_gallery', '1'),
  ('section_show_newsletter', '1'),
  ('section_show_donate', '1'),
  ('section_show_announcement', '0'),
  ('section_show_get_involved', '1'),
  ('causes_page_title', 'Causes'),
  ('causes_page_intro', ''),
  ('causes_intro', ''),
  ('gallery_intro', ''),
  ('get_involved_intro', ''),
  ('get_involved_photo', ''),
  ('get_involved_photo_alt', ''),
  ('about_band_photo', ''),
  ('about_band_photo_alt', ''),
  ('hero_slider_autorotate', '0'),
  ('home_impact_number_1', ''),
  ('home_impact_label_1', ''),
  ('home_impact_number_2', ''),
  ('home_impact_label_2', ''),
  ('home_impact_number_3', ''),
  ('home_impact_label_3', ''),
  ('home_impact_number_4', ''),
  ('home_impact_label_4', ''),
  ('home_card_1_title', 'Volunteer with us'),
  ('home_card_1_text', 'Give your time and skills to campaigns, clean-ups, RTI follow-ups, and field documentation.'),
  ('home_card_1_btn_label', 'Volunteer'),
  ('home_card_1_link', '/contact'),
  ('home_card_1_wa_message', ''),
  ('home_card_2_title', 'Report an environmental concern'),
  ('home_card_2_text', 'Seen damage to a wetland, mangrove or tree cover? Send location details and photos so we can verify and file records requests.'),
  ('home_card_2_btn_label', 'Report concern'),
  ('home_card_2_link', '/contact'),
  ('home_card_2_wa_message', ''),
  ('home_card_3_title', 'Share a story'),
  ('home_card_3_text', 'Have a local environmental story, photo or observation to publish? Reach out with notes or drafts.'),
  ('home_card_3_btn_label', 'Share story'),
  ('home_card_3_link', '/contact'),
  ('home_card_3_wa_message', '');

-- Seed Default System Pages
INSERT IGNORE INTO `pages` (`id`, `title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `show_in_nav`, `nav_label`, `nav_order`, `is_system`, `created_at`, `updated_at`)
VALUES
  (
    1,
    'About',
    'about',
    '<p>[CLIENT COPY] About NatConnect Foundation — Dedicated to nature conservation, environmental justice, and wetland preservation across the Mumbai Metropolitan Region.</p><p>[CLIENT COPY] We conduct grassroots research, engage with government authorities, and mobilize citizen awareness to protect our fragile coastal ecosystems, mangroves, and biodiversity hotspots.</p>',
    'About — NatConnect Foundation',
    'Learn about NatConnect Foundation, our mission, vision, and grassroots environmental protection efforts.',
    'published',
    1,
    NULL,
    1,
    1,
    NOW(),
    NOW()
  ),
  (
    2,
    'Privacy Policy',
    'privacy',
    '<h2>Privacy Policy</h2><p>[CLIENT COPY] At NatConnect Foundation, we respect and safeguard visitor privacy.</p><h3>Comments & Information Storage</h3><p>[CLIENT COPY] When you submit a comment on our articles, your comment name and email stored securely in our database. Your email is never published or shared publicly and is collected solely for comment moderation and abuse prevention.</p><h3>Spam Protection</h3><p>[CLIENT COPY] We use Cloudflare Turnstile for automated spam protection and CAPTCHA verification to prevent abuse without tracking personal browsing history.</p><h3>Visitor Cookies & Engagement</h3><p>[CLIENT COPY] We use an anonymous like cookie (nc_vid) to remember article likes and prevent duplicate entries without identifying individual users.</p><h3>Newsletter & Email List</h3><p>[CLIENT COPY] If you subscribe to our email newsletter, your email address is stored with your explicit consent and used exclusively to send NatConnect Foundation updates. We never share or sell our email list, and you can request deletion of your information at any time by contacting us.</p>',
    'Privacy Policy — NatConnect Foundation',
    'NatConnect Foundation privacy policy: details on comments, anonymous cookies, and Cloudflare Turnstile protection.',
    'published',
    0,
    NULL,
    2,
    1,
    NOW(),
    NOW()
  );

-- ---------------------------------------------------------------------
-- Schema Migrations Baseline
-- ---------------------------------------------------------------------

INSERT INTO `schema_migrations` (`version`, `applied_at`) VALUES
  ('001_initial_schema', NOW()),
  ('002_login_attempts_account_hash', NOW()),
  ('003_add_cover_alt_to_posts', NOW()),
  ('004_create_gallery_items', NOW()),
  ('005_clear_category_client_copy_descriptions', NOW()),
  ('006_create_like_rate_limits_and_indexes', NOW()),
  ('007_create_pages_table', NOW()),
  ('008_design_phase_d1', NOW()),
  ('009_categories_sort_order', NOW()),
  ('010_home_round_2_settings', NOW()),
  ('011_causes_page_settings', NOW()),
  ('012_get_involved_settings', NOW())
ON DUPLICATE KEY UPDATE `version` = `version`;

SET FOREIGN_KEY_CHECKS = 1;
