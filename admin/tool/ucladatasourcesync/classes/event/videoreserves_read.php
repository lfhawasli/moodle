<?php
// This file is part of the UCLA data source sync tool for Moodle - http://moodle.org/
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
 * Event class for video reserves read.
 *
 * @package    tool_ucladatasourcesync
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ucladatasourcesync\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The class for the events in video reserves read.
 *
 * @package    tool_ucladatasourcesync
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class videoreserves_read extends ucladatasourcesync_event {
    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns the name of the event.
     * @return string
     */
    public static function get_name() {
        return get_string('eventvrread', 'tool_ucladatasourcesync');;
    }
}