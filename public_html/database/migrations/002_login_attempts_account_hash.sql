-- NatConnect Foundation — Migration 002: Add account_hash to login_attempts
-- For databases created before account_hash existed.
-- Adds account_hash column and idx_login_attempts_account_time index ONLY if missing.
-- Compatible with MySQL 8.x and MariaDB 10.11+.

DROP PROCEDURE IF EXISTS migrate_002_add_account_hash;

CREATE PROCEDURE migrate_002_add_account_hash()
BEGIN
  -- Add account_hash column if it does not exist
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'login_attempts'
      AND COLUMN_NAME = 'account_hash'
  ) THEN
    ALTER TABLE `login_attempts`
      ADD COLUMN `account_hash` VARCHAR(64) NULL AFTER `ip_hash`;
  END IF;

  -- Add idx_login_attempts_account_time index if it does not exist
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'login_attempts'
      AND INDEX_NAME = 'idx_login_attempts_account_time'
  ) THEN
    ALTER TABLE `login_attempts`
      ADD INDEX `idx_login_attempts_account_time` (`account_hash`, `attempted_at`);
  END IF;
END;

CALL migrate_002_add_account_hash();

DROP PROCEDURE IF EXISTS migrate_002_add_account_hash();
