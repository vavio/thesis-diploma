<?php
/**
 * Defines the update weights result form for CodeCPP.
 *
 * @package    qtype_codecpp
 * @package   qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

class attempts_data
{
    /** @var int  */
    protected $attempt;

    /**  */

    public function __construct($attempt_id)
    {
        $this->attempt = quiz_attempt::create($attempt_id);
    }

    public function get_data() {
        if (!$this->attempt->is_finished()) {
            return array();
        }

        $raw_data = array();

        foreach ($this->attempt->get_slots() as $slot) {
            $qtype = $this->attempt->get_question_type_name($slot);

            $qa = $this->attempt->get_question_attempt($slot);
            foreach ($qa->get_step_iterator() as $step) {
                $raw_data[] = array(
                    'timestamp' => $step->get_timecreated(),
                    'slot' => $slot,
                    'type' => $qtype,
                    'state' => $step->get_state());
            }

            usort($raw_data, function ($a, $b) {
                if ($a['timestamp'] == $b['timestamp']) {
                    return $a['slot'] - $b['slot'];
                }

                return $a['timestamp'] - $b['timestamp'];
            });

        }

        $idx = 0;
        while($raw_data[$idx]['timestamp'] == $raw_data[0]['timestamp']){
            // We are skipping the init state for the question
            $idx++;
        }

        $attempt_data = array();

        while($raw_data[$idx]['state'] != question_state::$gradedright && $raw_data[$idx]['state'] != question_state::$gradedwrong) {
            $data = $raw_data[$idx];
            $attempt_data[] = array(
                'slot' => $data['slot'],
                'time' => $data['timestamp'] - $raw_data[$idx - 1]['timestamp'],
                'type' => $data['type']);
            $idx++;
        }

        foreach ($raw_data as $data) {
            $state = $data['state'];
            if ($state != question_state::$gradedwrong && $state != question_state::$mangrwrong) {
                continue;
            }

            if ($state != question_state::$gradedpartial && $state != question_state::$mangrpartial) {
                continue;
            }

            // answer is wrong
            $removed_count = 0;
            foreach (array_keys($attempt_data) as $key) {
                if (strcmp($attempt_data[$key - $removed_count]['slot'], $data['slot']) === 0) {
                    unset($attempt_data[$key]);
                    $attempt_data = array_values($attempt_data);
                    $removed_count++;
                }
            }
        }

        // Add the difficulty of the variation to the $attempt_data
        foreach ($attempt_data as &$data) {
            if ($data['type'] != 'codecpp') {
                continue;
            }

            $qa = $this->attempt->get_question_attempt($data['slot']);
            foreach ($qa->get_step_iterator() as $step) {
                if (!$step->has_qt_var('_qtext_')) {
                    continue;
                }
                $data['qid'] = $qa->get_question_id();
                $data['variation_id'] = $step->get_qt_var('_qid_');
                $data['difficulty'] = $step->get_qt_var('_qdiffc_');
                $data['text'] = $step->get_qt_var('_qtext_');
            }
        }

        return $attempt_data;
    }

    public static function get_attempts_for_quiz($quizid) {
        global $DB;

        $sql = 'SELECT quiza.id
           FROM {quiz_attempts} quiza
           WHERE quiza.quiz = :quizid AND quiza.preview = 0 AND (quiza.state = \'finished\' OR quiza.state IS NULL)';

        return $DB->get_records_sql($sql, array('quizid' => $quizid));
    }
}