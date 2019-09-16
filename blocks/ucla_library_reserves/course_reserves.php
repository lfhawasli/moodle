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
 * Course Reserves.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/blocks/ucla_library_reserves/locallib.php');
require_login();

$course = get_course(required_param('courseid', PARAM_INT));
$url = new moodle_url('/blocks/ucla_library_reserves/course_reserves.php', array('courseid' => $course->id));
$context = context_course::instance($course->id, MUST_EXIST);

init_pagex($course, $context, $url, BLOCK_UCLA_LIBRARY_RESERVES_LIB_RESERVES);

echo $OUTPUT->header();
print_library_tabs(get_string('coursereserves', 'block_ucla_library_reserves'), $course->id);
$hostcourseurl = get_hostcourseurl($course->id);
if (can_request_course_reserves($course->id)) {
    if ($hostcourseurl) {
        get_iframe($hostcourseurl);
    } else {
        $nocourseres = html_writer::start_tag('div', array('class' => 'alert alert-danger'));
        $nocourseres .= html_writer::div(get_string('courseresnotavailable', 'block_ucla_library_reserves'));
        $nocourseres .= html_writer::end_tag('div');
        echo $nocourseres;
    }
    // Show request link no matter what as long as you have permission to view it.
    echo html_writer::empty_tag('br');
    echo html_writer::link('https://www.library.ucla.edu/use/borrow-renew-return/course-reserves/information-instructors',
        get_string('lcrequest', 'block_ucla_library_reserves'),
        array('class' => 'btn btn-primary', 'role' => 'button'));
    // Show feedback link.
    echo html_writer::empty_tag('br'); // First <br> is of size 0x0 because of the above button.
    echo html_writer::empty_tag('br');
    echo html_writer::link('http://ucla.libsurveys.com/rg-feedback',
        get_string('libraryfeedback', 'block_ucla_library_reserves'),
        array('class' => 'btn btn-secondary', 'role' => 'button'));
} else {
    if ($hostcourseurl) {
        get_iframe($hostcourseurl);
    } else {
        $nopermission = html_writer::start_tag('div', array('class' => 'alert alert-danger'));
        $nopermission .= html_writer::div(get_string('nopermissionmsg', 'block_ucla_library_reserves'));
        $nopermission .= html_writer::end_tag('div');
        echo $nopermission;
    }
}

echo $OUTPUT->footer();

// Log student view.
\block_ucla_library_reserves\event\coursereserves_index_viewed::create(array('context' => $context))->trigger();