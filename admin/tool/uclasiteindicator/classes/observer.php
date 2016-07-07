<?php
// This file is part of the UCLA site indicator for Moodle - http://moodle.org/
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
 * @package    tool_uclasiteindicator
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasiteindicator/lib.php');

/**
 * When a course is deleted, also delete the site indicator entry.
 *
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_uclasiteindicator_observer {
    /**
     * Deletes associated site indicator for given course.
     *
     * @param \core\event\course_deleted $event
     */
    public static function delete_indicator(\core\event\course_deleted $event) {
        global $OUTPUT;
        if($indicator = siteindicator_site::load($event->courseid)) {
            $indicator->delete();
            echo $OUTPUT->notification(get_string('deleted').' - ' .
                    get_string('del_msg', 'tool_uclasiteindicator'), 'notifysuccess');
        }
    }
}
