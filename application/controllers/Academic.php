<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Academic
 *
 * Reference-data management for Administrators: school years, semesters,
 * sections and subjects. Every method is guarded by Admin_Controller, so
 * only role=admin can reach these endpoints.
 *
 * Integrity rules enforced here:
 *   - Only one school year and one semester may be active at a time
 *     (activation is atomic and clears the previous active flag).
 *   - A referenced row (a section with students/classes, a subject with
 *     classes, a school year or semester with classes/semesters) cannot
 *     be deleted — it must be left as-is rather than break FK integrity.
 *   - All writes go through the DB transaction where several rows change.
 */
class Academic extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Academic_model');
		$this->load->model('Enrollment_model');
	}

	// =================================================================
	// School Years
// =================================================================

/**
 * Validate a semester name and year_label.
 * @param string $year_label
 * @param string $name
 * @param int|null $except_id
 * @return array
 */
private function _validate_semester($year_label, $name, $except_id = NULL)
	{
		$errors = array();
		if ($year_label === '')
		{
			$errors[] = 'School year label is required.';
		}
		if ($name === '')
		{
			$errors[] = 'Semester name is required.';
		}
		$dup = $this->db->where('year_label', $year_label)
			->where('name', $name);
		if ($except_id !== NULL)
		{
			$dup = $dup->where('id !=', (int) $except_id);
		}
		if ($dup->count_all_results('semesters') > 0)
		{
			$errors[] = 'This semester already exists for that school year label.';
		}
		return $errors;
	}

	// =================================================================
	// Semesters
	// =================================================================

	public function semesters()
	{
		$filter_year = trim((string) $this->input->get('year_label'));

		$rows = $this->Academic_model->semesters($filter_year);

		$edit = NULL;
		$edit_id = (int) $this->input->get('edit');
		if ($edit_id > 0)
		{
			$edit = $this->Academic_model->get_semester($edit_id);
		}

		// Get unique year_labels for filter dropdown from ALL semesters
		$year_label_rows = $this->Academic_model->year_labels();
		$year_labels = array_map(function($r) { return $r->year_label; }, $year_label_rows);

		// Stats
		$total = $this->db->count_all('semesters');
		$active_sem = $this->Academic_model->active_semesters();
		$all_sems = $this->Academic_model->semesters();
		$unique_years = count(array_unique(array_map(function($s) { return $s->year_label; }, $all_sems)));

		$this->data['active_page'] = 'semesters';
		$this->_render('admin/semesters', array(
			'rows'        => $rows,
			'edit'        => $edit,
			'year_labels' => $year_labels,
			'filter_year' => $filter_year,
			'subtitle'    => 'Administration',
			'stats'       => array(
				'total'        => $total,
				'active'       => $active_sem ? $active_sem->name . ' (' . $active_sem->year_label . ')' : 'None',
				'unique_years' => $unique_years,
			),
		));
	}

	public function semester_store()
	{
		$this->_require_post();
		$year_label = trim($this->input->post('year_label'));
		$name       = trim($this->input->post('name'));
		$sem_num    = $this->input->post('semester_number');
		$errors = $this->_validate_semester($year_label, $name);

		if (empty($errors))
		{
			$this->Academic_model->create_semester($name, $year_label, $sem_num !== '' ? (int) $sem_num : NULL);
			$this->session->set_flashdata('admin_success', 'Semester created.');
			redirect('academic/semesters');
		}

		$this->_flash_errors($errors);
		redirect('academic/semesters');
	}

	public function semester_update($id)
	{
		$this->_require_post();
		$id = (int) $id;
		if ( ! $this->Academic_model->get_semester($id))
		{
			$this->session->set_flashdata('admin_error', 'Semester not found.');
			redirect('academic/semesters');
		}

		$year_label = trim($this->input->post('year_label'));
		$name       = trim($this->input->post('name'));
		$sem_num    = $this->input->post('semester_number');
		$errors = $this->_validate_semester($year_label, $name, $id);

		if (empty($errors))
		{
			$this->Academic_model->update_semester($id, $year_label, $name, $sem_num !== '' ? (int) $sem_num : NULL);
			$this->session->set_flashdata('admin_success', 'Semester updated.');
			redirect('academic/semesters');
		}

		$this->_flash_errors($errors);
		redirect('academic/semesters?edit=' . $id);
	}

	public function semester_activate($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$target = $this->Academic_model->get_semester($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'Semester not found.');
			redirect('academic/semesters');
		}

		$this->db->trans_start();
		$this->db->update('semesters', array('is_active' => 0));
		$this->db->where('id', $id)->update('semesters', array('is_active' => 1));

		// Ensure grading periods exist for the semester being activated
		$this->Academic_model->_ensure_grading_periods($id);

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE)
		{
			$this->session->set_flashdata('admin_error', 'Could not activate the semester. Please try again.');
		}
		else
		{
			$this->session->set_flashdata('admin_success', 'Semester activated.');
		}
		redirect('academic/semesters');
	}

	public function semester_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$target = $this->Academic_model->get_semester($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'Semester not found.');
			redirect('academic/semesters');
		}

		$classes = (int) $this->db->where('semester_id', $id)->count_all_results('teacher_subject_assignments');
		if ($classes > 0)
		{
			$this->session->set_flashdata('admin_error',
				'This semester has ' . $classes . ' related class(es). It cannot be deleted.');
			redirect('academic/semesters');
		}

		$graded_periods = (int) $this->db->select('COUNT(DISTINCT gp.id) AS cnt', FALSE)
			->from('grading_periods gp')
			->join('grades g', 'g.grading_period_id = gp.id')
			->where('gp.semester_id', $id)
			->get()->row()->cnt;
		if ($graded_periods > 0)
		{
			$this->session->set_flashdata('admin_error',
				'This semester has ' . $graded_periods .
				' grading period(s) with recorded grades. It cannot be deleted.');
			redirect('academic/semesters');
		}

		$this->Academic_model->delete_semester_with_periods($id);
		$this->session->set_flashdata('admin_success', 'Semester deleted.');
		redirect('academic/semesters');
	}

	// =================================================================
	// Sections
	// =================================================================

	public function sections()
	{
		$filters = array(
			'program_id' => (int) $this->input->get('program_id'),
			'status'     => $this->input->get('status'),
			'year_level' => $this->input->get('year_level'),
			'search'     => trim($this->input->get('search')),
		);

		$rows = $this->Academic_model->sections(FALSE, $filters);

		$stats = array();
		$sem = $this->Academic_model->active_semesters();
		$sec_subjects = $sem
			? $this->Academic_model->section_subjects_by_section($sem->id)
			: array();

		foreach ($rows as $s)
		{
			$students = (int) $this->db->where('section_id', $s->id)->count_all_results('students');
			$classes  = (int) $this->db->where('section_id', $s->id)->count_all_results('teacher_subject_assignments');
			$stats[$s->id] = array('students' => $students, 'classes' => $classes);
		}

		$edit = NULL;
		$edit_id = (int) $this->input->get('edit');
		if ($edit_id > 0)
		{
			$edit = $this->Academic_model->get_section($edit_id);
		}

		$this->data['active_page'] = 'sections';
		$this->load->model('User_model');
		$this->_render('admin/sections', array(
			'rows'            => $rows,
			'stats'           => $stats,
			'section_subjects'=> $sec_subjects,
			'active_sem'      => $sem,
			'active_sy'       => $this->Academic_model->active_school_year(),
			'active_teachers' => $this->User_model->active_teachers(),
			'programs'        => $this->Academic_model->programs(),
			'buildings_all'   => $this->Academic_model->buildings(),
			'rooms_grouped'   => $this->Academic_model->rooms_grouped_by_building(),
			'room_occupancy'  => $sem
				? $this->Academic_model->occupancy_by_room($sem->id)
				: array(),
			'sections_by_building' => $this->Academic_model->sections_by_building(),
			'filters'         => $filters,
			'edit'            => $edit,
			'subtitle'        => 'Administration',
		));
	}

	/**
	 * Assign (or change) the teacher for one class (section + subject) in the
	 * active semester. POST only. One teacher per class is enforced.
	 *
	 * AJAX requests (X-Requested-With: XMLHttpRequest) receive a JSON response
	 * so the admin can keep assigning on the same page without a reload.
	 * Non-AJAX POSTs keep the flash + redirect fallback (no-JS clients).
	 */
	public function section_assign_teacher()
	{
		$this->_require_post();
		$ajax = $this->input->is_ajax_request();

		$section_id = (int) $this->input->post('section_id');
		$subject_id = (int) $this->input->post('subject_id');
		$teacher_id = (int) $this->input->post('teacher_user_id');

		$section = $this->Academic_model->get_section($section_id);
		$sy      = $this->Academic_model->active_school_year();
		$sem     = $this->Academic_model->active_semesters();

		$errors = array();
		if ( ! $section)
		{
			$errors[] = 'A valid section is required.';
		}
		if ( ! $sy)
		{
			$errors[] = 'There is no active school year. Activate one first.';
		}
		if ( ! $sem)
		{
			$errors[] = 'There is no active semester. Activate one first.';
		}
		if ($section && $sem && ! $this->Academic_model->section_subject_exists($section_id, $subject_id, $sem->id))
		{
			$errors[] = 'That subject is not one of this section\'s classes for the active semester.';
		}

		$teacher = NULL;
		if ($teacher_id > 0)
		{
			$this->load->model('User_model');
			$teacher = $this->User_model->get($teacher_id);
			if ( ! $teacher || $teacher->role !== 'teacher' || $teacher->status !== 'active')
			{
				$errors[] = 'A valid active teacher is required.';
			}
		}
		else
		{
			$errors[] = 'A valid active teacher is required.';
		}

		if (empty($errors))
		{
			$ok = $this->Academic_model->assign_teacher_to_class(
				$section_id, $subject_id, (int) $sem->id, $teacher_id
			);
			$new_assignment = $ok ? $this->Academic_model->class_assignment($section_id, $subject_id, (int) $sem->id) : NULL;
			if ( ! $ok)
			{
				$errors[] = 'Could not save the assignment. Please try again.';
			}
			elseif ($ajax)
			{
				$this->_json_response(array(
					'ok'            => TRUE,
					'teacher_id'    => $teacher_id,
					'teacher_name'  => $teacher->first_name . ' ' . $teacher->last_name,
					'assignment_id' => $new_assignment ? (int) $new_assignment->id : NULL,
				));
				return;
			}
			// non-AJAX success path below
		}

		if ($ajax)
		{
			$this->_json_response(array(
				'ok'      => FALSE,
				'message' => implode(' ', $errors),
			));
			return;
		}

		if (empty($errors))
		{
			$teacher_name = $teacher->first_name . ' ' . $teacher->last_name;
			$subject = $this->Academic_model->get_subject($subject_id);
			$class_label = (($subject ? $subject->code . ' — ' . $subject->title : 'this class')
				. ' in ' . $section->name);
			$this->session->set_flashdata('admin_success',
				$teacher_name . ' is now assigned to ' . $class_label . '.');
		}
		else
		{
			$this->_flash_errors($errors);
		}
		redirect('academic/sections');
	}

	/**
	 * Remove the teacher from one class (section + subject) in the active
	 * semester. POST only.
	 *
	 * AJAX requests get a JSON response; non-AJAX keep flash + redirect.
	 */
	public function section_remove_teacher()
	{
		$this->_require_post();
		$ajax = $this->input->is_ajax_request();

		$section_id = (int) $this->input->post('section_id');
		$subject_id = (int) $this->input->post('subject_id');

		$section = $this->Academic_model->get_section($section_id);
		$errors = array();
		if ( ! $section)
		{
			$errors[] = 'A valid section is required.';
		}

		$sem = $this->Academic_model->active_semesters();
		if ( ! isset($errors[0]) && ! $sem)
		{
			$errors[] = 'There is no active semester. Activate one first.';
		}

		if (empty($errors))
		{
			$ok = $this->Academic_model->remove_teacher_from_class($section_id, $subject_id, (int) $sem->id);
			if ( ! $ok)
			{
				$errors[] = 'Could not remove the teacher. Please try again.';
			}
			elseif ($ajax)
			{
				$this->_json_response(array(
					'ok'          => TRUE,
					'teacher_id'  => NULL,
					'teacher_name'=> NULL,
				));
				return;
			}
		}

		if ($ajax)
		{
			$this->_json_response(array(
				'ok'      => FALSE,
				'message' => implode(' ', $errors),
			));
			return;
		}

		if (empty($errors))
		{
			$this->session->set_flashdata('admin_success',
				'Teacher removed from the class in ' . $section->name . '.');
		}
		else
		{
			$this->_flash_errors($errors);
		}
		redirect('academic/sections');
	}

	public function section_store()
	{
		$this->_require_post();
		$name = trim($this->input->post('name'));
		$program_id = (int) $this->input->post('program_id');
		$year_level = (int) $this->input->post('year_level');
		$building_id = (int) $this->input->post('building_id');
		if ($building_id > 0 && ! $this->Academic_model->get_building($building_id))
		{
			$building_id = 0;
		}
		$errors = $this->_validate_section($name);
		$errors = array_merge($errors, $this->_validate_section_program_slot($program_id, $year_level));
		if ($building_id <= 0)
		{
			$errors[] = 'Home Building is required. Every section belongs to a building, and its classes can only be assigned to rooms in that building — pick a building (add one in Rooms & Buildings if needed).';
		}

		if (empty($errors))
		{
			$this->db->trans_start();
			$section_id = $this->Academic_model->create_section($name, $program_id, $year_level, $building_id > 0 ? $building_id : NULL);

			// Auto-populate subjects from the active semester's curriculum slot.
			$sem = $this->Academic_model->active_semesters();
			$synced = 0;
			if ($sem && $sem->semester_number !== NULL)
			{
				$synced = $this->Academic_model->sync_section_subjects($section_id, $sem->id);
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE)
			{
				$this->session->set_flashdata('admin_error', 'Could not create the section. Please try again.');
				redirect('academic/sections');
			}

			$msg = 'Section created.';
			if ($synced > 0)
			{
				$msg .= ' ' . $synced . ' subject(s) auto-populated from the curriculum.';
			}
			else
			{
				$msg .= ' No subjects auto-populated (no predefined curriculum for this strand/year/semester). Use \'Sync Subjects\' later once the curriculum covers this strand/year/semester.';
			}
			$this->session->set_flashdata('admin_success', $msg);
			redirect('academic/sections');
		}

		$this->_flash_errors($errors);
		redirect('academic/sections');
	}

	public function section_update($id)
	{
		$this->_require_post();
		$id = (int) $id;
		if ( ! $this->Academic_model->get_section($id))
		{
			$this->session->set_flashdata('admin_error', 'Section not found.');
			redirect('academic/sections');
		}

		$name = trim($this->input->post('name'));
		$program_id = (int) $this->input->post('program_id');
		$year_level = (int) $this->input->post('year_level');
		$building_id = (int) $this->input->post('building_id');
		if ($building_id > 0 && ! $this->Academic_model->get_building($building_id))
		{
			$building_id = 0;
		}
		$errors = $this->_validate_section($name, $id);
		$errors = array_merge($errors, $this->_validate_section_program_slot($program_id, $year_level));
		if ($building_id <= 0)
		{
			$errors[] = 'Home Building is required. Every section belongs to a building, and its classes can only be assigned to rooms in that building — pick a building (add one in Rooms & Buildings if needed).';
		}

		if (empty($errors))
		{
			$this->Academic_model->update_section($id, $name, $program_id, $year_level, $building_id > 0 ? $building_id : NULL);
			$this->session->set_flashdata('admin_success', 'Section updated. Use \'Sync Subjects\' to refresh its subjects from the curriculum.');
			redirect('academic/sections');
		}

		$this->_flash_errors($errors);
		redirect('academic/sections?edit=' . $id);
	}

	public function section_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$target = $this->Academic_model->get_section($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'Section not found.');
			redirect('academic/sections');
		}

		// A section can only be deleted when it has no real data attached.
		// Auto-synced curriculum links (section_subjects) are removed with it,
		// otherwise the FK (section_subjects.section_id -> sections.id) blocks it.
		$students  = (int) $this->db->where('section_id', $id)->count_all_results('students');
		$classes   = (int) $this->db->where('section_id', $id)->count_all_results('teacher_subject_assignments');
		if ($students > 0 || $classes > 0)
		{
			$why = array();
			if ($students > 0) { $why[] = $students . ' enrolled student(s)'; }
			if ($classes > 0)  { $why[] = $classes . ' class(es)'; }
			$this->session->set_flashdata('admin_error',
				'This section cannot be deleted because it still has ' . implode(' and ', $why) .
				'. Move or reassign them first, or deactivate the section instead.');
			redirect('academic/sections');
		}

		$this->Academic_model->delete_section_subjects($id);
		$this->Academic_model->delete_section($id);
		$this->session->set_flashdata('admin_success', 'Section deleted.');
		redirect('academic/sections');
	}

	/**
	 * Activate/deactivate a section (POST). Inactive sections are kept in
	 * the database with their data intact — they are just no longer offered
	 * when creating/enrolling new students.
	 */
	public function section_status($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$target = $this->Academic_model->get_section($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'Section not found.');
			redirect('academic/sections');
		}

		$is_active = $this->input->post('status') === 'active';
		$this->Academic_model->set_section_active($id, $is_active);
		$this->session->set_flashdata('admin_success',
			'Section ' . $target->name . ' is now ' . ($is_active ? 'open for enrollment' : 'closed for enrollment') . '.');
		redirect('academic/sections');
	}

	/**
	 * Re-run the curriculum->section_subjects population for a section
	 * (the "Sync Subjects from Curriculum" button). Idempotent thanks to
	 * the UNIQUE (section_id, subject_id, semester_id) key.
	 */
	public function section_sync_subjects($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$section = $this->Academic_model->get_section($id);
		if ( ! $section)
		{
			$this->session->set_flashdata('admin_error', 'Section not found.');
			redirect('academic/sections');
		}

		$sem = $this->Academic_model->active_semesters();
		if ( ! $sem)
		{
			$this->session->set_flashdata('admin_error', 'No active semester. Activate one first, then sync subjects.');
			redirect('academic/sections');
		}

		if ($section->program_id === NULL || $section->year_level === NULL)
		{
			$this->session->set_flashdata('admin_error', 'This section has no strand/year level set. Edit it first, then sync.');
			redirect('academic/sections');
		}

		if ($sem->semester_number === NULL)
		{
			$this->session->set_flashdata('admin_error',
				'The active semester has no semester number (1st/2nd). Set the semester number on the semester so its predefined curriculum can be applied.');
			redirect('academic/sections');
		}

		$synced = $this->Academic_model->sync_section_subjects($id, $sem->id);
		$this->session->set_flashdata('admin_success',
			$synced > 0
				? $synced . ' subject(s) synced from the curriculum for ' . html_escape($section->name) . '.'
				: 'No new subjects to sync for ' . html_escape($section->name) . ' (already up to date, or no curriculum defined for its strand/year/semester).');
		redirect('academic/sections');
	}

	// =================================================================
	// Rooms & Buildings
	// =================================================================

	/**
	 * Rooms & Buildings — manage buildings and the rooms inside them, and
	 * see the current weekly occupancy (active semester) of every room.
	 */
	public function rooms()
	{
		$buildings = $this->Academic_model->buildings();
		$rooms     = $this->Academic_model->rooms();
		$sem       = $this->Academic_model->active_semesters();
		$occupancy = $sem
			? $this->Academic_model->occupancy_by_room($sem->id)
			: array();

		// Stats for the header tiles.
		$booked_rooms = 0;
		$total_rooms  = count($rooms);
		foreach ($occupancy as $room_id => $slots)
		{
			if ( ! empty($slots))
			{
				$booked_rooms++;
			}
		}

		$this->data['active_page'] = 'rooms';
		$this->_render('admin/rooms', array(
			'buildings'   => $buildings,
			'rooms'       => $rooms,
			'occupancy'   => $occupancy,
			'grid_hours'  => $this->Academic_model->occupancy_grid_hours(),
			'day_tokens'  => $this->Academic_model->day_tokens(),
			'schedulable' => $sem ? $this->Academic_model->schedulable_classes($sem->id) : array(),
			'sections_by_building' => $this->Academic_model->sections_by_building(),
			'active_sem'  => $sem,
			'subtitle'    => 'Administration',
			'stats'       => array(
				'buildings'    => count($buildings),
				'rooms'        => $total_rooms,
				'booked_rooms' => $booked_rooms,
			),
		));
	}

	/**
	 * Opt-in demo data for the Rooms page: creates a "Demo Building" with
	 * a "Demo Room 101" and, when a teacher-assigned class exists in the
	 * active semester, books the first free Mon 8:00-9:00 slot into it so
	 * the weekly grid immediately shows how scheduling works.
	 * POST only.
	 */
	public function room_demo()
	{
		$this->_require_post();
		$sem = $this->Academic_model->active_semesters();

		$b = $this->Academic_model->building_by_name('Demo Building');
		$b_id = $b ? (int) $b->id : (int) $this->Academic_model->create_building('Demo Building');

		// Reuse an existing demo room, otherwise create one.
		$demo_room = NULL;
		foreach ($this->Academic_model->rooms($b_id) as $r)
		{
			if (stripos($r->name, 'Demo') !== FALSE)
			{
				$demo_room = $r;
				break;
			}
		}
		if ( ! $demo_room)
		{
			$room_id  = (int) $this->Academic_model->create_room($b_id, 'Demo Room 101');
			$demo_room = $this->Academic_model->get_room($room_id);
		}
		$room_id = (int) $demo_room->id;

		// Book the first free slot for the first unscheduled class, and make
		// its section "live" in the demo building (home building) so the
		// own-building assignment flow is visible.
		$booked = FALSE;
		$booked_label = '';
		if ($sem)
		{
			// Only book a class whose section has no home building yet (it then
			// becomes a section of the demo building) or already belongs to the
			// demo building — never move a section across buildings.
			$candidate = NULL;
			foreach ($this->Academic_model->schedulable_classes($sem->id) as $c)
			{
				if ( ! empty($c->room_id)) { continue; }
				$home = $c->section_building_id !== NULL ? (int) $c->section_building_id : 0;
				if ($home === 0) { $candidate = $c; break; }
				if ($home === $b_id && ! $candidate) { $candidate = $c; }
			}
			if ($candidate)
			{
				$slot = $this->_first_free_slot($sem->id, $room_id, (int) $candidate->assignment_id);
				if ($slot)
				{
					$this->Academic_model->save_class_schedule(
						(int) $candidate->assignment_id, $room_id,
						$slot['day_bits'], $slot['start'], $slot['end']
					);
					// Set the section's home building to the demo building (if unset).
					$this->db->where('id', (int) $candidate->section_id)
						->where('building_id IS NULL', NULL, FALSE)
						->update('sections', array('building_id' => $b_id));
					$booked = TRUE;
					$booked_label = $candidate->section_name . ' · ' . $candidate->subject_code;
				}
			}
		}

		$msg = 'Demo data ready: Demo Building → Demo Room 101 created.';
		if ($booked)
		{
			$msg .= ' Sample class booked: ' . $booked_label . ' (Mon 8:00–9:00).';
		}
		else
		{
			$msg .= ' No class could be auto-booked (no teacher-assigned class in the active semester). Click “Schedule” on the room to book one.';
		}
		$this->session->set_flashdata('admin_success', $msg);
		redirect('academic/rooms');
	}

	/**
	 * First free Mon-Fri morning slot in a room for a class.
	 * @return array|null ['day_bits','start','end']
	 */
	private function _first_free_slot($semester_id, $room_id, $assignment_id)
	{
		$candidates = array(
			array(1, 8 * 60, 9 * 60),
			array(1, 9 * 60, 10 * 60),
			array(2, 8 * 60, 9 * 60),
			array(2, 10 * 60, 11 * 60),
			array(4, 8 * 60, 9 * 60),
			array(8, 8 * 60, 9 * 60),
			array(16, 8 * 60, 9 * 60),
		);
		foreach ($candidates as $c)
		{
			if (empty($this->Academic_model->room_conflicts(
				(int) $semester_id, (int) $room_id, $c[0], $c[1], $c[2], (int) $assignment_id)))
			{
				return array('day_bits' => $c[0], 'start' => $c[1], 'end' => $c[2]);
			}
		}
		return NULL;
	}

	public function building_store()
	{
		$this->_require_post();
		$name = trim((string) $this->input->post('name'));
		$errors = array();
		if (mb_strlen($name) < 2 || mb_strlen($name) > 100)
		{
			$errors[] = 'Building name must be between 2 and 100 characters.';
		}
		elseif ($this->Academic_model->building_by_name($name))
		{
			$errors[] = 'That building already exists.';
		}

		if (empty($errors))
		{
			$this->Academic_model->create_building($name);
			$this->session->set_flashdata('admin_success', 'Building created.');
		}
		else
		{
			$this->_flash_errors($errors);
		}
		redirect('academic/rooms');
	}

	public function building_update($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$name = trim((string) $this->input->post('name'));
		if ( ! $this->Academic_model->get_building($id))
		{
			$this->session->set_flashdata('admin_error', 'Building not found.');
			redirect('academic/rooms');
		}

		$errors = array();
		if (mb_strlen($name) < 2 || mb_strlen($name) > 100)
		{
			$errors[] = 'Building name must be between 2 and 100 characters.';
		}
		else
		{
			$dup = $this->Academic_model->building_by_name($name);
			if ($dup && (int) $dup->id !== $id)
			{
				$errors[] = 'That building already exists.';
			}
		}

		if (empty($errors))
		{
			$this->Academic_model->update_building($id, $name);
			$this->session->set_flashdata('admin_success', 'Building updated.');
		}
		else
		{
			$this->_flash_errors($errors);
		}
		redirect('academic/rooms');
	}

	public function building_status($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$building = $this->Academic_model->get_building($id);
		if ( ! $building)
		{
			$this->session->set_flashdata('admin_error', 'Building not found.');
			redirect('academic/rooms');
		}
		$is_active = $this->input->post('status') === 'active';
		$this->Academic_model->set_building_active($id, $is_active);
		$this->session->set_flashdata('admin_success',
			'Building ' . html_escape($building->name) . ' is now ' . ($is_active ? 'active' : 'inactive') . '.');
		redirect('academic/rooms');
	}

	public function building_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$building = $this->Academic_model->get_building($id);
		if ( ! $building)
		{
			$this->session->set_flashdata('admin_error', 'Building not found.');
			redirect('academic/rooms');
		}

		$room_count = (int) $this->db->where('building_id', $id)->count_all_results('rooms');
		if ($room_count > 0)
		{
			$this->session->set_flashdata('admin_error',
				'This building still contains ' . $room_count . ' room(s). Move or delete them first.');
			redirect('academic/rooms');
		}

		$this->Academic_model->delete_building($id);
		$this->session->set_flashdata('admin_success', 'Building deleted.');
		redirect('academic/rooms');
	}

	public function room_store()
	{
		$this->_require_post();
		$building_id = (int) $this->input->post('building_id');
		$name        = trim((string) $this->input->post('name'));
		$errors = $this->_validate_room($building_id, $name);

		if (empty($errors))
		{
			$this->Academic_model->create_room($building_id, $name);
			$this->session->set_flashdata('admin_success', 'Room created.');
		}
		else
		{
			$this->_flash_errors($errors);
		}
		redirect('academic/rooms');
	}

	public function room_update($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$building_id = (int) $this->input->post('building_id');
		$name        = trim((string) $this->input->post('name'));
		if ( ! $this->Academic_model->get_room($id))
		{
			$this->session->set_flashdata('admin_error', 'Room not found.');
			redirect('academic/rooms');
		}

		$errors = $this->_validate_room($building_id, $name, $id);
		if (empty($errors))
		{
			$this->Academic_model->update_room($id, $building_id, $name);
			$this->session->set_flashdata('admin_success', 'Room updated.');
		}
		else
		{
			$this->_flash_errors($errors);
		}
		redirect('academic/rooms');
	}

	/**
	 * Toggle a room between Available and Under Maintenance (POST).
	 * Maintenance rooms keep their bookings/history but are hidden from
	 * the class-schedule pickers until marked available again.
	 */
	public function room_status($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$room = $this->Academic_model->get_room($id);
		if ( ! $room)
		{
			$this->session->set_flashdata('admin_error', 'Room not found.');
			redirect('academic/rooms');
		}
		$status = $this->input->post('status');
		// Accept the new vocabulary plus the old one for safety.
		$is_active = $status === 'active' || $status === 'available';
		$this->Academic_model->set_room_active($id, $is_active);
		$this->session->set_flashdata('admin_success',
			'Room ' . html_escape($room->name) . ' is now ' . ($is_active ? 'available' : 'under maintenance') . '.');
		redirect('academic/rooms');
	}

	public function room_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$room = $this->Academic_model->get_room($id);
		if ( ! $room)
		{
			$this->session->set_flashdata('admin_error', 'Room not found.');
			redirect('academic/rooms');
		}

		$bookings = (int) $this->db->where('room_id', $id)->count_all_results('teacher_subject_assignments');
		if ($bookings > 0)
		{
			$this->session->set_flashdata('admin_error',
				'This room is used by ' . $bookings . ' class booking(s). Remove those schedules first.');
			redirect('academic/rooms');
		}

		$this->Academic_model->delete_room($id);
		$this->session->set_flashdata('admin_success', 'Room deleted.');
		redirect('academic/rooms');
	}

	/**
	 * Validate a room submission (building + unique name inside building).
	 * @param int    $building_id
	 * @param string $name
	 * @param int    $except_id  room id when editing
	 * @return array error strings
	 */
	private function _validate_room($building_id, $name, $except_id = NULL)
	{
		$errors = array();
		if ( ! $this->Academic_model->get_building($building_id))
		{
			$errors[] = 'A valid building is required.';
		}
		if (mb_strlen($name) < 1 || mb_strlen($name) > 100)
		{
			$errors[] = 'Room name must be between 1 and 100 characters.';
		}
		else
		{
			$dup = $this->db->where('name', $name)
				->where('building_id', $building_id);
			if ($except_id !== NULL)
			{
				$dup = $dup->where('id !=', (int) $except_id);
			}
			if ($dup->count_all_results('rooms') > 0)
			{
				$errors[] = 'That room already exists in this building.';
			}
		}
		return $errors;
	}

	/**
	 * Save the schedule (room + weekdays + time range) for one class.
	 * A class can only be scheduled once a teacher is assigned to it — the
	 * schedule lives on the teacher_subject_assignments row.
	 *
	 * POST only. AJAX requests receive JSON (with a refreshed CSRF token);
	 * non-AJAX POSTs keep the flash + redirect fallback.
	 */
	public function section_schedule_save()
	{
		$this->_require_post();
		$ajax = $this->input->is_ajax_request();

		$assignment_id = (int) $this->input->post('assignment_id');
		$room_id       = (int) $this->input->post('room_id');
		$day_bits      = (int) $this->input->post('day_bits');
		$start_min     = (int) $this->input->post('start_min');
		$end_min       = (int) $this->input->post('end_min');

		$assignment = $assignment_id > 0 ? $this->Academic_model->get_assignment($assignment_id) : NULL;
		$sem = $this->Academic_model->active_semesters();

		$errors = array();
		if ( ! $assignment)
		{
			$errors[] = 'A valid class (with an assigned teacher) is required.';
		}
		if ( ! $sem)
		{
			$errors[] = 'There is no active semester. Activate one first.';
		}
		if (empty($errors) && $assignment->semester_id != $sem->id)
		{
			$errors[] = 'This class belongs to a different semester than the active one.';
		}

		$room = $room_id > 0 ? $this->Academic_model->get_room($room_id) : NULL;
		if ( ! $room || ! (int) $room->is_active)
		{
			$errors[] = 'A valid active room is required.';
		}

		// A section belongs to its own building: it can only be assigned to
		// rooms inside that building. Sections without a home building cannot
		// be assigned anywhere until one is set in Sections.
		if (empty($errors) && $assignment)
		{
			if ($assignment->section_building_id === NULL)
			{
				$errors[] = $assignment->section_name . ' has no Home Building yet. Set its Home Building in Sections (edit the section) first, then assign it to a room.';
			}
			elseif ($room && (int) $assignment->section_building_id !== (int) $room->building_id)
			{
				$home = $this->Academic_model->get_building((int) $assignment->section_building_id);
				$home_name = $home ? $home->name : 'its home building';
				$errors[] = $assignment->section_name . ' belongs to ' . $home_name . ' — a section can only be assigned to rooms in its own building. Pick a room in ' . $home_name . '.';
			}
		}
		if ($day_bits <= 0 || $day_bits > 31)
		{
			$errors[] = 'Pick at least one weekday (Mon-Fri).';
		}
		$dur = $end_min - $start_min;
		if ($start_min < 6 * 60 || $end_min > 20 * 60 || $dur < 60 || $dur > 180)
		{
			$errors[] = 'Schedule must be 1-3 hours long, between 6:00 AM and 8:00 PM.';
		}

		if (empty($errors))
		{
			$conflicts = $this->Academic_model->room_conflicts(
				(int) $sem->id, $room_id, $day_bits, $start_min, $end_min, $assignment_id
			);
			if ( ! empty($conflicts))
			{
				$labels = array();
				foreach ($conflicts as $c)
				{
					$labels[] = $c->section_name . ' · ' . $c->subject_code
						. ' (' . $this->Academic_model->compose_schedule_text($c->day_bits, $c->start_min, $c->end_min) . ')';
				}
				$errors[] = 'This room is already occupied at that time by: ' . implode('; ', $labels)
					. '. Pick a free time slot instead.';
			}
		}

		// A teacher cannot be in two rooms at the same time, so the same
		// overlap check applies to the teacher's other classes as well.
		if (empty($errors) && ! empty($assignment->teacher_user_id))
		{
			$teacher_clashes = $this->Academic_model->teacher_conflicts(
				(int) $sem->id, (int) $assignment->teacher_user_id,
				$day_bits, $start_min, $end_min, $assignment_id
			);
			if ( ! empty($teacher_clashes))
			{
				$labels = array();
				foreach ($teacher_clashes as $c)
				{
					$labels[] = $c->section_name . ' · ' . $c->subject_code
						. ' (' . $this->Academic_model->compose_schedule_text($c->day_bits, $c->start_min, $c->end_min) . ')';
				}
				$errors[] = 'This teacher is already teaching at that time in: ' . implode('; ', $labels)
					. '. A teacher cannot be in two rooms at once.';
			}
		}

		if (empty($errors))
		{
			$ok = $this->Academic_model->save_class_schedule($assignment_id, $room_id, $day_bits, $start_min, $end_min);
			if ( ! $ok)
			{
				$errors[] = 'Could not save the schedule. Please try again.';
			}
			elseif ($ajax)
			{
				$this->_json_response(array(
					'ok'          => TRUE,
					'schedule'    => $this->Academic_model->compose_schedule_text($day_bits, $start_min, $end_min),
					'room_label'  => ($room->building_name ? $room->building_name . ' · ' : '') . $room->name,
				));
				return;
			}
		}

		if ($ajax)
		{
			$this->_json_response(array(
				'ok'      => FALSE,
				'message' => implode(' ', $errors),
			));
			return;
		}

		if (empty($errors))
		{
			$this->session->set_flashdata('admin_success',
				'Schedule saved for ' . html_escape($assignment->section_name . ' · ' . $assignment->subject_code)
				. ' in ' . html_escape(($room->building_name ? $room->building_name . ' · ' : '') . $room->name) . '.');
		}
		else
		{
			$this->_flash_errors($errors);
		}
		redirect('academic/sections');
	}

	/**
	 * Remove the schedule (room + time) from a class, keeping the teacher.
	 * POST only, AJAX-aware like section_schedule_save().
	 */
	public function section_schedule_remove()
	{
		$this->_require_post();
		$ajax = $this->input->is_ajax_request();
		$assignment_id = (int) $this->input->post('assignment_id');
		$assignment = $assignment_id > 0 ? $this->Academic_model->get_assignment($assignment_id) : NULL;

		if ( ! $assignment)
		{
			$message = 'A valid class is required.';
		}
		else
		{
			$ok = $this->Academic_model->save_class_schedule($assignment_id);
			if ( ! $ok)
			{
				$message = 'Could not remove the schedule. Please try again.';
			}
		}

		if ($ajax)
		{
			if ($assignment && ! empty($ok))
			{
				$this->_json_response(array('ok' => TRUE));
				return;
			}
			$this->_json_response(array('ok' => FALSE, 'message' => isset($message) ? $message : 'Could not remove the schedule.'));
			return;
		}

		if ($assignment && ! empty($ok))
		{
			$this->session->set_flashdata('admin_success', 'Schedule removed.');
		}
		else
		{
			$this->session->set_flashdata('admin_error', isset($message) ? $message : 'Could not remove the schedule.');
		}
		redirect('academic/sections');
	}

	// =================================================================
	// Subjects
	// =================================================================

	public function subjects()
	{
		$rows = $this->Academic_model->subjects();

		$stats = array();
		foreach ($rows as $s)
		{
			$classes = (int) $this->db->where('subject_id', $s->id)->count_all_results('teacher_subject_assignments');
			$stats[$s->id] = array('classes' => $classes);
		}

		$edit = NULL;
		$edit_id = (int) $this->input->get('edit');
		if ($edit_id > 0)
		{
			$edit = $this->Academic_model->get_subject($edit_id);
		}

		$this->data['active_page'] = 'subjects';
		$this->_render('admin/subjects', array(
			'rows'     => $rows,
			'stats'    => $stats,
			'edit'     => $edit,
			'subtitle' => 'Administration',
		));
	}

	public function subject_store()
	{
		$this->_require_post();
		$code  = trim($this->input->post('code'));
		$title = trim($this->input->post('title'));
		$units = (float) $this->input->post('units');
		$errors = $this->_validate_subject($code, $title, $units);

		if (empty($errors))
		{
			$this->Academic_model->create_subject($code, $title, $units);
			$this->session->set_flashdata('admin_success', 'Subject created.');
			redirect('academic/subjects');
		}

		$this->_flash_errors($errors);
		redirect('academic/subjects');
	}

	public function subject_update($id)
	{
		$this->_require_post();
		$id = (int) $id;
		if ( ! $this->Academic_model->get_subject($id))
		{
			$this->session->set_flashdata('admin_error', 'Subject not found.');
			redirect('academic/subjects');
		}

		$code  = trim($this->input->post('code'));
		$title = trim($this->input->post('title'));
		$units = (float) $this->input->post('units');
		$errors = $this->_validate_subject($code, $title, $units, $id);

		if (empty($errors))
		{
			$this->Academic_model->update_subject($id, $code, $title, $units);
			$this->session->set_flashdata('admin_success', 'Subject updated.');
			redirect('academic/subjects');
		}

		$this->_flash_errors($errors);
		redirect('academic/subjects?edit=' . $id);
	}

	public function subject_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$target = $this->Academic_model->get_subject($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'Subject not found.');
			redirect('academic/subjects');
		}

		$error = $this->_subject_delete_errors($id);
		if ($error !== '')
		{
			$this->session->set_flashdata('admin_error', $error);
			redirect('academic/subjects');
		}

		$this->Academic_model->delete_subject($id);
		$this->session->set_flashdata('admin_success', 'Subject deleted.');
		redirect('academic/subjects');
	}

	// =================================================================
	// Programs (displayed to admins as "Strands")
	// =================================================================

	public function programs()
	{
		$rows = $this->Academic_model->programs();

		$stats = array();
		foreach ($rows as $p)
		{
			$sections   = (int) $this->db->where('program_id', $p->id)->count_all_results('sections');
			$subjects   = (int) $this->db->where('program_id', $p->id)->count_all_results('curriculum_subjects');
			$stats[$p->id] = array('sections' => $sections, 'subjects' => $subjects);
		}

		$edit = NULL;
		$edit_id = (int) $this->input->get('edit');
		if ($edit_id > 0)
		{
			$edit = $this->Academic_model->get_program($edit_id);
		}

		$this->data['active_page'] = 'programs';
		$this->_render('admin/programs', array(
			'rows'     => $rows,
			'stats'    => $stats,
			'edit'     => $edit,
			'subtitle' => 'Administration',
		));
	}

	public function program_store()
	{
		$this->_require_post();
		$program_code = strtoupper(trim((string) $this->input->post('program_code')));
		$short_code   = strtoupper(trim((string) $this->input->post('short_code')));
		$program_name = trim((string) $this->input->post('program_name'));
		$errors = $this->_validate_program($program_code, $short_code, $program_name);

		if (empty($errors))
		{
			$this->Academic_model->create_program($program_code, $short_code, $program_name);
			$this->session->set_flashdata('admin_success', 'Strand created.');
			redirect('academic/programs');
		}

		$this->_flash_errors($errors);
		redirect('academic/programs');
	}

	public function program_update($id)
	{
		$this->_require_post();
		$id = (int) $id;
		if ( ! $this->Academic_model->get_program($id))
		{
			$this->session->set_flashdata('admin_error', 'Strand not found.');
			redirect('academic/programs');
		}

		$program_code = strtoupper(trim((string) $this->input->post('program_code')));
		$short_code   = strtoupper(trim((string) $this->input->post('short_code')));
		$program_name = trim((string) $this->input->post('program_name'));
		$errors = $this->_validate_program($program_code, $short_code, $program_name, $id);

		if (empty($errors))
		{
			$this->Academic_model->update_program($id, $program_code, $short_code, $program_name);
			$this->session->set_flashdata('admin_success', 'Strand updated.');
			redirect('academic/programs');
		}

		$this->_flash_errors($errors);
		redirect('academic/programs?edit=' . $id);
	}

	public function program_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$target = $this->Academic_model->get_program($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'Strand not found.');
			redirect('academic/programs');
		}

		$sections  = (int) $this->db->where('program_id', $id)->count_all_results('sections');
		$curriculum = (int) $this->db->where('program_id', $id)->count_all_results('curriculum_subjects');
		if ($sections > 0 || $curriculum > 0)
		{
			$this->session->set_flashdata('admin_error',
				'This strand is used by ' . $sections . ' section(s) and ' . $curriculum .
				' curriculum entr' . ($curriculum === 1 ? 'y' : 'ies') . '. It cannot be deleted.');
			redirect('academic/programs');
		}

		$this->Academic_model->delete_program($id);
		$this->session->set_flashdata('admin_success', 'Strand deleted.');
		redirect('academic/programs');
	}

	// =================================================================
	// Curriculum (predefined SHS subjects per strand × grade × semester)
	// =================================================================

	public function curriculum()
	{
		$filter_program = (int) $this->input->get('program_id');
		$filter_year    = (int) $this->input->get('year_level');
		$filter_sem     = (int) $this->input->get('semester');

		$valid_year = ($filter_year === 11 || $filter_year === 12);
		$valid_sem  = in_array($filter_sem, array(1, 2), TRUE);
		$program    = $filter_program > 0 ? $this->Academic_model->get_program($filter_program) : NULL;

		$slot_selected = (bool) ($program && $valid_year && $valid_sem);

		$slot_subjects = array();
		$available = array();
		if ($slot_selected)
		{
			$slot_subjects = $this->Academic_model->curriculum_slots($filter_program, $filter_year, $filter_sem);

			$in_slot = array();
			foreach ($slot_subjects as $r)
			{
				$in_slot[(int) $r->subject_id] = TRUE;
			}
			foreach ($this->Academic_model->subjects() as $s)
			{
				if ( ! isset($in_slot[(int) $s->id]))
				{
					$available[] = $s;
				}
			}
		}

		$this->data['active_page'] = 'curriculum';
		$this->_render('admin/curriculum', array(
			'programs'      => $this->Academic_model->programs(),
			'available'     => $available,
			'slot_subjects' => $slot_subjects,
			'filter'        => array(
				'program_id' => $filter_program,
				'year_level' => $filter_year,
				'semester'   => $filter_sem,
			),
			'slot_selected' => $slot_selected,
			'slot_program'  => $program,
			'subtitle'      => 'Administration',
		));
	}

	public function curriculum_store()
	{
		$this->_require_post();
		$program_id      = (int) $this->input->post('program_id');
		$year_level      = (int) $this->input->post('year_level');
		$semester_number = (int) $this->input->post('semester_number');
		$subject_id      = (int) $this->input->post('subject_id');

		$errors = $this->_validate_curriculum_slot($program_id, $year_level, $semester_number, $subject_id);
		$back = 'academic/curriculum?program_id=' . $program_id
			. '&year_level=' . $year_level
			. '&semester=' . $semester_number;

		if (empty($errors))
		{
			$this->_link_subject_to_slot($program_id, $year_level, $semester_number, $subject_id);
			redirect($back);
		}

		$this->_flash_errors($errors);
		redirect($back);
	}

	public function curriculum_delete($id)
	{
		$this->_require_post();
		$this->_curriculum_remove_flow((int) $id, 'academic/programs');
	}

	// =================================================================
	// Strand Detail (merged Subjects + Curriculum management)
	// =================================================================

	/**
	 * The legacy standalone Subjects page is no longer part of the sidebar.
	 * Old direct bookmarks get forwarded to the Strands list, where subject
	 * management now lives inside each strand.
	 */
	public function subjects_redirect()
	{
		redirect('academic/programs');
	}

	/**
	 * Same as subjects_redirect() for the old standalone Curriculum page.
	 */
	public function curriculum_redirect()
	{
		redirect('academic/programs');
	}

	/**
	 * Strand Detail — one strand's subjects and curriculum in one place.
	 * The subject catalog is managed here (create/link/edit/delete) and the
	 * same curriculum_subjects linking table is written as before; only the
	 * admin UI entry point moved.
	 *
	 * @param int $program_id
	 */
	public function strand_detail($program_id)
	{
		$program_id = (int) $program_id;
		$program = $this->Academic_model->get_program($program_id);
		if ( ! $program)
		{
			$this->session->set_flashdata('admin_error', 'Strand not found.');
			redirect('academic/programs');
		}

		$year_level = (int) $this->input->get('year_level');
		if ( ! in_array($year_level, array(11, 12), TRUE))
		{
			$year_level = 11;
		}

		// All slots for this strand, grouped by grade → semester.
		$slots = array(11 => array(1 => array(), 2 => array()), 12 => array(1 => array(), 2 => array()));
		foreach ($this->Academic_model->curriculum_slots($program_id) as $r)
		{
			$y = (int) $r->year_level;
			$s = (int) $r->semester_number;
			if (isset($slots[$y][$s]))
			{
				$slots[$y][$s][] = $r;
			}
		}

		// Per-slot remaining subjects for the "link an existing subject"
		// picker (subjects already in the slot are not offered again).
		$all_subjects = $this->Academic_model->subjects();
		$available = array();
		foreach (array(11, 12) as $y)
		{
			foreach (array(1, 2) as $s)
			{
				$in_slot = array();
				foreach ($slots[$y][$s] as $r)
				{
					$in_slot[(int) $r->subject_id] = TRUE;
				}
				$available[$y][$s] = array();
				foreach ($all_subjects as $sub)
				{
					if ( ! isset($in_slot[(int) $sub->id]))
					{
						$available[$y][$s][] = array(
							'id'    => (int) $sub->id,
							'label' => $sub->code . ' — ' . $sub->title,
						);
					}
				}
			}
		}

		$this->data['active_page'] = 'programs';
		$this->data['title'] = 'Strand Detail';
		$this->_render('admin/strand_detail', array(
			'program'    => $program,
			'year_level' => $year_level,
			'slots'      => $slots,
			'available'  => $available,
			'subtitle'   => 'Administration',
		));
	}

	/**
	 * Add Subject on Strand Detail: link an existing subject row, or create
	 * a brand new subjects row AND link it to this slot in one submission.
	 * Both paths go through the exact same validation as the standalone
	 * subject_store()/curriculum_store() flows.
	 */
	public function strand_add_subject()
	{
		$this->_require_post();
		$program_id      = (int) $this->input->post('program_id');
		$year_level      = (int) $this->input->post('year_level');
		$semester_number = (int) $this->input->post('semester_number');

		if ( ! $this->Academic_model->get_program($program_id))
		{
			$this->session->set_flashdata('admin_error', 'A valid strand is required.');
			redirect('academic/programs');
		}
		$back = 'academic/strands/' . $program_id . '?year_level=' . $year_level;

		$mode = $this->input->post('subject_mode');
		if ( ! in_array($mode, array('existing', 'new'), TRUE))
		{
			$mode = 'existing';
		}

		if ($mode === 'new')
		{
			$code  = strtoupper(trim((string) $this->input->post('code')));
			$title = trim((string) $this->input->post('title'));
			$units = (float) $this->input->post('units');
			$errors = $this->_validate_subject($code, $title, $units);

			if (empty($errors))
			{
				// Create the subject row AND its curriculum link atomically.
				$this->db->trans_start();
				$subject_id = $this->Academic_model->create_subject($code, $title, $units);
				$linked = $this->Academic_model->add_curriculum_slot($program_id, $year_level, $semester_number, $subject_id);
				$this->db->trans_complete();

				if ($this->db->trans_status() !== FALSE && $linked)
				{
					$this->session->set_flashdata('admin_success',
						html_escape($code) . ' created and linked to ' . html_escape($this->Academic_model->get_program($program_id)->short_code) . '.');
					redirect($back);
				}
				$this->session->set_flashdata('admin_error', 'Could not create and link the subject. Please try again.');
				redirect($back);
			}
			$this->_flash_errors($errors);
			redirect($back);
		}

		// "existing" path — link an already-created subject row into the slot.
		$subject_id = (int) $this->input->post('subject_id');
		$errors = $this->_validate_curriculum_slot($program_id, $year_level, $semester_number, $subject_id);
		if (empty($errors))
		{
			$this->_link_subject_to_slot($program_id, $year_level, $semester_number, $subject_id);
			redirect($back);
		}
		$this->_flash_errors($errors);
		redirect($back);
	}

	/**
	 * Remove a subject from one strand's slot (Strand Detail). Guarded by the
	 * same actively-graded check as the legacy curriculum removal.
	 * @param int $id curriculum_subjects row id
	 */
	public function strand_remove($id)
	{
		$this->_require_post();
		$row = $this->Academic_model->get_curriculum_slot((int) $id);
		if ( ! $row)
		{
			$this->session->set_flashdata('admin_error', 'Curriculum entry not found.');
			redirect('academic/programs');
		}
		$this->_curriculum_remove_flow((int) $id,
			'academic/strands/' . (int) $row->program_id . '?year_level=' . (int) $row->year_level);
	}

	/**
	 * Edit a subject's core details from Strand Detail. Because a subject can
	 * be shared across strands, the change applies everywhere it is used —
	 * the same semantics as the standalone subject_update().
	 * @param int $id subject id
	 */
	public function strand_subject_update($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$back = 'academic/programs';
		$program_id = (int) $this->input->post('program_id');
		$year_level = (int) $this->input->post('year_level');
		if ($program_id > 0)
		{
			$back = 'academic/strands/' . $program_id . '?year_level=' . $year_level;
		}

		$subject = $this->Academic_model->get_subject($id);
		if ( ! $subject)
		{
			$this->session->set_flashdata('admin_error', 'Subject not found.');
			redirect($back);
		}

		$code  = trim($this->input->post('code'));
		$title = trim($this->input->post('title'));
		$units = (float) $this->input->post('units');
		$errors = $this->_validate_subject($code, $title, $units, $id);

		if (empty($errors))
		{
			$this->Academic_model->update_subject($id, $code, $title, $units);
			$this->session->set_flashdata('admin_success', 'Subject updated. Changes apply everywhere this subject is used.');
			redirect($back);
		}

		$this->_flash_errors($errors);
		redirect($back);
	}

	/**
	 * Fully delete a subject from Strand Detail. Same guard as the standalone
	 * subject_delete(): allowed only when the subject has ZERO curriculum
	 * links anywhere, zero class assignments and zero grade records.
	 * @param int $id subject id
	 */
	public function strand_subject_delete($id)
	{
		$this->_require_post();
		$id = (int) $id;
		$back = 'academic/programs';
		$program_id = (int) $this->input->post('program_id');
		$year_level = (int) $this->input->post('year_level');
		if ($program_id > 0)
		{
			$back = 'academic/strands/' . $program_id . '?year_level=' . $year_level;
		}

		$target = $this->Academic_model->get_subject($id);
		if ( ! $target)
		{
			$this->session->set_flashdata('admin_error', 'Subject not found.');
			redirect($back);
		}

		$error = $this->_subject_delete_errors($id);
		if ($error !== '')
		{
			$this->session->set_flashdata('admin_error', $error);
			redirect($back);
		}

		$this->Academic_model->delete_subject($id);
		$this->session->set_flashdata('admin_success', 'Subject deleted.');
		redirect($back);
	}

	// =================================================================
	// Validation helpers
	// =================================================================

	private function _validate_section($name, $except_id = NULL)
	{
		$errors = array();
		if ( ! preg_match('/^[A-Z0-9\-]{2,20}$/', $name))
		{
			$errors[] = 'Section name must be 2-20 characters (letters, numbers and dashes), e.g. "11-STEM-1".';
		}
		elseif ($this->db->where('name', $name)->where('id !=', (int) $except_id)->count_all_results('sections') > 0)
		{
			$errors[] = 'That section already exists.';
		}
		return $errors;
	}

	private function _validate_subject($code, $title, $units, $except_id = NULL)
	{
		$errors = array();
		if ( ! preg_match('/^[A-Z0-9\-]{2,15}$/', $code))
		{
			$errors[] = 'Subject code must be 2-15 characters (letters, numbers and dashes), e.g. "CS101".';
		}
		elseif ($this->db->where('code', $code)->where('id !=', (int) $except_id)->count_all_results('subjects') > 0)
		{
			$errors[] = 'That subject code already exists.';
		}
		if (mb_strlen($title) < 3 || mb_strlen($title) > 120)
		{
			$errors[] = 'Subject title must be between 3 and 120 characters.';
		}
		if ($units < 0.5 || $units > 12.0)
		{
			$errors[] = 'Units must be between 0.5 and 12.0.';
		}
		return $errors;
	}

	/**
	 * Validate a program ("Strand") row: program code, short code and name.
	 * short_code is required, uppercase alphanumeric, max 10 chars and unique
	 * (same duplicate-check style as username/email/code on other entities).
	 * @return array error strings (empty = valid)
	 */
	private function _validate_program($program_code, $short_code, $program_name, $except_id = NULL)
	{
		$errors = array();
		if ( ! preg_match('/^[A-Z0-9\-]{2,20}$/', $program_code))
		{
			$errors[] = 'Strand code must be 2-20 characters (letters, numbers and dashes), e.g. "STEM".';
		}
		elseif ($this->db->where('program_code', $program_code)
				->where('id !=', (int) $except_id)
				->count_all_results('programs') > 0)
		{
			$errors[] = 'That strand code already exists.';
		}
		if ( ! preg_match('/^[A-Z0-9]{1,10}$/', $short_code))
		{
			$errors[] = 'Short code must be 1-10 uppercase letters/numbers, e.g. "STEM".';
		}
		elseif ($this->db->where('short_code', $short_code)
				->where('id !=', (int) $except_id)
				->count_all_results('programs') > 0)
		{
			$errors[] = 'That short code is already used by another strand.';
		}
		if (mb_strlen($program_name) < 3 || mb_strlen($program_name) > 150)
		{
			$errors[] = 'Strand name must be between 3 and 150 characters.';
		}
		return $errors;
	}

	/**
	 * Validate the strand/year-level pair on a section form.
	 * @return array error strings (empty = valid)
	 */
	private function _validate_section_program_slot($program_id, $year_level)
	{
		$errors = array();
		if ( ! $this->Academic_model->get_program($program_id))
		{
			$errors[] = 'A valid strand is required.';
		}
		if ($year_level < 11 || $year_level > 12)
		{
			$errors[] = 'Year level must be 11 or 12 (Grade 11/Grade 12).';
		}
		return $errors;
	}

	/**
	 * Validate a curriculum slot add (strand × grade × semester → subject).
	 * @return array error strings (empty = valid)
	 */
	private function _validate_curriculum_slot($program_id, $year_level, $semester_number, $subject_id)
	{
		$errors = array();
		if ( ! $this->Academic_model->get_program($program_id))
		{
			$errors[] = 'A valid strand is required.';
		}
		if ( ! in_array($year_level, array(11, 12), TRUE))
		{
			$errors[] = 'Year level must be 11 or 12 (Grade 11/Grade 12).';
		}
		if ( ! in_array($semester_number, array(1, 2), TRUE))
		{
			$errors[] = 'Semester must be 1st or 2nd semester.';
		}
		if ( ! $this->Academic_model->get_subject($subject_id))
		{
			$errors[] = 'A valid subject is required.';
		}
		elseif ($this->Academic_model->curriculum_slot_exists($program_id, $year_level, $semester_number, $subject_id))
		{
			$errors[] = 'That subject is already in this curriculum slot.';
		}
		return $errors;
	}

	/**
	 * Add an EXISTING subject row to a curriculum slot and flash the outcome.
	 * Shared by the legacy curriculum_store() and the Strand Detail
	 * "link an existing subject" path so the linking logic lives in one place.
	 * @return bool TRUE when a new curriculum_subjects row was inserted
	 */
	private function _link_subject_to_slot($program_id, $year_level, $semester_number, $subject_id)
	{
		if ($this->Academic_model->add_curriculum_slot($program_id, $year_level, $semester_number, $subject_id))
		{
			$subject = $this->Academic_model->get_subject($subject_id);
			$this->session->set_flashdata('admin_success',
				html_escape($subject ? $subject->code : 'Subject') . ' added to the curriculum.');
			return TRUE;
		}
		$this->session->set_flashdata('admin_error', 'That subject is already in this curriculum slot.');
		return FALSE;
	}

	/**
	 * Remove a curriculum slot with the "actively graded" guard: a pairing
	 * that already has grade records for its strand/grade/semester is kept.
	 * Shared by the legacy curriculum_delete() and Strand Detail remove.
	 * @param int    $id   curriculum_subjects row id
	 * @param string $back redirect target after success/failure
	 */
	private function _curriculum_remove_flow($id, $back)
	{
		$row = $this->Academic_model->get_curriculum_slot($id);
		if ( ! $row)
		{
			$this->session->set_flashdata('admin_error', 'Curriculum entry not found.');
			redirect('academic/programs');
		}

		if ($this->Academic_model->curriculum_slot_has_grades(
			(int) $row->program_id, (int) $row->year_level, (int) $row->semester_number, (int) $row->subject_id))
		{
			$subject = $this->Academic_model->get_subject((int) $row->subject_id);
			$this->session->set_flashdata('admin_error',
				html_escape($subject ? $subject->code : 'This subject')
				. ' cannot be removed from this strand because students in this strand/grade/semester already have graded records for it.');
			redirect($back);
		}

		$this->Academic_model->delete_curriculum_slot($id);
		$this->session->set_flashdata('admin_success', 'Subject removed from the curriculum.');
		redirect($back);
	}

	/**
	 * Reason a subject cannot be fully deleted, '' when deletion is allowed.
	 * A subject stays deletable only while it has no curriculum links
	 * anywhere, no class assignments and no grade records.
	 * @param int $id subject id
	 * @return string
	 */
	private function _subject_delete_errors($id)
	{
		$id = (int) $id;
		$links   = $this->Academic_model->curriculum_links_for_subject($id);
		$classes = (int) $this->db->where('subject_id', $id)->count_all_results('teacher_subject_assignments');
		$grades  = (int) $this->db->where('subject_id', $id)->count_all_results('grades');
		if ($links > 0 || $classes > 0 || $grades > 0)
		{
			return 'This subject is still linked to ' . $links . ' curriculum entr'
				. ($links === 1 ? 'y' : 'ies') . ', assigned to ' . $classes . ' class(es) and has '
				. $grades . ' grade record(s). It cannot be deleted.';
		}
		return '';
	}

	protected function _flash_errors(array $errors)
	{
		if (empty($errors))
		{
			return;
		}
		$this->session->set_flashdata('admin_error', implode(' ', array_map('htmlspecialchars', $errors)));
	}

	/**
	 * Send a JSON response and stop. Used by AJAX endpoints so the admin can
	 * act on the result (e.g. update one card) without a page reload.
	 *
	 * CSRF is configured to regenerate on every submission, so the freshly
	 * minted token is returned here and the client swaps it into its hidden
	 * forms — this keeps consecutive AJAX submissions valid without a reload.
	 *
	 * @param array $payload
	 */
	private function _json_response(array $payload)
	{
		$payload['csrf_token_name'] = $this->security->get_csrf_token_name();
		$payload['csrf_hash']       = $this->security->get_csrf_hash();
		$this->output->set_status_header(isset($payload['ok']) && $payload['ok'] ? 200 : 422)
			->set_content_type('application/json')
			->set_output(json_encode($payload));
	}
}