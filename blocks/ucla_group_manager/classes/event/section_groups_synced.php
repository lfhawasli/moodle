<?php
// This file is part of the UCLA group management plugin for Moodle - http://moodle.org/
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
 * Contains section_groups_synced event class.
 *
 * @package    block_ucla_group_manager
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_ucla_group_manager\event;
defined('MOODLE_INTERNAL') || die();

/**
 * section_groups_synced event
 * 
 * Class for event to be triggered when ucla_group_manager syncs
 * section groups during a course sync.
 * 
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int $groupids[] array of group ids
 *
 * @package    block_ucla_group_manager
 * @since      Moodle 2.7
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_groups_synced extends \core\event\base {

    /**
     * Init method.
     * 
     * @return void
     */
    public function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string   Contains event name
     */
    public static function get_name() {
        return get_string('eventsection_groups_synced', 'block_ucla_group_manager');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The section groups for the course with id '{$this->courseid}' have been synced";
    }

    /**
     * Get URL related to the action.
     * 
     * @return \moodle_url  
     */
    public function get_url() {
        return new \moodle_url('/group/index.php', array('id' => $this->courseid));
    }
}
