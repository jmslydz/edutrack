<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Applicant
 *
 * Public-facing admission portal for role=applicant accounts.
 *
 * Flow:
 *   1. Applicant registers (auth/register) -> status pending_exam.
 *   2. Applicant comes to campus, gets a one-time exam code from the
 *      registrar (admin/applicants -> Generate code).
 *   3. Applicant enters the code here -> a timed exam starts. The exam
 *      auto-submits when time runs out (server also enforces the deadline).
 *   4. Passed -> applicant picks a preferred program; admin reviews
 *      credentials and either admits (student account + enrollment +
 *      notification + email) or rejects.
 *
 * The exam code is single-use: entering it consumes it. The question set
 * is snapshotted into exam_answers at start so a retake isn't possible
 * with the same code.
 */
class Applicant extends MY_Controller
{
	const EXAM_TIME_MINUTES = 20;
	const PASS_PERCENT      = 70; // % needed to pass
	const QUESTIONS_PER_EXAM = 15;

	public function __construct()
	{
		parent::__construct();
		$this->_require_role('applicant');
		$this->load->model('User_model');
		$this->load->model('Enrollment_model');
		$this->load->model('Notification_model');
	}

	/**
	 * Applicant home — renders the right step based on applicant status.
	 */
	public function dashboard()
	{
		$applicant = $this->_my_applicant();

		$programs = array();
		if ($applicant->status === 'passed_exam')
		{
			$programs = $this->db->order_by('program_name', 'ASC')->get('programs')->result();
		}

		// Exam in progress? The exam itself lives on its own page
		// (applicant/exam) so the applicant can always come back here —
		// the dashboard shows the steps + a Resume Exam card instead.
		$in_progress = ($applicant->exam_started_at !== NULL && $applicant->exam_finished_at === NULL);

		$this->data['active_page'] = 'applicant_dashboard';
		$this->_render('applicant/dashboard', array(
			'applicant'     => $applicant,
			'programs'      => $programs,
			'in_progress'   => $in_progress,
			'exam_minutes'  => self::EXAM_TIME_MINUTES,
			'exam_started'  => $in_progress ? strtotime($applicant->exam_started_at) : 0,
			'notice'        => $this->session->flashdata('notice'),
			'error'         => $this->session->flashdata('error'),
			'subtitle'      => 'Admission Portal',
		));
	}

	/**
	 * The exam page itself (separate from the dashboard so applicants can
	 * always navigate back home). Server-enforced deadline, same as before.
	 */
	public function exam()
	{
		$applicant = $this->_my_applicant();

		$in_progress = ($applicant->exam_started_at !== NULL && $applicant->exam_finished_at === NULL);
		if ( ! $in_progress)
		{
			redirect('applicant/dashboard');
		}

		$questions = $this->db->select('exam_answers.question_id, exam_questions.question, exam_questions.option_a, exam_questions.option_b, exam_questions.option_c, exam_questions.option_d')
			->from('exam_answers')
			->join('exam_questions', 'exam_questions.id = exam_answers.question_id')
			->where('exam_answers.applicant_id', $applicant->id)
			->order_by('exam_answers.id', 'ASC')
			->get()
			->result();

		$this->data['active_page'] = 'applicant_dashboard';
		$this->_render('applicant/exam', array(
			'applicant'    => $applicant,
			'questions'    => $questions,
			'exam_minutes' => self::EXAM_TIME_MINUTES,
			'exam_started' => strtotime($applicant->exam_started_at),
			'subtitle'     => 'Admission Exam',
		));
	}

	/**
	 * POST: validate the one-time exam code and start the exam.
	 */
	public function start_exam()
	{
		$this->_require_post();

		$applicant = $this->_my_applicant();
		$code = trim((string) $this->input->post('exam_code'));

		if ($applicant->status !== 'pending_exam' || $applicant->exam_code === NULL)
		{
			$this->session->set_flashdata('error', 'This application cannot start an exam right now.');
			redirect('applicant/dashboard');
		}

		if ($code === '' || ! hash_equals((string) $applicant->exam_code, $code))
		{
			$this->session->set_flashdata('error', 'That exam code is incorrect. Please check it with the registrar.');
			redirect('applicant/dashboard');
		}

		// Snapshot a random question set for this attempt.
		$questions = $this->db->where('is_active', 1)
			->order_by('id', 'RANDOM')
			->limit(self::QUESTIONS_PER_EXAM)
			->get('exam_questions')
			->result();

		if (count($questions) < 5)
		{
			$this->session->set_flashdata('error', 'The exam is not ready yet. Please inform the registrar.');
			redirect('applicant/dashboard');
		}

		// Consume the code + snapshot answers (NULL = unanswered).
		foreach ($questions as $q)
		{
			$this->db->insert('exam_answers', array(
				'applicant_id' => $applicant->id,
				'question_id'  => $q->id,
				'answer'       => NULL,
				'is_correct'   => NULL,
			));
		}

		$this->db->where('id', $applicant->id)->update('applicants', array(
			'exam_code'        => NULL,          // single use
			'exam_started_at'  => date('Y-m-d H:i:s'),
			'exam_finished_at' => NULL,
			'exam_score'       => NULL,
			'exam_total'       => count($questions),
			'exam_passed'      => NULL,
			'status'           => 'pending_exam',
		));

		$this->session->set_userdata('exam_started_at', time());
		$this->session->set_flashdata('notice', 'Your exam has started. You have ' . self::EXAM_TIME_MINUTES . ' minutes.');
		redirect('applicant/exam');
	}

	/**
	 * POST: score the exam. The server enforces the deadline regardless of
	 * what the client-side timer does.
	 */
	public function submit_exam()
	{
		$this->_require_post();

		$applicant = $this->_my_applicant();

		// Must have started but not finished.
		if ($applicant->exam_started_at === NULL || $applicant->exam_finished_at !== NULL)
		{
			$this->session->set_flashdata('error', 'No active exam session found.');
			redirect('applicant/dashboard');
		}

		$deadline = strtotime($applicant->exam_started_at) + (self::EXAM_TIME_MINUTES * 60);
		$expired  = (time() > $deadline);

		$answers = $this->db->select('exam_answers.*, exam_questions.correct_answer')
			->from('exam_answers')
			->join('exam_questions', 'exam_questions.id = exam_answers.question_id')
			->where('exam_answers.applicant_id', $applicant->id)
			->get()
			->result();

		$score = 0;
		foreach ($answers as $a)
		{
			$given = NULL;
			if ( ! $expired)
			{
				$given = strtoupper(trim((string) $this->input->post('q_' . $a->question_id)));
				if ( ! in_array($given, array('A', 'B', 'C', 'D'), TRUE))
				{
					$given = NULL;
				}
			}
			$is_correct = ($given !== NULL && $given === strtoupper($a->correct_answer)) ? 1 : 0;
			if ($is_correct)
			{
				$score++;
			}
			$this->db->where('id', $a->id)->update('exam_answers', array(
				'answer'     => $given,
				'is_correct' => $is_correct,
			));
		}

		$total = max(1, count($answers));
		$passed = ((int) round(($score / $total) * 100)) >= self::PASS_PERCENT;

		$this->db->where('id', $applicant->id)->update('applicants', array(
			'exam_finished_at' => date('Y-m-d H:i:s'),
			'exam_score'       => $score,
			'exam_total'       => $total,
			'exam_passed'      => $passed ? 1 : 0,
			'status'           => $passed ? 'passed_exam' : 'failed_exam',
		));

		$this->session->unset_userdata('exam_started_at');

		if ($passed)
		{
			$this->session->set_flashdata('notice',
				'Congratulations! You passed the admission exam (' . $score . '/' . $total . '). '
				. 'Please choose your preferred program below.');
		}
		else
		{
			$this->session->set_flashdata('error',
				'You scored ' . $score . '/' . $total . ', which is below the passing mark. '
				. 'Please contact the registrar about retaking the exam.');
		}
		redirect('applicant/dashboard');
	}

	/**
	 * POST: save the applicant's preferred program after passing.
	 */
	public function choose_program()
	{
		$this->_require_post();

		$applicant = $this->_my_applicant();
		$program_id = (int) $this->input->post('program_id');

		if ($applicant->status !== 'passed_exam')
		{
			$this->session->set_flashdata('error', 'You can only choose a program after passing the exam.');
			redirect('applicant/dashboard');
		}

		$program = $this->db->where('id', $program_id)->get('programs')->row();
		if ( ! $program)
		{
			$this->session->set_flashdata('error', 'Please select a valid program.');
			redirect('applicant/dashboard');
		}

		$this->db->where('id', $applicant->id)->update('applicants', array(
			'preferred_program_id' => $program_id,
		));

		$this->session->set_flashdata('notice',
			'Your preferred program (' . $program->program_name . ') has been recorded. '
			. 'The registrar will review your application and email you the result.');
		redirect('applicant/dashboard');
	}

	// -----------------------------------------------------------------

	/**
	 * The applicant row for the logged-in user (with program name joined).
	 * @return object|null
	 */
	private function _my_applicant()
	{
		return $this->db->select(
				'applicants.*, programs.program_name AS preferred_program_name, '
				. 'CONCAT(users.first_name, " ", users.last_name) AS full_name'
			)
			->from('applicants')
			->join('users', 'users.id = applicants.user_id')
			->join('programs', 'programs.id = applicants.preferred_program_id', 'left')
			->where('applicants.user_id', $this->current_user->id)
			->get()
			->row();
	}
}