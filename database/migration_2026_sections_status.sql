-- ------------------------------------------------------------------
-- EduTrack — sections status migration (Sep 2026)
--
-- Adds sections.is_active (1 = active/available for new enrollments,
-- 0 = inactive). Inactive sections and their historical data are kept
-- intact; they simply stop appearing in enrollment/section pickers.
--
-- Idempotent: safe to re-run (checks information_schema first),
-- because MySQL/MariaDB do not support "ADD COLUMN IF NOT EXISTS".
--
--   mysql -u root -P 3307 edutrack < database/migration_2026_sections_status.sql
-- ------------------------------------------------------------------

SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sections'
    AND COLUMN_NAME = 'is_active'
);

SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE sections ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER year_level',
  'SELECT \'sections.is_active already exists — nothing to do\'');

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;