-- =====================================================================
-- Add type column to notifications table
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE notifications
    ADD COLUMN type ENUM('system', 'announcement') NOT NULL DEFAULT 'system' AFTER link;

SET FOREIGN_KEY_CHECKS = 1;