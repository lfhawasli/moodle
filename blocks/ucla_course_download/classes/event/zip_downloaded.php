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
 * Zip downloaded event.
 *
 * @package    block_ucla_course_download
 * @since      Moodle 2.7
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_course_download\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Zip downloaded event class.
 * 
 * Class for event to be triggered when a user downloads the course content zip.
 *
 * @package    block_ucla_course_download
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class zip_downloaded extends \core\event\base {

    /**
     * init()
     * Creates the event.
     * */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'ucla_archives';
    }

    /**
     * get_name()
     * @return the name of the event.
     * */
    public static function get_name() {
        return get_string('eventzipdownloaded', 'block_ucla_course_download');
    }

    /**
     * get_description()
     * @return a short description for the event log.
     * */
    public function get_description() {
        return "The user with id '{$this->userid}' downloaded a course content zip referenced by ucla_archives id "
                . "'{$this->objectid}'.";
    }

    /**
     * get_url()
     * @return the URL of the event.
     * */
    public function get_url() {
        return new \moodle_url('/blocks/ucla_course_download/view.php', array('courseid' => $this->courseid));
    }

    /**
     * get_legacy_logdata()
     * Used to add log data to legacy log, this is only called if legacy logging
     * is enabled through the legacy logging plugin.
     * 
     * @return an array that imitates the arguments that are used to be passed
     * to the old add_to_log function.
     * */
    public function get_legacy_logdata() {
        return array($this->courseid, 'course', 'ucla archive download', $this->get_url());
    }

}
