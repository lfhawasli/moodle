<?php
// This file is part of Moodle - http://moodle.org/
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

defined('MOODLE_INTERNAL') || die();

function xmldb_report_emaillog_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2016012600) {
        // Define field recipient_id to be added to report_emaillog.
        $table = new xmldb_table('report_emaillog');
        $field = new xmldb_field('recipient_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'post');
        // Conditionally launch add field recipient_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index recipient_id_index (not unique) to be added to report_emaillog.
        $index = new xmldb_index('recipient_id_index', XMLDB_INDEX_NOTUNIQUE, array('recipient_id'));

        // Conditionally launch add index recipient_id_index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Emaillog savepoint reached.
        upgrade_plugin_savepoint(true, 2016012600, 'report', 'emaillog');
    }

    return $result;
}