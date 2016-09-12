<?php
// This file is part of the UCLA Site Invitation Plugin for Moodle - http://moodle.org/
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
 * Event handlers.
 *
 * @package    enrol_invitation
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package    enrol_invitation
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_invitation_observer {
    /**
     * Automatically add enrollment plugin if "enrol_invitation|status" is enabled
     * by default.
     *
     * @param \core\event\base $event
     * @return boolean  Returns false on error, otherwise true.
     */
    public static function add_site_invitation_plugin(\core\event\base $event) {
        // Only respond to course restores.
        if ($event instanceof \core\event\course_restored) {
            if ($event->other['type'] != backup::TYPE_1COURSE) {
                return true;
            }
        }

        // Make sure you aren't trying something silly like adding enrollment 
        // plugin to siteid.
        if ($event->courseid == SITEID) {
            return false;
        }

        // This hopefully means that this plugin IS enabled.
        $invitation = enrol_get_plugin('invitation');
        if (empty($invitation)) {
            debugging('Site invitation enrolment plugin is not installed');
            return false;
        }

        // Check if we need to add this plugin by default.
        $enable = get_config('enrol_invitation', 'status');
        if ($enable == ENROL_INSTANCE_DISABLED) {
            return false;
        }

        // Get course object.
        $course = get_course($event->courseid);

        // Returns instance id, else returns null.
        $instanceid = $invitation->add_instance($course);
        if (is_null($instanceid)) {
            debugging('Cannot add site invitation for course: ' .
                    $course['shortname']." ".$course['fullname']);
            return false;
        }

        return true;
    }
}
