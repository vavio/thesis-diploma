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

admin_externalpage_setup('qtype_codecpp_showquestiondata');

$questionid = optional_param('questionid', '', PARAM_INT);
$download = optional_param('download', '', PARAM_BOOL);
$thispageurl = new moodle_url('/question/type/codecpp/admin_show_question_data.php');


// Process actions ============================================================

if ($download && $questionid && confirm_sesskey()) {
    $question_data = get_question_data($questionid)[$questionid];
    $statistic = get_attemp_data($questionid);
    generate_download_file($question_data['question_name'], $statistic, $questionid);
    return;
}

echo $OUTPUT->header();

// Accept.
if ($questionid && confirm_sesskey()) {
    $question_data = get_question_data($questionid)[$questionid];
    $statistic = get_attemp_data($questionid);

    $avg_data = array();
    $std_dev = array();
    $data_count = array();
    $diff_data = array();
    $min_data = array();
    $max_data = array();
    $labels = array();

    foreach ($statistic as $k => $s) {
        $avg_data[] = array_sum($s['times']) / count($s['times']);
        $std_dev[] = stats_standard_deviation($s['times']);
        $data_count[] = count($s['times']);
        $min_data[] = min($s['times']);
        $max_data[] = max($s['times']);
        $diff_data[] = $s['difficulty'];
        $labels[] = 'Variation ' . ($k + 1);
    }

    $chart = new \core\chart_line();
    $chart->set_title(sprintf(get_string('format_question_data', 'qtype_codecpp'), $question_data['question_name']));
    $chart->set_smooth(true); // Calling set_smooth() passing true as parameter, will display smooth lines.

    $avg_time = new \core\chart_series(get_string('average_time', 'qtype_codecpp'), $avg_data);
    $std_dev_chart = new \core\chart_series(get_string('standard_deviation', 'qtype_codecpp'), $std_dev);
    $count = new \core\chart_series(get_string('attempts_count', 'qtype_codecpp'), $data_count);
    $difficulty = new \core\chart_series(get_string('difficulty', 'qtype_codecpp'), $diff_data);
    $min_chart = new \core\chart_series(get_string('min_time', 'qtype_codecpp'), $min_data);
    $max_chart = new \core\chart_series(get_string('max_time', 'qtype_codecpp'), $max_data);
    $chart->add_series($avg_time);
    $chart->add_series($std_dev_chart);
    $chart->add_series($count);
    $chart->add_series($difficulty);
    $chart->add_series($min_chart);
    $chart->add_series($max_chart);
    $chart->set_labels($labels);

    echo $OUTPUT->render($chart);
    echo html_writer::link(
        new moodle_url($thispageurl, array('questionid' => $questionid, 'sesskey' => sesskey(), 'download' => true)),
        get_string('download_csv', 'qtype_codecpp'),
        array('title' => get_string('download_csv', 'qtype_codecpp')));
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

$data = get_question_data();

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
        $attempt_count += count(attempts_data::get_attempts_for_quiz($quiz_id));
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

function get_question_data($question_id = null) {
    global $DB;
    // qs.id is used as first value because get_records_sql is using the first value as key and it needs to be unique
    $sql_query = "
    SELECT
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
    WHERE q.qtype = 'codecpp'";
    if ($question_id != null) {
        $sql_query .= sprintf("\n AND q.id = %s", $question_id);
    }
    $sql_query .= "\n ORDER BY q.id DESC";
    $sql_data = $DB->get_records_sql($sql_query);

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

    return $data;
}

/**
 * This user-land implementation follows the implementation quite strictly;
 * it does not attempt to improve the code or algorithm in any way. It will
 * raise a warning if you have fewer than 2 values in your array, just like
 * the extension does (although as an E_USER_WARNING, not E_WARNING).
 *
 * @param array $a
 * @param bool $sample [optional] Defaults to false
 * @return float|bool The standard deviation or false on error.
 */
function stats_standard_deviation(array $a, $sample = false) {
    $n = count($a);
    if ($n === 0) {
        trigger_error("The array has zero elements", E_USER_WARNING);
        return false;
    }
    if ($sample && $n === 1) {
        trigger_error("The array has only 1 element", E_USER_WARNING);
        return false;
    }
    $mean = array_sum($a) / $n;
    $carry = 0.0;
    foreach ($a as $val) {
        $d = ((double) $val) - $mean;
        $carry += $d * $d;
    };
    if ($sample) {
        --$n;
    }
    return sqrt($carry / $n);
}

function get_attemp_data($questionid) {
    $question_data = get_question_data($questionid)[$questionid];

    $all_attempts_data = array();
    foreach ($question_data['quiz_data'] as $quiz_id => $quiz_data) {
        $all_attempts = attempts_data::get_attempts_for_quiz($quiz_id);

        foreach ($all_attempts as $a) {
            $attempt = new attempts_data($a->id);
            $all_attempts_data[] = $attempt->get_data();
        }
    }

    $filtered_data = array();
    foreach ($all_attempts_data as $attempt_data) {
        foreach ($attempt_data as $data) {
            if ($data['qid'] == $questionid) {
                $filtered_data[] = $data;
            }
        }
    }

    $statistic = array();

    foreach ($filtered_data as $data) {
        $varid = $data['variation_id'];
        if (!key_exists($varid, $statistic)) {
            $statistic[$varid]['times'] = array();
            $statistic[$varid]['difficulty'] = $data['difficulty'];
            $statistic[$varid]['text'] = $data['text'];
        }

        $statistic[$varid]['times'][] = $data['time'];
    }

    usort($statistic, function ($a, $b) {
        return (double)$a['difficulty'] - (double)$b['difficulty'];
    });

    return $statistic;
}

function generate_download_file($question_name, $statistic, $questionid) {
    $content = "variation_id,calculated_difficulty,response1;response2;....;responseN\n";
    foreach ($statistic as $k => $s) {
        $content .= sprintf("%d,%s,%s\n", $k + 1, $s['difficulty'], join(";", $s['times']));
    }
    $filename = sprintf("response_times_%s_%d-%s.csv", $question_name, $questionid, date("Ymd"));
    send_file($content, $filename, 0, 0, true, true, 'text/csv');
}