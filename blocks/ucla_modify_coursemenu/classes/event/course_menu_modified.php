<?php
// This file is part of UCLA Modify Coursemenu plugin for Moodle - http://moodle.org/
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
 * Contains the event class for when coursemenus are modified.
 *
 * @package    block_ucla_modify_coursemenu
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_modify_coursemenu\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Records that the sections were modified for a course.
 *
 * @package    block_ucla_modify_coursemenu
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_menu_modified extends \core\event\base {

    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'u'; // Notation: [c]reate [r]ead [u]pdate [d]elete.
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns the name of the event.
     * @return string
     */
    public static function get_name() {
        return get_string('coursemenu_modified', 'block_ucla_modify_coursemenu');
    }

    /**
     * Returns a short description for the event.
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' modified the site menu of course with id '{$this->courseid}'.";
    }

    /**
     * Returns URL to the course page.
     * @return moodle_url
     */
    public function get_url() {
        return new \moodle_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php', array('courseid' => $this->courseid));
    }

    /**
     * Adds data to legacy log.
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'modify sections', $this->get_url(),
            $this->get_description());
    }
}