-- =====================================================================
-- Add school_year_id to semesters
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE semesters
    ADD COLUMN school_year_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER name,
    ADD KEY idx_semesters_sy (school_year_id),
    ADD CONSTRAINT fk_semesters_sy FOREIGN KEY (school_year_id) REFERENCES school_years (id);

SET FOREIGN_KEY_CHECKS = 1;