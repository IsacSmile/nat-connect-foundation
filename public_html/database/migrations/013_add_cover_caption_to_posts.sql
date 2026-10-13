-- NatConnect Foundation — Migration 013: Add cover_caption to posts
-- Purpose: Store optional photo credit/caption for post cover image

ALTER TABLE `posts`
  ADD COLUMN `cover_caption` VARCHAR(500) NULL AFTER `cover_alt`;
