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
 * Defines the editing form for the codecpp question data set definitions.
 *
 * @package    qtype
 * @subpackage codecpp
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/edit_question_form.php');


/**
 * codecpp question data set definitions editing form definition.
 *
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_dataset_dependent_definitions_form extends question_wizard_form {
    /**
     * Question object with options and answers already loaded by get_question_options
     * Be careful how you use this it is needed sometimes to set up the structure of the
     * form in definition_inner but data is always loaded into the form with set_defaults.
     *
     * @var object
     */
    protected $question;
    /**
     * Reference to question type object
     *
     * @var question_dataset_dependent_questiontype
     */
    protected $qtypeobj;
    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    public function __construct($submiturl, $question) {
        global $DB;
        $this->question = $question;
        $this->qtypeobj = question_bank::get_qtype($this->question->qtype);
        // Validate the question category.
        if (!$category = $DB->get_record('question_categories',
                array('id' => $question->category))) {
            print_error('categorydoesnotexist', 'question', $returnurl);
        }
        //$this->category = $category;
        //$this->categorycontext = context::instance_by_id($category->contextid);
        parent::__construct($submiturl);
    }

    protected function definition() {
        global $SESSION;

        $mform = $this->_form;
        $mform->setDisableShortforms();

        $possibledatasets = $this->qtypeobj->find_editable($this->question->questiontext);

        // Explaining the role of datasets so other strings can be shortened.

        $count = 1;
        foreach ($possibledatasets as $datasetentry) {
            if ($datasetentry == ""){
                continue;
            }
            $mform->addElement('header', "mandatoryhdr[{$count}]",
                  "Element ".(string)$count);
            $show_question_text = $this->qtypeobj->get_question_substring($this->question, (int)$datasetentry[0], (int)$datasetentry[2]);
            $label = "<p>Lines of code: </p>";
            for ($i=0; $i<count($show_question_text); $i++){
                $currtext = rtrim($show_question_text[$i]);
                $from = (int)$datasetentry[1];
                $to = (int)$datasetentry[3];
                $label = $label . "<p style=\"color:red;\">";
                $label = $label . htmlspecialchars(substr($currtext, 0, $from - 1));
                $label = $label . "<strong>" . htmlspecialchars(substr($currtext, $from-1, $to-$from)) . "</strong>";
                $label = $label . htmlspecialchars(substr($currtext, $to-1, strlen($currtext))) . "</p>";
                //$label = $label . "<p style=\"color:red;\">".htmlspecialchars($show_question_text[$i], ENT_NOQUOTES)."</b>";
            }
            $mform->addElement('html', $label);
            $label = "<p>Starting row and column: " . $datasetentry[0] . " " . $datasetentry[1] . "</p>";
            $mform->addElement('html', $label);
            $label = "<p>Ending row and column: " . $datasetentry[2] ." " . $datasetentry[3] . "</p>";
            $mform->addElement('html', $label);
            $label = "<p>Value: ".$datasetentry[4]." Type: ".$datasetentry[5]."</p>";
            $mform->addElement('html', $label);
            $mform->addElement('header', "edithdr[{$count}]", "Edit elements: ");
            $temp = rtrim($datasetentry[5]);
            if (strcmp($temp, "integer") == 0){
                $mform->addElement('text', "min[{$count}]", "Range from: ");
                $mform->addElement('text', "max[{$count}]", "Range to: ");
                $mform->addElement('text', "exclude[{$count}]", "Exclude values (separated by comma): ");
                $label = "<p>or use</p>";
                $mform->addElement('html', $label);
                $mform->addElement('text', "exact[{$count}]", "Exact values (separated by comma): ");
            }
            else if (strcmp($temp, "binary_op") == 0){
                $mform->addElement('advcheckbox', "multiplication[{$count}]", "*", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "addition[{$count}]", "+", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "substraction[{$count}]", "-", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "equals[{$count}]", "=", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "modulo[{$count}]", "%", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "smallerorequal[{$count}]", "<=", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "smaller[{$count}]", "<", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "biggerorequal[{$count}]", ">=", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "bigger[{$count}]", ">", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "equalsequals[{$count}]", "==", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "notequals[{$count}]", "!=", "", "", array(0, 1));
            }
            else if (strcmp($temp, "logical") == 0){
                $mform->addElement('advcheckbox', "andoperator[{$count}]", "&&", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "oroperator[{$count}]", "||", "", "", array(0, 1));
            }
            else if (strcmp($temp, "text") == 0){
                $mform->addElement('advcheckbox', "lowercase[{$count}]", "Lowercase letters", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "uppercase[{$count}]", "Uppercase letters", "", "", array(0, 1));
                $mform->addElement('advcheckbox', "digits[{$count}]", "Digits", "", "", array(0, 1));
            }
            else if (strcmp($temp, "float") == 0){
                $mform->addElement('text', "minfloat[{$count}]", "Range from: ");
                $mform->addElement('text', "maxfloat[{$count}]", "Range to: ");
            }
            $mform->addElement('advcheckbox', "editable[{$count}]", "Edit", "", "", array(0, 1));
            $count = $count + 1;
        }
        //$this->qtypeobj->generate_datasets($this->question);

        // Temporary strings.
        $mform->setDefault('synchronize', 0);

        $mform->addElement('submit', 'savechanges', "Save Changes");

        $this->add_hidden_fields();

        $mform->addElement('hidden', 'category');
        $mform->setType('category', PARAM_SEQUENCE);

        $mform->addElement('hidden', 'wizard', 'datasetitems');
        $mform->setType('wizard', PARAM_ALPHA);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $datasets = $data['dataset'];
        $countvalid = 0;
        foreach ($datasets as $key => $dataset) {
            if ($dataset != '0') {
                $countvalid++;
            }
        }
        if (!$countvalid) {
            foreach ($datasets as $key => $dataset) {
                $errors['dataset['.$key.']'] = "OSAIJDOIASJD";
            }
        }
        return $errors;
    }
}
