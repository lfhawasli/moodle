<?php
// This file is part of the UCLA support console for Moodle - http://moodle.org/
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
 * Upgrade code.
 *
 * @package    tool_uclasupportconsole
 * @copyright  2015 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_tool_uclasupportconsole_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();


    // Create uclaieiclasses table and import 12F-13S IEI data.
    if ($oldversion < 2015051200) {

        // Add fields.
        $table = new xmldb_table('uclaieiclasses');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('term', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('srs', XMLDB_TYPE_CHAR, '9', null, XMLDB_NOTNULL, null, null);

        // Add keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('uniqtermsrs', XMLDB_KEY_UNIQUE, array('term', 'srs'));

        // Conditionally launch create table for uclaieiclasses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Import data from text file to the new table.
        // LOAD DATA INFILE syntax uses '\t' as field separator and '\n' as line
        // separator by default, so no need to specify.
        $sql = "LOAD DATA LOCAL INFILE '" . $CFG->dirroot . "/admin/tool/uclasupportconsole/db/iei/12F-13S.txt'
                    INTO TABLE {uclaieiclasses}
                    (term, srs)
                ";
        $DB->execute($sql);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2015051200, 'tool', 'uclasupportconsole');
    }

    // Import 13F-151 IEI data.
    if ($oldversion < 2015101600) {
        // Import data from text file to the IEI table.
        // LOAD DATA INFILE syntax uses '\t' as field separator and '\n' as line
        // separator by default, so no need to specify.
        $sql = "LOAD DATA LOCAL INFILE '" . $CFG->dirroot . "/admin/tool/uclasupportconsole/db/iei/13F-151.txt'
                    INTO TABLE {uclaieiclasses}
                    (term, srs)
                ";
        $DB->execute($sql);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2015101600, 'tool', 'uclasupportconsole');
    }

    return true;
}