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
 * @package    qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license    http://opensource.org/licenses/mit-license The MIT License
 */


defined('MOODLE_INTERNAL') || die();


class codecpp_quiz_cache
{
    public static function update_cache($quizid) {
        $cache = cache::make_from_params(
            cache_store::MODE_APPLICATION,
            'qtype_codecpp',
            'question_images');

        $question_ids = self::get_quiz_codecpp_questions($quizid);
        $data = array();
        foreach ($question_ids as $question) {
            if ($cache->get($question->codecpp_id) !== false) {
                // We have stored cache for this question, no need to recalculate it
                continue;
            }

            $data[$question->codecpp_id] = self::generate_image($question->question_text);
        }

        $cache->set_many($data);
    }

    public static function get_text_image($codecpp_id) {
        global $DB;

        $cache = cache::make_from_params(
            cache_store::MODE_APPLICATION,
            'qtype_codecpp',
            'question_images');

        $text_image = $cache->get($codecpp_id);
        if ( $text_image !== false) {
            return $text_image;
        }

        // Update the cache
        $text = $DB->get_record(
            'question_codecpp_dataset',
            array('id' => $codecpp_id),
            'text')->text;

        $text_image = self::generate_image($text);
        $cache->set($codecpp_id, $text_image);

        return $text_image;
    }

    private static function generate_image($text) {
        global $CFG;
        $font = $CFG->dirroot . '/question/type/codecpp/fonts/JetBrainsMono-Regular.ttf';
        $font_size = 12;
        $line_height = $font_size + 10;
        $padding = 10;
        $lines = explode("\n", $text);

        // Calculate the width of the image
        $image_width = 0;
        foreach ($lines as &$line) {
            $size = imagettfbbox($font_size, 0, $font, $line);
            $image_width = max($image_width, abs($size[0]) + abs($size[2]) + $padding);
        }

        $image_height = count($lines) * $line_height + $padding * 2;

        $image = imagecreatetruecolor($image_width, $image_height);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $textcolor = imagecolorallocate($image, 0, 0, 0); // TODO VVV add config
        imagefill($image, 0, 0, 0x7fff0000);

        $current_x = $line_height + $padding;
        foreach($lines as &$line){
            imagettftext($image, $font_size, 0, $padding, $current_x, $textcolor, $font, $line);
            $current_x += $line_height;
        }

        ob_start();
        imagepng($image);

        return base64_encode(ob_get_clean());
    }

    private static function get_quiz_codecpp_questions($quizid) {
        global $DB;

        $sql = 'SELECT qcd.id as codecpp_id,
                 qt.id as question_id,
                 qcd.text as question_text
                FROM {quiz} q
                LEFT JOIN {quiz_slots} qs ON q.id = qs.quizid
                LEFT JOIN {course} c ON q.course = c.id
                JOIN {question} qt ON qs.questionid = qt.id
                JOIN {question_codecpp} qc ON qt.id = qc.questionid
                JOIN {question_codecpp_dataset} qcd ON qt.id = qcd.questionid
                WHERE q.id = :quizid
                ';

        return $DB->get_records_sql($sql, array('quizid' => $quizid));
    }

    public static function get_quiz_data_codecpp(){
        global $DB;
        $sql = 'SELECT DISTINCT q.id as quiz_id,
                 q.name,
                 q.course as course_id,
                 c.fullname,
                 c.shortname
                FROM {quiz} q
                LEFT JOIN {quiz_slots} qs ON q.id = qs.quizid
                LEFT JOIN {course} c ON q.course = c.id
                JOIN {question} qt ON qs.questionid = qt.id
                WHERE qt.qtype = \'codecpp\'
                ORDER BY q.id DESC
                ';

        return $DB->get_records_sql($sql);
    }
}