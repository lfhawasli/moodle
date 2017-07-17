<?php
// This file is part of UCLA local plugin for Moodle - http://moodle.org/
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
 * Triggered when courses have their enrollments synced by enrollment helper.
 *
 * @package    local_ucla
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package    local_ucla
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_enrolments_finished extends \core\event\base {

    /**
     * Decodes courses from the event data.
     *
     * @return array
     */
    public function get_courses() {
        return json_decode($this->data['other']);
    }

    /**
     * Returns a short description for the event.
     *
     * @return string
     */
    public function get_description() {
        return "UCLA registrar enrolments sychronized";
    }

    /**
     * Returns the name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsyncenrolmentsfinished', 'local_ucla');
    }

    /**
     * Creates the event.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->context = \context_system::instance();
    }
}
