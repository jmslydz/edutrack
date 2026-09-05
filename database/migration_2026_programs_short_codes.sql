-- ============================================================================
-- EduTrack — Feature: Course short codes + auto-suggested section names
-- Migration (Part 1). Run against the `edutrack` database.
--
-- Adds `programs.short_code` — a short, admin-authoritative course code
-- (e.g. "IT" for BSIT) used to auto-suggest section names in the form
-- `{short_code}{year_level}R{sequence}` (e.g. "IT4R1").
--
-- The value is set explicitly by the admin on the Manage Courses page
-- (editable, authoritative) and initialized with a suggestion by the
-- backfill script. Unique per program.
-- ============================================================================

ALTER TABLE programs
    ADD COLUMN short_code VARCHAR(10) NULL AFTER program_code,
    ADD UNIQUE KEY uq_program_shortcode (short_code);