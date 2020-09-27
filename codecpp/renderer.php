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
 * CodeCPP question renderer class.
 *
 * @package    qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/codecpp/classes/codecpp_quiz_cache.php');

/**
 * Generates the output for CodeCPP questions.
 *
 * @package    qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_codecpp_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
        $question = $qa->get_question();
        $question_id = $qa->get_last_qt_var('_qid_');
        $currentanswer = $qa->get_last_qt_var('answer');
        $currenttext = $qa->get_last_qt_var('_qtext_');
        $qa->questiontext = $currenttext;

        $inputname = $qa->get_qt_field_name('_qans_');
        $inputattributes = array(
            'type' => 'text',
            'name' => $inputname,
            'value' => $currentanswer,
            'id' => $inputname,
            'size' => 30,
            'class' => 'form-control d-inline',
        );

        if ($options->readonly) {
            $inputattributes['readonly'] = 'readonly';
        }

        $feedbackimg = '';

        $questiontext = $this->format_newquestiontext($qa, $currenttext);
        $input = html_writer::empty_tag('input', $inputattributes) . $feedbackimg;
        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        $config = get_config('qtype_codecpp');
        if ($config->text_image) {
            $text_image = codecpp_quiz_cache::get_text_image($question_id);
            $result = html_writer::img('data:image/png;base64,' . $text_image,
                'qtext', array('class' => 'qtext'));
        }

        $result .= html_writer::start_div('ablock form-inline');
        $result .= html_writer::label(get_string('answercolon', 'qtype_codecpp'), $inputattributes['id']);
        $result .= html_writer::span($input, 'answer');
        $result .= html_writer::end_div();
        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }

        return $result;
    }

    public function format_newquestiontext($qa, $temp){
        $question = $qa->get_question();
        return $question->format_text($temp, FORMAT_PLAIN, $qa, 'question', 'questiontext', $question->id);
    }

    public function specific_feedback(question_attempt $qa) {
        return "";
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('_qans_');
        return "The answer is: " . $currentanswer;
    }
}
