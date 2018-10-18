<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Response to events.
 *
 * @package    block_ucla_tasites
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class.
 *
 * @package    block_ucla_tasites
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_tasites_observer {
    /**
     * Caches TA tole ids, in case we are doing mass role assignments, want to
     * prevent additional database queries for same data.
     *
     * @var array 
     */
    private static $taroles = null;

    /**
     * Clears the tasitemapping cache when a TA/TA admin is assigned.
     *
     * @param \core\event\base $event   Can be either role_assigned or
     *                                  role_unassigned.
     */
    public static function clear_tasitemapping_cache(\core\event\base $event) {
        global $DB;

        // Make sure to only response to adding role at course level.
        if ($event->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        // Get TA roles.
        if (empty(self::$taroles)) {
            $taroles = $DB->get_records_list('role', 'shortname',
                    ['ta_admin', 'ta'], null, 'id');
            // DB functions returns id as the index to result array.
            self::$taroles = array_keys($taroles);
        }

        // See if role is TA or TA admin.
        if (in_array($event->objectid, self::$taroles)) {
            // It is, so clear any cache for given courseid.
            $cache = cache::make('block_ucla_tasites', 'tasitemapping');
            $cache->delete($event->courseid);
        }
    }
}
