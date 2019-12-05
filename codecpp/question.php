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
 * @package    qtype
 * @subpackage codecpp
 * @copyright  2009 The Open University
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
    public $rightanswer;
    public $truefeedback;
    public $falsefeedback;
    public $trueanswerid;
    public $falseanswerid;
    public $questionsloader;
    public $questiondata;
    public $questionanswer;
    public $dataset;
    public $index;

    public function start_attempt(question_attempt_step $step, $variant) {
        $num = rand(0, count($this->dataset)-1);
        $step->set_qt_var('_qtext_', htmlspecialchars($this->dataset[$num]->questiontext, ENT_NOQUOTES));
        $step->set_qt_var('_qans_', $this->dataset[$num]->result);
        $step->set_qt_var('_num_', $num);
    }

    public function apply_attempt_state(question_attempt_step $step){
        $this->index = $step->get_qt_var('_num_');
    }

    public function get_index(){
        return array('answer' => $this->index);
    }

    public function get_expected_data() {
        return array('answer' => PARAM_RAW_TRIMMED);
    }

    public function get_correct_response() {
        return array('answer' => $this->rightanswer);
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
        return $this->questionanswer;
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
        if (($response['answer'] == $this->dataset[$this->index]->result)
            || (abs(floatval($response['answer']) - floatval($this->dataset[$this->index]->result)) <= 0.01)){
            $fraction = 1;
        }
        else {
            $fraction = 0;
        }
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'answerfeedback') {
            $answerid = reset($args); // Itemid is answer id.
            $response = $qa->get_last_qt_var('answer', '');
            return $options->feedback && (
                    ($answerid == $this->trueanswerid && $response) ||
                    ($answerid == $this->falseanswerid && $response !== ''));

        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }
}

class qtype_codecpp_question_loader {
    protected $question;
    public $qt;
    public $ra;

    /**
     * Constructor
     * @param array $availablequestions array of available question ids.
     * @param int $choose how many questions to load.
     */
    public function __construct($question) {
        $this->question = $question;
    }

    public function get_qt(){
        return $this->qt;
    }

    public function get_ra(){
        return $this->ra;
    }

    /**
     * Choose and load the desired number of questions.
     * @return array of short answer questions.
     */
    public function load_questions() {
        global $DB;
        $new_question = $this->$question;
        $sql = "SELECT a.id
                  FROM {dataset_codecpp} a
                 WHERE a.category = ?";
        $temp = $DB->get_records_sql($sql, array($this->question->id));
        $minn = INF;
        $maxx = -1;
        foreach ($temp as $t){
            if ($t->id < $minn)
                $minn = $t->id;
            if ($t->id > $maxx)
                $maxx = $t->id;
        }
        $num = rand($minn, $maxx);
        $res = $DB->get_record("dataset_codecpp", array('id' => $num));
        $new_question = array();
        $new_question[] = $res->questiontext;
        $new_question[] = $res->result;
        $this->qt = $res->questiontext;
        $this->ra = $res->result;
        return $new_question;
    }
}
