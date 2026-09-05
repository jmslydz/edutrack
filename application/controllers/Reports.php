<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reports
 *
 * Academic reports + CSV/PDF export.
 *
 * Data scoping (security):
 *   - Admin may select "All Sections" or any section.
 *   - A Teacher's section options come ONLY from their own
 *     teacher_subject_assignments rows; any other section id (including
 *     "all") is rejected/reset server-side. Nothing here trusts a
 *     role/teacher_id passed from the browser.
 *   - Export endpoints re-run the exact same scoping logic.
 *
 * PDF export uses mPDF (Composer). CSV uses native fputcsv().
 */
class Reports extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->_require_roles(array('admin', 'teacher'));

		$this->load->model('Academic_model');
		$this->load->model('Enrollment_model');
		$this->load->model('Report_model');
	}

	public function index()
	{
		$ctx = $this->_resolve_context();

		$rows = array();
		$tiles = array('total' => 0, 'passed' => 0, 'failed' => 0, 'honors' => 0, 'class_gwa' => NULL);

		if ( ! $ctx['empty'])
		{
			if ($ctx['report_type'] === 'subject_performance')
			{
				$rows = $this->Report_model->subject_performance(
					$ctx['semester_id'], $ctx['section_id']
				);
				$passed = 0;
				$failed = 0;
				$avg_sum = 0.0;
				$avg_n = 0;
				foreach ($rows as $r)
				{
					$passed += $r->passed;
					$failed += $r->failed;
					if ($r->average !== NULL)
					{
						$avg_sum += $r->average;
						$avg_n++;
					}
				}
				$tiles = array(
					'total'     => count($rows),
					'passed'    => $passed,
					'failed'    => $failed,
					'honors'    => $avg_n,
					'class_gwa' => $avg_n > 0 ? round($avg_sum / $avg_n, 4) : NULL,
				);
			}
			else
			{
				$rows = $this->Report_model->summary(
					$ctx['semester_id'], $ctx['section_id']
				);
				if ($ctx['report_type'] === 'honor_roll')
				{
					$rows = array_filter($rows, function ($r) { return $r->honor !== NULL; });
					$rows = array_values($rows);
				}
				$tiles = $this->Report_model->tiles($rows);
			}
		}

		$this->data['role'] = $ctx['role'];
		$this->data['active_page'] = 'reports';
		$this->_render('reports/index', array(
			'ctx'         => $ctx,
			'rows'        => $rows,
			'tiles'       => $tiles,
			'semesters'   => $this->Academic_model->semesters(),
			'section_options' => $ctx['section_options'],
			'report_title' => $this->_report_title($ctx),
			'generated_at' => date('F j, Y'),
			'subtitle'    => $ctx['role'] === 'admin' ? 'Administration' : 'Faculty',
		));
	}

	public function export_csv()
	{
		$payload = $this->_export_payload();
		if ($payload === NULL)
		{
			return; // nothing to export — already redirected with a notice
		}
		$ctx  = $payload['ctx'];
		$rows = $payload['rows'];

		// Filename + headers
		$filename = 'edutrack-' . $ctx['report_type'] . '-' . date('Ymd-His') . '.csv';
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		$out = fopen('php://output', 'w');

		if ($ctx['report_type'] === 'subject_performance')
		{
			fputcsv($out, array('Subject Code', 'Subject', 'Section', 'Instructor', 'Units', 'Students Graded', 'Average', 'Passed', 'Failed'));
			foreach ($rows as $r)
			{
				fputcsv($out, array(
					$r->subject_code, $r->subject_title, $r->section, $r->instructor,
					$r->units, $r->students, $r->average, $r->passed, $r->failed,
				));
			}
		}
		else
		{
			fputcsv($out, array('Student No.', 'Full Name', 'Section', 'GWA', 'Units', 'Status', 'Latin Honor'));
			foreach ($rows as $r)
			{
				fputcsv($out, array(
					$r->student_no, $r->name, $r->section,
					$r->gwa === NULL ? '' : number_format($r->gwa, 2),
					$r->units, $r->status, $r->honor === NULL ? '' : $r->honor,
				));
			}
		}
		fclose($out);
		exit;
	}

	public function export_pdf()
	{
		$payload = $this->_export_payload();
		if ($payload === NULL)
		{
			return; // nothing to export — already redirected with a notice
		}
		$ctx  = $payload['ctx'];
		$rows = $payload['rows'];

		// Render the report as a simple printable HTML table.
		$html = '<h2>EduTrack Academic Records System</h2>';
		$html .= '<h3>' . html_escape($this->_report_title($ctx)) . '</h3>';
		$html .= '<p><em>Generated: ' . date('F j, Y') . '</em></p>';

		if ($ctx['report_type'] === 'subject_performance')
		{
			$html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
			$html .= '<thead><tr><th>Subject</th><th>Section</th><th>Instructor</th><th>Units</th><th>Students</th><th>Average</th><th>Passed</th><th>Failed</th></tr></thead><tbody>';
			foreach ($rows as $r)
			{
				$html .= '<tr><td>' . html_escape($r->subject_code . ' — ' . $r->subject_title)
					. '</td><td>' . html_escape($r->section) . '</td><td>' . html_escape($r->instructor)
					. '</td><td>' . $r->units . '</td><td>' . $r->students . '</td><td>'
					. ($r->average === NULL ? '—' : number_format($r->average, 2))
					. '</td><td>' . $r->passed . '</td><td>' . $r->failed . '</td></tr>';
			}
			$html .= '</tbody></table>';
		}
		else
		{
			$html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
			$html .= '<thead><tr><th>Student No.</th><th>Full Name</th><th>Section</th><th>GWA</th><th>Units</th><th>Status</th><th>Latin Honor</th></tr></thead><tbody>';
			foreach ($rows as $r)
			{
				$html .= '<tr><td>' . html_escape($r->student_no) . '</td><td>' . html_escape($r->name)
					. '</td><td>' . html_escape($r->section) . '</td><td>'
					. ($r->gwa === NULL ? '—' : number_format($r->gwa, 2))
					. '</td><td>' . $r->units . '</td><td>' . html_escape($r->status)
					. '</td><td>' . html_escape($r->honor === NULL ? '—' : $r->honor) . '</td></tr>';
			}
			$html .= '</tbody></table>';
		}

		if ( ! class_exists('Mpdf\Mpdf'))
		{
			show_error('PDF export requires mPDF. Run `composer install` in the project root.', 500, 'Missing dependency');
		}

		$mpdf = new \Mpdf\Mpdf(array('tempDir' => APPPATH . 'cache/mpdf'));
		$mpdf->WriteHTML($html);
		$mpdf->Output('edutrack-' . $ctx['report_type'] . '-' . date('Ymd-His') . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
		exit;
	}

	/**
	 * Shared payload for the export endpoints. Resolves the report context
	 * and the rows to export; when there is nothing to export (no semester/
	 * section matches, or zero rows after filtering) it flashes a notice on
	 * the Reports page and redirects back — never a bare error page.
	 *
	 * @return array|NULL array('ctx' => ..., 'rows' => ...) when there is
	 *                    data to export; NULL after redirecting.
	 */
	private function _export_payload()
	{
		$ctx = $this->_resolve_context();

		$rows = array();
		if ( ! $ctx['empty'])
		{
			$rows = ($ctx['report_type'] === 'subject_performance')
				? $this->Report_model->subject_performance($ctx['semester_id'], $ctx['section_id'])
				: $this->Report_model->summary($ctx['semester_id'], $ctx['section_id']);

			if ($ctx['report_type'] === 'honor_roll')
			{
				$rows = array_values(array_filter($rows, function ($r) { return $r->honor !== NULL; }));
			}
		}

		if ($ctx['empty'] || count($rows) === 0)
		{
			$flash_key = ($ctx['role'] === 'admin') ? 'admin_error' : 'grade_error';
			$this->session->set_flashdata($flash_key, 'No data available. Nothing to export for the selected filters.');

			$qs = array('report_type' => $ctx['report_type']);
			if ((int) $ctx['semester_id'])
			{
				$qs['semester'] = (int) $ctx['semester_id'];
			}
			if ($ctx['section_id'] !== NULL)
			{
				$qs['section'] = (int) $ctx['section_id'];
			}

			redirect('reports/index?' . http_build_query($qs));
			return NULL;
		}

		return array('ctx' => $ctx, 'rows' => $rows);
	}

	// -----------------------------------------------------------------
	// Shared context resolution (index + both exports)
	// -----------------------------------------------------------------

	/**
	 * Validate/normalise filters and enforce role-based section scoping.
	 * @return array
	 */
	private function _resolve_context()
	{
		$role = $this->current_user->role;

		$active_sem = $this->Academic_model->active_semesters();

		$sem_id = (int) $this->input->get('semester') ?: ($active_sem ? (int) $active_sem->id : 0);

		// Fall back to the active term if the requested id is bogus.
		if ($sem_id <= 0)
		{
			$sem_id = $active_sem ? (int) $active_sem->id : 0;
		}

		$report_type = $this->input->get('report_type');
		if ( ! in_array($report_type, array('grade_summary', 'honor_roll', 'subject_performance'), TRUE))
		{
			$report_type = 'grade_summary';
		}

		$section_options = array();
		$section_id = NULL;
		$allow_all = FALSE;

		if ($role === 'admin')
		{
			$allow_all = TRUE;
			$incoming = (int) $this->input->get('section');
			if ($incoming > 0 && $this->Academic_model->get_section($incoming))
			{
				$section_id = $incoming;
			}
			$sections = $this->Academic_model->sections();
			foreach ($sections as $s)
			{
				$section_options[(int) $s->id] = $s->name;
			}
		}
		else // teacher — students are blocked by _require_roles() in the constructor
		{
			$allowed = ($sem_id > 0)
				? $this->Enrollment_model->sections_for_teacher($this->current_user->id, $sem_id)
				: array();
			foreach ($allowed as $s)
			{
				$section_options[(int) $s->id] = $s->name;
			}
			$incoming = (int) $this->input->get('section');
			if ($incoming > 0 && isset($section_options[$incoming]))
			{
				$section_id = $incoming;
			}
			else
			{
				$section_id = count($section_options) > 0 ? (int) array_keys($section_options)[0] : NULL;
			}
		}

		return array(
			'role'            => $role,
			'semester_id'     => $sem_id,
			'section_id'      => $section_id,
			'section_options' => $section_options,
			'allow_all'       => $allow_all,
			'report_type'     => $report_type,
			'empty'           => ($sem_id <= 0 || ($section_id === NULL && ! $allow_all) || empty($section_options)),
		);
	}

	private function _report_title(array $ctx)
	{
		$sem_name = '';
		$sem_row = $this->Academic_model->get_semester($ctx['semester_id']);
		if ($sem_row)
		{
			$sem_name = $sem_row->name;
		}

		$type_labels = array(
			'grade_summary'       => 'Grade Summary Report',
			'honor_roll'          => 'Honor Roll',
			'subject_performance' => 'Subject Performance Report',
		);
		$label = $type_labels[$ctx['report_type']];

		$scope = 'All Sections';
		if ($ctx['section_id'] !== NULL)
		{
			$sec = $this->Academic_model->get_section($ctx['section_id']);
			$scope = $sec ? $sec->name : 'All Sections';
		}

		return $label . ' — ' . $sem_name . ' (' . $scope . ')';
	}

}