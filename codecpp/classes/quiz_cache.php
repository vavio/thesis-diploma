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
 * Defines the update cache for CodeCPP to generate images for specific quizid.
 *
 * @package    qtype_codecpp
 * @package   qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

class quiz_cache
{
    public function update_cache($quizid) {

    }

    public static function get_quiz_with_codecpp($quizid = null){
        global $DB;
        $sql = 'SELECT DISTINCT q.id as quiz_id,';
        if ($quizid !== null) {
            $sql = 'SELECT qs.id as question_id,
                 q.id as quiz_id,';
        }

        $sql .= 'q.name,
                 q.course as course_id,
                 c.fullname,
                 c.shortname
                FROM {quiz} q
                LEFT JOIN {quiz_slots} qs ON q.id = qs.quizid
                LEFT JOIN {course} c ON q.course = c.id
                JOIN {question} qt ON qs.questionid = qt.id
                WHERE qt.qtype = \'codecpp\'';

        if ($quizid !== null) {
            $sql .= 'AND q.id = ' . $quizid;
        } else {
            $sql .= 'ORDER BY q.id DESC';
        }

        return $DB->get_records_sql($sql);
    }
}