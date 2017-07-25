<?php
// This file is part of UCLA local plugin for Moodle - http://moodle.org/
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
 * Contains the event class for when files are deleted.
 *
 * @package    local_ucla_syllabus
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla_syllabus\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Records the file hash before a file is deleted from the file system.
 *
 * @package    local_ucla_syllabus
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_updated extends syllabus_base {

    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'ucla_syllabus';
    }

    /**
     * Returns a short description for the event that includes the syllabus id
     * being updated.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' updated the syllabus with id '$this->objectid'";
    }

    /**
     * Add data to legacy log.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'syllabus updated',
            '../local/ucla_syllabus/index.php?id=' . $this->courseid, '');
    }

    /**
     * Returns the name of the legacy event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'ucla_syllabus_updated';
    }

    /**
     * Returns data for legacy events.
     *
     * @return object
     */
    protected function get_legacy_eventdata() {
        $event = new \stdClass();
        $event->id = $this->objectid;
        return $event;
    }
}
