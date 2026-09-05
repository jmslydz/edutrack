-- =====================================================================
-- Ticket System Redesign - Recipient Routing
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE tickets
    ADD COLUMN recipient_type ENUM('admin','teacher') NOT NULL DEFAULT 'admin' AFTER submitted_by,
    ADD COLUMN recipient_id INT UNSIGNED NULL AFTER recipient_type,
    ADD CONSTRAINT fk_tickets_recipient FOREIGN KEY (recipient_id) REFERENCES users(id);

SET FOREIGN_KEY_CHECKS = 1;