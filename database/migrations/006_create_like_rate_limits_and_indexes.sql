-- NatConnect Foundation — Migration 006: Like Rate Limits & Pruning Index
-- Purpose: Track like actions for rate limiting (60/hour/IP) and optimize 90-day view pruning.

CREATE TABLE IF NOT EXISTS `like_rate_limits` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip_hash` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_like_rate_limits_ip_created` (`ip_hash`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `post_views` ADD INDEX `idx_post_views_viewed_on` (`viewed_on`);
