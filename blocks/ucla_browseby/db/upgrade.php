<?php
// This file is part of the UCLA browse-by plugin for Moodle - http://moodle.org/
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
 * Upgrades the browse-by plug-in version.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrader.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_block_ucla_browseby_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2016111800) {
        // Define index uid (unique) to be added to ucla_browseall_instrinfo.
        $table = new xmldb_table('ucla_browseall_instrinfo');
        $index = new xmldb_index('uid', XMLDB_INDEX_NOTUNIQUE, array('uid'));

        // Conditionally launch add index uid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Ucla_browseby savepoint reached.
        upgrade_block_savepoint(true, 2016111800, 'ucla_browseby');
    }

    if ($oldversion < 2018051700) {
        // Delete block instances since we are displaying contents directly in
        // lefthand nav and frontpage.
        $blockinstances = $DB->get_recordset('block_instances',
                array('blockname' => 'ucla_browseby',));
        // If atleast one block instance exists, delete them.
        if ($blockinstances->valid()) {
            foreach($blockinstances as $blockinstance) {
                blocks_delete_instance($blockinstance);
            }
        }
        $blockinstances->close();

        // Ucla_browseby savepoint reached.
        upgrade_block_savepoint(true, 2018051700, 'ucla_browseby');
    }

    return $result;
}
