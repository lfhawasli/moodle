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
 * Research Guide.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->dirroot.'/blocks/ucla_library_reserves/locallib.php');
require_login(null, true, null, true, false);

$courseshortname = optional_param('courseshortname', '', PARAM_TEXT);
$course = get_course(required_param('courseid', PARAM_INT));
if ($courseshortname == '') {
    $courseshortname = $course->shortname;
}
$url = new moodle_url('/blocks/ucla_library_reserves/index.php', array('courseid' => $course->id));
$context = context_course::instance($course->id, MUST_EXIST);

init_pagex($course, $context, $url, BLOCK_UCLA_LIBRARY_RESERVES_LIB_GUIDE);

echo $OUTPUT->header();

$cache = cache::make('block_ucla_library_reserves', 'hostcourseurl');
$hostcourseurl = get_hostcourseurl($course->id);
$printlibtour = (strpos($course->shortname,'CLUSTER') !== false);
// Don't print the reserves tab if you're a student and there are no reserves.
if (!can_request_course_reserves($course->id) && !$hostcourseurl) {
    print_library_tabs(get_string('researchguide', 'block_ucla_library_reserves'), $course->id, $printlibtour, false);
} else {
    print_library_tabs(get_string('researchguide', 'block_ucla_library_reserves'), $course->id, $printlibtour);
}

$PAGE->set_pagelayout('incourse');

show_research_guide($course->id, $courseshortname);

if (can_request_course_reserves($course->id)) {
    // Show feedback link.
    echo html_writer::empty_tag('br');
    echo html_writer::link('http://ucla.libsurveys.com/rg-feedback',
        get_string('libraryfeedback', 'block_ucla_library_reserves'),
        array('class' => 'btn btn-secondary', 'role' => 'button'));
}

echo $OUTPUT->footer();

// Log student view.
\block_ucla_library_reserves\event\researchguide_index_viewed::create(array('context' => $context))->trigger();
