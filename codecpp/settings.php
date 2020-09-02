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
 * Admin settings for the codecpp question type.
 *
 * @package   qtype_codecpp
 * @copyright  2020 onwards Valentin Ambaroski
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

$settings = null;

if (is_siteadmin()) {
    $ADMIN->add('qtypesettings', new admin_category('qtype_codecpp_category', get_string('pluginname', 'qtype_codecpp')));

    $ADMIN->add('qtype_codecpp_category',
        new admin_externalpage(
            'qtype_codecpp_updateweigths',
            get_string('update_weights', 'qtype_codecpp'),
            new moodle_url('/question/type/codecpp/update_weights.php'),
            'moodle/site:config'
        ));

    $conf = get_config('qtype_codecpp');
    $settingspage = new admin_settingpage('codecppsetting' , get_string('settings'));
    $ADMIN->add('qtype_codecpp_category', $settingspage);

    $settingspage->add(new admin_setting_configcheckbox('qtype_codecpp/use_http',
        get_string('use_http', 'qtype_codecpp'),
        get_string('use_http_text', 'qtype_codecpp'), 1));

    $settingspage->add(new admin_setting_configtext('qtype_codecpp/servicehost',
        get_string('servicehost', 'qtype_codecpp'),
        get_string('servicehost_text', 'qtype_codecpp'), '0.0.0.0', PARAM_HOST));

    $settingspage->add(new admin_setting_configtext('qtype_codecpp/serviceport',
        get_string('serviceport', 'qtype_codecpp'),
        get_string('serviceport_text', 'qtype_codecpp'), 5000, PARAM_INT));
}
