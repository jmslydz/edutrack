-- =====================================================================
-- Admissions: applicant records + admission exam (migration)
-- Run manually against the live DB, then keep in sync with edutrack.sql.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `applicants` (
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

CREATE TABLE IF NOT EXISTS `exam_questions` (
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

CREATE TABLE IF NOT EXISTS `exam_answers` (
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