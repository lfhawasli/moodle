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
 * The mod_qanda entry deleted event.
 *
 * @package    mod_qanda
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_qanda\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_qanda entry deleted event class.
 *
 * @package    mod_qanda
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entry_deleted extends \core\event\base {
    /**
    * Creates the event.
    */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'qanda_entries';
    }
    /**
    * Returns the name of the event.
    *
    * @return string
    */
    public static function get_name() {
        return get_string('evententrydeleted', 'qanda');
    }
    /**
    * Returns a short description for the event.
    *
    * NOTE: Must be non-localized string.
    *
    * @return string
    */
    public function get_description() {
        return "The user with id '$this->userid' has deleted the qanda entry with id '$this->objectid'.";
    }
    /**
    * Returns URL to the course page.
    *
    * @return moodle_url
    */
    public function get_url() {
        return new \moodle_url('/mod/qanda/view.php',
            array('id' => $this->contextinstanceid,
                  'mode' => $this->other['mode'],
                  'hook' => $this->other['hook']));
    }
    /**
    * Add data to legacy log.
    *
    * @return array
    */
    public function get_legacy_logdata() {
        return array($this->courseid, 'qanda', 'delete entry', 
            "view.php?id={$this->contextinstanceid}&amp;mode={$this->other['mode']}&amp;hook={$this->other['hook']}",
            $this->objectid, $this->contextinstanceid);
    }
}