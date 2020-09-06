<?php
/**
 * Defines the editing form for the codecpp question data set definitions.
 *
 * @package    qtype
 * @subpackage codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/edit_question_form.php');


class question_dataset_dependent_definitions_form extends question_wizard_form {
    /**
     * @var array question_lines
     */
    protected $question_lines;

    /**
     * @var array possibledatasets
     */
    protected $possibledatasets;

    public function __construct($submiturl, $question) {
        global $DB;

        $this->question_lines = explode("\n", html_to_text($question->questiontext));
        $qtypeobj = question_bank::get_qtype($question->qtype);

        // Validate the question category.
        if (!$category = $DB->get_record('question_categories',
                array('id' => $question->category))) {
            print_error('categorydoesnotexist', 'question', $returnurl);
        }

        $this->possibledatasets = $qtypeobj->find_editable($question->questiontext);

        parent::__construct($submiturl);
    }

    protected function definition() {
        $mform = $this->_form;
        $mform->setDisableShortforms();

        for ($idx = 0; $idx<count($this->possibledatasets); $idx++) {
            $datasetentry = $this->possibledatasets[$idx];
            if ($datasetentry == ""){
                continue;
            }
            $mform->addElement('header', "header[{$idx}]",
                  "Element ".(string)($idx + 1));

            $mform->addElement('html', $this->get_code_label($datasetentry));

//            $label = "<p>Starting row and column: " . $datasetentry[0] . " " . $datasetentry[1] . "</p>";
//            $mform->addElement('html', $label);
//            $label = "<p>Ending row and column: " . $datasetentry[2] ." " . $datasetentry[3] . "</p>";
//            $mform->addElement('html', $label);
//            $label = "<p>Value: ".$datasetentry[4]." Type: ".$datasetentry[5]."</p>";
//            $mform->addElement('html', $label);

            $temp = rtrim($datasetentry[5]);
            if (strcmp($temp, "integer") == 0 || strcmp($temp, "float") == 0){
                $mform->addElement('text', "range[{$idx}]", 'Range: ');
            }
            else if (strcmp($temp, "binary_op") == 0){
                $checkboxes = [
                    $mform->createElement('advcheckbox', "multiplication[{$idx}]", "", "*"),
                    $mform->createElement('advcheckbox', "addition[{$idx}]", "", "+"),
                    $mform->createElement('advcheckbox', "substraction[{$idx}]", "", "-"),
                    $mform->createElement('advcheckbox', "equals[{$idx}]", "", "="),
                    $mform->createElement('advcheckbox', "modulo[{$idx}]", "", "%"),
                    $mform->createElement('advcheckbox', "smallerorequal[{$idx}]", "", "<="),
                    $mform->createElement('advcheckbox', "smaller[{$idx}]", "", "<"),
                    $mform->createElement('advcheckbox', "biggerorequal[{$idx}]", "", ">="),
                    $mform->createElement('advcheckbox', "bigger[{$idx}]", "", ">"),
                    $mform->createElement('advcheckbox', "equalsequals[{$idx}]", "", "=="),
                    $mform->createElement('advcheckbox', "notequals[{$idx}]", "", "!=")
                ];
                $mform->addGroup($checkboxes, 'binary_opselectedoptions');
            }
            else if (strcmp($temp, "logical") == 0){
                $checkboxes = [
                    $mform->createElement('advcheckbox', "andoperator[{$idx}]", "", "&&"),
                    $mform->createElement('advcheckbox', "oroperator[{$idx}]", "", "||")
               ];
                $mform->addGroup($checkboxes, 'logicalselectedoptions');
            }
            else if (strcmp($temp, "text") == 0){
                $checkboxes = [
                    $mform->createElement('advcheckbox', "lowercase[{$idx}]", "", "Lowercase letters"),
                    $mform->createElement('advcheckbox', "uppercase[{$idx}]", "", "Uppercase letters"),
                    $mform->createElement('advcheckbox', "digits[{$idx}]", "", "Digits")
                    // TODO VVV add specific string
                ];
                $mform->addGroup($checkboxes, 'textselectedoptions');
            }
            $mform->addElement('advcheckbox', "editable[{$idx}]", "Edit", "", "", array(0, 1));
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

    private function get_code_label($entry) {
        $config = get_config('qtype_codecpp');
        $num_lines = $config->code_preview_lines;

        $lines = array_slice($this->question_lines, (int)$entry[0] - $num_lines - 1, 2 * $num_lines + 1);

        $label = html_writer::start_tag('code', array('style' => 'color:black'));
        for ($i=0; $i<count($lines); $i++){
            $currtext = trim($lines[$i]);

            if ((count($lines) - 1) / 2 != $i) {
                $label = $label . htmlspecialchars($currtext) . html_writer::empty_tag('br');
                continue;
            }

            $from = (int)$entry[1];
            $to = (int)$entry[3];
            $label = $label . htmlspecialchars(substr($currtext, 0, $from - 1));
            $label = $label . html_writer::start_tag('strong', array('style' => 'color:red'));
            $label = $label . htmlspecialchars(substr($currtext, $from-1, $to-$from));
            $label = $label . html_writer::end_tag('strong');
            $label = $label . htmlspecialchars(substr($currtext, $to-1, strlen($currtext)));
            $label = $label . html_writer::empty_tag('br');
        }
        $label = $label . html_writer::end_tag('code');

        return $label;
    }

    // TODO VVV
//    public function validation($data, $files) {
//        $errors = parent::validation($data, $files);
//        $datasets = $data['dataset'];
//        $countvalid = 0;
//        foreach ($datasets as $key => $dataset) {
//            if ($dataset != '0') {
//                $countvalid++;
//            }
//        }
//        if (!$countvalid) {
//            foreach ($datasets as $key => $dataset) {
//                $errors['dataset['.$key.']'] = "OSAIJDOIASJD";
//            }
//        }
//        return $errors;
//    }
}
