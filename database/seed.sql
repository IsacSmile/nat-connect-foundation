-- NatConnect Foundation Initial Seed Data
-- =====================================================================
-- Notice: No default admin user or credentials are seeded here.
-- Run public_html/setup.php on first install to configure the initial
-- administrator account securely, then delete setup.php.
-- =====================================================================

SET NAMES utf8mb4;

-- Seed Default Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`)
VALUES
  (1, 'Causes', 'causes', '[CLIENT COPY: Category description for Causes]'),
  (2, 'News', 'news', '[CLIENT COPY: Category description for News]'),
  (3, 'Campaigns', 'campaigns', '[CLIENT COPY: Category description for Campaigns]'),
  (4, 'RTI Applications', 'rti-applications', '[CLIENT COPY: Category description for RTI Applications]')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- Seed Default Site Settings (Placeholders)
INSERT INTO `settings` (`key`, `value`)
VALUES
  ('site_name', 'NatConnect Foundation'),
  ('site_tagline', 'Nature, Everything'),
  ('contact_email', '[CLIENT COPY: contact email]'),
  ('footer_about', '[CLIENT COPY: mission statement and organizational description]')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
