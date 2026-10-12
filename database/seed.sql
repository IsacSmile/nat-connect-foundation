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
  ('footer_about', '');

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
