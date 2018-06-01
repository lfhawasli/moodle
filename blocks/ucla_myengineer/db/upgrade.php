<?php
// This file is part of the UCLA Engineering sites block for Moodle - http://moodle.org/
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
 * Upgrade code for the UCLA Engineering sites block.
 *
 * @package    block_ucla_myengineer
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute block upgrade from the given older version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_ucla_myengineer_upgrade($oldversion) {
    global $DB;

    // Adding Gradebook preference for Engineering sites.
    if ($oldversion < 2018060500) {
        // Add Gradebook preference for all Engineering sites by:
        // 1) Get all Engineering sites for active terms.
        // 2) For each site, add a new gradebook redirect preference to course_format_options
        //    with default value as 1 (MyUCLA gradebook).

        // 1) Get all Engineering sites for active terms.
        list($terms, $params) = $DB->get_in_or_equal(get_active_terms(), SQL_PARAMS_NAMED);
        $sql = "SELECT DISTINCT urc.courseid
                FROM {ucla_request_classes} urc
                JOIN {ucla_reg_classinfo} urci ON (urc.term=urci.term AND urc.srs=urci.srs)
                WHERE urci.division='EN' AND urci.term $terms";
        $engineeringsites = $DB->get_records_sql($sql, $params);

        // 2) For each site, add a new gradebook redirect preference to course_format_options
        //    with default value as 1 (MyUCLA gradebook).
        foreach ($engineeringsites as $site) {
            // If site already has the preference set, reset the value to MyUCLA gradebook (1).
            // Otherwise create a new entry with value set to MyUCLA gradebook (1).
            course_get_format($site->courseid)->update_course_format_options(
                array('myuclagradelinkredirect' => true));
        }

        // Migration complete.
        upgrade_block_savepoint(true, 2018060500, 'ucla_myengineer');
    }

    return true;
}
