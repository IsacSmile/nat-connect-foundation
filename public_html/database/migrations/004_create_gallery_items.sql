-- NatConnect Foundation — Migration 004: Create gallery_items table
-- Purpose: Photo gallery items with ordering and accessibility metadata

CREATE TABLE IF NOT EXISTS `gallery_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(255) NOT NULL,
  `caption` TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_gallery_sort` (`sort_order`, `created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
