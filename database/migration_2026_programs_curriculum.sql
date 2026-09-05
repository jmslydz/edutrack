-- ============================================================================
-- EduTrack — Feature: Programs, Curriculum & Auto-Populated Section Subjects
-- Migration (Part 1 of the feature). Run against the `edutrack` database.
--
-- Corrections to the original draft, approved:
--   * All new FK columns are INT UNSIGNED to match the referenced columns
--     (subjects.id, sections.id, semesters.id are INT UNSIGNED).
--   * `ALTER TABLE ... AFTER name` because the real column is `name`, not
--     `term_name` / `section_name`.
-- ============================================================================

CREATE TABLE IF NOT EXISTS programs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  program_code VARCHAR(20) NOT NULL,
  program_name VARCHAR(150) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_program_code (program_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curriculum_subjects (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  program_id INT UNSIGNED NOT NULL,
  year_level TINYINT NOT NULL,
  semester_number TINYINT NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_curriculum (program_id, year_level, semester_number, subject_id),
  CONSTRAINT fk_curriculum_program FOREIGN KEY (program_id) REFERENCES programs (id),
  CONSTRAINT fk_curriculum_subject FOREIGN KEY (subject_id) REFERENCES subjects (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS section_subjects (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  section_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  semester_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_section_subject (section_id, subject_id, semester_id),
  CONSTRAINT fk_secsub_section FOREIGN KEY (section_id) REFERENCES sections (id),
  CONSTRAINT fk_secsub_subject FOREIGN KEY (subject_id) REFERENCES subjects (id),
  CONSTRAINT fk_secsub_semester FOREIGN KEY (semester_id) REFERENCES semesters (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE sections
  ADD COLUMN program_id INT UNSIGNED NULL AFTER name,
  ADD COLUMN year_level TINYINT NULL AFTER program_id,
  ADD CONSTRAINT fk_sections_program FOREIGN KEY (program_id) REFERENCES programs (id);

ALTER TABLE semesters
  ADD COLUMN semester_number TINYINT NULL AFTER name;