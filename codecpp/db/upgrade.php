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
 * Short-answer question type upgrade code.
 *
 * @package    qtype
 * @subpackage shortanswer
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the essay question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_codecpp_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2020090501) {
        if ($dbman->table_exists('dataset_codecpp')) {
            $table = new xmldb_table('dataset_codecpp');
            // There is no need to migrate the data, even though it would be nice to have it
            $dbman->drop_table($table);
        }

        if (!$dbman->table_exists('question_codecpp_dataset')) {
            $table = new xmldb_table('question_codecpp_dataset');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null );
            $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
            $table->add_field('text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'questionid');
            $table->add_field('result', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'text');
            $table->add_field('difficulty', XMLDB_TYPE_FLOAT, '11,4', null, XMLDB_NOTNULL, null, 0.00, 'result');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('questionid', XMLDB_KEY_FOREIGN, ['questionid'], 'question', ['id']);

            $dbman->create_table($table);
        }

        if ($dbman->field_exists('question_codecpp', 'question')) {
            // Drop the old foreign key
            $table = new xmldb_table('question_codecpp');
            $refkey = new xmldb_key('question', XMLDB_KEY_FOREIGN, ['question'], 'question', ['id']);
            $dbman->drop_key($table, $refkey);

            // Drop the field now
            $table->deleteField('question');

            // Now rename it
            $field = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $dbman->add_field($table, $field);
            $dbman->add_key($table, $refkey);
        }

        if ($dbman->field_exists('question_codecpp', 'trueanswer')){
            $table = new xmldb_table('question_codecpp');
            $table->deleteField('trueanswer');
        }

        if ($dbman->field_exists('question_codecpp', 'falseanswer')){
            $table = new xmldb_table('question_codecpp');
            $table->deleteField('falseanswer');
        }

        if (!$dbman->table_exists('question_codecpp_quizupdate')) {
            $table = new xmldb_table('question_codecpp_quizupdate');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null );
            $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'quizid');
            $table->add_field('changes_applied', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'timecreated');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('quizid', XMLDB_KEY_FOREIGN, ['quizid'], 'quiz', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2020090501, 'qtype', 'codecpp');
    }

    return true;
}
