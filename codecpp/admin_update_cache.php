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
require_once('./classes/codecpp_quiz_cache.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/xmlize.php');
require_once($CFG->libdir . '/questionlib.php');

admin_externalpage_setup('qtype_codecpp_updatecache');

$quizid = optional_param('quizid', '', PARAM_INT);
$thispageurl = new moodle_url('/question/type/codecpp/admin_update_cache.php');

// Process actions ============================================================

// Accept.
if ($quizid && confirm_sesskey()) {
    throw_if_quiz($quizid);

    codecpp_quiz_cache::update_cache($quizid);

    $quiz_name = $DB->get_record('quiz', array('id' => $quizid), 'name')->name;
    $update_message = get_string('cache_updated_success', 'qtype_codecpp', $quiz_name);
    redirect($thispageurl, $update_message);
    return;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('update_weights', 'qtype_codecpp'));
echo $OUTPUT->box_start('generalbox', 'notice');

$table = new flexible_table('qtype_codecpp_update_cache');
$table->define_baseurl($thispageurl);
$table->define_columns(array('course_name', 'quiz_name', 'update_cache'));
$table->define_headers(array(
    get_string('course_name', 'qtype_codecpp'),
    get_string('quiz_name', 'qtype_codecpp'),
    get_string('cache_header', 'qtype_codecpp')
));
$table->set_attribute('id', 'codecpp_caches');
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

$quiz_data = codecpp_quiz_cache::get_quiz_data_codecpp();

foreach ($quiz_data as $quiz) {
    $row = array();

    $courseurl = new moodle_url('/course/view.php', array('id' => $quiz->course_id));
    $quiz_name = sprintf('%s (%s)', $quiz->fullname, $quiz->shortname);
    $row[] = html_writer::link($courseurl, $quiz_name, array('title' => $quiz_name));

    $quizurl = new moodle_url('/mod/quiz/view.php', array('q' => $quiz->quiz_id));
    $row[] = html_writer::link($quizurl, $quiz->name, array('title' => $quiz->name));

    $generate_cache = get_string('generate_cache', 'qtype_codecpp');
    $row[] = html_writer::link(
        new moodle_url($thispageurl, array('quizid' => $quiz->quiz_id, 'sesskey' => sesskey())),
        $generate_cache,
        array('title' => $generate_cache, 'class' => 'btn btn-primary')
    );

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
