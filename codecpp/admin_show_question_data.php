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
 * Show question data
 *
 * This is a page which shows the data from finished attempts
 * @package   qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

require_once('../../../config.php');
require_once('./questiontype.php');
require_once('./classes/update_weights_decision_form.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/xmlize.php');
require_once($CFG->libdir . '/questionlib.php');

admin_externalpage_setup('qtype_codecpp_showquestiondata');

$questionid = optional_param('questionid', '', PARAM_INT);
$thispageurl = new moodle_url('/question/type/codecpp/admin_show_question_data.php');

echo $OUTPUT->header();

// Process actions ============================================================

// Accept.
if ($questionid && confirm_sesskey()) {
    $questioname = $DB->get_record('question', array('id' => $questionid), 'name')->name;

    $chart = new \core\chart_line();
    $chart->set_title(sprintf(get_string('format_question_data', 'qtype_codecpp'), $questioname));
    $chart->set_smooth(true); // Calling set_smooth() passing true as parameter, will display smooth lines.
    $sales = new \core\chart_series('sales', array(400, 500, 1200, 540));
    $expenses = new \core\chart_series('expenses', array(1000, 1150, 680, 1250));
    $chart->add_series($sales);
    $chart->add_series($expenses);
    $chart->set_labels(['2004', '2005', '2006', '2007']);

    echo $OUTPUT->render($chart);
    echo $OUTPUT->footer();
    return;
}

echo $OUTPUT->heading(get_string('show_question_data', 'qtype_codecpp'));
echo $OUTPUT->box_start('generalbox', 'notice');

$table = new flexible_table('qtype_codecpp_question_data');
$table->define_baseurl($thispageurl);
$table->define_columns(array('question_name', 'quiz_name', 'attempts_count', 'report'));
$table->define_headers(array(
    get_string('question_name', 'qtype_codecpp'),
    get_string('quiz_name', 'qtype_codecpp'),
    get_string('attempts_count', 'qtype_codecpp'),
    get_string('report', 'qtype_codecpp')
));
$table->set_attribute('id', 'codecpp_question_data');
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

// qs.id is used as first value because get_records_sql is using the first value as key and it needs to be unique
$sql_data = $DB->get_records_sql("SELECT
    qs.id,
    q.id as question_id,
    q.name as question_name,
    qz.id as quiz_id,
 qz.name as quiz_name,
 c.id as course_id,
 c.fullname as course_fullname,
 c.shortname as course_shortname
FROM {question} q
JOIN {quiz_slots} qs ON q.id = qs.questionid
LEFT JOIN {quiz} qz ON qz.id = qs.quizid
LEFT JOIN {course} c ON c.id = qz.course 
WHERE q.qtype = 'codecpp'
ORDER BY q.id DESC"
);

$data = array();

foreach ($sql_data as $item) {
    if (!array_key_exists($item->question_id, $data)) {
        $data[$item->question_id] = array(
            'question_name' => $item->question_name,
            'quiz_data' => array()
        );
    }

    $data[$item->question_id]['quiz_data'][$item->quiz_id] = array(
        'quiz_name' => $item->quiz_name,
        'course_id' => $item->course_id,
        'course_fullname' => $item->course_fullname,
        'course_shortname' => $item->course_shortname
    );
}

foreach ($data as $question_id => $question_data) {
    $row = array();

    $row[] = $question_data['question_name'];

    $quiz_array = array();
    $attempt_count = 0;
    foreach ($question_data['quiz_data'] as $quiz_id => $quiz_data){
        $quizurl = new moodle_url('/mod/quiz/view.php', array('q' => $quiz_id));
        $courseurl = new moodle_url('/course/view.php', array('id' => $quiz_data['course_id']));
        $quiz_array[] = html_writer::link($quizurl, $quiz_data['quiz_name'], array('title' => $quiz_data['quiz_name'])) .
            ' | ' .
            html_writer::link($courseurl, sprintf('%s (%s)', $quiz_data['course_fullname'], $quiz_data['course_shortname']),
                array('title' => sprintf('%s (%s)', $quiz_data['course_fullname'], $quiz_data['course_shortname'])));

        // TODO VVV improve this
        $attempt_count += count(get_attempts_for_quiz($quiz_id));
    }

    $row[] = join(html_writer::empty_tag('br'), $quiz_array);
    $row[] = $attempt_count;

    $row[] = html_writer::link(
        new moodle_url($thispageurl, array('questionid' => $question_id, 'sesskey' => sesskey())),
        get_string('view_data', 'qtype_codecpp'),
        array('title' => get_string('view_data', 'qtype_codecpp'), 'class' => 'btn btn-primary')
    );

    $table->add_data($row);
}

$table->finish_output();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

function get_attempts_for_quiz($quizid) {
    global $DB;

    $sql = 'SELECT quiza.id
       FROM {quiz_attempts} quiza
       WHERE quiza.quiz = :quizid AND quiza.preview = 0 AND (quiza.state = \'finished\' OR quiza.state IS NULL)';

    return $DB->get_records_sql($sql, array('quizid' => $quizid));
}
