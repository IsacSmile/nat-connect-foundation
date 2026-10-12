-- NatConnect Foundation — Migration 005: Clear Category & Setting Placeholder Descriptions
-- Purpose: Remove [CLIENT COPY] strings from categories and settings so public pages do not display placeholder text.

UPDATE `categories` 
SET `description` = NULL 
WHERE `description` LIKE '[CLIENT COPY%';

UPDATE `settings` 
SET `value` = '' 
WHERE `value` LIKE '[CLIENT COPY%';
