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
 * Strings for component 'qtype_codecpp', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['correctanswer'] = 'Correct answer';
$string['help'] = 'Select the range of each elements';
$string['choose_element'] = 'Choose element';
$string['questiondatasets'] = 'CodeCPP question datasets';
$string['questiondatasets_help'] = 'Select values or specify range for each variable which is in red';
$string['pleaseselectananswer'] = 'Please select an answer.';
$string['answercolon'] = 'Answer: ';
$string['selectone'] = 'Select one:';
$string['pluginname'] = 'CodeCPP';
$string['source_code'] = 'Source Code';
$string['int_range'] = 'int Range';
$string['int_range_help'] = 'Specify range (:) or specific values or excluded values with caret (^) all separated  with comma (,). For example: \'1:5,7,^3\' means [1,2,4,5,7]';
$string['float_range'] = 'float Range';
$string['float_range_help'] = 'Specify range (:). For example: \'-1.2:1.2\' would select random value between (-1.2, 1.2)';
$string['string_range'] = 'String length';
$string['range_error'] = 'Bad range text';
$string['binary_operators'] = '';
$string['binary_operators_help'] = 'Select the desired binary operators to choose one randomly for each variation';
$string['logical_operators'] = '';
$string['logical_operators_help'] = 'Select the desired logical operators to choose one randomly for each variation';
$string['text_options'] = '';
$string['text_options_help'] = 'Select the set to choose a random character from. On string you can select the length of the string to be changed randomly. For example: selecting \'Digits\' and writing range 3:5 would generate string with length between 3 to 5 with digits only';
$string['lowercase_letters'] = 'Lowercase letters';
$string['uppercase_letters'] = 'Uppercase letters';
$string['digits'] = 'Digits';
$string['edit_element'] = 'Edit element';
$string['pluginname_help'] = 'In response to a question (that may include an image) the respondent gives an answer.';
$string['pluginname_link'] = 'question/type/codecpp';
$string['pluginnameadding'] = 'Adding a CodeCPP question';
$string['pluginnameediting'] = 'Editing a CodeCPP question';
$string['pluginnamesummary'] = 'A simple form of a question which generates unique questions based on the given template';
$string['privacy:metadata'] = 'The CodeCPP question type plugin does not store any personal data.';

$string['show_question_data'] = 'CodeCPP show question data';
$string['format_question_data'] = 'Attempts data for question: %s';
$string['question_name'] = 'Question name';
$string['report'] = 'Report';
$string['view_data'] = 'View data';
$string['difficulty'] = 'Difficulty';
$string['average_time'] = 'Average response time /s';
$string['min_time'] = 'Minimum response time /s';
$string['max_time'] = 'Maximum response time /s';
$string['standard_deviation'] = 'Standard deviation';
$string['download_csv'] = 'Download CSV file with response data';

$string['view_variations'] = 'CodeCPP view question variations';
$string['variations'] = 'Question variations';

$string['update_weights'] = 'CodeCPP update weights';
$string['course_name'] = 'Course Name';
$string['attempts_count'] = 'Number of attempts';
$string['quiz_name'] = 'Quiz Name with CodeCPP questions';
$string['last_updated'] = 'Update/Last updated';
$string['update_button'] = 'Update';
$string['operation'] = 'Operation';
$string['old_value'] = 'Old value';
$string['new_value'] = 'Old value';
$string['new_values'] = 'New values';
$string['same_values'] = 'Same values';
$string['accept_changes'] = 'Accept changes';
$string['weights_updated_success'] = 'Successfully updated weights for %s';

$string['update_cache'] = 'CodeCPP update cache';
$string['cache_header'] = '';
$string['generate_cache'] = 'Generate cache';
$string['cache_updated_success'] = 'Successfully updated cache for quiz: {$a}';

$string['use_http'] = 'Use HTTP';
$string['use_http_text'] = 'When communicating with the service, should HTTP be used instead of HTTPS';
$string['servicehost'] = 'Service HOST';
$string['servicehost_text'] = '';
$string['serviceport'] = 'Service PORT';
$string['serviceport_text'] = '';
$string['show_code_preview'] = 'Show code preview';
$string['show_code_preview_text'] = 'While editing range, should the original code be displayed';
$string['code_preview_lines'] = 'Code preview lines#';
$string['code_preview_lines_text'] = 'Before and After lines to display for context';
$string['text_image'] = 'Generate image from text';
$string['text_image_text'] = 'Should the plugin generate image from the text to prevent copy/paste';
$string['font_size'] = 'Font size';
$string['font_size_text'] = 'Specify the font size which will be used in image';
$string['padding'] = 'Padding';
$string['padding_text'] = 'Specify the padding for text which will be used in image';
$string['text_color'] = 'Color for text in image';
$string['text_color_text'] = 'Specify RGB values for text color in image separated by ;';