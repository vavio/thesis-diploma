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
require_once('./classes/attempts_data.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/xmlize.php');
require_once($CFG->libdir . '/questionlib.php');

admin_externalpage_setup('qtype_codecpp_viewvariations');

$thispageurl = new moodle_url('/question/type/codecpp/admin_view_variations.php');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('show_question_data', 'qtype_codecpp'));
echo $OUTPUT->box_start('generalbox', 'notice');

$table = new flexible_table('qtype_codecpp_variations_data');
$table->define_baseurl($thispageurl);
$table->define_columns(array('question_name', 'variations'));
$table->define_headers(array(
    get_string('question_name', 'qtype_codecpp'),
    get_string('variations', 'qtype_codecpp')
));
$table->set_attribute('id', 'codecpp_vairations_data');
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

$data = get_codecpp_questions();

foreach ($data as $question_id => $question_data) {
    $row = array();

    $row[] = $question_data['question_name'];

    $variation_array = array();
    foreach ($question_data['variations'] as $variation_id => $variation_data) {
        $variationurl = new moodle_url('/question/type/codecpp/preview.php', array('codecpp_id' => $variation_data['codecpp_id']));
        $text = sprintf('Variation %3d | Difficulty: %.2f', $variation_id + 1, $variation_data['question_difficulty']);

        $action = new popup_action('click', $variationurl, 'questionpreview', question_preview_popup_params());
        $variation_array[] = $OUTPUT->action_link($variationurl, $text, $action, array('title' => $text));
    }

    $row[] = join(html_writer::empty_tag('br'), $variation_array);

    $table->add_data($row);
}

$table->finish_output();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

function get_codecpp_questions() {
    global $DB;
    $sql_data = $DB->get_records_sql("
    SELECT
     qcd.id as codecpp_id,
     q.id as question_id,
     q.name as question_name,
     qcd.difficulty as question_difficulty
    FROM {question} q
    JOIN {question_codecpp_dataset} qcd ON q.id = qcd.questionid
    ORDER BY q.id DESC
    ");

    $data = array();
    // preprocess data

    foreach ($sql_data as $question) {
        if (!array_key_exists($question->question_id, $data)) {
            $data[$question->question_id]['question_name'] = $question->question_name;
            $data[$question->question_id]['variations'] = array();
        }

        $data[$question->question_id]['variations'][] = array(
            'codecpp_id' => $question->codecpp_id,
            'question_difficulty' => $question->question_difficulty
        );
    }

    foreach ($data as &$d) {
        usort($d['variations'], function ($a, $b) {
            $diffic_a = (double)$a['question_difficulty'];
            $diffic_b = (double)$b['question_difficulty'];
            if ($diffic_a == $diffic_b) {
                return (int)$a['codecpp_id'] - (int)$b['codecpp_id'];
            }

            return $diffic_a - $diffic_b;
        });
    }

    return $data;
}