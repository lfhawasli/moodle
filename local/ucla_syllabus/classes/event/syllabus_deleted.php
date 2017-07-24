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
 * Contains the event class for when a syllabus is deleted.
 *
 * @package    local_ucla_syllabus
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla_syllabus\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Records when a syllabus is deleted.
 *
 * @package    local_ucla_syllabus
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_deleted extends syllabus_base {

    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'ucla_syllabus';
    }

    /**
     * Returns a short description for the event that includes the syllabus id
     * being deleted.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' deleted the syllabus with id '$this->objectid'";
    }

    /**
     * Returns URL to the course page.
     *
     * @return moodle_url
     */
    public function get_url() {
        // Course might be deleted, so obtain courseid from other data.
        return new \moodle_url('/local/ucla_syllabus/index.php',
                array('id' => $this->other['courseid']));
    }

    /**
     * Add data to legacy log.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'syllabus delete',
            '../local/ucla_syllabus/index.php?id=' . $this->other['courseid'], '');
    }

    /**
     * Returns the name of the legacy event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'ucla_syllabus_deleted';
    }

    /**
     * Returns data for legacy events.
     *
     * @return object
     */
    protected function get_legacy_eventdata() {
        $event = new \stdClass();
        $event->courseid = $this->other['courseid'];
        $event->access_type = $this->other['access_type'];
        return $event;
    }
}
