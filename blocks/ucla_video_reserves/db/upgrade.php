<?php
// This file is part of the UCLA video reserves block for Moodle - http://moodle.org/
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
 * Upgrades the video reserves video block.
 *
 * @package    block_ucla_video_reserves
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function.
 *
 * @param int $oldversion
 */
function xmldb_block_ucla_video_reserves_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015092500) {
        // Changing type of field height/width to int.
        $table = new xmldb_table('ucla_video_reserves');

        $fields = array();
        $fields[] = new xmldb_field('height', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'subtitle');
        $fields[] = new xmldb_field('width', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'height');

        foreach ($fields as $field) {
            $dbman->change_field_type($table, $field);
        }

        upgrade_block_savepoint(true, 2015092500, 'ucla_video_furnace');
    }

    if ($oldversion < 2015100700) {
        // Add unique key.
        $table = new xmldb_table('ucla_video_reserves');
        $index = new xmldb_index('uniquevideo', XMLDB_INDEX_UNIQUE, array('term', 'srs', 'video_title'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_block_savepoint(true, 2015100700, 'ucla_video_furnace');
    }

    return true;
}
