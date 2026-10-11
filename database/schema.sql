-- NatConnect Foundation Database Schema
-- Compatible with MySQL 8.x / MariaDB 10.4+ on Hostinger Shared Hosting
-- Character set: utf8mb4 | Collation: utf8mb4_unicode_ci | Engine: InnoDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. Users Table
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. Categories Table
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(191) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. Tags Table
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(191) NOT NULL UNIQUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. Posts Table
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(191) NOT NULL UNIQUE,
  `excerpt` TEXT NULL,
  `content` LONGTEXT NULL,
  `cover_type` ENUM('image', 'video') NOT NULL DEFAULT 'image',
  `cover_image` VARCHAR(255) NULL,
  `video_url` VARCHAR(255) NULL,
  `author_name` VARCHAR(150) NULL DEFAULT 'NatConnect Foundation',
  `category_id` INT UNSIGNED NULL,
  `status` ENUM('draft', 'published', 'scheduled') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `likes` INT UNSIGNED NOT NULL DEFAULT 0,
  `read_time_minutes` INT UNSIGNED NOT NULL DEFAULT 1,
  `meta_title` VARCHAR(255) NULL,
  `meta_description` VARCHAR(500) NULL,
  `legacy_url` VARCHAR(500) NULL,
  INDEX `idx_posts_status_published` (`status`, `published_at`),
  INDEX `idx_posts_category_id` (`category_id`),
  CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) 
    REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. Post Tags Pivot Table (Composite PK)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_tags` (
  `post_id` INT UNSIGNED NOT NULL,
  `tag_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`post_id`, `tag_id`),
  INDEX `idx_post_tags_tag` (`tag_id`),
  CONSTRAINT `fk_post_tags_post` FOREIGN KEY (`post_id`) 
    REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_post_tags_tag` FOREIGN KEY (`tag_id`) 
    REFERENCES `tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. Comments Table
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'spam') NOT NULL DEFAULT 'pending',
  `ip_hash` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_comments_post_status` (`post_id`, `status`),
  CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) 
    REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. Post Likes Table (Unique per visitor hash per post)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_likes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT UNSIGNED NOT NULL,
  `visitor_hash` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_post_visitor_like` (`post_id`, `visitor_hash`),
  CONSTRAINT `fk_post_likes_post` FOREIGN KEY (`post_id`) 
    REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. Post Views Table (Unique per visitor hash per post per day)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_views` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT UNSIGNED NOT NULL,
  `visitor_hash` VARCHAR(64) NOT NULL,
  `viewed_on` DATE NOT NULL,
  UNIQUE KEY `uq_post_visitor_view` (`post_id`, `visitor_hash`, `viewed_on`),
  INDEX `idx_post_views_post` (`post_id`),
  CONSTRAINT `fk_post_views_post` FOREIGN KEY (`post_id`) 
    REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 9. Redirects Table (For legacy migration and custom 301s)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `redirects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `old_path` VARCHAR(255) NOT NULL UNIQUE,
  `new_path` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 10. Settings Table
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(100) NOT NULL PRIMARY KEY,
  `value` LONGTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 11. Login Attempts Table (Rate limiting / Brute force protection)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip_hash` VARCHAR(64) NOT NULL,
  `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_login_attempts_ip_time` (`ip_hash`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
