<?php
// This file is part of the local UCLA plugin for Moodle - http://moodle.org/
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
 * Core edit class for TurnItIn 2 module.
 *
 * @package    local_ucla
 * @copyright  2017 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @copyright  2017 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_turnitintwo {

    /**
     * Given a module or course context, returns records from turnitintooltwo
     * table.
     *
     * @param context $context
     * @return array
     */
    public static function get_turnitintwo_by_context(context $context) {
        global $DB;

        // What context is the user being given the capbility? Only care
        // about course and module contexts.
        if ($context->contextlevel == CONTEXT_MODULE) {
            // Check if module is a TurnItInTwo assignment.
            $sql = "SELECT tii.*
                      FROM {turnitintooltwo} tii
                      JOIN {course_modules} cm ON (cm.instance=tii.id)
                      JOIN {modules} m ON (m.id=cm.module)
                      JOIN {context} ct ON (ct.instanceid=cm.id)
                     WHERE m.name='turnitintooltwo'
                       AND ct.id=?";
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            // Get all TurnItInTwo assignments for course.
            $sql = "SELECT tii.*
                      FROM {turnitintooltwo} tii
                      JOIN {course_modules} cm ON (cm.instance=tii.id)
                      JOIN {modules} m ON (m.id=cm.module)
                      JOIN {context} ct ON (ct.instanceid=cm.course)
                     WHERE m.name='turnitintooltwo'
                       AND ct.id=?";
        } else {
            // We only support course or module context.
            return array();
        }
        return $DB->get_records_sql($sql, array($context->id));
    }

    /**
     * When a TurnItInTwo assignment is created or a role is added to the
     * course or module, synchronize the tutors listing.
     *
     * @param \core\event\base $event   Only expecting course_module_created,
     *                                  role_assigned, role_unassigned events.
     */
    public static function sync_assignments(\core\event\base $event) {
        // Make sure we should process this event.
        $context = $event->get_context();
        switch ($event->eventname) {
            case '\core\event\course_module_created':
                // Make sure module is TurnItInTwo assignment.
                if ($event->other['modulename'] != 'turnitintooltwo') {
                    return;
                }
                break;
            case '\core\event\role_assigned':
                // Make sure user being assigned can grade TurnitinTwo.
                if (!has_capability('mod/turnitintooltwo:grade',
                        $context, $event->relateduserid)) {
                    return;
                }
                break;
            case '\core\event\role_unassigned':
                // We cannot check if user used to be able to grade TurnItInTwo
                // so we need to sychronize all assignments.
                break;
            case '\core\event\course_restored':
                // Restored event does not tell us what modules got restored,
                // so we need to sychronize all assignments.
                break;
            default:
                // Ignore any other events.
                return;
        }

        // Get assignments by either course or module context.
        $tiiassignments = self::get_turnitintwo_by_context($context);

        // If any TurnItInTwo assignments found, synchronize Tutors.
        if (!empty($tiiassignments)) {
            foreach ($tiiassignments as $tiiassignment) {
                self::sync_tutors($tiiassignment);
            }
        }
    }

    /**
     * Adds/remove Tutor roles from given TurnItInTwo assignment.
     *
     * @param object $tiiassignment
     * @return boolean
     */
    public static function sync_tutors($tiiassignment) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/turnitintooltwo/turnitintooltwo_assignment.class.php');
        require_once($CFG->dirroot . '/mod/turnitintooltwo/turnitintooltwo_user.class.php');
        
        $tiiassign = new turnitintooltwo_assignment($tiiassignment->id, $tiiassignment);
        $cm = get_coursemodule_from_instance('turnitintooltwo', $tiiassignment->id);

        $members = $tiiassign->get_tii_users_by_role('Instructor', 'mdl');
        $course = $tiiassign->get_course_data($tiiassign->turnitintooltwo->course);
        // Get current Tutors.
        $moodleusers = get_users_by_capability(context_module::instance($cm->id),
                'mod/turnitintooltwo:grade', 'u.id', '', '', '',
                groups_get_activity_group($cm));

        // Register new user with TII with new membership.
        foreach ($moodleusers as $userid => $moodleuser) {
            if (!array_key_exists($userid, $members)) {
                $user = new turnitintooltwo_user($userid, 'Instructor');
                $user->join_user_to_class($course->turnitin_cid);
            }
            unset($members[$userid]);
        }
        // User whose role is dropped, remove membership from class.
        foreach ($members as $userid => $member) {
            $user = new turnitintooltwo_user($userid, 'Instructor');
            turnitintooltwo_user::remove_user_from_class($member['membership_id']);
        }

        return true;
    }
}
