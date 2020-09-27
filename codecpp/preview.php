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
 * This page displays a preview of a CodeCPP question
 *
 * Simple page that shows the question text and the question result.
 *
 * @package    qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('qtype_codecpp_viewvariations');

// Get and validate question id.
$codecpp_id = required_param('codecpp_id', PARAM_INT);
$question = $DB->get_record_sql('
    SELECT q.id,
     q.name,
     qcd.text,
     qcd.result,
     qcd.difficulty
    FROM {question} q
    JOIN {question_codecpp_dataset} qcd ON q.id = qcd.questionid
    WHERE qcd.id = :codecpp_id',
array("codecpp_id" => $codecpp_id));

$PAGE->set_pagelayout('popup');
$PAGE->set_url(new moodle_url('/question/type/codecpp/preview.php', array('codecpp_id' => $codecpp_id)));

// Start output.
$title = get_string('previewquestion', 'question',
    sprintf("%s, Difficulty: %.2f", format_string($question->name), $question->difficulty));
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo html_writer::start_div('que content');
echo html_writer::start_div('formulation clearfix');
echo html_writer::start_div('qtext');
//array('font-family' => 'SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace')

// Output the question.
echo format_text($question->text, FORMAT_PLAIN);

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('outcome clearfix');
echo html_writer::start_div('feedback');

echo sprintf("Output: %s", $question->result);

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

$PAGE->requires->js_module('core_question_engine');
$PAGE->requires->strings_for_js(array(
    'closepreview',
), 'question');
$PAGE->requires->yui_module('moodle-question-preview', 'M.question.preview.init');
//echo $OUTPUT->box_end();
echo $OUTPUT->footer();

