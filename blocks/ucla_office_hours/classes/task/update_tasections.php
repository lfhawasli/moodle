<?php
// This file is part of the UCLA office hours block for Moodle - http://moodle.org/
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
 * Task execution definition for tasections.
 *
 * @package    block_ucla_office_hours
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_office_hours\task;

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot .
        '/blocks/ucla_office_hours/block_ucla_office_hours.php');

/**
 * Task that will update ta sections.
 *
 * @package    block_ucla_office_hours
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_tasections extends \core\task\scheduled_task {

    /**
     * Task 'friendly' name.
     * 
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('task', 'block_ucla_office_hours');
    }

    /**
     * Action to execute.
     */
    public function execute() {
        $allactivecourses = ucla_get_courses_by_terms(get_active_terms());
        mtrace('Updating ' . count($allactivecourses) . ' courses');
        foreach ($allactivecourses as $courseid => $course) {
            \block_ucla_office_hours::update_ta_sections($courseid);
        }
    }

}