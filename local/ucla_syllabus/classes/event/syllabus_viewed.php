<?php
// This file is part of UCLA syllabus plugin for Moodle - http://moodle.org/
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
 * Contains the event class for when a syllabus is viewed.
 *
 * @package    local_ucla_syllabus
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla_syllabus\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Records when a syllabus is viewed.
 *
 * @package    local_ucla_syllabus
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_viewed extends \core\event\base {

    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'ucla_syllabus';
    }

    /**
     * Returns the name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsyllabusviewed', 'local_ucla_syllabus');
    }

    /**
     * Returns a short description for the event that includes the syllabus id
     * being viewed.
     *
     * NOTE: Must be non-localized string.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the syllabus with id '$this->objectid'";
    }

    /**
     * Returns URL to the syllabus page.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/ucla_syllabus/index.php',
                array('id' => $this->courseid));
    }

    /**
     * Add data to legacy log.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'syllabus view',
            '../local/ucla_syllabus/index.php?id=' . $this->courseid, '');
    }
}
