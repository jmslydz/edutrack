-- =====================================================================
-- Add school_year_id to teacher_subject_assignments
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE teacher_subject_assignments
    ADD COLUMN school_year_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER section_id,
    ADD CONSTRAINT fk_assign_sy FOREIGN KEY (school_year_id) REFERENCES school_years (id);

SET FOREIGN_KEY_CHECKS = 1;