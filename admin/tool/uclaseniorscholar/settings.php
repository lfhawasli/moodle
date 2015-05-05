<?php
// This file is part of the UCLA Senior Scholar Site Invitation Plugin for Moodle - http://moodle.org/
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
 * Invitation enrolments plugin settings and presets.
 *
 * @package    tool_uclaseniorscholar
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage('tool_uclaseniorscholar_settings',
get_string('pluginname_desc', 'tool_uclaseniorscholar'));

// Setting for senior scholar administrator UID.
$settings->add(new admin_setting_configtext('tool_uclaseniorscholar/seniorscholaradministrator',
               get_string('seniorscholaradministratoraccount', 'tool_uclaseniorscholar'),
               get_string('seniorscholaradministratoraccount_instruction', 'tool_uclaseniorscholar'), '', PARAM_NOTAGS));

// Setting for senior scholar support email.
$settings->add(new admin_setting_configtext('tool_uclaseniorscholar/seniorscholarsupportemail',
               get_string('seniorscholarsupportemail', 'tool_uclaseniorscholar'),
               get_string('seniorscholarsupportemail_instruction', 'tool_uclaseniorscholar'), '', PARAM_NOTAGS));

$ADMIN->add('tools', $settings);
$ADMIN->add('users', new admin_externalpage(
        'uclaseniorscholar',
        get_string('pluginname', 'tool_uclaseniorscholar'),
        "$CFG->wwwroot/$CFG->admin/tool/uclaseniorscholar/index.php",
        "tool/uclaseniorscholar:view"));
