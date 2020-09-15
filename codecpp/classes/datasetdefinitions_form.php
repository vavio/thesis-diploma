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
            print_error('categorydoesnotexist', 'question');
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

        $config = get_config('qtype_codecpp');
        if ($config->show_code_preview) {
            $mform->addElement('header', 'source_code', get_string('source_code', 'qtype_codecpp'));
            $mform->addElement('html', $this->get_code());
            $mform->addElement('header', "empty_header", '');
        }

        for ($idx = 0; $idx<count($this->possibledatasets); $idx++) {
            $datasetentry = $this->possibledatasets[$idx];
            if ($datasetentry == ""){
                continue;
            }

            $mform->addElement('header', "header[{$idx}]", '');

            $mform->addElement('html', $this->get_code_label($datasetentry));
            $mform->addElement('advcheckbox', "edit[{$idx}]", get_string('edit_element', 'qtype_codecpp'));

            $edit_type = rtrim($datasetentry[5]);
            if (strcmp($edit_type, "integer") == 0){
                $mform->addElement('text', "int_range[{$idx}]", get_string('int_range', 'qtype_codecpp'));
                $mform->addHelpButton("int_range[{$idx}]", 'int_range', 'qtype_codecpp');
                $mform->setType("int_range[{$idx}]", PARAM_TEXT);

                $mform->hideIf("int_range[{$idx}]", "edit[{$idx}]",'neq', '1');
                continue;
            }

            if (strcmp($edit_type, "float") == 0){
                $mform->addElement('text', "float_range[{$idx}]", get_string('float_range', 'qtype_codecpp'));
                $mform->addHelpButton("float_range[{$idx}]", 'float_range', 'qtype_codecpp');
                $mform->setType("float_range[{$idx}]", PARAM_TEXT);

                $mform->hideIf("float_range[{$idx}]", "edit[{$idx}]",'neq', '1');
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
                $mform->addHelpButton("selectedoptions[{$idx}]", 'binary_operators', 'qtype_codecpp');

                $mform->hideIf("selectedoptions[{$idx}]", "edit[{$idx}]",'neq', '1');
                continue;
            }

            if (strcmp($edit_type, "logical") == 0) {
                $checkboxes = [
                    $mform->createElement('advcheckbox', "andoperator", "", "&&"),
                    $mform->createElement('advcheckbox', "oroperator", "", "||")
               ];
                $mform->addGroup($checkboxes, "selectedoptions[{$idx}]", get_string('logical_operators', 'qtype_codecpp'));
                $mform->addHelpButton("selectedoptions[{$idx}]", 'logical_operators', 'qtype_codecpp');

                $mform->hideIf("selectedoptions[{$idx}]", "edit[{$idx}]",'neq', '1');
                continue;
            }

            if (strcmp($edit_type, "text") == 0 || strcmp($edit_type, "character") == 0) {
                $checkboxes = array();
                if (strcmp($edit_type, "text") == 0){
                    $checkboxes[] = $mform->createElement('text', "range", get_string('string_range', 'qtype_codecpp'));
                }

                $checkboxes += array_merge($checkboxes, [
                    $mform->createElement('advcheckbox', "lowercase", "", get_string('lowercase_letters', 'qtype_codecpp')),
                    $mform->createElement('advcheckbox', "uppercase", "", get_string('uppercase_letters', 'qtype_codecpp')),
                    $mform->createElement('advcheckbox', "digits", "", get_string('digits', 'qtype_codecpp'))
                ]);

                $mform->addGroup($checkboxes, "textoptions[{$idx}]", get_string('text_options', 'qtype_codecpp'));
                $mform->setType("textoptions[{$idx}][range]", PARAM_TEXT);
                $mform->addHelpButton("textoptions[{$idx}]", 'text_options', 'qtype_codecpp');

                $mform->hideIf("textoptions[{$idx}]", "edit[{$idx}]",'neq', '1');
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

    private function get_code() {
        $label = html_writer::start_tag('code', array('style' => 'color:black'));

        foreach ($this->question_lines as $line) {
            $label = $label . htmlspecialchars($line);
            $label = $label . html_writer::empty_tag('br');
        }

        $label = $label . html_writer::end_tag('code');
        return $label;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // range for int and float validation
        foreach ($data['int_range'] as $idx => $value) {
            if ($data['edit'][$idx] == '0') {
                continue;
            }

            if (!$this->is_valid_range($value)) {
                $errors["int_range[{$idx}]"] = get_string('range_error', 'qtype_codecpp');
            }
        }

        // range for float validation
        foreach ($data['float_range'] as $idx => $value) {
            if ($data['edit'][$idx] == '0') {
                continue;
            }

            if (!$this->is_valid_float_range($value)) {
                $errors["float_range[{$idx}]"] = get_string('range_error', 'qtype_codecpp');
            }
        }

        // range for text length validation
        foreach ($data['textoptions'] as $idx => $value) {
            if ($data['edit'][$idx] == '0') {
                continue;
            }

            if (!array_key_exists('range', $value)) {
                continue;
            }

            if (!$this->is_valid_range($value['range'])) {
                $errors["textoptions[{$idx}]"] = get_string('range_error', 'qtype_codecpp');
            }
        }

        return $errors;
    }

    private function is_valid_range($value) {
        if (strlen($value) == 0) {
            return true;
        }

        $splitted = explode(",", $value);

        foreach ($splitted as $s) {
            if (!preg_match('/(^[-+]?\d+$|^[-+]?\d+:-?\d+$|^\^[-+]?\d)/m', $s)) {
                return false;
            }
        }

        return true;
    }

    private function is_valid_float_range($value) {
        if (strlen($value) == 0) {
            return true;
        }

        return preg_match('/^[-+]?\d+(\.?\d+)?:[-+]?\d+(\.?\d+)?$/m', $value);
    }
}
