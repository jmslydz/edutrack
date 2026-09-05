<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Academic_model
 *
 * Lookups for the shared reference data: sections, subjects, school
 * years and semesters. Used by dashboards, the grade encoder and the
 * reports page.
 */
class Academic_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * The currently active semester.
	 * @return object|null
	 */
	public function active_semesters()
	{
		return $this->db->where('is_active', 1)->get('semesters')->row();
	}

	/**
	 * The active semester's name, e.g. "2nd Semester".
	 * @return string
	 */
	public function active_semesters_name()
	{
		$sem = $this->active_semesters();
		return $sem ? $sem->name : '—';
	}

	/**
	 * The active semester's year label, e.g. "S.Y. 2025-2026".
	 * @return string
	 */
	public function active_year_label()
	{
		$sem = $this->active_semesters();
		return $sem && $sem->year_label ? $sem->year_label : '—';
	}

	/**
	 * The active school year (derived from the active semester's year_label).
	 * @return object|null
	 */
	public function active_school_year()
	{
		$sem = $this->active_semesters();
		if ( ! $sem)
		{
			return NULL;
		}
		// No separate school_years table — year info lives in semesters.year_label
		// Return a compatible object so callers that need $sy->id / $sy->name still work
		return (object) array(
			'id'   => (int) $sem->id,
			'name' => $sem->year_label,
		);
	}

	/**
	 * All sections (optionally active-only for enrollment/section pickers).
	 * @param bool  $only_active when TRUE, inactive sections are excluded
	 * @param array $filters     optional: program_id (int), status (active|inactive)
	 * @return array
	 */
	public function sections($only_active = FALSE, $filters = array())
	{
		if ($only_active)
		{
			$this->db->where('sections.is_active', 1);
		}
		if ( ! empty($filters['program_id']))
		{
			$this->db->where('sections.program_id', (int) $filters['program_id']);
		}
		if ( ! empty($filters['status']) && in_array($filters['status'], array('active', 'inactive'), TRUE))
		{
			$this->db->where('sections.is_active', $filters['status'] === 'active' ? 1 : 0);
		}
		if ( ! empty($filters['year_level']) && in_array((int) $filters['year_level'], array(11, 12), TRUE))
		{
			$this->db->where('sections.year_level', (int) $filters['year_level']);
		}
		if ( ! empty($filters['search']))
		{
			$s = $this->db->escape_like_str(trim($filters['search']));
			$this->db->group_start()
				->like('sections.name', $s)
				->or_like('programs.program_code', $s)
				->group_end();
		}
		return $this->db->select('sections.*, programs.program_code, programs.short_code, buildings.name AS building_name')
			->from('sections')
			->join('programs', 'programs.id = sections.program_id', 'left')
			->join('buildings', 'buildings.id = sections.building_id', 'left')
			->order_by('sections.year_level', 'ASC')
			->order_by('sections.is_active', 'DESC')
			->order_by('name', 'ASC')
			->get()
			->result();
	}

	/**
	 * A section by primary key.
	 * @param int $id
	 * @return object|null
	 */
	public function get_section($id)
	{
		return $this->db->select('sections.*, buildings.name AS building_name')
			->from('sections')
			->join('buildings', 'buildings.id = sections.building_id', 'left')
			->where('sections.id', (int) $id)
			->get()
			->row();
	}

	/**
	 * A section by exact name (case-sensitive to avoid ambiguity).
	 * @param string $name
	 * @return object|null
	 */
	public function section_by_name($name)
	{
		return $this->db->where('name', $name)->get('sections')->row();
	}

	/**
	 * All subjects.
	 * @return array
	 */
	public function subjects()
	{
		return $this->db->order_by('code', 'ASC')->get('subjects')->result();
	}

	/**
	 * A subject by code.
	 * @param string $code
	 * @return object|null
	 */
	public function subject_by_code($code)
	{
		return $this->db->where('code', $code)->get('subjects')->row();
	}

	/**
	 * A subject by primary key.
	 * @param int $id
	 * @return object|null
	 */
	public function get_subject($id)
	{
		return $this->db->where('id', (int) $id)->get('subjects')->row();
	}

	/**
	 * All semesters, most recent first (by year_label DESC, then semester_number).
	 * @return array
	 */
	public function semesters($filter_year = '')
	{
		$filter_year = trim((string) $filter_year);
		if ($filter_year !== '')
		{
			$this->db->where('year_label', $filter_year);
		}
		return $this->db->order_by('year_label', 'DESC')
			->order_by('semester_number', 'ASC')
			->get('semesters')
			->result();
	}

	/**
	 * All distinct year labels in the semesters table.
	 * @return array
	 */
	public function year_labels()
	{
		return $this->db->select('year_label')
			->where('year_label IS NOT NULL', NULL, FALSE)
			->group_by('year_label')
			->order_by('year_label', 'DESC')
			->get('semesters')
			->result();
	}

	/**
	 * A semester by primary key.
	 * @param int $id
	 * @return object|null
	 */
	public function get_semester($id)
	{
		return $this->db->where('id', (int) $id)->get('semesters')->row();
	}

	// -----------------------------------------------------------------
	// Grading periods (Midterm / Final per semester)
	// -----------------------------------------------------------------

	/**
	 * The grading periods for a semester (Midterm, Final).
	 * @param int $semester_id
	 * @return array of objects
	 */
	public function grading_periods_for($semester_id)
	{
		return $this->db->where('semester_id', (int) $semester_id)
			->order_by('id', 'ASC')
			->get('grading_periods')
			->result();
	}

	/**
	 * A single grading period by primary key.
	 * @param int $id
	 * @return object|null
	 */
	public function get_grading_period($id)
	{
		return $this->db->where('id', (int) $id)->get('grading_periods')->row();
	}

	/**
	 * Resolve a grading period id for a semester by period name.
	 * @param int    $semester_id
	 * @param string $period_name 'Midterm' or 'Final'
	 * @return int|null
	 */
	public function grading_period_id($semester_id, $period_name)
	{
		$period_name = ucfirst(strtolower(trim((string) $period_name)));
		$row = $this->db->where('semester_id', (int) $semester_id)
			->where('period_name', $period_name)
			->get('grading_periods')
			->row();
		return $row ? (int) $row->id : NULL;
	}

	// -----------------------------------------------------------------
	// Semesters (write)
	// -----------------------------------------------------------------

	/**
	 * Create a semester (inactive by default).
	 * @param string $name
	 * @param string $year_label   e.g. "S.Y. 2025-2026"
	 * @param int    $semester_number  1, 2, or NULL for Summer
	 * @return int insert id
	 */
	public function create_semester($name, $year_label, $semester_number = NULL)
	{
		$this->db->insert('semesters', array(
			'name'             => $name,
			'year_label'       => $year_label,
			'semester_number'  => $semester_number !== NULL ? (int) $semester_number : NULL,
			'is_active'        => 0,
		));
		$sem_id = (int) $this->db->insert_id();

		// Auto-create grading periods (Midterm + Final) for the new semester
		if ($sem_id > 0)
		{
			$this->_ensure_grading_periods($sem_id);
		}

		return $sem_id;
	}

	/**
	 * Ensure a semester has both Midterm and Final grading periods.
	 * Idempotent — skips if they already exist.
	 * @param int $semester_id
	 */
	public function _ensure_grading_periods($semester_id)
	{
		$existing = $this->db->where('semester_id', (int) $semester_id)
			->count_all_results('grading_periods');
		if ($existing >= 2)
		{
			return;
		}
		$this->db->insert('grading_periods', array(
			'semester_id'     => (int) $semester_id,
			'period_name'     => 'Midterm',
			'weight_percent'  => 50.00,
		));
		$this->db->insert('grading_periods', array(
			'semester_id'     => (int) $semester_id,
			'period_name'     => 'Final',
			'weight_percent'  => 50.00,
		));
	}

	/**
	 * Update a semester's year label, name, and semester number.
	 * @param int    $id
	 * @param string $year_label
	 * @param string $name
	 * @param int|null $semester_number
	 * @return bool
	 */
	public function update_semester($id, $year_label, $name, $semester_number = NULL)
	{
		return $this->db->where('id', (int) $id)->update('semesters', array(
			'year_label'      => $year_label,
			'name'            => $name,
			'semester_number' => $semester_number !== NULL ? (int) $semester_number : NULL,
		)) !== FALSE;
	}

	/**
	 * Delete a semester (caller must guard against FK references).
	 * @param int $id
	 * @return bool
	 */
	public function delete_semester($id)
	{
		return $this->db->where('id', (int) $id)->delete('semesters') !== FALSE;
	}

	/**
	 * Delete a semester plus its (empty) grading periods in one transaction.
	 * Caller must guard: no classes on the semester and no grades on any of
	 * its grading periods.
	 * @param int $id
	 * @return bool
	 */
	public function delete_semester_with_periods($id)
	{
		$id = (int) $id;
		$this->db->trans_start();
		$this->db->where('semester_id', $id)->delete('grading_periods');
		$this->db->where('id', $id)->delete('semesters');
		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	// -----------------------------------------------------------------
	// Sections (write)
	// -----------------------------------------------------------------

	/**
	 * Create a section.
	 * @param string   $name
	 * @param int|null $program_id
	 * @param int|null $year_level
	 * @return int insert id
	 */
	public function create_section($name, $program_id = NULL, $year_level = NULL, $building_id = NULL)
	{
		$this->db->insert('sections', array(
			'name'        => $name,
			'program_id'  => $program_id !== NULL ? (int) $program_id : NULL,
			'year_level'  => $year_level !== NULL ? (int) $year_level : NULL,
			'building_id' => $building_id !== NULL ? (int) $building_id : NULL,
		));
		return (int) $this->db->insert_id();
	}

	/**
	 * Rename a section.
	 * @param int      $id
	 * @param string   $name
	 * @param int|null $program_id
	 * @param int|null $year_level
	 * @return bool
	 */
	public function update_section($id, $name, $program_id = NULL, $year_level = NULL, $building_id = NULL)
	{
		return $this->db->where('id', (int) $id)->update('sections', array(
			'name'        => $name,
			'program_id'  => $program_id !== NULL ? (int) $program_id : NULL,
			'year_level'  => $year_level !== NULL ? (int) $year_level : NULL,
			'building_id' => $building_id !== NULL ? (int) $building_id : NULL,
		)) !== FALSE;
	}

	/**
	 * Sections grouped by their home building.
	 * @return array building_id => list of section rows (with building_name)
	 */
	public function sections_by_building()
	{
		$rows = $this->db->select('sections.*, buildings.name AS building_name')
			->from('sections')
			->join('buildings', 'buildings.id = sections.building_id', 'left')
			->order_by('sections.name', 'ASC')
			->get()
			->result();
		$out = array();
		foreach ($rows as $s)
		{
			$bid = (int) $s->building_id;
			if ( ! isset($out[$bid])) { $out[$bid] = array(); }
			$out[$bid][] = $s;
		}
		return $out;
	}

	/**
	 * Delete a section (caller must guard against FK references).
	 * @param int $id
	 * @return bool
	 */
	public function delete_section($id)
	{
		return $this->db->where('id', (int) $id)->delete('sections') !== FALSE;
	}

	/**
	 * Remove a section's auto-synced curriculum links (section_subjects).
	 * Called before deleting a section; its classes/students are protected
	 * by the caller's guard.
	 * @param int $id
	 * @return bool
	 */
	public function delete_section_subjects($id)
	{
		return $this->db->where('section_id', (int) $id)->delete('section_subjects') !== FALSE;
	}

	/**
	 * Activate or deactivate a section. Inactive sections keep their data
	 * but are excluded from enrollment/section pickers.
	 * @param int  $id
	 * @param bool $is_active
	 * @return bool
	 */
	public function set_section_active($id, $is_active)
	{
		return $this->db->where('id', (int) $id)
			->update('sections', array('is_active' => $is_active ? 1 : 0)) !== FALSE;
	}

	// -----------------------------------------------------------------
	// Subjects (write)
	// -----------------------------------------------------------------

	/**
	 * Create a subject.
	 * @param string $code
	 * @param string $title
	 * @param float  $units
	 * @return int insert id
	 */
	public function create_subject($code, $title, $units)
	{
		$this->db->insert('subjects', array(
			'code'  => $code,
			'title' => $title,
			'units' => (float) $units,
		));
		return (int) $this->db->insert_id();
	}

	/**
	 * Update a subject.
	 * @param int    $id
	 * @param string $code
	 * @param string $title
	 * @param float  $units
	 * @return bool
	 */
	public function update_subject($id, $code, $title, $units)
	{
		return $this->db->where('id', (int) $id)->update('subjects', array(
			'code'  => $code,
			'title' => $title,
			'units' => (float) $units,
		)) !== FALSE;
	}

	/**
	 * Delete a subject (caller must guard against FK references).
	 * @param int $id
	 * @return bool
	 */
	public function delete_subject($id)
	{
		return $this->db->where('id', (int) $id)->delete('subjects') !== FALSE;
	}

	// -----------------------------------------------------------------
	// Programs
	// -----------------------------------------------------------------

	/**
	 * All programs, ordered by code.
	 * @return array
	 */
	public function programs()
	{
		return $this->db->order_by('program_code', 'ASC')->get('programs')->result();
	}

	/**
	 * A program by primary key.
	 * @param int $id
	 * @return object|null
	 */
	public function get_program($id)
	{
		return $this->db->where('id', (int) $id)->get('programs')->row();
	}

	/**
	 * Create a program (displayed to admins as a "Strand").
	 * @param string $program_code
	 * @param string $short_code
	 * @param string $program_name
	 * @return int insert id
	 */
	public function create_program($program_code, $short_code, $program_name)
	{
		$this->db->insert('programs', array(
			'program_code' => $program_code,
			'short_code'   => $short_code,
			'program_name' => $program_name,
		));
		return (int) $this->db->insert_id();
	}

	/**
	 * Update a program (displayed to admins as a "Strand").
	 * @param int    $id
	 * @param string $program_code
	 * @param string $short_code
	 * @param string $program_name
	 * @return bool
	 */
	public function update_program($id, $program_code, $short_code, $program_name)
	{
		return $this->db->where('id', (int) $id)->update('programs', array(
			'program_code' => $program_code,
			'short_code'   => $short_code,
			'program_name' => $program_name,
		)) !== FALSE;
	}

	/**
	 * Delete a program (caller must guard against FK references).
	 * @param int $id
	 * @return bool
	 */
	public function delete_program($id)
	{
		return $this->db->where('id', (int) $id)->delete('programs') !== FALSE;
	}

	// -----------------------------------------------------------------
	// Curriculum (predefined subjects per strand × grade × semester)
	// -----------------------------------------------------------------

	/**
	 * Curriculum slots (curriculum_subjects rows) joined with their subject
	 * details, optionally narrowed to one strand × grade × semester.
	 *
	 * @param int $program_id       strand id, 0 = any
	 * @param int $year_level       grade level (11/12), 0 = any
	 * @param int $semester_number  1 or 2, 0 = any
	 * @return array of objects
	 */
	public function curriculum_slots($program_id = 0, $year_level = 0, $semester_number = 0)
	{
		$this->db->select(
				'curriculum_subjects.id, curriculum_subjects.program_id, ' .
				'curriculum_subjects.year_level, curriculum_subjects.semester_number, ' .
				'curriculum_subjects.subject_id, subjects.code, subjects.title, subjects.units'
			)
			->from('curriculum_subjects')
			->join('subjects', 'subjects.id = curriculum_subjects.subject_id');
		if ($program_id > 0)
		{
			$this->db->where('curriculum_subjects.program_id', (int) $program_id);
		}
		if ($year_level > 0)
		{
			$this->db->where('curriculum_subjects.year_level', (int) $year_level);
		}
		if ($semester_number > 0)
		{
			$this->db->where('curriculum_subjects.semester_number', (int) $semester_number);
		}
		return $this->db->order_by('subjects.code', 'ASC')->get()->result();
	}

	/**
	 * A single curriculum slot by primary key.
	 * @param int $id
	 * @return object|null
	 */
	public function get_curriculum_slot($id)
	{
		return $this->db->where('id', (int) $id)->get('curriculum_subjects')->row();
	}

	/**
	 * Whether a subject is already in a curriculum slot.
	 * @param int $program_id
	 * @param int $year_level
	 * @param int $semester_number
	 * @param int $subject_id
	 * @return bool
	 */
	public function curriculum_slot_exists($program_id, $year_level, $semester_number, $subject_id)
	{
		return $this->db->where('program_id', (int) $program_id)
			->where('year_level', (int) $year_level)
			->where('semester_number', (int) $semester_number)
			->where('subject_id', (int) $subject_id)
			->count_all_results('curriculum_subjects') > 0;
	}

	/**
	 * Add a subject to a curriculum slot (idempotent — returns FALSE when
	 * the slot already has that subject).
	 * @param int $program_id
	 * @param int $year_level
	 * @param int $semester_number
	 * @param int $subject_id
	 * @return int|bool insert id, or FALSE when the subject is already there
	 */
	public function add_curriculum_slot($program_id, $year_level, $semester_number, $subject_id)
	{
		if ($this->curriculum_slot_exists($program_id, $year_level, $semester_number, $subject_id))
		{
			return FALSE;
		}
		$this->db->insert('curriculum_subjects', array(
			'program_id'      => (int) $program_id,
			'year_level'      => (int) $year_level,
			'semester_number' => (int) $semester_number,
			'subject_id'      => (int) $subject_id,
		));
		return (int) $this->db->insert_id();
	}

	/**
	 * Remove a curriculum slot (caller must guard it exists and is not
	 * actively graded — see curriculum_slot_has_grades()).
	 * @param int $id
	 * @return bool
	 */
	public function delete_curriculum_slot($id)
	{
		return $this->db->where('id', (int) $id)->delete('curriculum_subjects') !== FALSE;
	}

	/**
	 * Whether a curriculum pairing (strand × grade × semester-number ×
	 * subject) is "actively graded": at least one grade row exists for the
	 * subject, given by a student whose section is this strand/grade, in a
	 * semester that maps to this slot's semester number. Once students have
	 * received a grade for the pairing it must be left intact, otherwise the
	 * historical record stops matching the curriculum.
	 *
	 * @param int $program_id
	 * @param int $year_level
	 * @param int $semester_number
	 * @param int $subject_id
	 * @return bool
	 */
	public function curriculum_slot_has_grades($program_id, $year_level, $semester_number, $subject_id)
	{
		$sql = 'SELECT COUNT(*) AS n '
			. 'FROM grades g '
			. 'JOIN students st ON st.id = g.student_id '
			. 'JOIN sections sec ON sec.id = st.section_id '
			. 'JOIN grading_periods gp ON gp.id = g.grading_period_id '
			. 'JOIN semesters sem ON sem.id = gp.semester_id '
			. 'WHERE g.subject_id = ? AND sec.program_id = ? '
			. 'AND sec.year_level = ? AND sem.semester_number = ?';
		$row = $this->db->query($sql, array(
			(int) $subject_id,
			(int) $program_id,
			(int) $year_level,
			(int) $semester_number,
		))->row();
		return $row ? ((int) $row->n > 0) : FALSE;
	}

	/**
	 * How many curriculum slots (any strand/grade/semester) reference a
	 * subject. A subject that is still linked anywhere cannot be fully
	 * deleted, only unlinked from one strand at a time.
	 * @param int $subject_id
	 * @return int
	 */
	public function curriculum_links_for_subject($subject_id)
	{
		return (int) $this->db->where('subject_id', (int) $subject_id)
			->count_all_results('curriculum_subjects');
	}

	// -----------------------------------------------------------------
	// Section subjects (per section + semester) and curriculum sync
	// -----------------------------------------------------------------

	/**
	 * All section_subjects for a semester, joined with subject + section
	 * details, and the teacher assigned to that pairing (if any).
	 * Keyed by section_id so callers can group rows per section.
	 *
	 * @param int $semester_id
	 * @return array section_id => list of subject rows
	 */
	public function section_subjects_by_section($semester_id)
	{
		$rows = $this->db->select(
				'section_subjects.section_id, section_subjects.semester_id, ' .
				'subjects.id AS subject_id, subjects.code, subjects.title, subjects.units, ' .
				'tsa.id AS assignment_id, tsa.teacher_user_id, tsa.room_id, tsa.day_bits, ' .
				'tsa.start_min, tsa.end_min, tsa.schedule, ' .
				'rooms.name AS room_name, buildings.name AS building_name, ' .
				'CONCAT(users.first_name, \' \', users.last_name) AS teacher_name', FALSE
			)
			->from('section_subjects')
			->join('subjects', 'subjects.id = section_subjects.subject_id')
			->join('teacher_subject_assignments tsa',
				'tsa.section_id = section_subjects.section_id ' .
				'AND tsa.subject_id = section_subjects.subject_id ' .
				'AND tsa.semester_id = section_subjects.semester_id', 'left')
			->join('rooms', 'rooms.id = tsa.room_id', 'left')
			->join('buildings', 'buildings.id = rooms.building_id', 'left')
			->join('users', 'users.id = tsa.teacher_user_id', 'left')
			->where('section_subjects.semester_id', (int) $semester_id)
			->order_by('subjects.code', 'ASC')
			->get()
			->result();

		$grouped = array();
		foreach ($rows as $r)
		{
			$sid = (int) $r->section_id;
			if ( ! isset($grouped[$sid]))
			{
				$grouped[$sid] = array();
			}
			$grouped[$sid][] = array(
				'subject_id'      => (int) $r->subject_id,
				'semester_id'     => (int) $r->semester_id,
				'code'            => $r->code,
				'title'           => $r->title,
				'units'           => $r->units,
				'assignment_id'   => $r->assignment_id !== NULL ? (int) $r->assignment_id : NULL,
				'teacher_user_id' => $r->teacher_user_id !== NULL ? (int) $r->teacher_user_id : NULL,
				'teacher_name'    => $r->teacher_name !== NULL ? $r->teacher_name : NULL,
				'room_id'         => $r->room_id !== NULL ? (int) $r->room_id : NULL,
				'room_name'       => $r->room_name !== NULL ? $r->room_name : NULL,
				'building_name'   => $r->building_name !== NULL ? $r->building_name : NULL,
				'day_bits'        => $r->day_bits !== NULL ? (int) $r->day_bits : NULL,
				'start_min'       => $r->start_min !== NULL ? (int) $r->start_min : NULL,
				'end_min'         => $r->end_min !== NULL ? (int) $r->end_min : NULL,
				'schedule_text'   => $r->schedule !== NULL ? $r->schedule : NULL,
			);
		}
		return $grouped;
	}

	/**
	 * Populate section_subjects for a section from its curriculum slot.
	 * Uses the section's program/year_level and the given semester's number.
	 * Idempotent: the UNIQUE (section_id, subject_id, semester_id) key
	 * silently ignores duplicates, so calling this repeatedly is safe.
	 *
	 * @param int $section_id
	 * @param int $semester_id  the semester whose number drives the lookup
	 * @return int number of rows inserted (0 when none matched or none new)
	 */
	public function sync_section_subjects($section_id, $semester_id)
	{
		$section = $this->get_section($section_id);
		$semester = $this->get_semester($semester_id);
		if ( ! $section || ! $semester || $section->program_id === NULL
			|| $section->year_level === NULL || $semester->semester_number === NULL)
		{
			return 0;
		}

		$sql = 'INSERT IGNORE INTO section_subjects (section_id, subject_id, semester_id) '
			. 'SELECT ?, subject_id, ? FROM curriculum_subjects '
			. 'WHERE program_id = ? AND year_level = ? AND semester_number = ?';
		$this->db->query($sql, array(
			(int) $section_id,
			(int) $semester_id,
			(int) $section->program_id,
			(int) $section->year_level,
			(int) $semester->semester_number,
		));
		return (int) $this->db->affected_rows();
	}

	/**
	 * Whether a subject is linked to a section for a given semester.
	 * @param int $section_id
	 * @param int $subject_id
	 * @param int $semester_id
	 * @return bool
	 */
	public function section_subject_exists($section_id, $subject_id, $semester_id)
	{
		return $this->db->where('section_id', (int) $section_id)
			->where('subject_id', (int) $subject_id)
			->where('semester_id', (int) $semester_id)
			->count_all_results('section_subjects') > 0;
	}

	/**
	 * Assign a teacher to a class (section + subject) for a semester.
	 * One teacher per class: any previous assignment for the same class is
	 * replaced. If the previous assignment carried a room/schedule, those
	 * are preserved (they belong to the class, not the teacher).
	 * Runs in a transaction so a failed insert rolls back the replacement.
	 *
	 * @param int $section_id
	 * @param int $subject_id
	 * @param int $semester_id
	 * @param int $teacher_user_id
	 * @return bool
	 */
	public function assign_teacher_to_class($section_id, $subject_id, $semester_id, $teacher_user_id)
	{
		$old = $this->db->select('room_id, day_bits, start_min, end_min, schedule, room')
			->where('section_id', (int) $section_id)
			->where('subject_id', (int) $subject_id)
			->where('semester_id', (int) $semester_id)
			->get('teacher_subject_assignments')
			->row();

		$this->db->trans_start();
		$this->db->where('section_id', (int) $section_id)
			->where('subject_id', (int) $subject_id)
			->where('semester_id', (int) $semester_id)
			->delete('teacher_subject_assignments');
		$row = array(
			'teacher_user_id' => (int) $teacher_user_id,
			'subject_id'      => (int) $subject_id,
			'section_id'      => (int) $section_id,
			'semester_id'     => (int) $semester_id,
		);
		if ($old)
		{
			foreach (array('room_id', 'day_bits', 'start_min', 'end_min', 'schedule', 'room') as $f)
			{
				if ($old->$f !== NULL)
				{
					$row[$f] = $old->$f;
				}
			}
		}
		$this->db->insert('teacher_subject_assignments', $row);
		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	/**
	 * Remove the teacher from a class (section + subject) for a semester.
	 * @param int $section_id
	 * @param int $subject_id
	 * @param int $semester_id
	 * @return bool
	 */
	public function remove_teacher_from_class($section_id, $subject_id, $semester_id)
	{
		return $this->db->where('section_id', (int) $section_id)
			->where('subject_id', (int) $subject_id)
			->where('semester_id', (int) $semester_id)
			->delete('teacher_subject_assignments') !== FALSE;
	}

	// -----------------------------------------------------------------
	// Buildings & Rooms
	// -----------------------------------------------------------------

	/**
	 * Day letters indexed by day bit (0 = Mon .. 4 = Fri). The weekly grid
	 * and the schedule text use these tokens.
	 * @return array int => string
	 */
	public function day_tokens()
	{
		return array(1 => 'Mon', 2 => 'Tue', 4 => 'Wed', 8 => 'Thu', 16 => 'Fri');
	}

	/**
	 * All active buildings ordered by name.
	 * @return array
	 */
	public function buildings($only_active = FALSE)
	{
		if ($only_active)
		{
			$this->db->where('is_active', 1);
		}
		return $this->db->order_by('name', 'ASC')->get('buildings')->result();
	}

	/**
	 * A building by primary key.
	 * @param int $id
	 * @return object|null
	 */
	public function get_building($id)
	{
		return $this->db->where('id', (int) $id)->get('buildings')->row();
	}

	/**
	 * A building by exact name.
	 * @param string $name
	 * @return object|null
	 */
	public function building_by_name($name)
	{
		return $this->db->where('name', $name)->get('buildings')->row();
	}

	/**
	 * Create a building.
	 * @param string $name
	 * @return int insert id
	 */
	public function create_building($name)
	{
		$this->db->insert('buildings', array('name' => $name));
		return (int) $this->db->insert_id();
	}

	/**
	 * Rename a building.
	 * @param int    $id
	 * @param string $name
	 * @return bool
	 */
	public function update_building($id, $name)
	{
		return $this->db->where('id', (int) $id)->update('buildings', array('name' => $name)) !== FALSE;
	}

	/**
	 * Activate/deactivate a building. Inactive buildings keep their rooms
	 * but are hidden from room pickers.
	 * @param int  $id
	 * @param bool $is_active
	 * @return bool
	 */
	public function set_building_active($id, $is_active)
	{
		return $this->db->where('id', (int) $id)
			->update('buildings', array('is_active' => $is_active ? 1 : 0)) !== FALSE;
	}

	/**
	 * Delete a building (caller must guard: only when it has no rooms).
	 * @param int $id
	 * @return bool
	 */
	public function delete_building($id)
	{
		return $this->db->where('id', (int) $id)->delete('buildings') !== FALSE;
	}

	/**
	 * All rooms, optionally narrowed to one building, joined with their
	 * building details and their active-class count for a semester.
	 * @param int $building_id 0 = all buildings
	 * @return array of objects
	 */
	public function rooms($building_id = 0)
	{
		if ((int) $building_id > 0)
		{
			$this->db->where('rooms.building_id', (int) $building_id);
		}
		return $this->db->select('rooms.*, buildings.name AS building_name, buildings.is_active AS building_active')
			->from('rooms')
			->join('buildings', 'buildings.id = rooms.building_id')
			->order_by('buildings.name', 'ASC')
			->order_by('rooms.name', 'ASC')
			->get()
			->result();
	}

	/**
	 * A room by primary key (joined with its building).
	 * @param int $id
	 * @return object|null
	 */
	public function get_room($id)
	{
		return $this->db->select('rooms.*, buildings.name AS building_name')
			->from('rooms')
			->join('buildings', 'buildings.id = rooms.building_id')
			->where('rooms.id', (int) $id)
			->get()
			->row();
	}

	/**
	 * Create a room in a building.
	 * @param int    $building_id
	 * @param string $name
	 * @return int insert id
	 */
	public function create_room($building_id, $name)
	{
		$this->db->insert('rooms', array(
			'building_id' => (int) $building_id,
			'name'        => $name,
		));
		return (int) $this->db->insert_id();
	}

	/**
	 * Update a room (move between buildings and/or rename).
	 * @param int    $id
	 * @param int    $building_id
	 * @param string $name
	 * @return bool
	 */
	public function update_room($id, $building_id, $name)
	{
		return $this->db->where('id', (int) $id)->update('rooms', array(
			'building_id' => (int) $building_id,
			'name'        => $name,
		)) !== FALSE;
	}

	/**
	 * Activate/deactivate a room. Inactive rooms keep their bookings but are
	 * hidden from room pickers.
	 * @param int  $id
	 * @param bool $is_active
	 * @return bool
	 */
	public function set_room_active($id, $is_active)
	{
		return $this->db->where('id', (int) $id)
			->update('rooms', array('is_active' => $is_active ? 1 : 0)) !== FALSE;
	}

	/**
	 * Delete a room (caller must guard: only when it has no class bookings).
	 * @param int $id
	 * @return bool
	 */
	public function delete_room($id)
	{
		return $this->db->where('id', (int) $id)->delete('rooms') !== FALSE;
	}

	/**
	 * All rooms in an active state, pre-grouped by building for pickers.
	 * @return array building_id => list of room rows
	 */
	public function rooms_grouped_by_building()
	{
		$out = array();
		foreach ($this->rooms() as $room)
		{
			if ( ! (int) $room->is_active || ! (int) $room->building_active)
			{
				continue;
			}
			$out[(int) $room->building_id][] = $room;
		}
		return $out;
	}

	// -----------------------------------------------------------------
	// Class scheduling (room + day/time) with occupancy conflicts
	// -----------------------------------------------------------------

	/**
	 * Whether an assignment currently has a scheduled time slot.
	 * @param object|array|null $assignment
	 * @return bool
	 */
	public function assignment_has_slot($assignment)
	{
		if ( ! $assignment)
		{
			return FALSE;
		}
		$o = (object) $assignment;
		return $o->room_id !== NULL && $o->day_bits !== NULL
			&& $o->start_min !== NULL && $o->end_min !== NULL;
	}

	/**
	 * A single teacher_subject_assignment by primary key.
	 * @param int $id
	 * @return object|null
	 */
	public function get_assignment($id)
	{
		return $this->db->select('tsa.*, rooms.name AS room_name, buildings.name AS building_name,'
				. ' sections.name AS section_name, sections.building_id AS section_building_id,'
				. ' subjects.code AS subject_code,'
				. ' CONCAT(users.first_name, \' \', users.last_name) AS teacher_name', FALSE)
			->from('teacher_subject_assignments tsa')
			->join('rooms', 'rooms.id = tsa.room_id', 'left')
			->join('buildings', 'buildings.id = rooms.building_id', 'left')
			->join('sections', 'sections.id = tsa.section_id')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('users', 'users.id = tsa.teacher_user_id', 'left')
			->where('tsa.id', (int) $id)
			->get()
			->row();
	}

	/**
	 * The class (assignment) for a section+subject+semester, or NULL.
	 * @param int $section_id
	 * @param int $subject_id
	 * @param int $semester_id
	 * @return object|null
	 */
	public function class_assignment($section_id, $subject_id, $semester_id)
	{
		return $this->db->where('section_id', (int) $section_id)
			->where('subject_id', (int) $subject_id)
			->where('semester_id', (int) $semester_id)
			->get('teacher_subject_assignments')
			->row();
	}

	/**
	 * Render minutes-from-midnight as a compact clock string, e.g. 450 -> "7:30".
	 * @param int $minutes
	 * @return string
	 */
	public function minutes_to_clock($minutes)
	{
		$minutes = (int) $minutes;
		$h = intdiv($minutes, 60);
		$m = $minutes % 60;
		return $h . ':' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
	}

	/**
	 * Compose the human schedule text for a slot, e.g. "MonWed 7:30 - 8:30".
	 * @param int|null $day_bits
	 * @param int|null $start_min
	 * @param int|null $end_min
	 * @return string empty when no slot
	 */
	public function compose_schedule_text($day_bits, $start_min, $end_min)
	{
		if ($day_bits === NULL || $start_min === NULL || $end_min === NULL)
		{
			return '';
		}
		$days = '';
		foreach ($this->day_tokens() as $bit => $token)
		{
			if (((int) $day_bits) & $bit)
			{
				$days .= $token;
			}
		}
		return $days === '' ? ''
			: $days . ' ' . $this->minutes_to_clock($start_min) . ' – ' . $this->minutes_to_clock($end_min);
	}

	/**
	 * Save (or clear) a class schedule: room + day bits + start/end minutes.
	 * When a full slot is given the free-text schedule/room columns are
	 * regenerated so the existing weekly-grid views keep working unchanged.
	 *
	 * @param int          $assignment_id
	 * @param int|null     $room_id    NULL or 0 clears the schedule
	 * @param int|null     $day_bits
	 * @param int|null     $start_min
	 * @param int|null     $end_min
	 * @return bool
	 */
	public function save_class_schedule($assignment_id, $room_id = NULL, $day_bits = NULL, $start_min = NULL, $end_min = NULL)
	{
		$assignment_id = (int) $assignment_id;
		$assignment = $this->get_assignment($assignment_id);
		if ( ! $assignment)
		{
			return FALSE;
		}

		$room_id   = (int) $room_id;
		$day_bits  = (int) $day_bits;
		$start_min = (int) $start_min;
		$end_min   = (int) $end_min;

		if ($room_id <= 0 || $day_bits <= 0 || $end_min <= $start_min)
		{
			// Clear: keep the teacher, drop room/time only.
			return $this->db->where('id', $assignment_id)->update('teacher_subject_assignments', array(
				'room_id'   => NULL,
				'day_bits'  => NULL,
				'start_min' => NULL,
				'end_min'   => NULL,
				'schedule'  => NULL,
				'room'      => NULL,
			)) !== FALSE;
		}

		$room = $this->get_room($room_id);
		if ( ! $room)
		{
			return FALSE;
		}

		return $this->db->where('id', $assignment_id)->update('teacher_subject_assignments', array(
			'room_id'   => $room_id,
			'day_bits'  => $day_bits,
			'start_min' => $start_min,
			'end_min'   => $end_min,
			'schedule'  => $this->compose_schedule_text($day_bits, $start_min, $end_min),
			'room'      => ($room->building_name ? $room->building_name . ' · ' : '') . $room->name,
		)) !== FALSE;
	}

	/**
	 * Find OTHER classes that collide with a proposed booking: same room,
	 * same semester, overlapping on at least one weekday and with an
	 * overlapping clock-time window (strict overlap => occupied).
	 *
	 * @param int $semester_id
	 * @param int $room_id
	 * @param int $day_bits
	 * @param int $start_min
	 * @param int $end_min
	 * @param int $except_assignment_id  exclude this class (editing it)
	 * @return array of objects (section code, subject, teacher, own timeslot)
	 */
	public function room_conflicts($semester_id, $room_id, $day_bits, $start_min, $end_min, $except_assignment_id = 0)
	{
		$day_bits  = (int) $day_bits;
		$start_min = (int) $start_min;
		$end_min   = (int) $end_min;
		if ($day_bits <= 0 || $end_min <= $start_min || (int) $room_id <= 0)
		{
			return array();
		}

		$this->db->select(
				'tsa.id AS assignment_id, tsa.day_bits, tsa.start_min, tsa.end_min,' .
				'sections.name AS section_name, subjects.code AS subject_code, subjects.title AS subject_title,' .
				'CONCAT(users.first_name, \' \', users.last_name) AS teacher_name', FALSE)
			->from('teacher_subject_assignments tsa')
			->join('sections', 'sections.id = tsa.section_id')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('users', 'users.id = tsa.teacher_user_id', 'left')
			->where('tsa.semester_id', (int) $semester_id)
			->where('tsa.room_id', (int) $room_id)
			->where('tsa.day_bits IS NOT NULL', NULL, FALSE)
			->where('tsa.start_min IS NOT NULL', NULL, FALSE)
			->where('tsa.end_min IS NOT NULL', NULL, FALSE);
		if ((int) $except_assignment_id > 0)
		{
			$this->db->where('tsa.id !=', (int) $except_assignment_id);
		}

		$rows = $this->db->get()->result();
		$conflicts = array();
		foreach ($rows as $r)
		{
			$same_day = (((int) $r->day_bits) & $day_bits) !== 0;
			$same_time = ((int) $r->start_min < $end_min) && ((int) $r->end_min > $start_min);
			if ($same_day && $same_time)
			{
				$conflicts[] = $r;
			}
		}
		return $conflicts;
	}

	/**
	 * Find OTHER classes the same teacher is already teaching at an
	 * overlapping time — no matter which room they are in. A teacher
	 * cannot be in two rooms at once, so this is a hard conflict too.
	 *
	 * @param int $semester_id
	 * @param int $teacher_user_id
	 * @param int $day_bits
	 * @param int $start_min
	 * @param int $end_min
	 * @param int $except_assignment_id  exclude this class (editing it)
	 * @return array of objects (section, subject, own timeslot)
	 */
	public function teacher_conflicts($semester_id, $teacher_user_id, $day_bits, $start_min, $end_min, $except_assignment_id = 0)
	{
		$day_bits  = (int) $day_bits;
		$start_min = (int) $start_min;
		$end_min   = (int) $end_min;
		$teacher_user_id = (int) $teacher_user_id;
		if ($day_bits <= 0 || $end_min <= $start_min || $teacher_user_id <= 0)
		{
			return array();
		}

		$this->db->select(
				'tsa.id AS assignment_id, tsa.day_bits, tsa.start_min, tsa.end_min,' .
				'sections.name AS section_name, subjects.code AS subject_code, subjects.title AS subject_title,' .
				'CONCAT(users.first_name, \' \', users.last_name) AS teacher_name', FALSE)
			->from('teacher_subject_assignments tsa')
			->join('sections', 'sections.id = tsa.section_id')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('users', 'users.id = tsa.teacher_user_id', 'left')
			->where('tsa.semester_id', (int) $semester_id)
			->where('tsa.teacher_user_id', $teacher_user_id)
			->where('tsa.day_bits IS NOT NULL', NULL, FALSE)
			->where('tsa.start_min IS NOT NULL', NULL, FALSE)
			->where('tsa.end_min IS NOT NULL', NULL, FALSE);
		if ((int) $except_assignment_id > 0)
		{
			$this->db->where('tsa.id !=', (int) $except_assignment_id);
		}

		$rows = $this->db->get()->result();
		$conflicts = array();
		foreach ($rows as $r)
		{
			$same_day = (((int) $r->day_bits) & $day_bits) !== 0;
			$same_time = ((int) $r->start_min < $end_min) && ((int) $r->end_min > $start_min);
			if ($same_day && $same_time)
			{
				$conflicts[] = $r;
			}
		}
		return $conflicts;
	}

	/**
	 * Every booked slot in a semester, pre-grouped by room for occupancy
	 * boards. One assignment may repeat across weekdays, so each occupied
	 * weekday is expanded into its own entry here.
	 *
	 * @param int $semester_id
	 * @return array room_id => list of entries
	 */
	public function occupancy_by_room($semester_id)
	{
		$rows = $this->db->select(
				'tsa.room_id, tsa.day_bits, tsa.start_min, tsa.end_min, tsa.teacher_user_id,' .
				'sections.name AS section_name, subjects.code AS subject_code,' .
				'CONCAT(users.first_name, \' \', users.last_name) AS teacher_name', FALSE)
			->from('teacher_subject_assignments tsa')
			->join('sections', 'sections.id = tsa.section_id')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('users', 'users.id = tsa.teacher_user_id', 'left')
			->where('tsa.semester_id', (int) $semester_id)
			->where('tsa.room_id IS NOT NULL', NULL, FALSE)
			->where('tsa.day_bits IS NOT NULL', NULL, FALSE)
			->where('tsa.start_min IS NOT NULL', NULL, FALSE)
			->where('tsa.end_min IS NOT NULL', NULL, FALSE)
			->order_by('tsa.start_min', 'ASC')
			->get()
			->result();

		$out = array();
		foreach ($rows as $r)
		{
			$rid = (int) $r->room_id;
			if ( ! isset($out[$rid]))
			{
				$out[$rid] = array();
			}
			foreach ($this->day_tokens() as $bit => $token)
			{
				if (((int) $r->day_bits) & $bit)
				{
					$out[$rid][] = array(
						'day'          => $token,
					'start_min'     => (int) $r->start_min,
					'end_min'       => (int) $r->end_min,
					'section_name'  => $r->section_name,
					'subject_code'  => $r->subject_code,
					'teacher_name'  => $r->teacher_name !== NULL ? $r->teacher_name : '',
					'teacher_user_id' => $r->teacher_user_id !== NULL ? (int) $r->teacher_user_id : 0,
				);
			}
		}
	}
	return $out;
	}

	/**
	 * Classes that can be booked into a room: every teacher-assigned class
	 * in the semester. Used by the room booking picker on the Rooms page.
	 *
	 * @param int $semester_id
	 * @return array of objects
	 */
	public function schedulable_classes($semester_id)
	{
		return $this->db->select(
				'tsa.id AS assignment_id, tsa.section_id, tsa.subject_id, tsa.teacher_user_id, tsa.room_id,' .
				'sections.name AS section_name, sections.building_id AS section_building_id,' .
				'subjects.code AS subject_code, subjects.title AS subject_title,' .
				'CONCAT(users.first_name, \' \', users.last_name) AS teacher_name', FALSE)
			->from('teacher_subject_assignments tsa')
			->join('sections', 'sections.id = tsa.section_id')
			->join('subjects', 'subjects.id = tsa.subject_id')
			->join('users', 'users.id = tsa.teacher_user_id', 'left')
			->where('tsa.semester_id', (int) $semester_id)
			->order_by('sections.name', 'ASC')
			->order_by('subjects.code', 'ASC')
			->get()
			->result();
	}

	/**
	 * Standard weekly clock grid used for occupancy boards: rows are fixed
	 * hourly start times within school hours, columns Mon-Fri.
	 * @return array of start minutes
	 */
	public function occupancy_grid_hours()
	{
		$hours = array();
		for ($h = 7; $h <= 17; $h++)
		{
			$hours[] = $h * 60;
		}
		return $hours;
	}
}