-- Migration 011: Causes Page Settings
-- Adds causes_intro setting with default empty string

INSERT INTO settings (`key`, `value`) VALUES
    ('causes_intro', '')
ON DUPLICATE KEY UPDATE `key` = `key`;
