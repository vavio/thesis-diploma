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
 *
 * Update weigths
 *
 * This is updating the constants which are used to calculate the code difficulty
 * @package   qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/xmlize.php');
require_once($CFG->libdir . '/questionlib.php');

admin_externalpage_setup('qtype_codecpp_updateweigths');

$thispageurl = new moodle_url('/question/type/codecpp/update_weights.php/');

echo $OUTPUT->header();

$table = new flexible_table('qtype_codecpp_adjust_weights');
$table->define_baseurl($thispageurl);
$table->define_columns(array('course_name', 'quiz_name', 'last_updated'));
$table->define_headers(array(
    get_string('course_name', 'qtype_codecpp'),
    get_string('quiz_name', 'qtype_codecpp'),
    get_string('last_updated', 'qtype_codecpp')
));
$table->set_attribute('id', 'codecpp');
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

$quiz_with_codecpp = $DB->get_records_sql('SELECT q.id as quiz_id,
       q.name,
       q.course as course_id,
       c.fullname,
       c.shortname
       FROM {quiz} q
        LEFT JOIN {quiz_slots} qs ON q.id = qs.quizid
        LEFT JOIN {course} c ON q.course = c.id
       WHERE qs.questionid IN (
        SELECT qs.id
        FROM {question} qs
        WHERE qs.qtype = \'codecpp\'
       )');


$updated_quizes = $DB->get_records_sql('SELECT quizid FROM {question_codecpp_quizupdate}');

foreach ($quiz_with_codecpp as $quiz) {
    $row = array();

    $courseurl = new moodle_url('/course/view.php', array('id' => $quiz->course_id));
    $row[] = html_writer::link($courseurl, sprintf('%s (%s)', $quiz->fullname, $quiz->shortname),
        array('title' => sprintf('%s (%s)', $quiz->fullname, $quiz->shortname)));

    $quizurl = new moodle_url('/mod/quiz/view.php', array('q' => $quiz->quiz_id));
    $row[] = html_writer::link($quizurl, $quiz->name, array('title' => $quiz->name));
    if (isset($updated_quizes[$quiz->quiz_id])) {
        $row[] = "Updated on: ";
    } else {
        $row[] = "Update";
    }

    $table->add_data($row);
}

$table->finish_output();

echo $OUTPUT->footer();
