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
require_once('./questiontype.php');
require_once('./classes/update_weights_decision_form.php');
require_once('./classes/attempts_data.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/xmlize.php');
require_once($CFG->libdir . '/questionlib.php');

admin_externalpage_setup('qtype_codecpp_updateweights');

$confirm = optional_param('confirm', 0, PARAM_BOOL);
$quizid = optional_param('quizid', '', PARAM_INT);
$changes_applied = optional_param('changes_applied', '', PARAM_TEXT);
$thispageurl = new moodle_url('/question/type/codecpp/admin_update_weights.php');

// Process actions ============================================================

// Accept.
if ($confirm && confirm_sesskey()) {
    throw_if_quiz($quizid);

    $new_weights = json_encode(unserialize(base64_decode($changes_applied)));

    $updated_record = new stdClass();
    $updated_record->quizid = $quizid;
    $updated_record->timecreated = time();
    $updated_record->changes_applied = $new_weights;

    qtype_codecpp::call_service('accept_weights', $new_weights);
    $DB->insert_record('question_codecpp_quizupdate', $updated_record);

    $quizname = $DB->get_record('quiz', array('id' => $quizid), 'name')->name;
    redirect($thispageurl, sprintf(get_string('weights_updated_success', 'qtype_codecpp'), $quizname));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('update_weights', 'qtype_codecpp'));
echo $OUTPUT->box_start('generalbox', 'notice');

// There is no need to handle Decline action since we just show the normal page

// Update weights.
if ($quizid && confirm_sesskey()) {
    throw_if_quiz($quizid);

    $all_attempts_data = array();
    $all_attempts = attempts_data::get_attempts_for_quiz($quizid);

    foreach ($all_attempts as $a) {
        $attempt = new attempts_data($a->id);
        $all_attempts_data[] = $attempt->get_data();
    }

    $result = qtype_codecpp::call_service('update_weights', json_encode($all_attempts_data));
    $result = json_decode($result)->weights;

    $mform = new update_weights_decision_form($result,null, ['returnurl' => $thispageurl, 'quizid' => $quizid]);

    $mform->display();
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();

    return;
}

$table = new flexible_table('qtype_codecpp_adjust_weights');
$table->define_baseurl($thispageurl);
$table->define_columns(array('course_name', 'quiz_name', 'last_updated'));
$table->define_headers(array(
    get_string('course_name', 'qtype_codecpp'),
    get_string('quiz_name', 'qtype_codecpp'),
    get_string('last_updated', 'qtype_codecpp')
));
$table->set_attribute('id', 'codecpp_weights');
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
        $timecreated = $DB->get_record('question_codecpp_quizupdate', array('quizid' => $quiz->quiz_id), 'timecreated')->timecreated;
        $row[] = userdate((int)$timecreated);
    } else {
        $row[] = html_writer::link(
            new moodle_url($thispageurl, array('quizid' => $quiz->quiz_id, 'sesskey' => sesskey())),
            get_string('update_button', 'qtype_codecpp'),
            array('title' => get_string('update_button', 'qtype_codecpp'), 'class' => 'btn btn-primary')
        );
    }

    $table->add_data($row);
}

$table->finish_output();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

function throw_if_quiz($quizid) {
    global $DB;
    global $thispageurl;

    if (!$DB->record_exists('quiz', array('id' => $quizid))) {
        throw new moodle_exception('errquizdoesntexists', 'qtype_codecpp', $thispageurl, null, $quizid);
    }

    if ($DB->record_exists('question_codecpp_quizupdate', array('quizid' => $quizid))) {
        throw new moodle_exception('errquizalreadyupdated', 'qtype_codecpp', $thispageurl, null, $quizid);
    }
}

function get_attempts_for_quiz($quizid) {
    global $DB;

    $sql = 'SELECT quiza.id,
       u.id AS userid,
       quiza.timefinish,
       quiza.timestart
       FROM {user} u
        LEFT JOIN {quiz_attempts} quiza ON quiza.userid = u.id AND quiza.quiz = :quizid
       WHERE quiza.id IS NOT NULL AND quiza.preview = 0 AND (quiza.state = \'finished\' OR quiza.state IS NULL)';

    return $DB->get_records_sql($sql, array('quizid' => $quizid));
}
