-- ============================================================================
-- EduTrack — Pivot: College Programs -> Senior High School Strands
-- PART 3 — Seed the 4 SHS strands. `programs` is empty after delete_2026_college_data.php.
--
-- For SHS the `short_code` IS the strand name itself (STEM, ABM, HUMSS, GAS).
-- Idempotent: uses INSERT IGNORE so re-running never duplicates a strand.
-- ============================================================================

INSERT IGNORE INTO programs (program_code, program_name, short_code) VALUES
('STEM', 'Science, Technology, Engineering, and Mathematics', 'STEM'),
('ABM', 'Accountancy, Business, and Management', 'ABM'),
('HUMSS', 'Humanities and Social Sciences', 'HUMSS'),
('GAS', 'General Academic Strand', 'GAS');