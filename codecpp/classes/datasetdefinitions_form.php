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
        $mform->setDefault('synchronize', 0);

        $this->add_hidden_fields();

        $mform->addElement('hidden', 'category');
        $mform->setType('category', PARAM_SEQUENCE);

        $mform->addElement('hidden', 'wizard', 'datasetitems');
        $mform->setType('wizard', PARAM_ALPHA);

        for ($idx = 0; $idx<count($this->possibledatasets); $idx++) {
            $datasetentry = $this->possibledatasets[$idx];
            if ($datasetentry == ""){
                continue;
            }

            $mform->addElement('header', "header[{$idx}]", "Element ".(string)($idx + 1));

            $mform->addElement('html', $this->get_code_label($datasetentry));

            $edit_type = rtrim($datasetentry[5]);
            if (strcmp($edit_type, "integer") == 0 || strcmp($edit_type, "float") == 0){
                $mform->addElement('text', "range[{$idx}]", get_string('range_text', 'qtype_codecpp'));
                $mform->addHelpButton("range[{$idx}]", 'range_text_explanation', 'qtype_codecpp');

                continue;
            }

            if (strcmp($edit_type, "binary_op") == 0){
                $checkboxes = [
                    $mform->createElement('advcheckbox', "multiplication", "", "*"),
                    $mform->createElement('advcheckbox', "addition", "", "+"),
                    $mform->createElement('advcheckbox', "subtraction", "", "-"),
                    $mform->createElement('advcheckbox', "equals", "", "="),
                    $mform->createElement('advcheckbox', "modulo", "", "%"),
                    $mform->createElement('advcheckbox', "smallerorequal", "", "<="),
                    $mform->createElement('advcheckbox', "smaller", "", "<"),
                    $mform->createElement('advcheckbox', "biggerorequal", "", ">="),
                    $mform->createElement('advcheckbox', "bigger", "", ">"),
                    $mform->createElement('advcheckbox', "equalsequals", "", "=="),
                    $mform->createElement('advcheckbox', "notequals", "", "!=")
                ];

                $mform->addGroup($checkboxes, "selectedoptions[{$idx}]", get_string('binary_operators', 'qtype_codecpp'));
                continue;
            }

            if (strcmp($edit_type, "logical") == 0){
                $checkboxes = [
                    $mform->createElement('advcheckbox', "andoperator", "", "&&"),
                    $mform->createElement('advcheckbox', "oroperator", "", "||")
               ];
                $mform->addGroup($checkboxes, "selectedoptions[{$idx}]", get_string('logical_operators', 'qtype_codecpp'));
                continue;
            }

            if (strcmp($edit_type, "text") == 0){
                $checkboxes = [
                    $mform->createElement('advcheckbox', "lowercase", "", get_string('lowercase_letters', 'qtype_codecpp')),
                    $mform->createElement('advcheckbox', "uppercase", "", get_string('uppercase_letters', 'qtype_codecpp')),
                    $mform->createElement('advcheckbox', "digits", "", get_string('digits', 'qtype_codecpp'))
                    // TODO VVV add specific string
                ];
                $mform->addGroup($checkboxes, "selectedoptions[{$idx}]", get_string('text_options', 'qtype_codecpp'));
                continue;
            }
        }

        $mform->addElement('submit', 'savechanges', "Save Changes");
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

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        foreach ($data['range'] as $idx => $value) {
            if (strlen($value) == 0) {
                continue;
            }

            $splitted = explode(",", $value);

            foreach ($splitted as $s) {
                if (!preg_match('/(^-?\d+$|^-?\d+:-?\d+$|^\^-?\d)/m', $s)) {
                    $errors["range[{$idx}]"] = get_string('range_error', 'qtype_codecpp');
                }
            }
        }

        return $errors;
    }
}
