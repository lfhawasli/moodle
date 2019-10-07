<?php
// This file is part of UCLA's local_ucla plugin for Moodle - http://moodle.org/
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
 * A scheduled task to for local_ucla.
 *
 * @package    local_ucla
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Deactivates previous iClicker registrations for re-registered devices.
 *
 * @package    local_ucla
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deactivate_duplicate_iclickers_task extends \core\task\scheduled_task {
 
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('deactivateduplicateiclickers', 'local_ucla');
    }
 
    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Query for activated iClicker devices that are registered more than once.
        $sql = "SELECT clicker_id, max(timemodified) AS latest
                  FROM {iclicker_registration}
                 WHERE activated = 1
              GROUP BY clicker_id
                HAVING COUNT(*) > 1";

        $registrations = $DB->get_recordset_sql($sql);

        if ($registrations->valid()) {
            foreach ($registrations as $registration) {
                $clickerid = $registration->clicker_id;

                mtrace('Deactivating duplicate active registrations for iClicker device ' . $clickerid);

                // Deactivate iClicker registrations except the latest one for each device.
                $select = 'clicker_id = :clickerid AND timemodified < :latest';
                $params = array('clickerid' => $clickerid, 'latest' => $registration->latest);
                $DB->set_field_select('iclicker_registration', 'activated', 0, $select, $params);
            }
        }

        $registrations->close();
    }
}
