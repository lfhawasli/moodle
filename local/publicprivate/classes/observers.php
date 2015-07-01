<?php
// This file is part of the UCLA public/private plugin for Moodle - http://moodle.org/
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
 * Observers file.
 * 
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_publicprivate;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/publicprivate/lib.php');

class observers {

    /**
     * Section groups synced.
     * 
     * Called by Events API when new section groups are created for a course. Adds the new groups to
     * the course's public/private grouping.
     * 
     * @param \block_ucla_group_manager\event\section_groups_synced $event
     */
    public static function section_groups_synced(\block_ucla_group_manager\event\section_groups_synced $event) {
        global $CFG;
        require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
        $groupids = $event->other['groupids'];
        $course = get_course($event->courseid);
        if (\PublicPrivate_Course::is_publicprivate_capable($course)) {
            $ppcourse = \PublicPrivate_Course::build($course);
            if ($ppgroupingid = $ppcourse->get_grouping()) {
                require_once($CFG->dirroot . '/group/lib.php');
                foreach ($groupids as $groupid) {
                    groups_assign_grouping($ppgroupingid, $groupid);
                }
            }
        }
    }

    /**
     * Triggered via user_enrolment_updated event.
     *
     * @param \core\event\user_enrolment_updated $event
     * @global object $CFG
     */
    public static function user_enrolment_updated(\core\event\user_enrolment_updated $event) {
        global $CFG;
        require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
        $publicprivate = new \PublicPrivate_Course($event->courseid);
        $publicprivate->check_enrolments($event->relateduserid);
    }

    /**
     * Role assigned.
     * 
     * Called by Events API when a new role is assigned. Add user to private public group.
     * 
     * @param \core\event\role_assigned $event
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        global $CFG;

        $context = \context::instance_by_id($event->contextid);
        $userid = $event->relateduserid;

        if ($context->contextlevel == CONTEXT_COURSE) {
            require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
            $pubprivcourse = \PublicPrivate_Course::build($context->instanceid);

            if ($pubprivcourse->is_activated()) {
                $pubprivcourse->add_user($userid);
            }
        }
    }
    /**
     * Role unassigned.
     * 
     * Called by Events API when an user is unassigned. If all roles of an user are unassigned, 
     * remove this user from public/private group.
     * 
     * @param \core\event\role_unassigned $event
     */
    public static function role_unassigned(\core\event\role_unassigned $event) {
        global $CFG, $DB;

        $userid = $event->relateduserid;
        $contextid = $event->contextid;
        $context = \context::instance_by_id($contextid);

        if ($context->contextlevel == CONTEXT_COURSE &&
            !$DB->record_exists('role_assignments', array('userid' => $userid, 'contextid' => $contextid))) {

            require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');

            $pubprivcourse = \PublicPrivate_Course::build($context->instanceid);

            if ($pubprivcourse->is_activated()) {
                $pubprivcourse->remove_user($userid);
            }
        }
    }
}
