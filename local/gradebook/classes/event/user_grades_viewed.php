<?php
// This file is part of the UCLA gradebook customizations plugin for Moodle - http://moodle.org/
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
 * The overview_grades_viewed event.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradebook\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The user_grades_viewed event class.
 * 
 * Triggered when a user views the user gradebook report.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - option    the specific kind of grade report viewed, passed into event name and url
 *      - suboption subtype of grade report, used to encompass the different kinds of user report views
 * }
 *
 * @since     Moodle 2.7
 * @copyright 2014 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_grades_viewed extends grades_viewed {

    /**
     * Returns the name of the event.
     * @return string
     */
    public static function get_name() {
        return get_string('eventgradesviewed', 'local_gradebook', 'user');
    }

    /**
     * Returns the description of the event.
     * @return string
     */
    public function get_description() {
        if (isset($this->other['suboption'])) {
            $sub = $this->other['suboption'];
            $desc = "The user with id '{$this->userid}' viewed ";
            if ($sub == 'all') {
                $desc .= "the user grade report for all users ";
            } else if ($sub == 'self') {
                $desc .= "their personal user grade report ";
            } else {
                // This is only executed if the event is created with some invalid suboption.
                // Ensures that the description will never accidentally end up ungrammatical or
                // nonsensical.
                $desc .= "the user grade report ";
            }
            $desc .= "from the course with id '{$this->courseid}'";
            return $desc;
        } else {
            return parent::get_description();
        }
    }

    /**
     * Returns the event data in legacy log form.
     * @return array
     */
    public function get_legacy_logdata() {
        $logdata = parent::get_legacy_logdata();
        if (isset($this->other['suboption'])) {
            $logdata[2] .= ' ' . $this->other['suboption'];
        }
        return $logdata;
    }

}
