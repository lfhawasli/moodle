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
class ucla_course_alert_task extends \core\task\adhoc_task {

    /**
     * Executes the task.
     *
     * @throws Exception on error
     */
    public function execute() {

        $customdata = parent::get_custom_data();
        $courseid = $customdata->courseid;
        
        if (!is_collab_site($courseid)) {

            // If a course is crosslisted, we want to send multiple alerts.
            $courses = ucla_get_course_info($courseid);

            $result = true;
            // Do for all coures found.
            foreach ($courses as $course) {
                // Prepare criteria & payload.
                list($criteria, $payload) = \syllabus_ws_manager::setup_alert($course);
                // Handle event.
                $result &= \syllabus_ws_manager::handle(\syllabus_ws_manager::ACTION_ALERT, $criteria, $payload);
            }
            if (!$result) {
                throw new \moodle_exception('err_course_alert', 'local_ucla_syllabus');
            }
        }
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcourse_alert', 'local_ucla_syllabus');
    }
}
