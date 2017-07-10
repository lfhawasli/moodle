<?php
// This file is part of the UCLA modify course menu block for Moodle - http://moodle.org/
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
 * Contains XML database block upgrade function.
 *
 * @package block_ucla_modify_course_menu
 * @copyright 2017 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 defined('MOODLE_INTERNAL') || die();

 /**
  * XML database block upgrade function.
  *
  * @param version $oldversion
  * @param block $block
  * @return boolean
  */
function xmldb_block_ucla_modify_coursemenu_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();
    // Add a table for the Landing Page by Date modify coursemenu date ranges.
    if ($oldversion < 2017081412) {
        $table = new xmldb_table('ucla_modify_coursemenu');

        // Add fields to the table.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', true, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', true, XMLDB_NOTNULL);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '11', true, XMLDB_NOTNULL);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL);
        $table->add_field('timeend', XMLDB_TYPE_INTEGER, '10', true);

        // Add keys to the table.
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));

        // Add index(es) to the table.
        $table->add_index('csid', XMLDB_INDEX_UNIQUE, array('courseid', 'sectionid'));

        $dbman->create_table($table);

        // Ucla_modify_coursemenu savepoint reached.
        upgrade_block_savepoint(true, 2017081412, 'ucla_modify_coursemenu');
    }

    return true;
}
