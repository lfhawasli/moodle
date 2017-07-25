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
 * @package    local_ucla_syllabus
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Handling the following events.
require_once($CFG->dirroot . '/local/ucla_syllabus/webservice/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/alert_form.php');

/**
 * Syllabus event handler class file.
 *
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_syllabus_observer {

    /**
     * Delete a course's syllabus when a course is deleted.
     *
     * NOTE: Unfortunately cannot use ucla_syllabus_manager to delete syllabus
     * entry and files, because course context is already deleted. Need to manually
     * find the syllabus entries and delete associated files.
     *
     * @param \core\event\course_deleted $event
     */
    public static function delete_syllabi(\core\event\course_deleted $event) {
        global $DB;

        // Get all syllabus entries for course.
        $syllabi = $DB->get_records('ucla_syllabus',
                array('courseid' => $event->courseid));

        if (empty($syllabi)) {
            return true;
        }

        $fs = get_file_storage();

        foreach ($syllabi as $syllabus) {
            // Delete any files associated with syllabus entry.
            $files = $fs->get_area_files($event->contextinstanceid,
                    'local_ucla_syllabus', 'syllabus', $syllabus->id, '', false);
            if (!empty($files)) {
                foreach ($files as $file) {
                    $file->delete();
                }
            }

            // Next, delete entry in syllabus table.
            $DB->delete_records('ucla_syllabus', array('id' => $syllabus->id));

            // Trigger any necessary events.
            $deletedevent = \local_ucla_syllabus\event\syllabus_deleted::create(
                array(
                    'objectid' => $syllabus->id,
                    'other' => array(
                            'courseid' => $event->courseid,
                            'access_type' => $syllabus->access_type
                        ),
                    'context' => \context_system::instance()
                )
            );
            $deletedevent->trigger();
        }
    }
}
