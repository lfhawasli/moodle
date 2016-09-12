<?php
// This file is part of the UCLA group management plugin for Moodle - http://moodle.org/
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
 * Event handler class.
 *
 * @package    block_ucla_group_manager
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');

/**
 * Event handler class file.
 *
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_group_manager_observer {
    /**
     * Updates the groups of the given courses.
     *
     * Called when Registrar enrollment plugin runs.
     *
     * @param \local_ucla\event\sync_enrolments_finished $event
     * @return boolean
     */
    public static function ucla_group_manager_sync_course_event(\local_ucla\event\sync_enrolments_finished $event) {
        // Extract out what enrolments got updates.
        $courses = $event->get_courses();
        if (empty($courses)) {
            return true;
        }

        foreach ($courses as $courseid => $course) {
            $uclagroupmanager = new ucla_group_manager();
            $uclagroupmanager->sync_course($courseid);
        }
        return true;
    }
}
