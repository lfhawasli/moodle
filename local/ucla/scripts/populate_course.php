<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Script to automatically populate a course for the given term with users with the roles:
 *
 *      * editinginstructor
 *      * ta_instructor
 *
 * Usage: php populate_course.php <courseid> <term>
 *
 * Users that are enrolled are enrolled with a default role: participant.
 *
 * See CCLE-3532
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_ucla
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

// SET ROLE FOR ENROLLED USERS.
$role = 'participant';

// Needs two arguments, courseid and term. Unenroll is optional.
if ($argc != 3 && $argc != 4) {
    exit ('Usage: populate_course.php <courseid> <term> <unenroll|optional>' . "\n");
}

$courseid = (int) $argv[1];
$term = $argv[2];
$dounenroll = false;
if (isset($argv[3])) {
    $dounenroll = true;
}

// Validate arguments.
if (!ucla_validator('term', $term)) {
    exit ('The term parameter is incorrectly formatted.' . "\n");
}
if (!is_int($courseid) || $courseid == 0 || $courseid == $SITE->id) {
    exit ('The courseid parameter is incorrectly formatted.' . "\n");
}
$course = get_course($courseid);  // Verify course exists.
echo "Processing " . $course->shortname . "\n";

// Check if course has "self-enrollment" plugin enabled.
$selfenrol = local_ucla_copyright_enrollment::get_self_enrol($courseid);

$roleid = local_ucla_copyright_enrollment::get_roleid($role);

if ($dounenroll) {
    $usersremoved = local_ucla_copyright_enrollment::unenroll_all($courseid, $roleid, $selfenrol);
    echo($usersremoved . ' users were removed.' . "\n");
}

$usersadded = local_ucla_copyright_enrollment::enroll_all($term, $courseid, $roleid, $selfenrol);
if ($usersadded == 1) {
    echo($usersadded . ' user was added.' . "\n");
} else {
    echo($usersadded . ' users were added.' . "\n");
}
echo "DONE!\n";
