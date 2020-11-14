<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CodeCPP question definition class.
 *
 * @package    qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');

/**
 * Represents a true-false question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_codecpp_question extends question_graded_automatically {
    /** @var qtype_codecpp_question_loader helper for loading the variation of the codecpp question. */
    public $questionloader;

    /** @var string variation id of codecpp question. */
    public $variation_id;
    /** @var string variation text of codecpp question. */
    public $variation_text;
    /** @var string variation result of codecpp question. */
    public $variation_result;
    /** @var string variation difficulty of codecpp question. */
    public $variation_difficulty;

    public function start_attempt(question_attempt_step $step, $variant) {
        $codecppquestion = $this->questionloader->load_question();

        $step->set_qt_var('_qid_', $codecppquestion->id);
        $step->set_qt_var('_qtext_', $codecppquestion->variation_text);
        $step->set_qt_var('_qans_', $codecppquestion->variation_result);
        $step->set_qt_var('_qdiffc_', $codecppquestion->variation_difficulty);

        parent::start_attempt($step, $variant);
    }

    public function apply_attempt_state(question_attempt_step $step) {
        $this->variation_id = $step->get_qt_var('_qid_');
        $this->variation_text = $step->get_qt_var('_qtext_');
        $this->variation_result = $step->get_qt_var('_qans_');
        $this->variation_difficulty = $step->get_qt_var('_qdiffc_');

        parent::apply_attempt_state($step);
    }

    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    public function get_correct_response() {
        return array('answer' => $this->variation_result);
    }

    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    public function un_summarise_response(string $summary) {
        if (!empty($summary)) {
            return ['answer' => $summary];
        } else {
            return [];
        }
    }

    public function get_correct_answer() {
        return $this->variation_result;
    }

    public function is_complete_response(array $response) {
        return array_key_exists('answer', $response);
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaseselectananswer', 'qtype_codecpp');
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function grade_response(array $response) {
        $fraction = $response['answer'] == $this->variation_result ? 1 : 0;
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }
}


/**
 * This class is responsible for loading the questions that a question needs from the database.
 *
 * @copyright  2020 Valentin Ambaroski
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */
class qtype_codecpp_question_loader {
    /** @var array hold available codecpp variation ids to choose from. */
    protected $available_variations;

    /**
     * Constructor
     * @param array $availablequestions array of available variation ids.
     */
    public function __construct($available_variations) {
        $this->available_variations = $available_variations;
    }

    /**
     * Choose and load the desired random variation of codecpp question.
     * @return array of short answer questions.
     * @throws coding_exception
     */
    public function load_question() {
        if (count($this->available_variations) == 0) {
            throw new coding_exception('notenoughcodecppvariation');
        }

        $rand_array = draw_rand_array($this->available_variations, 1);
        $keys = array_keys($rand_array);

        return $rand_array[$keys[0]];
    }
}
