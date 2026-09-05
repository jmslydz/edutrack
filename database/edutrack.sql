-- =====================================================================
-- EduTrack Academic Records System - MySQL schema + seed data
-- Canonical schema file — must always match the live database.
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `applicants`;
DROP TABLE IF EXISTS `exam_answers`;
DROP TABLE IF EXISTS `exam_questions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `ticket_replies`;
DROP TABLE IF EXISTS `teacher_subject_assignments`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `semesters`;
DROP TABLE IF EXISTS `sections`;
DROP TABLE IF EXISTS `section_subjects`;
DROP TABLE IF EXISTS `programs`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `grading_periods`;
DROP TABLE IF EXISTS `grades`;
DROP TABLE IF EXISTS `grade_logs`;
DROP TABLE IF EXISTS `grade_correction_requests`;
DROP TABLE IF EXISTS `enrollments`;
DROP TABLE IF EXISTS `curriculum_subjects`;

-- -----------------------------------------------------------
-- curriculum_subjects
-- -----------------------------------------------------------
CREATE TABLE `curriculum_subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned NOT NULL,
  `year_level` tinyint(4) NOT NULL,
  `semester_number` tinyint(4) NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curriculum` (`program_id`,`year_level`,`semester_number`,`subject_id`),
  KEY `fk_curriculum_subject` (`subject_id`),
  CONSTRAINT `fk_curriculum_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`),
  CONSTRAINT `fk_curriculum_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=278 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- enrollments
-- -----------------------------------------------------------
CREATE TABLE `enrollments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `semester_id` int(10) unsigned NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `enrolled_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment` (`student_id`,`semester_id`),
  KEY `idx_enroll_student` (`student_id`),
  KEY `idx_enroll_section` (`section_id`),
  KEY `fk_enroll_sem` (`semester_id`),
  CONSTRAINT `fk_enroll_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `fk_enroll_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `fk_enroll_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- grade_correction_requests
-- -----------------------------------------------------------
CREATE TABLE `grade_correction_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_user_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `grading_period_id` int(10) unsigned NOT NULL,
  `old_value` decimal(4,2) DEFAULT NULL,
  `requested_value` decimal(4,2) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pending_correction` (`teacher_user_id`,`student_id`,`subject_id`,`grading_period_id`,`status`),
  KEY `fk_correction_student` (`student_id`),
  KEY `fk_correction_subject` (`subject_id`),
  KEY `fk_correction_period` (`grading_period_id`),
  KEY `fk_correction_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_correction_period` FOREIGN KEY (`grading_period_id`) REFERENCES `grading_periods` (`id`),
  CONSTRAINT `fk_correction_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_correction_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `fk_correction_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `fk_correction_teacher` FOREIGN KEY (`teacher_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- grade_logs
-- -----------------------------------------------------------
CREATE TABLE `grade_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `grade_id` int(10) unsigned NOT NULL,
  `old_value` decimal(4,2) DEFAULT NULL,
  `new_value` decimal(4,2) DEFAULT NULL,
  `changed_by` int(10) unsigned NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gradelogs_grade` (`grade_id`),
  KEY `idx_gradelogs_by` (`changed_by`),
  CONSTRAINT `fk_gradelogs_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_gradelogs_grade` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=578 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- grades
-- -----------------------------------------------------------
CREATE TABLE `grades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `teacher_id` int(10) unsigned NOT NULL,
  `grading_period_id` int(10) unsigned NOT NULL,
  `grade_value` decimal(4,2) DEFAULT NULL,
  `date_recorded` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grade` (`student_id`,`subject_id`,`grading_period_id`),
  KEY `idx_grades_student` (`student_id`),
  KEY `idx_grades_subject` (`subject_id`),
  KEY `idx_grades_period` (`grading_period_id`),
  KEY `fk_grades_teacher` (`teacher_id`),
  CONSTRAINT `fk_grades_period` FOREIGN KEY (`grading_period_id`) REFERENCES `grading_periods` (`id`),
  CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `fk_grades_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `fk_grades_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=674 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- grading_periods
-- -----------------------------------------------------------
CREATE TABLE `grading_periods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `semester_id` int(10) unsigned NOT NULL,
  `period_name` enum('Midterm','Final') NOT NULL,
  `weight_percent` decimal(5,2) NOT NULL DEFAULT 50.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grading_periods` (`semester_id`,`period_name`),
  CONSTRAINT `fk_grading_periods_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- login_attempts
-- -----------------------------------------------------------
CREATE TABLE `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(120) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_email_time` (`email`,`attempted_at`),
  KEY `idx_login_attempts_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=338 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- notifications
-- -----------------------------------------------------------
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` enum('system','announcement') NOT NULL DEFAULT 'system',
  `title` varchar(120) NOT NULL,
  `body` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- password_resets
-- -----------------------------------------------------------
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(120) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_token` (`token_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- programs
-- -----------------------------------------------------------
CREATE TABLE `programs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_code` varchar(20) NOT NULL,
  `short_code` varchar(10) DEFAULT NULL,
  `program_name` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_program_code` (`program_code`),
  UNIQUE KEY `uq_program_shortcode` (`short_code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- section_subjects
-- -----------------------------------------------------------
CREATE TABLE `section_subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `semester_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_section_subject` (`section_id`,`subject_id`,`semester_id`),
  KEY `fk_secsub_subject` (`subject_id`),
  KEY `fk_secsub_semester` (`semester_id`),
  CONSTRAINT `fk_secsub_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `fk_secsub_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `fk_secsub_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1062 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- sections
-- -----------------------------------------------------------
CREATE TABLE `sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `program_id` int(10) unsigned DEFAULT NULL,
  `year_level` tinyint(4) DEFAULT NULL,
  `building_id` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sections_name` (`name`),
  KEY `fk_sections_program` (`program_id`),
  KEY `fk_sections_building` (`building_id`),
  CONSTRAINT `fk_sections_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`),
  CONSTRAINT `fk_sections_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- semesters
-- -----------------------------------------------------------
CREATE TABLE `semesters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `year_label` varchar(20) DEFAULT NULL,
  `semester_number` tinyint(4) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `enrollment_deadline` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- students
-- -----------------------------------------------------------
CREATE TABLE `students` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `student_no` varchar(30) NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_students_user` (`user_id`),
  UNIQUE KEY `uq_students_no` (`student_no`),
  KEY `idx_students_section` (`section_id`),
  CONSTRAINT `fk_students_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- subjects
-- -----------------------------------------------------------
CREATE TABLE `subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `title` varchar(120) NOT NULL,
  `units` decimal(3,1) NOT NULL DEFAULT 3.0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subjects_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- teacher_subject_assignments
-- -----------------------------------------------------------
CREATE TABLE `teacher_subject_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_user_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `semester_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `day_bits` tinyint(3) unsigned DEFAULT NULL COMMENT 'bit 0=Mon .. bit 4=Fri',
  `start_min` smallint(5) DEFAULT NULL COMMENT 'minutes from midnight',
  `end_min` smallint(5) DEFAULT NULL,
  `schedule` varchar(120) DEFAULT NULL,
  `room` varchar(60) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assign` (`teacher_user_id`,`subject_id`,`section_id`,`semester_id`),
  KEY `idx_assign_teacher` (`teacher_user_id`),
  KEY `idx_assign_section` (`section_id`),
  KEY `fk_assign_subject` (`subject_id`),
  KEY `fk_assign_sem` (`semester_id`),
  KEY `fk_assign_room` (`room_id`),
  CONSTRAINT `fk_assign_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `fk_assign_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `fk_assign_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `fk_assign_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `fk_assign_teacher` FOREIGN KEY (`teacher_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- buildings
-- -----------------------------------------------------------
CREATE TABLE `buildings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_buildings_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- rooms
-- -----------------------------------------------------------
CREATE TABLE `rooms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `building_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rooms_name_building` (`name`,`building_id`),
  KEY `fk_rooms_building` (`building_id`),
  CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- ticket_replies
-- -----------------------------------------------------------
CREATE TABLE `ticket_replies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(10) unsigned NOT NULL,
  `replied_by` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_replies_ticket` (`ticket_id`),
  KEY `fk_replies_user` (`replied_by`),
  CONSTRAINT `fk_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  CONSTRAINT `fk_replies_user` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- tickets
-- -----------------------------------------------------------
CREATE TABLE `tickets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `submitted_by` int(10) unsigned NOT NULL,
  `recipient_type` enum('admin','teacher','student') NOT NULL DEFAULT 'admin',
  `recipient_id` int(10) unsigned DEFAULT NULL,
  `category` enum('Technical Issue','Grade Concern','Account Issue','Other') NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Open','In Progress','Resolved') NOT NULL DEFAULT 'Open',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tickets_user` (`submitted_by`),
  KEY `fk_tickets_recipient` (`recipient_id`),
  CONSTRAINT `fk_tickets_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_tickets_user` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- users
-- -----------------------------------------------------------
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student','applicant') NOT NULL DEFAULT 'student',
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA
-- =====================================================================

INSERT INTO programs (program_code, short_code, program_name) VALUES
  ('STEM', 'STEM', 'Science, Technology, Engineering, and Mathematics'),
  ('ABM', 'ABM', 'Accountancy, Business, and Management'),
  ('HUMSS', 'HUMSS', 'Humanities and Social Sciences'),
  ('GAS', 'GAS', 'General Academic Strand');

INSERT INTO users (username, email, password_hash, role, first_name, last_name, status, must_change_password) VALUES
  ('admin', 'admin@school.edu', '$2y$10$Qo9sKlvfJYNeAQwTtFzJDODHq3jSJva9QvAPx1MLSdyB4RFWfnbY2', 'admin', 'Administrator', 'EduTrack', 'active', 0),
  ('mlopez', 'mlopez@school.edu', '$2y$10$bQHebMVss8VYMgMjmEfBm.DKkWZLGZ2/W.5sdAWe/pxReLqvlRW2e', 'teacher', 'Maria', 'Lopez', 'active', 0),
  ('msantos', 'msantos@school.edu', '$2y$10$bQHebMVss8VYMgMjmEfBm.DKkWZLGZ2/W.5sdAWe/pxReLqvlRW2e', 'teacher', 'Marco', 'Santos', 'active', 0),
  ('kreyes', 'kreyes@school.edu', '$2y$10$bQHebMVss8VYMgMjmEfBm.DKkWZLGZ2/W.5sdAWe/pxReLqvlRW2e', 'teacher', 'Karen', 'Reyes', 'active', 0),
  ('cbautista', 'cbautista@school.edu', '$2y$10$bQHebMVss8VYMgMjmEfBm.DKkWZLGZ2/W.5sdAWe/pxReLqvlRW2e', 'teacher', 'Carlos', 'Bautista', 'active', 0),
  ('jfernandez', 'jfernandez@school.edu', '$2y$10$bQHebMVss8VYMgMjmEfBm.DKkWZLGZ2/W.5sdAWe/pxReLqvlRW2e', 'teacher', 'Jose', 'Fernandez', 'active', 0),
  ('avillanueva', 'avillanueva@school.edu', '$2y$10$bQHebMVss8VYMgMjmEfBm.DKkWZLGZ2/W.5sdAWe/pxReLqvlRW2e', 'teacher', 'Ana', 'Villanueva', 'inactive', 0),
  ('james5x931', 'jameslloydtampipiclosas@gmail.com', '$2y$10$F8jZYU7PEnVxe5Mydw7v4.4tOwbQJUDLnryQ4DDvT3fvdpOAsBrze', 'student', 'James Lloyd', 'Closas', 'active', 0),
  ('maria.santos', 'maria.santos@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Maria', 'Santos', 'active', 0),
  ('juan.dela.cruz', 'juan.delacruz@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Juan', 'Dela Cruz', 'active', 0),
  ('ana.reyes', 'ana.reyes@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Ana', 'Reyes', 'active', 0),
  ('carlos.garcia', 'carlos.garcia@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Carlos', 'Garcia', 'active', 0),
  ('rose.torres', 'rose.torres@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Rose', 'Torres', 'active', 0),
  ('miguel.lim', 'miguel.lim@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Miguel', 'Lim', 'active', 0),
  ('sofia.rivera', 'sofia.rivera@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Sofia', 'Rivera', 'active', 0),
  ('joshua.mendoza', 'joshua.mendoza@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Joshua', 'Mendoza', 'active', 0),
  ('patricia.cruz', 'patricia.cruz@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Patricia', 'Cruz', 'active', 0),
  ('angelo.bautista', 'angelo.bautista@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Angelo', 'Bautista', 'active', 0),
  ('camille.villanueva', 'camille.villanueva@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Camille', 'Villanueva', 'active', 0),
  ('daniel.fernandez', 'daniel.fernandez@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Daniel', 'Fernandez', 'active', 0),
  ('nicole.pascual', 'nicole.pascual@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Nicole', 'Pascual', 'active', 0),
  ('bianca.aquino', 'bianca.aquino@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Bianca', 'Aquino', 'active', 0),
  ('rafael.morales', 'rafael.morales@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Rafael', 'Morales', 'active', 0),
  ('ivana.gonzales', 'ivana.gonzales@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Ivana', 'Gonzales', 'active', 0),
  ('mark.santiago', 'mark.santiago@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Mark', 'Santiago', 'active', 0),
  ('chloe.diaz', 'chloe.diaz@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Chloe', 'Diaz', 'active', 0),
  ('kevin.ramos', 'kevin.ramos@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Kevin', 'Ramos', 'active', 0),
  ('princess.torres', 'princess.torres@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Princess', 'Torres', 'active', 0),
  ('andre.castro', 'andre.castro@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Andre', 'Castro', 'active', 0),
  ('angel.navarro', 'angel.navarro@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Angel', 'Navarro', 'active', 0),
  ('jerome.padilla', 'jerome.padilla@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Jerome', 'Padilla', 'active', 0),
  ('trisha.gomez', 'trisha.gomez@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Trisha', 'Gomez', 'active', 0),
  ('luis.hernandez', 'luis.hernandez@student.edu', '$2y$10$rZhdYimosI565Y.StvCvouBQ4UWOtLqGPrrsdEtjmaMO8OgUUm.ue', 'student', 'Luis', 'Hernandez', 'active', 0);

INSERT INTO semesters (id, name, year_label, semester_number, is_active, enrollment_deadline, created_at) VALUES
  (1, '1st Semester', 'S.Y. 2025-2026', 1, 1, NULL, CURRENT_TIMESTAMP),
  (2, '2nd Semester', 'S.Y. 2025-2026', 2, 0, NULL, CURRENT_TIMESTAMP),
  (3, 'Summer', 'S.Y. 2025-2026', NULL, 0, NULL, CURRENT_TIMESTAMP),
  (4, '1st Semester', 'S.Y. 2024-2025', 1, 0, NULL, CURRENT_TIMESTAMP),
  (5, '2nd Semester', 'S.Y. 2024-2025', 2, 0, NULL, CURRENT_TIMESTAMP),
  (6, 'Summer', 'S.Y. 2024-2025', NULL, 0, NULL, CURRENT_TIMESTAMP),
  (7, '1st Semester', 'S.Y. 2026-2027', 1, 0, NULL, CURRENT_TIMESTAMP),
  (8, '1st Semester', 'S.Y. 2027-2028', 1, 0, NULL, CURRENT_TIMESTAMP);

INSERT INTO grading_periods (semester_id, period_name, weight_percent) VALUES
  (1, 'Midterm', 50.00),
  (1, 'Final', 50.00),
  (2, 'Midterm', 50.00),
  (2, 'Final', 50.00),
  (3, 'Midterm', 50.00),
  (3, 'Final', 50.00),
  (4, 'Midterm', 50.00),
  (4, 'Final', 50.00),
  (5, 'Midterm', 50.00),
  (5, 'Final', 50.00),
  (6, 'Midterm', 50.00),
  (6, 'Final', 50.00);

INSERT INTO subjects (code, title, units) VALUES
  ('ORALCOM', 'Oral Communication in Context', 3.0),
  ('KOMFIL', 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino', 3.0),
  ('GENMATH', 'General Mathematics', 3.0),
  ('ELS', 'Earth and Life Science', 3.0),
  ('PERDEV', 'Personal Development', 3.0),
  ('UCSP', 'Understanding Culture, Society and Politics', 3.0),
  ('PEH1', 'Physical Education and Health 1', 2.0),
  ('READWRIT', 'Reading and Writing', 3.0),
  ('LIT21', '21st Century Literature from the Philippines and the World', 3.0),
  ('STATPROB', 'Statistics and Probability', 3.0),
  ('PHYSCI', 'Physical Science', 3.0),
  ('PHILO', 'Introduction to the Philosophy of the Human Person', 3.0),
  ('MIL', 'Media and Information Literacy', 3.0),
  ('PEH2', 'Physical Education and Health 2', 2.0),
  ('CPA', 'Contemporary Philippine Arts from the Regions', 3.0),
  ('PEH3', 'Physical Education and Health 3', 2.0),
  ('PEH4', 'Physical Education and Health 4', 2.0),
  ('EAPP', 'English for Academic and Professional Purposes', 3.0),
  ('EMPTECH', 'Empowerment Technologies', 3.0),
  ('PRACRES1', 'Practical Research 1', 3.0),
  ('PRACRES2', 'Practical Research 2', 3.0),
  ('PWKD', 'Pagsulat sa Filipino sa Piling Larangan', 3.0),
  ('INQUIRY', 'Inquiries, Investigation and Immersion', 3.0),
  ('STEM-PRECAL', 'Pre-Calculus', 3.0),
  ('STEM-CHEM1', 'General Chemistry 1', 3.0),
  ('STEM-BIO1', 'General Biology 1', 3.0),
  ('STEM-CALC', 'Basic Calculus', 3.0),
  ('STEM-PHY1', 'General Physics 1', 3.0),
  ('STEM-BIO2', 'General Biology 2', 3.0),
  ('STEM-CHEM2', 'General Chemistry 2', 3.0),
  ('STEM-PHY2', 'General Physics 2', 3.0),
  ('STEM-RES', 'Research/Capstone Project', 3.0),
  ('BUSMATH', 'Business Mathematics', 3.0),
  ('FABM1', 'Fundamentals of Accountancy, Business and Management 1', 3.0),
  ('ORGMAN', 'Organization and Management', 3.0),
  ('FABM2', 'Fundamentals of Accountancy, Business and Management 2', 3.0),
  ('MARKTNG', 'Principles of Marketing', 3.0),
  ('APECON', 'Applied Economics', 3.0),
  ('BIZFIN', 'Business Finance', 3.0),
  ('BIZETH', 'Business Ethics and Social Responsibility', 3.0),
  ('BIZSIM', 'Business Enterprise Simulation', 3.0),
  ('PHILPOL', 'Philippine Politics and Governance', 3.0),
  ('INTWORL', 'Introduction to World Religions and Belief Systems', 3.0),
  ('TRENDS', 'Trends, Networks, and Critical Thinking in the 21st Century', 3.0),
  ('COMMENG', 'Community Engagement, Solidarity, and Citizenship', 3.0),
  ('DISS', 'Disciplines and Ideas in the Social Sciences', 3.0),
  ('DIASS', 'Disciplines and Ideas in the Applied Social Sciences', 3.0),
  ('CRENON', 'Creative Nonfiction', 3.0),
  ('HUMSRES', 'Humanities and Social Sciences Research/Capstone', 3.0),
  ('CREWRI', 'Creative Writing', 3.0),
  ('GASRES', 'Academic Research Project', 3.0),
  ('ASDA', 'asdads', 3.0);

CREATE TABLE `applicants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `status` enum('pending_exam','passed_exam','failed_exam','admitted','rejected') NOT NULL DEFAULT 'pending_exam',
  `exam_code` varchar(40) DEFAULT NULL,
  `exam_started_at` datetime DEFAULT NULL,
  `exam_finished_at` datetime DEFAULT NULL,
  `exam_score` int(11) DEFAULT NULL,
  `exam_total` int(11) DEFAULT NULL,
  `exam_passed` tinyint(1) DEFAULT NULL,
  `preferred_program_id` int(10) unsigned DEFAULT NULL,
  `admitted_section_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_applicants_user` (`user_id`),
  KEY `fk_applicants_program` (`preferred_program_id`),
  CONSTRAINT `fk_applicants_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_applicants_program` FOREIGN KEY (`preferred_program_id`) REFERENCES `programs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `exam_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) NOT NULL,
  `option_d` varchar(500) NOT NULL,
  `correct_answer` enum('A','B','C','D') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `exam_answers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `applicant_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned NOT NULL,
  `answer` enum('A','B','C','D') DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_answers` (`applicant_id`,`question_id`),
  KEY `fk_exam_answers_q` (`question_id`),
  CONSTRAINT `fk_exam_answers_a` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exam_answers_q` FOREIGN KEY (`question_id`) REFERENCES `exam_questions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
