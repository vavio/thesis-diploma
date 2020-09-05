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
 * Question type class for the CodeCPP question type.
 *
 * @package    qtype
 * @subpackage codecpp
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');


/**
 * The true-false question type class.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_codecpp extends question_type
{

    public $wizardpagesnumber = 2;

    public function finished_edit_wizard($form)
    {
        return isset($form->savechanges);
    }

    public function wizardpagesnumber()
    {
        return 2;
    }

    private static function get_service_url() {
        $config = get_config('qtype_codecpp');

        $protocol = 'https';
        if ($config->use_http) {
            $protocol = 'http';
        }

        return sprintf('%s://%s:%d', $protocol, $config->servicehost, $config->serviceport);
    }

    public static function call_service($path, $data) {
        $c = new curl();
        $c->setHeader(array('Content-type: application/json'));
        $result = $c->post(qtype_codecpp::get_service_url() . '/' . $path, $data);

        if ($c->get_errno()) {
            throw new moodle_exception('err' . $path, 'qtype_codecpp', '',
                array('url' => qtype_codecpp::get_service_url(), 'result' => $result), json_encode($data));
        }

        return $result;
    }

    // This gets called by editquestion.php after the standard question is saved.
    public function print_next_wizard_page($question, $form, $course)
    {
        global $CFG, $SESSION, $COURSE;

        // Catch invalid navigation & reloads.
        if (empty($question->id) && empty($SESSION->codecpp)) {
            redirect('edit.php?courseid=' . $COURSE->id, 'The page you are loading has expired.', 3);
        }

        // See where we're coming from.
        switch ($form->wizardpage) {
            case 'question':
            case 'datasetdefinitions':
                require("{$CFG->dirroot}/question/type/codecpp/datasetdefinitions.php");
                break;
            default:
                //print_error('invalidwizardpage', 'question');
                break;
        }
    }

    // This gets called by question2.php after the standard question is saved.
    public function &next_wizard_form($submiturl, $question, $wizardnow)
    {
        global $CFG, $SESSION, $COURSE;

        // Catch invalid navigation & reloads.
        if (empty($question->id) && empty($SESSION->codecpp)) {
            redirect('edit.php?courseid=' . $COURSE->id,
                'The page you are loading has expired. Cannot get next wizard form.', 3);
        }
        if (empty($question->id)) {
            $question = $SESSION->codecpp->questionform;
        }

        // See where we're coming from.
        switch ($wizardnow) {
            case 'datasetdefinitions':
                require("{$CFG->dirroot}/question/type/codecpp/datasetdefinitions_form.php");
                $mform = new question_dataset_dependent_definitions_form(
                    "{$submiturl}?wizardnow=datasetdefinitions", $question);
                break;
            default:
                //print_error('invalidwizardpage', 'question');
                break;
        }

        return $mform;
    }

    /**
     * This method should be overriden if you want to include a special heading or some other
     * html on a question editing page besides the question editing form.
     *
     * @param question_edit_form $mform a child of question_edit_form
     * @param object $question
     * @param string $wizardnow is '' for first page.
     */
    public function display_question_editing_page($mform, $question, $wizardnow)
    {
        global $OUTPUT;
        switch ($wizardnow) {
            case '':
                // On the first page, the default display is fine.
                parent::display_question_editing_page($mform, $question, $wizardnow);
                return;

            case 'datasetdefinitions':
                echo $OUTPUT->heading_with_help(
                    get_string('choose_element', 'qtype_codecpp'),
                    'questiondatasets', 'qtype_codecpp');
                break;

        }

        $mform->display();
    }

    public function save_question($question, $form)
    {
        if ($this->wizardpagesnumber() == 1 || $question->qtype == 'calculatedsimple') {
            $question = parent::save_question($question, $form);
            return $question;
        }

        $wizardnow = optional_param('wizardnow', '', PARAM_ALPHA);
        $id = optional_param('id', 0, PARAM_INT); // Question id.
        // In case 'question':
        // For a new question $form->id is empty
        // when saving as new question.
        // The $question->id = 0, $form is $data from question2.php
        // and $data->makecopy is defined as $data->id is the initial question id.
        // Edit case. If it is a new question we don't necessarily need to
        // return a valid question object.

        // See where we're coming from.
        switch ($wizardnow) {
            case '' :
            case 'question': // Coming from the first page, creating the second.
                if (empty($form->id)) { // or a new question $form->id is empty.
                    $question = parent::save_question($question, $form);
                    // Prepare the datasets using default $questionfromid.
                    //$this->preparedatasets($form);
                    //$form->id = $question->id;
                    //$this->save_dataset_definitions($form);
                    //if (isset($form->synchronize) && $form->synchronize == 2) {
                    //$this->addnamecategory($question);
                    //}
                } else if (!empty($form->makecopy)) {
                    $questionfromid = $form->id;
                    $question = parent::save_question($question, $form);
                    // Prepare the datasets.
                    //$this->preparedatasets($form, $questionfromid);
                    $form->id = $question->id;
                    //$this->save_as_new_dataset_definitions($form, $questionfromid);
                    //if (isset($form->synchronize) && $form->synchronize == 2) {
                    //$this->addnamecategory($question);
                    //}
                } else {
                    // Editing a question.
                    $question = parent::save_question($question, $form);
                    // Prepare the datasets.
                    //$this->preparedatasets($form, $question->id);
                    //$form->id = $question->id;
                    //$this->save_dataset_definitions($form);
                    //if (isset($form->synchronize) && $form->synchronize == 2) {
                    //$this->addnamecategory($question);
                    //}
                }
                break;
            case 'datasetdefinitions':
                // Calculated options.
                // It cannot go here without having done the first page,
                // so the question_calculated_options should exist.
                // We only need to update the synchronize field.
                if (isset($form->synchronize)) {
                    $optionssynchronize = $form->synchronize;
                } else {
                    $optionssynchronize = 0;
                }
                $this->generate_datasets($form, $question);
                //$DB->set_field('question_calculated_options', 'synchronize', $optionssynchronize,
                //array('question' => $question->id));
                //if (isset($form->synchronize) && $form->synchronize == 2) {
                //$this->addnamecategory($question);
                //}

                //$this->save_dataset_definitions($form);
                break;
            default:
                print_error('invalidwizardpage', 'question');
                break;
        }
        return $question;
    }

    public function generate_datasets($form, $question)
    {
        global $DB, $CFG;
        $possibledatasets = $this->find_editable($question->questiontext);
        $editable = "";
        for ($i = 1; $i <= count($form->editable); $i++) {
            $temp = array();
            if ($form->editable[$i] == 0) {
                $temp[] = "X";
            } else if (rtrim($possibledatasets[$i - 1][5]) == "integer") {
                if ($form->min[$i] != null) {
                    $from = $form->min[$i];
                    $to = $form->max[$i];
                    $excluded = explode(",", $form->exclude[$i]);
                    $invalid_values = array();
                    for ($j = 0; $j < count($excluded); $j++) {
                        $invalid_values[] = (int)$excluded[$j];
                    }
                    for ($j = $from; $j <= $to; $j++) {
                        if (($form->exclude[$i] != null) && (in_array($j, $invalid_values, true)))
                            continue;
                        $temp[] = $j;
                    }
                } else {
                    $exact_values = explode(",", $form->exact[$i]);
                    for ($j = 0; $j < count($exact_values); $j++) {
                        $temp[] = (int)$exact_values[$j];
                    }
                }
            } else if (rtrim($possibledatasets[$i - 1][5]) == "binary_op") {
                $temp[] = (string)$form->multiplication[$i];
                $temp[] = (string)$form->addition[$i];
                $temp[] = (string)$form->substraction[$i];
                $temp[] = (string)$form->equals[$i];
                $temp[] = (string)$form->modulo[$i];
                $temp[] = (string)$form->smallerorequal[$i];
                $temp[] = (string)$form->smaller[$i];
                $temp[] = (string)$form->biggerorequal[$i];
                $temp[] = (string)$form->bigger[$i];
                $temp[] = (string)$form->equalsequals[$i];
                $temp[] = (string)$form->notequals[$i];
            } else if (rtrim($possibledatasets[$i - 1][5]) == "logical") {
                $temp[] = (string)$form->andoperator[$i];
                $temp[] = (string)$form->oroperator[$i];
            } else if (rtrim($possibledatasets[$i - 1][5]) == "text") {
                $temp[] = (string)$form->lowercase[$i];
                $temp[] = (string)$form->uppercase[$i];
                $temp[] = (string)$form->digits[$i];
            } else if (rtrim($possibledatasets[$i - 1][5]) == "float") {
                $temp[] = (string)$form->minfloat[$i];
                $temp[] = (string)$form->maxfloat[$i];
            }
            $editable .= join(";", $temp);
            $editable .= "\n";
        }

        $call_data = array(
            "source_code" => html_to_text($question->questiontext),
            "edit" => $editable
        );
        $callresult = qtype_codecpp::call_service("codeprocessor", json_encode($call_data));
        $callresult = json_decode($callresult, true);
        for ($i = 0; $i < count($callresult); $i++) {
            $new_question = new stdClass();
            $new_question->questionid = $question->id;
            $new_question->text = $callresult[$i]['new_source_code'];
            $new_question->result = $callresult[$i]['output'];
            $new_question->difficulty = $callresult[$i]['difficulty'];

            $DB->insert_record('question_codecpp_dataset', $new_question);
        }
    }

    public function save_question_options($question)
    {
        global $DB, $CFG;
        $result = new stdClass();
        $context = $question->context;
        // Fetch old answer ids so that we can reuse them.
        $oldanswers = $DB->get_records('question_answers',
            array('question' => $question->id), 'id ASC');

        // Save the true answer - update an existing answer if possible.
        $answer = array_shift($oldanswers);
        if (!$answer) {
            $answer = new stdClass();
            $answer->questionid = $question->id;
            $answer->answer = '';
            $answer->feedback = '';
            $answer->id = $DB->insert_record('question_answers', $answer);
        }

        $answer->answer = 'variable_output_per_codecpp_question';
        $answer->fraction = 1;
        $answer->question = $question->id;
        $DB->update_record('question_answers', $answer);

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        // Save question options in question_codecpp table.
        if ($options = $DB->get_record('question_codecpp', array('questionid' => $question->id))) {
            // No need to do anything, since the answer IDs won't have changed
            // But we'll do it anyway, just for robustness.
            $DB->update_record('question_codecpp', $options);
        } else {
            $options = new stdClass();
            $options->questionid = $question->id;
            $DB->insert_record('question_codecpp', $options);
        }

        $this->save_hints($question);

        return true;
    }

    /**
     * Loads the question type specific options for the question.
     */

    public function get_question_substring($question, $from, $to)
    {
        $cleaned_questiontext = html_to_text($question->questiontext);
        $lines = explode("\n", $cleaned_questiontext);
        $result = array();
        for ($i = $from - 1; $i <= $to - 1; $i++) {
            $result[] = $lines[$i];
        }
        return $result;
    }

    public function find_editable($question_text)
    {
        $call_data = array(
            "source_code" => html_to_text($question_text)
        );
        $callresult = qtype_codecpp::call_service("get_key_locations", json_encode($call_data));
        $callresult = json_decode($callresult, true);
        $result_data = array();
        for ($i = 0; $i < count($callresult['result']['key_locations']); $i++) {
            $result_data[] = explode(";", $callresult['result']['key_locations'][$i]);
        }
        return $result_data;
    }

    public function get_question_options($question)
    {
        global $DB, $OUTPUT;
        // Get additional information from database
        // and attach it to the question object.
        if (!$question->options = $DB->get_record('question_codecpp',
            array('questionid' => $question->id))) {
            echo $OUTPUT->notification('Error: Missing question options!');
            return false;
        }
        // Load the answers.
        if (!$question->options->answers = $DB->get_records('question_answers',
            array('question' => $question->id), 'id ASC')) {
            echo $OUTPUT->notification('Error: Missing question answers for CodeCPP question ' .
                $question->id . '!');
            return false;
        }

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata)
    {
        parent::initialise_question_instance($question, $questiondata);
        global $DB;

        $range_sql = "SELECT a.id as id,
                        a.text as variation_text,
                        a.result as variation_result,
                        a.difficulty as variation_difficulty
                        FROM {question_codecpp_dataset} a
                        WHERE a.questionid = :questionid";

        $codecpp_records = $DB->get_records_sql($range_sql, array('questionid' => $question->id));

        $question->questionloader = new qtype_codecpp_question_loader($codecpp_records);
    }

    public function delete_question($questionid, $contextid)
    {
        global $DB;
        $DB->delete_records('question_codecpp_dataset', array('questionid' => $questionid));
        $DB->delete_records('question_codecpp', array('question' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    public function move_files($questionid, $oldcontextid, $newcontextid)
    {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid)
    {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata)
    {
        return 0;
    }

    public function get_possible_responses($questiondata)
    {
         //TODO VVV fix
        return array(
            $questiondata->id => array(
                0 => new question_possible_response(get_string('false', 'qtype_codecpp'),
                    $questiondata->options->answers[$questiondata->options->falseanswer]->fraction),
                1 => new question_possible_response(get_string('true', 'qtype_codecpp'),
                    $questiondata->options->answers[$questiondata->options->trueanswer]->fraction),
                null => question_possible_response::no_response()
            )
        );
    }
}
