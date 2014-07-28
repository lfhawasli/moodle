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
 * UCLA events.
 *
 * Contains the event class for entry added.
 *
 * @package    mod_qanda
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_qanda\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The class for the event entry_added in qanda.
 *
 * @package    mod_qanda
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entry_added extends \core\event\base {
    /**
     * init()
     * Creates the event.
     **/
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'qanda_entries';
    }

    /**
     * get_name()
     * @return the name of the event.
     **/
    public static function get_name() {
        return get_string('evententryadded', 'qanda');
    }

    /**
     * get_description()
     * @return a short description for the event log.
     **/
    public function get_description() {
        return "User {$this->userid} has added an entry with id {$this->objectid}.";
    }

    /**
     * get_url()
     * @return the URL of the event.
     **/
    public function get_url() {
        return new \moodle_url('/mod/'. 'qanda' . '/view.php',
                array('id' => $this->other['instanceid'], 'mode' => 'entry',
                    'hook' => $this->objectid) );
    }

    /**
     * get_legacy_logdata()
     * Used to add log data to legacy log, this is only called if legacy logging
     * is enabled through the legacy logging plugin.
     * 
     * @return an array that imitates the arguments that are used to be passed
     * to the old add_to_log function.
     **/
    public function get_legacy_logdata() {
        return array($this->courseid, "qanda", "add entry", "view.php?id=" .
            $this->other['instanceid'] . "&amp;mode=entry&amp;hook=$this->objectid",
            $this->objectid, $this->other['instanceid']);
    }
}