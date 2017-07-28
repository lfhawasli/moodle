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
class local_ucla_syllabus_webservice_observer {

    /**
     * Event handler for course alert.
     *
     * This handles crosslisted courses by sending multiple alerts
     * in those cases.
     *
     * @param   \core\event\course_created $event event object
     */
    public static function ucla_course_alert(\core\event\course_created $event) {

        $task = new \local_ucla_syllabus\task\ucla_course_alert_task();
        // $task->set_blocking(true)

        $task->set_custom_data(
            array(
                'courseid' => $event->courseid
            )
        );
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Handle deletion of syllabus.
     *
     * @param   \local_ucla_syllabus\event\syllabus_deleted $event event object
     */
    public static function ucla_syllabus_deleted(\local_ucla_syllabus\event\syllabus_deleted $event) {
        $task = new \local_ucla_syllabus\task\ucla_syllabus_deleted_task();
        $task->set_custom_data(
            array(
                'courseid' => $event->courseid,
                'access_type' => $event->other['access_type']
            )
        );
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Handle syllabus add/update.
     *
     * @param   \local_ucla_syllabus\event\syllabus_base $event event object
     */
    public static function ucla_syllabus_updated(\local_ucla_syllabus\event\syllabus_base $event) {

        $task = new \local_ucla_syllabus\task\ucla_syllabus_updated_task();
        // $task->set_blocking(true)

        $task->set_custom_data(
            array(
                'objectid' => $event->objectid
            )
        );
        \core\task\manager::queue_adhoc_task($task);
    }
}
