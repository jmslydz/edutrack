-- database/migration_2026_enrollments.sql
-- Self-enrollment history table. Additive only — does not alter any
-- existing table, column, or constraint.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS enrollments (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id     INT UNSIGNED NOT NULL,
  school_year_id INT UNSIGNED NOT NULL,
  semester_id    INT UNSIGNED NOT NULL,
  section_id     INT UNSIGNED NOT NULL,
  enrolled_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_enrollment (student_id, semester_id),
  KEY idx_enroll_student (student_id),
  KEY idx_enroll_section (section_id),
  CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES students(id),
  CONSTRAINT fk_enroll_sy      FOREIGN KEY (school_year_id) REFERENCES school_years(id),
  CONSTRAINT fk_enroll_sem     FOREIGN KEY (semester_id) REFERENCES semesters(id),
  CONSTRAINT fk_enroll_section FOREIGN KEY (section_id) REFERENCES sections(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;