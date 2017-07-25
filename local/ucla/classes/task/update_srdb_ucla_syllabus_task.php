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
 * Updates the syllabus entry at SRDB.
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
class update_srdb_ucla_syllabus_task extends \core\task\adhoc_task {

    /**
     * Executes the task.
     *
     * @throws Exception on error
     */
    public function execute() {
        global $DB;

        $customdata = parent::get_custom_data();
        $courseid = $customdata->courseid;
        if (empty($courseid)) {
            // Syllabus must have been deleted after it was added, so do not process
            // entry anymore.
            return true;
        }

        // If course is deleted, clearing of syllabi links is done through
        // ucla_course_deleted event handler.
        if (!$DB->record_exists('course', array('id' => $courseid))) {
            // Don't send anything and dequeue.
            return true;
        }

        // If course is a collaboration site, then  don't process syllabus.
        if (is_collab_site($courseid)) {
            return true;
        }

        // Get all syllabi for course and then send links.
        $regsender = new \local_ucla_regsender();

        return $regsender->push_course_links($courseid);
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('update_srdb_ucla_syllabus_task', 'local_ucla');
    }
}
