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
 * Handles events when roles are (un)assigned and enrolment status updated.
 *
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_publicprivate;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');

/**
 * Observers class.
 *
 * @package    local_publicprivate
 * @copyright  2015 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observers {

    /**
     * Enables public/private for any newly built courses.
     *
     * @param \core\event\course_created $event
     */
    public static function course_created(\core\event\course_created $event) {
        global $CFG;
        require_once($CFG->dirroot . '/local/publicprivate/lib/site.class.php');

        if (\PublicPrivate_Site::is_enabled()) {
            require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
            $pubprivcourse = new \PublicPrivate_Course($event->courseid);
            if (!$pubprivcourse->is_activated()) {
                $pubprivcourse->activate();
            }
        }
    }

    /**
     * Enables/disables public/private depending on $course->enablepublicprivate.
     *
     * For courses with public/private enabled:
     *  - if hidden, then make sure guest enrollment plugin is disabled
     *  - if visible, then make sure guest enrollment plugin is enabled
     *
     * @param \core\event\course_updated $event
     */
    public static function course_updated(\core\event\course_updated $event) {
        global $CFG;

        require_once($CFG->dirroot . '/local/publicprivate/lib/site.class.php');
        if (!\PublicPrivate_Site::is_enabled()) {
            return;
        }

        require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
        $ppcourse = new \PublicPrivate_Course($event->courseid);
        $course = $ppcourse->get_course();
        $changehappened = false;

        // Activate public/private if form has enabled set or creating a course
        // not through the course edit form (i.e. collab site requestor)
        if ($course->enablepublicprivate == 1 && !$ppcourse->is_activated()) {
            $ppcourse->activate();
            $changehappened = true;
        } else if ($course->enablepublicprivate == 0 && $ppcourse->is_activated()) {
            $ppcourse->deactivate();
            $changehappened = true;
        }

        // If we enabled or disabled public/private, then guest enrollment 
        // plugin was properly set anyways.
        if (!$changehappened) {
            if ($ppcourse->is_activated()) {
                if ($course->visible == 0) {
                    // Course is hidden, need to disable guest enrollment plugin.
                    \PublicPrivate_Course::set_guest_plugin($course, ENROL_INSTANCE_DISABLED);
                } else {
                    // Course is visible, need to enable guest enrollment plugin.
                    \PublicPrivate_Course::set_guest_plugin($course, ENROL_INSTANCE_ENABLED);
                }
            }
        }
    }

    /**
     * For newly created course modules, make sure they are private by default.
     * @param \core\event\course_module_created $event
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        global $CFG;

        require_once($CFG->dirroot . '/local/publicprivate/lib/site.class.php');
        if (!\PublicPrivate_Site::is_enabled()) {
            return;
        }

        if (!\PublicPrivate_Course::build($event->courseid)->is_activated()) {
            return;
        }

        require_once($CFG->dirroot . '/local/publicprivate/lib/module.class.php');

        $ppmod = \PublicPrivate_Module::build($event->objectid);
        $ppmod->enable();
    }

    /**
     * Role assigned.
     *
     * Called by Events API when a new role is assigned. Add user to private public group.
     *
     * @param \core\event\role_assigned $event
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        $userid = $event->relateduserid;

        if ($event->contextlevel == CONTEXT_COURSE) {
            $pubprivcourse = \PublicPrivate_Course::build($event->contextinstanceid);
            if ($pubprivcourse->is_activated()) {
                $pubprivcourse->add_user($userid);
            }
        }
    }
    /**
     * Role unassigned.
     *
     * Called by Events API when an user is unassigned. If all roles of an user
     * are unassigned, remove this user from public/private group.
     *
     * @param \core\event\role_unassigned $event
     */
    public static function role_unassigned(\core\event\role_unassigned $event) {
        global $DB;

        $userid = $event->relateduserid;

        if ($event->contextlevel == CONTEXT_COURSE &&
            !$DB->record_exists('role_assignments', array('userid' => $userid,
                    'contextid' => $event->contextid))) {
            $ppcourse = \PublicPrivate_Course::build($event->contextinstanceid);
            if ($ppcourse->is_activated()) {
                $ppcourse->remove_user($userid);
            }
        }
    }

    /**
     * Section groups synced.
     *
     * Called by Events API when new section groups are created for a course.
     * Adds the new groups to the course's public/private grouping.
     *
     * @param \block_ucla_group_manager\event\section_groups_synced $event
     */
    public static function section_groups_synced(\block_ucla_group_manager\event\section_groups_synced $event) {
        global $CFG;
        $groupids = $event->other['groupids'];
        $course = get_course($event->courseid);
        $ppcourse = \PublicPrivate_Course::build($course);
        if ($ppcourse->is_activated()) {
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
     */
    public static function user_enrolment_updated(\core\event\user_enrolment_updated $event) {
        $ppcourse = new \PublicPrivate_Course($event->courseid);
        if ($ppcourse->is_activated()) {
            $ppcourse->check_enrolment($event->relateduserid);
        }
    }
}
