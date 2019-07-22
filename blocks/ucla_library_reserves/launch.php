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
 * This file contains code to view library reserves lti activity instance.
 *
 * @package    block_ucla_library_reserves
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/lti/locallib.php');
require_once($CFG->dirroot.'/blocks/ucla_library_reserves/locallib.php');

$id = required_param('id', PARAM_INT); // Course ID.
$shortname = optional_param('shortname', '', PARAM_TEXT);
$placement = 'activity';
$lti = construct_lti_config();
$lti->course = $id;
$course = get_course($id);
require_login($course);
if ($shortname != '') {
    $course->shortname = $shortname;
}
$PAGE->set_course($course);
lti_launch_tool($lti, $placement);

