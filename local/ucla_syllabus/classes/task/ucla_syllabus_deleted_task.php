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
 * UCLA local plugin cron task runner.
 *
 * @package    local_ucla_syllabus
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla_syllabus\task;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/ucla/lib.php');

// Handling the following events.
require_once($CFG->dirroot . '/local/ucla_syllabus/webservice/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/alert_form.php');

/**
 * Task class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_syllabus_deleted_task extends \core\task\adhoc_task {

    /**
     * Executes the task.
     *
     * @throws Exception on error
     */
    public function execute() {

        global $DB;

        $customdata = parent::get_custom_data();

        $hostcourse = $DB->get_record('course', array('id' => $customdata->courseid));

        // The course may not exist if the event is delayed...
        if (empty($hostcourse)) {
            // Don't send anything and dequeue.
            return true;
        }

        $courses = ucla_get_course_info($hostcourse->id);

        // Get all the syllabi.
        $manager = new \ucla_syllabus_manager($hostcourse);
        $syllabi = $manager->get_syllabi();

        $result = true;
        foreach ($courses as $course) {
            switch(intval($customdata->access_type)) {
                case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:

                    // Case where syllabus is private:
                    // If no public syllabus exists, POST delete.
                    // If public syllabus exists, POST public syllabus.
                    if (empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC])) {
                        list($criteria, $payload) = \syllabus_ws_manager::setup_delete($course);
                        \syllabus_ws_manager::handle(\syllabus_ws_manager::ACTION_TRANSFER, $criteria, $payload);
                    } else {
                        $publicsyllabus = array_shift($syllabi);

                        // Pass it on to another handler...
                        // Execute ucla_syllabus_updated task right away.

                        $task = new \local_ucla_syllabus\task\ucla_syllabus_updated_task();
                        $task->set_custom_data(
                            array(
                                'objectid' => $publicsyllabus->id
                            )
                        );
                        $task->execute();

                        // We want to break out of the loop as well...
                        // The ucla_syllabus_updated() function already checks if course is crosslisted.
                        break 2;
                    }

                    break;
                case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:

                    // Case where syllabus is public:
                    // If no private syllabus exists, POST delete.
                    // Else do nothing.
                    if (empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])) {
                        list($criteria, $payload) = \syllabus_ws_manager::setup_delete($course);
                        $result &= \syllabus_ws_manager::handle(\syllabus_ws_manager::ACTION_TRANSFER, $criteria, $payload);
                    }
                    // Else do nothing.
                    break;
            }
        }
        if (!$result) {
            throw new \moodle_exception('err_syllabus_delete', 'local_ucla_syllabus');
        }
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasksyllabus_deleted', 'local_ucla_syllabus');
    }
}
