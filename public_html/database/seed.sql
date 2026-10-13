-- NatConnect Foundation Initial Seed Data
-- =====================================================================
-- Notice: No default admin user or credentials are seeded here.
-- Run public_html/setup.php on first install to configure the initial
-- administrator account securely, then delete setup.php.
-- Safe to run multiple times (uses INSERT IGNORE).
-- =====================================================================

SET NAMES utf8mb4;

-- Seed Default Categories
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `description`, `sort_order`)
VALUES
  (1, 'Causes', 'causes', NULL, 1),
  (2, 'News', 'news', NULL, 2),
  (3, 'Campaigns', 'campaigns', NULL, 3),
  (4, 'RTI Applications', 'rti-applications', NULL, 4);

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
  ('section_intro_newsletter', 'Sign up to hear about our campaigns and stories.'),
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
  ('home_card_1_text', 'Give your time and skills to campaigns, drives and awareness work.'),
  ('home_card_1_btn_label', 'Volunteer'),
  ('home_card_1_link', '/contact'),
  ('home_card_1_wa_message', ''),
  ('home_card_2_title', 'Report an environmental concern'),
  ('home_card_2_text', 'Seen damage to a wetland, mangrove or trees in your area? Tell us what you saw.'),
  ('home_card_2_btn_label', 'Report concern'),
  ('home_card_2_link', '/contact'),
  ('home_card_2_wa_message', ''),
  ('home_card_3_title', 'Share a story'),
  ('home_card_3_text', 'Have a local environmental story, photo or observation? Send it to us.'),
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
