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

$course = get_course(required_param('courseid', PARAM_INT));
$url = new moodle_url('/blocks/ucla_library_reserves/course_reserves.php', array('courseid' => $course->id));
$context = context_course::instance($course->id, MUST_EXIST);
require_login($course);

init_pagex($course, $context, $url, BLOCK_UCLA_LIBRARY_RESERVES_LIB_RESERVES);

echo $OUTPUT->header();
print_library_tabs(get_string('coursereserves', 'block_ucla_library_reserves'), $course->id);

// TODO: Get the links.
// $reserves = $DB->get_records('ucla_library_reserves', array('courseid' => $course->id));
// foreach ($reserves as $reserve) {
//     echo $reserve->url;
// }

echo $OUTPUT->footer();

// Log student view.
\block_ucla_library_reserves\event\coursereserves_index_viewed::create(array('context' => $context))->trigger();