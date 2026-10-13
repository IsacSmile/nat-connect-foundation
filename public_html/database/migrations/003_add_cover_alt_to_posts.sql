-- NatConnect Foundation — Migration 003: Add cover_alt column to posts
-- Purpose: Required alt text for post cover images (accessibility + SEO)

ALTER TABLE `posts`
  ADD COLUMN `cover_alt` VARCHAR(255) NULL AFTER `cover_image`;
