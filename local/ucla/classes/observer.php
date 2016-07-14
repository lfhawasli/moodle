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
 * Event handler class.
 *
 * @package    local_ucla
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event handler class file.
 *
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_observer {

    /**
     * After courses are built, run prepop on them.
     *
     * @param \tool_uclacoursecreator\event\course_creator_finished $event
     * @return boolean
     */
    public static function ucla_sync_built_courses(\tool_uclacoursecreator\event\course_creator_finished $event) {
        $edata = json_decode($event->other);

        // Don't run during unit tests. Can be triggered via
        // course_creator_finished event.
        if (defined('PHPUNIT_TEST') and PHPUNIT_TEST) {
            return true;
        }

        // This hopefully means that this plugin IS enabled.
        $enrol = enrol_get_plugin('database');
        if (empty($enrol)) {
            debugging('Database enrolment plugin is not installed');
            return false;
        }
        
        $trace = null;
        if (debugging()) {
            $trace = new text_progress_trace();
        } else {
            $trace = new null_progress_trace();
        }
        $courseids = array();
        foreach ($edata->completed_requests as $request) {
            if (empty($request->courseid)) {
                continue;
            }
            $courseids[] = $request->courseid;
        }

        foreach ($courseids as $courseid) {
            // This will handle auto-groups via events api.
            $enrol->sync_enrolments($trace, $courseid);
        }

        return true;
    }
}