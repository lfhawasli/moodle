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
 * The course creator finished event.
 *
 * @package    tool_uclacoursecreator
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_uclacoursecreator\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The course creator finished event class.
 *
 * @property-read array $other {
 *      Object with array of completed requests
 * }
 *
 * @since     Moodle 3.1
 * @copyright 2016 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_creator_finished extends \core\event\base {
    /**
     * Get event description.
     *
     * @return string
     */
    public function get_description() {
        return "Course creator finished execution.";
    }

    /**
     * Returns the name of the legacy event.
     *
     * @return string legacy event name
     */
    public static function get_legacy_eventname() {
        return 'course_creator_finished';
    }

    /**
     * Get event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcoursecreatorfinished', 'tool_uclacoursecreator');
    }

    /**
     * Get course requests.
     *
     * @return array
     */
    public function get_requests() {
        return json_decode($this->other);
    }

    /**
     * Initializer.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->context = \context_system::instance();
    }
}