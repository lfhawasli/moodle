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
 * UCLA local plugin settings.
 *
 * Contains the settings for UCLA specific customizations.
 *
 * @package    local_ucla
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('localsettingucla',
            get_string('pluginname', 'local_ucla'), 'moodle/site:config');

    $settings->add(new admin_setting_configtext(
            'local_ucla/student_access_ends_week',
            get_string('student_access_ends_week', 'local_ucla'),
            get_string('student_access_ends_week_description', 'local_ucla'), 0,
            PARAM_INT));

    $settings->add(new admin_setting_configtext(
            'local_ucla/maxcrosslistshown',
            get_string('maxcrosslistshown', 'local_ucla'),
            get_string('maxcrosslistshowndesc', 'local_ucla'), 6,
            PARAM_INT));

    // CCLE-4521 - Handle "preferred name".
    $settings->add(new admin_setting_configcheckbox('local_ucla/handlepreferredname',
        get_string('handlepreferredname', 'local_ucla'),
        get_string('handlepreferrednamedesc', 'local_ucla'),
        0));

    $settings->add(new admin_setting_configtext(
            'local_ucla/registrarurl',
            get_string('registrarurlconfig', 'local_ucla'),
            get_string('registrarurlconfighelp', 'local_ucla'),
            'https://sa.ucla.edu', PARAM_URL));

    // SSC-2050 - Include associated courses in subject header of forum emails.
    $settings->add(new admin_setting_configtext(
        'local_ucla/limitcrosslistemail',
        get_string('limitcrosslistemailname', 'local_ucla'),
        get_string('limitcrosslistemaildesc', 'local_ucla'),
        2, PARAM_INT));

    $ADMIN->add('localplugins', $settings);

    // Inject setting to turn on UCLA edits for enrol_database into
    // Site administration > Plugins > Enrolments > UCLA registrar.
    $temp = $ADMIN->locate('enrolsettingsdatabase');
    $temp->add(new admin_setting_configcheckbox('local_ucla/overrideenroldatabase',
            get_string('overrideenroldatabase', 'local_ucla'),
            get_string('overrideenroldatabasedesc', 'local_ucla'),
            0));
    // CCLE-2924 - Prevent blind updating of users, give a time-out before
    // registrar information trumps shibboleth information.
    $temp->add(new admin_setting_configtext('local_ucla/minuserupdatewaitdays',
            get_string('minuserupdatewaitdays', 'local_ucla'),
            get_string('minuserupdatewaitdays_desc', 'local_ucla'), 30));
}

// CCLE-3970 - Install and evaluate LSU's Gradebook Improvements
// Inject new gradebook settings for repeat headers and non-override category totals.
if ($ADMIN->fulltree) {
    $grade = $ADMIN->locate('gradecategorysettings');
    if (!empty($grade)) {
        $grade->add(new admin_setting_configcheckbox('grade_overridecat',
                get_string('overridecat', 'local_ucla'),
                get_string('overridecat_help', 'local_ucla'), 1));
    }
}
