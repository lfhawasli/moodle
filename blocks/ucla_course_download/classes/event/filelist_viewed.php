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
 * File list viewed event.
 *
 * @package    block_ucla_course_download
 * @since      Moodle 2.7
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_course_download\event;

defined('MOODLE_INTERNAL') || die();

/**
 * File list viewed event class.
 * 
 * Class for event to be triggered when a user views a course's course download page.
 *
 * @package    block_ucla_course_download
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filelist_viewed extends \core\event\base {

    /**
     * init()
     * Creates the event.
     * */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * get_name()
     * @return the name of the event.
     * */
    public static function get_name() {
        return get_string('eventfilelistviewed', 'block_ucla_course_download');
    }

    /**
     * get_description()
     * @return a short description for the event log.
     * */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed the UCLA course download status page for the course with id "
                . "'{$this->courseid}'.";
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
        $logurl = new \moodle_url('/blocks/ucla_course_download/view.php', array('courseid' => $this->courseid));
        return array($this->courseid, 'course', 'ucla archive view', $logurl);
    }

}
