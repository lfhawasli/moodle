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
 * Checks if a course should be visible and updates course visibility accordingly.
 *
 * @package    local_ucla
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Task class.
 *
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_visibility_task extends \core\task\scheduled_task {
    /**
     * Updates a course to be hidden/visible by comparing current time against
     * hidestartdate or hideenddate (if set). Also updates hideenddate or
     * hidestartdate to null if appropriate.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        // Get all courses that have visiblity not matching start/time times.
        $sql = "(
            SELECT c.*
              FROM {course} c
             WHERE visible=0
               AND hidestartdate!=0
               AND hidestartdate IS NOT NULL
               AND hidestartdate > UNIX_TIMESTAMP(NOW())
                )
            UNION DISTINCT
                (
            SELECT c.*
              FROM {course} c
             WHERE visible=0
               AND hideenddate <= UNIX_TIMESTAMP(NOW())
                )
            UNION DISTINCT
                (
            SELECT c.*
              FROM {course} c
             WHERE visible=1
               AND hidestartdate <= UNIX_TIMESTAMP(NOW())
                )
            UNION DISTINCT
                (
            SELECT c.*
              FROM {course} c
             WHERE visible=1
               AND hideenddate > UNIX_TIMESTAMP(NOW())
                )";
        $courses = $DB->get_records_sql($sql);

        if (!empty($courses)) {
            foreach ($courses as $course) {
                self::set_visiblity($course);
            }
        }
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('course_visibility_task', 'local_ucla');
    }


    /**
     * Checks if course needs to have its visiblity changed.
     *
     * @param object $course
     * @return object
     */
    public static function set_visiblity($course) {
        $visiblitychange = false;
        $oldvisible = $course->visible;
        if (!empty($course->hidestartdate)) {
            if ($course->visible && $course->hidestartdate <= time()) {
                // Course is visible, but start time has passed.
                $course->visible = 0;
            } else if (!$course->visible && $course->hidestartdate > time()) {
                // Course is hidden, but start time not has passed.                
                $course->visible = 1;
            }
        }
        // Separate if statement because might have start and end time set.
        if (!empty($course->hideenddate)) {
            if ($course->visible && $course->hideenddate > time()) {
                // Course is visible, but end time has not passed.
                // Make sure start time has passed or is not set.
                if (empty($course->hidestartdate) || $course->hidestartdate <=   time(0)) {
                    $course->visible = 0;
                }
            } else if (!$course->visible && $course->hideenddate <= time()) {
                // Course is hidden, but end time has passed.
                $course->visible = 1;
            }
        }
        
        // Update visiblity if it changed from the start.
        if ($oldvisible != $course->visible) {
            course_change_visibility($course->id, $course->visible);
        }

        return $course;
    }
}
