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

// SET ROLE FOR ENROLLED USERS.
$role = 'participant';

// Needs two arguments, courseid and term.
if ($argc != 3) {
    exit ('Usage: populate_course.php <courseid> <term>' . "\n");
}

$courseid = $argv[1];
$courseid = (int) $courseid;
$term = $argv[2];

// Validate arguments.
if (!ucla_validator('term', $term)) {
    exit ('The term parameter is incorrectly formatted.' . "\n");
}
if (!is_int($courseid) || $courseid == 0 || $courseid == $SITE->id) {
    exit ('The courseid parameter is incorrectly formatted.' . "\n");
}

// Check if course has "self-enrollment" plugin enabled.
$selfenrol = enrol_selfenrol_available($courseid);

if ($selfenrol == false) {
    exit ('Self-enrollment is not enabled.' . "\n");
}

// Get 'self' enrollment instance for function 'enrol_user'.
$enrolinstances = enrol_get_instances($courseid, true);

foreach ($enrolinstances as $enrolinstance) {
    if ($enrolinstance->enrol === 'self') {
        break;
    }
}

// Get enrollment plugin.
$enrolplugin = enrol_get_plugin('self');

// Get roleid from mdl_role.id, given $role.
$roleid = $DB->get_record('role', array('shortname' => $role), 'id');
if (empty($roleid)) {
    exit ('Unable to find role to enroll users.' . "\n");
}
$roleid = $roleid->id;

// Find roleid's for roles with instructor privileges.
$ideditinginstructor = $DB->get_record('role', array('shortname' => 'editinginstructor'), 'id');
$idtainstructor = $DB->get_record('role', array('shortname' => 'ta_instructor'), 'id');
$ideditinginstructor = $ideditinginstructor->id;
$idtainstructor = $idtainstructor->id;

// Find the users with instructor priveledges in course.
$sqlfindusers = "SELECT DISTINCT mdl_role_assignments.userid
                   FROM {role_assignments} ra
                   JOIN {context} cxt ON ra.contextid = cxt.id
                   JOIN {ucla_request_classes} urc ON cxt.instanceid = urc.courseid
                  WHERE ra.roleid IN (:ideditinginstructor, :idtainstructor)
                    AND urc.term = :term
                    AND cxt.contextlevel = 50";

$params = array('ideditinginstructor' => $ideditinginstructor,
    'idtainstructor' => $idtainstructor,
    'term' => $term);

$coursecontext = context_course::instance($courseid);

$a = $DB->get_recordset_sql($sqlfindusers, $params);

$usersadded = 0;
if ($a->valid()) {
    foreach ($a as $userid) {
        // For each user, add to course using "self-enrollment" plugin.
        $userid = $userid->userid;

        // If user is already in course, then don't enrol.
        if (!is_enrolled($coursecontext, $userid, '', true)) {
            $enrolplugin->enrol_user($enrolinstance, $userid, $roleid);
            $usersadded++;
        }
    }
}

$a->close();

if ($usersadded == 1) {
    echo($usersadded . ' user was added.' . "\n");
} else {
    echo($usersadded . ' users were added.' . "\n");
}
