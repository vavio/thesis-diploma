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
 * Defines the update weights result form for CodeCPP.
 *
 * @package    qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

class update_weights_decision_form extends moodleform
{
    /** @var array updated weights */
    protected $new_weights;

    public function __construct($new_weights, $action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true,
                                $ajaxformdata=null)
    {
        $this->new_weights = $new_weights;
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    protected function definition()
    {
        $mform = $this->_form;

        $mform->addElement('hidden', 'returnurl', $this->_customdata['returnurl']);
        $mform->setType('returnurl', PARAM_LOCALURL);

        $mform->addElement('hidden', 'quizid', $this->_customdata['quizid']);
        $mform->setType('quizid', PARAM_INT);

        $mform->addElement('hidden', 'changes_applied', base64_encode(serialize($this->new_weights)));
        $mform->setType( 'changes_applied', PARAM_BASE64);

        // -------------------------------------------------------------------------------
        // Updated values values
        $mform->addElement('header', 'new_values', get_string('new_values', 'qtype_codecpp'));

        foreach ($this->new_weights as $key => $value) {
            if ($value->old_value === $value->new_value) {
                continue;
            }

            $this->add_data($key, $value->old_value, $value->new_value);
        }

        // -------------------------------------------------------------------------------
        // Same values
        $mform->addElement('header', 'same_values', get_string('same_values', 'qtype_codecpp'));
        foreach ($this->new_weights as $key => $value) {
            if ($value->old_value !== $value->new_value) {
                continue;
            }
            $this->add_data($key, $value->old_value, $value->new_value);
        }

        // -------------------------------------------------------------------------------
        // Buttons
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'confirm', get_string('accept_changes', 'qtype_codecpp'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    protected function add_data($key, $old_value, $new_value) {
        $mform = $this->_form;

        $name = 'name.' . $key;
        $mform->addElement('static', $name, $key);
        $mform->setType( $name, PARAM_RAW);
        $mform->setDefault($name, sprintf('%.3f → %.3f', $old_value, $new_value));
    }
}