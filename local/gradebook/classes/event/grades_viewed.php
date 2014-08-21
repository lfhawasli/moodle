<?php
// This file is part of UCLA gradebook customizations local plugin for 
// Moodle - http://moodle.org/
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
 * The grades_viewed event.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradebook\event;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/classes/user.php');

/**
 * Abstract grades_viewed event class.
 * 
 * Extended by events logging gradebook views.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - option    the specific kind of grade report viewed
 * }
 *
 * @since     Moodle 2.7
 * @copyright 2014 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class grades_viewed extends \core\event\base {

    /**
     * Initiates the event.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns the name of the event.
     * @return string
     */
    public static function get_name() {
        return get_string('eventgradesviewed', 'local_gradebook', '');
    }

    /**
     * Returns the description of the event.
     * @return string
     */
    public function get_description() {
        $desc = "The user with id '{$this->userid}' viewed the {$this->other['option']} grade report ";
        if (!empty($this->relateduserid)) {
            $desc .= "of the user with id '{$this->relateduserid}' ";
        }
        $desc .= "from the course with id '{$this->courseid}'";
        return $desc;
    }

    /**
     * Returns the url of the event.
     * @return moodle_url
     */
    public function get_url() {
        $urlparams = array('id' => $this->courseid);
        if (!is_null($this->relateduserid)) {
            $urlparams['userid'] = $this->relateduserid;
        }
        return new \moodle_url('/grade/report/' . $this->other['option'] . '/index.php', $urlparams);
    }

    /**
     * Returns the event data in legacy log form.
     * @return array
     */
    public function get_legacy_logdata() {
        global $DB;
        $logdata = array($this->courseid, 'grade', 'view ' . $this->other['option'],
            '/report/' . $this->other['option'] . '/index.php?id=' . $this->courseid);
        if (!is_null($this->relateduserid)) {
            $logdata[3] .= '&userid=' . $this->relateduserid;
            if ($user = \core_user::get_user($this->relateduserid)) {
                $logdata[] = fullname($user);
            }
        }
        return $logdata;
    }

}
