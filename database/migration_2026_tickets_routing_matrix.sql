-- Migration: Ticket Routing Matrix 2026
-- Adds recipient_type/recipient_id columns and updates ENUM values
-- to support Teacher→Student and Student→Teacher ticket paths.
--
-- BACKUP FIRST: mysqldump -u root -p edutrack > edutrack_backup_$(date +%Y%m%d).sql
-- Run: mysql -u root -p edutrack < migration_2026_tickets_routing_matrix.sql

USE edutrack;

-- 1. Add recipient_type and recipient_id if they don't exist
ALTER TABLE tickets 
ADD COLUMN IF NOT EXISTS recipient_type ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'admin' 
AFTER submitted_by;

ALTER TABLE tickets 
ADD COLUMN IF NOT EXISTS recipient_id INT(11) NULL DEFAULT NULL 
AFTER recipient_type;

-- 2. Update recipient_type ENUM to include all three values (idempotent)
ALTER TABLE tickets 
MODIFY COLUMN recipient_type ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'admin';

-- 3. Update category ENUM to add 'Missing Activity' for teacher→student messages
ALTER TABLE tickets 
MODIFY COLUMN category ENUM(
    'Technical Issue',
    'Grade Concern',
    'Account Issue',
    'Account Access',
    'Other',
    'Missing Activity'
) NOT NULL;

-- 4. Add foreign key for recipient_id → users(id) if not exists
-- (Check first to avoid duplicate key error)
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = 'edutrack' 
    AND TABLE_NAME = 'tickets' 
    AND CONSTRAINT_NAME = 'fk_tickets_recipient'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE tickets ADD CONSTRAINT fk_tickets_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "FK already exists" AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Add index on (recipient_type, recipient_id) for query performance
CREATE INDEX IF NOT EXISTS idx_tickets_recipient ON tickets(recipient_type, recipient_id);

-- Migration complete
SELECT 'Migration completed successfully. Ticket routing matrix is now enabled.' AS status;
