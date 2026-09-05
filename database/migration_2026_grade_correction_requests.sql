-- =====================================================================
-- Grade Correction Requests Table
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS grade_correction_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_user_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    grading_period_id INT UNSIGNED NOT NULL,
    old_value DECIMAL(4,2) NULL,
    requested_value DECIMAL(4,2) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','denied') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pending_correction (teacher_user_id, student_id, subject_id, grading_period_id, status),
    CONSTRAINT fk_correction_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id),
    CONSTRAINT fk_correction_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_correction_subject FOREIGN KEY (subject_id) REFERENCES subjects(id),
    CONSTRAINT fk_correction_period FOREIGN KEY (grading_period_id) REFERENCES grading_periods(id),
    CONSTRAINT fk_correction_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;