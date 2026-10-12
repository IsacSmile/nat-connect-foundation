-- Migration 008: Design Phase D1
-- Adds editor's picks, tag taxonomies (topics & causes), subscribers table, and home page settings

-- 1. Editor's Pick flag on posts
ALTER TABLE posts ADD COLUMN is_pick TINYINT NOT NULL DEFAULT 0 AFTER status;

-- 2. Enhanced Tag attributes (topics & causes)
ALTER TABLE tags ADD COLUMN description TEXT NULL AFTER name;
ALTER TABLE tags ADD COLUMN image_path VARCHAR(255) NULL AFTER description;
ALTER TABLE tags ADD COLUMN image_alt VARCHAR(255) NULL AFTER image_path;
ALTER TABLE tags ADD COLUMN show_as_topic TINYINT NOT NULL DEFAULT 0 AFTER image_alt;
ALTER TABLE tags ADD COLUMN show_as_cause TINYINT NOT NULL DEFAULT 0 AFTER show_as_topic;
ALTER TABLE tags ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER show_as_cause;

-- 3. Subscribers table
CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(254) NOT NULL UNIQUE,
    consent_at DATETIME NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    source VARCHAR(30) NOT NULL DEFAULT 'website',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_subscribers_created (created_at),
    INDEX idx_subscribers_ip (ip_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Subscription rate limiting table
CREATE TABLE IF NOT EXISTS subscribe_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_hash CHAR(64) NOT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sub_rate_ip_time (ip_hash, recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Home Page Settings defaults
INSERT INTO settings (`key`, `value`) VALUES
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
    ('section_intro_rti', 'Our ongoing investigations and RTI appeals uncovering wetland encroachments and environmental governance issues.'),
    ('section_intro_newsletter', 'Get our latest research, campaign dispatches, and RTI findings delivered straight to your inbox.'),
    ('section_intro_donate', 'Your support powers our independent ground investigations, legal notices, and conservation advocacy.'),
    ('section_show_announcement', '0'),
    ('section_show_latest', '1'),
    ('section_show_topics', '1'),
    ('section_show_causes', '1'),
    ('section_show_campaigns', '1'),
    ('section_show_rti', '1'),
    ('section_show_gallery', '1'),
    ('section_show_newsletter', '1'),
    ('section_show_donate', '1')
ON DUPLICATE KEY UPDATE `key` = `key`;
