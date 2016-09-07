<?php
// This file is part of the UCLA gradebook customizations plugin for Moodle - http://moodle.org/
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
 * Upgrade script.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_local_gradebook_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2012110700) {
        // Define table ucla_grade_failed_updates to be dropped.
        $table = new xmldb_table('ucla_grade_failed_updates');

        // Conditionally launch drop table for ucla_grade_failed_updates.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Gradebook savepoint reached.
        upgrade_plugin_savepoint(true, 2012110700, 'local', 'gradebook');
    }

    // Freeze grades for existing gradebooks.
    if ($oldversion < 2016080800) {
        $pbar = new progress_bar('freezegradebooks', 500, true);

        // Find all courses with grades.
        $sql = "SELECT COUNT(DISTINCT gi.courseid)
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON (gg.itemid=gi.id)
                 WHERE gg.rawgrade IS NOT NULL";
        $total = $DB->count_records_sql($sql);
        $sql = "SELECT DISTINCT gi.courseid
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON (gg.itemid=gi.id)
                 WHERE gg.rawgrade IS NOT NULL";
        $gradebooks = $DB->get_recordset_sql($sql);
        $i = 0;
        foreach ($gradebooks as $gradebook) {
            // Freeze gradebook.
            $gradebookfreeze = get_config('core', 'gradebook_calculations_freeze_' . $gradebook->courseid);
            if (!$gradebookfreeze) {
                // We were using the Gradebook from 2.7.
                set_config('gradebook_calculations_freeze_' . $gradebook->courseid, 20140512);
            }

            // Update progress.
            $i++;
            $pbar->update($i, $total, "Freezing gradebooks - $i/$total.");
        }
        $gradebooks->close();
        upgrade_plugin_savepoint(true, 2016080800, 'local', 'gradebook');
    }

    return true;
}
