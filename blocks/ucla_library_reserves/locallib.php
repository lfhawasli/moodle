<?php
// This file is part of the UCLA Library Reserves block for Moodle - http://moodle.org/
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
 * Local library file.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
define('BLOCK_UCLA_LIBRARY_RESERVES_LIB_GUIDE', 1);
define('BLOCK_UCLA_LIBRARY_RESERVES_LIB_RESERVES', 2);

function init_pagex($course, $context, $url, $mode = null, $title = null) {
    global $PAGE;

    $PAGE->set_pagetype('course-view-' . $course->format);
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('base');
    $PAGE->set_title($course->shortname . ': ' . get_string('pluginname', 'block_ucla_library_reserves'));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_url($url);

    // Reset breadcrumbs and make it start with course and Library reserves.
    $PAGE->navbar->ignore_active();
    $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php' ,
            array('id' => $course->id)));
    $PAGE->navbar->add(get_string('title', 'block_ucla_library_reserves'));

    if (!empty($mode)) {
        $index = '';
        $indextitle = '';
        if ($mode == BLOCK_UCLA_LIBRARY_RESERVES_LIB_GUIDE) {
            $index = '/blocks/ucla_library_reserves/index.php';
            $indextitle = get_string('researchguide', 'block_ucla_library_reserves');
        } else if ($mode == BLOCK_UCLA_LIBRARY_RESERVES_LIB_RESERVES) {
            $index = '/blocks/ucla_library_reserves/course_reserves.php';
            $indextitle = get_string('coursereserves', 'block_ucla_library_reserves');
        }
        $indexurl = new moodle_url($index, array('courseid' => $course->id));
        $PAGE->navbar->add($indextitle, $indexurl);
    }

    if (!empty($title)) {
        $PAGE->navbar->add($title, new moodle_url($url));
    }
}

function print_library_tabs($activetab, $courseid) {
    $tabs = [];
    $tabs[] = new tabobject(get_string('researchguide', 'block_ucla_library_reserves'),
        new moodle_url('/blocks/ucla_library_reserves/index.php',
            array('courseid' => $courseid)),
            get_string('researchguide', 'block_ucla_library_reserves'));

    $tabs[] = new tabobject(get_string('coursereserves', 'block_ucla_library_reserves'),
    new moodle_url('/blocks/ucla_library_reserves/course_reserves.php',
        array('courseid' => $courseid)),
        get_string('coursereserves', 'block_ucla_library_reserves'));

    print_tabs(array($tabs), $activetab);
}

function construct_lti_config() {
    $toolconfig = new stdClass();
    // Add config info.
    $toolconfig->name = get_config('block_ucla_library_reserves', 'lti_tool_name');
    $toolconfig->toolurl = get_config('block_ucla_library_reserves', 'lti_tool_url');
    $toolconfig->resourcekey = get_config('block_ucla_library_reserves', 'consumer_key');
    $toolconfig->password = get_config('block_ucla_library_reserves', 'lti_sharedsecret');
    // Default info.
    $toolconfig->instructorchoicesendname = 0; // Do not send name.
    $toolconfig->instructorchoicesendemailaddr = 0; // Do not send email address.
    $toolconfig->instructorchoiceacceptgrades = 0; // Do not accept grade from the LTI tool.
    $toolconfig->instructorcustomparameters = ''; // No custom parameters.
    $toolconfig->instructorchoiceallowroster = null;
    $toolconfig->securetoolurl = '';
    $toolconfig->debuglaunch = 0; // No debuglaunch.
    $toolconfig->id = 0; // Property $toolconfig->id is an optional param in mod/lti/return.php, default to 0.
    return $toolconfig;
}

/**
 * Checks if user can access course reserves.
 *
 * @param int $courseid
 * @return bool
 */
function can_request_course_reserves(int $courseid) {
    $caneditcourse = has_capability('format/ucla:viewadminpanel',
        context_course::instance($courseid, MUST_EXIST));
    return $caneditcourse;
}
