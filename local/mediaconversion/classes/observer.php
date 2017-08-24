<?php
// This file is part of Moodle - http://moodle.org/
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
 * The event handlers for this plugin.
 *
 * @package    local_mediaconversion
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Observers for this plugin go here.
 *
 * @package    local_mediaconversion
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_mediaconversion_observer {
    /**
     * This is called whenever a course module is added. It dispatches the convert
     * media task to convert video files to kaltura video resources.
     *
     * @param \core\event\course_module_created $event
     */
    public static function mediaconversion_convert(\core\event\course_module_created $event) {
        global $CFG, $USER;
        $data = $event->get_data();
        // We only want to deal with resources.
        if ($data['other']['modulename'] !== 'resource') {
            return;
        }
        $task = new \local_mediaconversion\task\mediaconversion_convert_task();
        $task->set_custom_data(
            array(
                'eventdata' => $data,
                'userid' => $USER->id
            )
        );
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * This is called whenever a course is restored.
     *
     * @param \core\event\course_restored $event
     */
    public static function mediaconversion_convert_restored(\core\event\course_restored $event) {
        global $USER;
        $data = $event->get_data();
        $task = new \local_mediaconversion\task\mediaconversion_convert_restored_task();
        $task->set_custom_data(
            array(
                'courseid' => $data['courseid'],
                'userid' => $USER->id
            )
        );
        \core\task\manager::queue_adhoc_task($task);
    }
}