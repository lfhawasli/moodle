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
 * UCLA Manage copyright status
 *
 * @package    ucla
 * @subpackage ucla_copyright_status
 * @copyright  2012 UC Regents
 * @author     Jun Wan <jwan@humnet.ucla.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $DB;

require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/lib.php');

$courseid = required_param('courseid', PARAM_INT); // Course ID.
$action = optional_param('action_edit', null, PARAM_TEXT);
$filter = optional_param('filter_copyright', null, PARAM_TEXT);
$cancelled = optional_param('action_cancel', null, PARAM_TEXT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}
require_login($course);

$context = context_course::instance($courseid);
if (!has_capability('moodle/course:manageactivities', $context)) {
    print_error('permission_not_allow', 'block_ucla_copyright_status');
}

if (isset($cancelled)) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
}

// Since editing button depends on the data, we update the data before it is called.
if (isset($action)) {
    $data = data_submitted();
    update_copyright_status($data);
}

init_copyright_page($course, $courseid, $context);
setup_js_tablesorter('copyright_status_table', array('textExtraction:uclaCopyrightTextExtraction'));
$PAGE->requires->yui_module('moodle-core-formchangechecker', 'M.core_formchangechecker.init', array(array(
        'formid' => 'block_ucla_copyright_status_form_copyright_status_list')));
$PAGE->requires->string_for_js('changesmadereallygoaway', 'moodle');
// Start output screen.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_ucla_copyright_status'),
        2, 'headingblock');

// If javascript disabled.
echo html_writer::tag('noscript',
        get_string('javascriptdisabled', 'block_ucla_copyright_status'),
        array('id' => 'block-ucla-copyright-status-noscript'));

if (isset($action)) {
    echo $OUTPUT->notification(get_string('changessaved', 'block_ucla_copyright_status'), 'notifysuccess');

    $event = \block_ucla_copyright_status\event\copyright_status_updated::create(array(
        'context' => $context
    ));
    $event->trigger();
}

$filter = optional_param('filter_copyright', $CFG->sitedefaultlicense,
        PARAM_TEXT);
display_copyright_status_contents($courseid, $filter);