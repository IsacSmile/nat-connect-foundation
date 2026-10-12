-- NatConnect Foundation — Migration 007: Create pages table, seed system pages, and add mission statement setting

CREATE TABLE IF NOT EXISTS `pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `content` LONGTEXT NOT NULL,
  `meta_title` VARCHAR(255) NULL DEFAULT NULL,
  `meta_description` TEXT NULL DEFAULT NULL,
  `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  `show_in_nav` TINYINT NOT NULL DEFAULT 0,
  `nav_label` VARCHAR(40) NULL DEFAULT NULL,
  `nav_order` INT NOT NULL DEFAULT 0,
  `is_system` TINYINT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_pages_status_nav` (`status`, `show_in_nav`, `nav_order`),
  INDEX `idx_pages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed system pages (published, is_system=1)
INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `meta_title`, `meta_description`, `status`, `show_in_nav`, `nav_label`, `nav_order`, `is_system`, `created_at`, `updated_at`)
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
    '<h2>Privacy Policy</h2><p>[CLIENT COPY] At NatConnect Foundation, we respect and safeguard visitor privacy.</p><h3>Comments & Information Storage</h3><p>[CLIENT COPY] When you submit a comment on our articles, your comment name and email stored securely in our database. Your email is never published or shared publicly and is collected solely for comment moderation and abuse prevention.</p><h3>Spam Protection</h3><p>[CLIENT COPY] We use Cloudflare Turnstile for automated spam protection and CAPTCHA verification to prevent abuse without tracking personal browsing history.</p><h3>Visitor Cookies & Engagement</h3><p>[CLIENT COPY] We use an anonymous like cookie (nc_vid) to remember article likes and prevent duplicate entries without identifying individual users.</p>',
    'Privacy Policy — NatConnect Foundation',
    'NatConnect Foundation privacy policy: details on comments, anonymous cookies, and Cloudflare Turnstile protection.',
    'published',
    0,
    NULL,
    2,
    1,
    NOW(),
    NOW()
  )
ON DUPLICATE KEY UPDATE `slug` = `slug`;

-- Add mission_statement setting (Settings -> Site)
INSERT IGNORE INTO `settings` (`key`, `value`)
VALUES ('mission_statement', '[CLIENT COPY] Mission statement');
