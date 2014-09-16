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
 * Contains the event class for ucladatasourcesync.
 *
 * @package    tool_ucladatasourcesync
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ucladatasourcesync\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The class for the events in ucladatasourcesync.
 *
 * @package    tool_ucladatasourcesync
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucladatasourcesync_event extends \core\event\base {
    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns the name of the event.
     * @return string
     **/
    public static function get_name() {
        return "";
    }

    /**
     * Returns a short description for the event.
     * @return string
     **/
    public function get_description() {
        return $this->other['message'];
    }

    /**
     * Returns URL to the report result page.
     * @return moodle_url
     **/
    public function get_url() {
        return null;
    }

    /**
     * Add data to legacy log.
     * @return array
     **/
    public function get_legacy_logdata() {
        return array($this->contextinstanceid, $this->other['func'], $this->other['action'], '',
            $this->other['message']);
    }
    
    /**
     * Returns the correct event path
     * @return string
     **/
    public static function datasource($func, $action) {
        $source = $func.'_'.$action;
        return "\\tool_ucladatasourcesync\\event\\".$source;
    }
}