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
class ucla_syllabus_updated_task extends \core\task\adhoc_task {

    /**
     * Executes the task.
     *
     * @throws Exception on error
     */
    public function execute() {

        $customdata = parent::get_custom_data();
        $instanceid = $customdata->objectid;

        if ($syllabus = \ucla_syllabus_manager::instance($instanceid)) {
            global $DB;

            /* Outgoing syllabus logic:
             *
             *  If public syllabus added/updated and no private syllabus exist,
             *      send public.
             *  If public syllabus added/updated and private syllabus exist,
             *      do not send public (do not send anything).
             *  If private syllabus added/updated and public syllabus exists/does not exist,
             *      send private.
             */

            $hostcourse = $DB->get_record('course', array('id' => $syllabus->courseid));

            // Given that events can be held up in the queue, the course associated
            // with the sillabus might have been deleted by the time it's our turn...
            if (empty($hostcourse)) {
                // Don't send anything.. dequeue.
                return true;
            }

            if ($syllabus instanceof \ucla_private_syllabus) {
                // Private syllabus added, we'll send it.
                $outgoing = $syllabus;
            } else {
                // We got a public syllabus.

                // Get all the syllabi.
                $manager = new \ucla_syllabus_manager($hostcourse);
                $syllabi = $manager->get_syllabi();

                // Check if private syllabus exists.
                foreach ($syllabi as $si) {
                    if ($si instanceof \ucla_private_syllabus) {
                        // If it does, send nothing.
                        return true;
                    }
                }

                // Public syllabus added, and private syllabus does not exist.
                $outgoing = $syllabus;
            }

            // Check that file still exists, this may happen when user deletes
            // syllabus before cron runs.
            if (empty($outgoing->stored_file)) {
                return true;
            }

            $courses = ucla_get_course_info($hostcourse->id);

            $result = true;

            foreach ($courses as $course) {
                // Prepare criteria and payload.
                list($criteria, $payload) = \syllabus_ws_manager::setup_transfer($outgoing, $course);
                if (empty($criteria) || empty($payload)) {
                    continue;
                }

                // Handle event.
                $result &= \syllabus_ws_manager::handle(\syllabus_ws_manager::ACTION_TRANSFER, $criteria, $payload);
            }
            if (!$result) {
                throw new \moodle_exception('err_syllabus_transfer', 'local_ucla_syllabus');
            }
        }
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasksyllabus_updated', 'local_ucla_syllabus');
    }
}
