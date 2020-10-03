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
 * @package    qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

            $suggested_data = array();
            if (key_exists('suggested', $datasetentry)) {
                $suggested_data = $datasetentry['suggested'];
            }

            $mform->addElement('header', "header[{$idx}]", '');

            $edit_type = rtrim($datasetentry['type']);

            $mform->addElement('html', $this->get_code_label($datasetentry));
            $mform->addElement('advcheckbox', "edit[{$idx}]",
                get_string('edit_element', 'qtype_codecpp', $this->getDescriptionForOp($edit_type)));

            switch ($edit_type) {
                case "integer":
                    $this->addIntegerOptions($idx, $suggested_data);
                    break;

                case "float":
                    $this->addFloatOptions($idx, $suggested_data);
                    break;

                case "binary_op":
                    $this->addBinaryOptions($idx, $suggested_data);
                    break;

                case "unary_op":
                    $this->addUnaryOptions($idx, $suggested_data);
                    break;

                case "logical":
                    $this->addLogicalOptions($idx, $suggested_data);
                    break;

                case "text":
                    $this->addTextOptions($idx, true, $suggested_data);
                    break;

                case "character":
                    $this->addTextOptions($idx, false, $suggested_data);
                    break;
            }
        }

        $mform->addElement('submit', 'savechanges', "Save Changes");
    }

    private function addIntegerOptions($idx, $suggested_data) {
        $mform = $this->_form;

        $mform->addElement('text', "int_range[{$idx}]", get_string('int_range', 'qtype_codecpp'));
        $mform->addHelpButton("int_range[{$idx}]", 'int_range', 'qtype_codecpp');
        $mform->setType("int_range[{$idx}]", PARAM_TEXT);

        $mform->hideIf("int_range[{$idx}]", "edit[{$idx}]",'neq', '1');

        if (!empty($suggested_data)) {
            $this->set_data(array("edit[{$idx}]" => 1));
            $this->set_data(array("int_range[{$idx}]" => $suggested_data));
        }
    }

    private function addFloatOptions($idx, $suggested_data) {
        $mform = $this->_form;

        $mform->addElement('text', "float_range[{$idx}]", get_string('float_range', 'qtype_codecpp'));
        $mform->addHelpButton("float_range[{$idx}]", 'float_range', 'qtype_codecpp');
        $mform->setType("float_range[{$idx}]", PARAM_TEXT);

        $mform->hideIf("float_range[{$idx}]", "edit[{$idx}]",'neq', '1');

        if (!empty($suggested_data)) {
            $this->set_data(array("edit[{$idx}]" => 1));
            $this->set_data(array("float_range[{$idx}]" => $suggested_data));
        }
    }

    private function addBinaryOptions($idx, $suggested_data) {
        $binary_ops = array(
            '*' => 'multiplication',
            '+' => 'addition',
            '-' => 'subtraction',
            '=' => 'equals',
            '%' => 'modulo',
            '<=' => 'smallerorequal',
            '<' => 'smaller',
            '>=' => 'biggerorequal',
            '>' => 'bigger',
            '==' => 'equalsequals',
            '!=' => 'notequals'
        );
        $mform = $this->_form;

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

        if (!empty($suggested_data)) {
            $this->setOperatorData($idx, $binary_ops, explode(";", $suggested_data));
        }
    }

    private function addUnaryOptions($idx, $suggested_data) {
        $unary_ops = array('++' => 'increment', '--' => 'decrement');

        $mform = $this->_form;

        $checkboxes = [
            $mform->createElement('advcheckbox', "increment", "", "++"),
            $mform->createElement('advcheckbox', "decrement", "", "--")
        ];
        $mform->addGroup($checkboxes, "selectedoptions[{$idx}]", get_string('unary_operators', 'qtype_codecpp'));
        $mform->addHelpButton("selectedoptions[{$idx}]", 'unary_operators', 'qtype_codecpp');

        $mform->hideIf("selectedoptions[{$idx}]", "edit[{$idx}]",'neq', '1');

        if (!empty($suggested_data)) {
            $this->setOperatorData($idx, $unary_ops, explode(";", $suggested_data));
        }
    }

    private function addLogicalOptions($idx, $suggested_data) {
        $logical_ops = array('&&' => 'andoperator', '||' => 'oroperator');

        $mform = $this->_form;

        $checkboxes = [
            $mform->createElement('advcheckbox', "andoperator", "", "&&"),
            $mform->createElement('advcheckbox', "oroperator", "", "||")
        ];
        $mform->addGroup($checkboxes, "selectedoptions[{$idx}]", get_string('logical_operators', 'qtype_codecpp'));
        $mform->addHelpButton("selectedoptions[{$idx}]", 'logical_operators', 'qtype_codecpp');

        $mform->hideIf("selectedoptions[{$idx}]", "edit[{$idx}]",'neq', '1');

        if (!empty($suggested_data)) {
            $this->setOperatorData($idx, $logical_ops, explode(";", $suggested_data));
        }
    }

    private function addTextOptions($idx, $isText, $suggested_data) {
        $string_ops = array('lowercase' => 'lowercase', 'uppercase' => 'uppercase', 'digits' => 'digits');

        $mform = $this->_form;

        $checkboxes = array();
        if ($isText){
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

        if (!empty($suggested_data)) {
            $this->set_data(array("edit[{$idx}]" => 1));

            $splitted = explode(";", $suggested_data);
            if ($isText) {
                $this->set_data(array("textoptions[{$idx}][range]" => $splitted[0]));
                $splitted = array_slice($splitted, 1);
            }

            foreach ($splitted as $item) {
                $this->set_data(array("textoptions[{$idx}][" . $string_ops[$item] . "]" => "1"));
            }
        }
    }

    private function setOperatorData($idx, $ops, $data) {
        $this->set_data(array("edit[{$idx}]" => 1));
        foreach ($data as $item) {
            $this->set_data(array("selectedoptions[{$idx}][" . $ops[$item] . "]" => "1"));
        }
    }

    private function getDescriptionForOp($operation) {
        switch ($operation) {
            case "integer":
                return "int range";

            case "float":
                return "float range";

            case "binary_op":
                return "binary operator";

            case "unary_op":
                return "unary operator";

            case "logical":
                return "logical operator";

            case "text":
                return "text options";

            case "character":
                return "char options";
        }

        return "UNKNOWN OP";
    }

    private function get_code_label($entry) {
        $config = get_config('qtype_codecpp');
        $num_lines = $config->code_preview_lines;

        $lines = array_slice($this->question_lines, $entry['start_line'] - $num_lines - 1, 2 * $num_lines + 1);

        $label = html_writer::start_tag('code', array('style' => 'color:black'));
        for ($i=0; $i<count($lines); $i++){
            $currtext = trim($lines[$i]);

            if ((count($lines) - 1) / 2 != $i) {
                $label .= htmlspecialchars($currtext) . html_writer::empty_tag('br');
                continue;
            }

            // Do not remove this magic string. I spent days because I don't know how to work with UTF-8 or w.e this is
            // If this gets removed there will be strange formatting for the element
            $splitted = preg_split('/^[\x00-\x1F\x7F\xA0]+/u', $currtext,
                -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);

            if (!empty($splitted)) {
                $label .= htmlspecialchars(substr($currtext, 0, $splitted[0][1] - 2));
            }

            $currtext = preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $currtext);

            $from = (int)$entry['start_column'];
            $to = (int)$entry['end_column'];
            $before = substr($currtext, 0, $from - 1);
            $value = substr($currtext, $from-1, $to-$from);
            $after = substr($currtext, $to-1, strlen($currtext));

            $label .= htmlspecialchars($before);
            $label .= html_writer::start_tag('strong', array('style' => 'color:red'));
            $label .= htmlspecialchars($value);
            $label .= html_writer::end_tag('strong');
            $label .= htmlspecialchars($after);
            $label .= html_writer::empty_tag('br');
        }
        $label .= html_writer::end_tag('code');

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
        if (key_exists('int_range', $data)) {

            foreach ($data['int_range'] as $idx => $value) {
                if ($data['edit'][$idx] == '0') {
                    continue;
                }

                if (!$this->is_valid_range($value)) {
                    $errors["int_range[{$idx}]"] = get_string('range_error', 'qtype_codecpp');
                }
            }
        }

        // range for float validation
        if (key_exists('float_range', $data)) {
            foreach ($data['float_range'] as $idx => $value) {
                if ($data['edit'][$idx] == '0') {
                    continue;
                }

                if (!$this->is_valid_float_range($value)) {
                    $errors["float_range[{$idx}]"] = get_string('range_error', 'qtype_codecpp');
                }
            }
        }

        // range for text length validation
        if (key_exists('textoptions', $data)) {
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
