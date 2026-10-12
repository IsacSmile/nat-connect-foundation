-- NatConnect Foundation — Database Migration 009: Add sort_order to categories
ALTER TABLE `categories` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `description`;

-- Default order: Causes, News, Campaigns, RTI Applications
UPDATE `categories` SET `sort_order` = 1 WHERE `slug` = 'causes';
UPDATE `categories` SET `sort_order` = 2 WHERE `slug` = 'news';
UPDATE `categories` SET `sort_order` = 3 WHERE `slug` = 'campaigns';
UPDATE `categories` SET `sort_order` = 4 WHERE `slug` IN ('rti-applications', 'right-to-information', 'rti');
