<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Upgrades database for bruincast
 *
 * @package    block_ucla_media
 * @author     Anant Mahajan
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function.
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_block_ucla_media_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2016082300) {
        $table = new xmldb_table('ucla_bruincast');
        $indexes[] = new xmldb_field('audio_url', XMLDB_TYPE_TEXT, null, null, null, null, null, 'bruincast_url');
        $indexes[] = new xmldb_field('podcast_url', XMLDB_TYPE_TEXT, null, null, null, null, null, 'audio_url');
        $indexes[] = new xmldb_field('week', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'podcast_url');
        $indexes[] = new xmldb_field('name', XMLDB_TYPE_TEXT, null, null, null, null, null, 'week');

        foreach ($indexes as $index) {
            if (!$dbman->field_exists($table, $index)) {
                $dbman->add_field($table, $index);
            }
        }
        upgrade_block_savepoint(true, 2016082300, 'ucla_media');
    }
    if ($oldversion < 2016090800) {
        // Define table ucla_library_audio_reserves to be created.
        $table = new xmldb_table('ucla_library_music_reserves');

        // Adding fields to table ucla_library_audio_reserves.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('term', XMLDB_TYPE_CHAR, null, null, null, null, null);
        $table->add_field('srs', XMLDB_TYPE_CHAR, '9', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('title', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('videohttp', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('videortmp', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('audiohttp', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('audiortmp', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table ucla_library_audio_reserves.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table ucla_library_audio_reserves.
        $table->add_index('termsrs', XMLDB_INDEX_NOTUNIQUE, array('term', 'srs'));

        // Conditionally launch create table for ucla_library_audio_reserves.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ucla_media savepoint reached.

        upgrade_block_savepoint(true, 2016090800, 'ucla_media');
    }
    if ($oldversion < 2016091200) {
        $table = new xmldb_table('ucla_library_music_reserves');
        $fields[] = new xmldb_field('videohttp');
        $fields[] = new xmldb_field('videortmp');
        $fields[] = new xmldb_field('audiohttp');
        $fields[] = new xmldb_field('audiortmp');

        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        $addfields[] = new xmldb_field('httpurl', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $addfields[] = new xmldb_field('rtmpurl', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $addfields[] = new xmldb_field('isvideo', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        
        foreach ($addfields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_block_savepoint(true, 2016091200, 'ucla_media');
    }
    return true;
}
