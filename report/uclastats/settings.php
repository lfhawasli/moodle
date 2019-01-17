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
 * Settings file to add link to admin menu.
 *
 * @package    report_uclastats
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Add link to admin report.
$ADMIN->add('reports', new admin_externalpage('uclastats', 
        get_string('pluginname', 'report_uclastats'),
        "$CFG->wwwroot/report/uclastats/index.php", 'report/uclastats:view'));

// Automate stats reporting every new term.
$settings->add(new admin_setting_configcheckbox('report_uclastats/enable',
        get_string('enable', 'report_uclastats'),
        get_string('enable_desc', 'report_uclastats'), 1));

// Who to email when report/uclastats/cli/run_reports.php or automated report is run.
$settings->add(new admin_setting_configtext('report_uclastats/notifylist',
        get_string('notifylist', 'report_uclastats'),
        get_string('notifylist_desc', 'report_uclastats'), '', PARAM_TEXT));
