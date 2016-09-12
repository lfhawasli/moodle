<?php
// This file is part of the UCLA weeks display block for Moodle - http://moodle.org/
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
 * Event triggered when the weeks change.
 *
 * @package    block_ucla_weeksdisplay
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_weeksdisplay\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package    block_ucla_weeksdisplay
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class week_changed extends \core\event\base {

    /**
     * Initialization method.
     */
    protected function init() {
        $this->context = \context_system::instance();
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventweekchanged', 'block_ucla_weeksdisplay');
    }

    /**
     * Returns what week we changed to.
     * @return string
     */
    public function get_description() {
        return "Week changed to '{$this->other['week']}'.";
    }
}