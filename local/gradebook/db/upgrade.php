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

    return true;
}
