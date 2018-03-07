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

    if ($oldversion < 2016110800) {
        $table = new xmldb_table('ucla_library_music_reserves');

        $addfields[] = new xmldb_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $addfields[] = new xmldb_field('performers', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $addfields[] = new xmldb_field('albumtitle', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $addfields[] = new xmldb_field('composer', XMLDB_TYPE_TEXT, null, null, null, null, null);

        foreach ($addfields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_block_savepoint(true, 2016110800, 'ucla_media');
    }

    // Add support for Wowza server.
    if ($oldversion < 2017051500) {
        $table = new xmldb_table('ucla_bruincast');

        $fields = array();
        $fields[] = new xmldb_field('audio_url', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'bruincast_url');
        $fields[] = new xmldb_field('podcast_url', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'audio_url');
        $fields[] = new xmldb_field('date', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'podcast_url');
        $fields[] = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'date');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_block_savepoint(true, 2017051500, 'ucla_media');
    }

    // Add comments.
    if ($oldversion < 2017091300) {
        $table = new xmldb_table('ucla_bruincast');
        $field = new xmldb_field('comments', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2017091300, 'ucla_media');
    }

    // Make bruincast_url nullable.
    if ($oldversion < 2017091800) {
        $table = new xmldb_table('ucla_bruincast');
        $field = new xmldb_field('bruincast_url', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'restricted');
        $dbman->change_field_notnull($table, $field);
        upgrade_block_savepoint(true, 2017091800, 'ucla_media');
    }

    // Remove old fields and add in unique key for BruinCast.
    if ($oldversion < 2017101901) {
        $table = new xmldb_table('ucla_bruincast');
        $oldfields = array();
        $oldfields[] = new xmldb_field('restricted');
        $oldfields[] = new xmldb_field('podcast_url');
        foreach ($oldfields as $field) {
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Rename misnamed fields.
        $renamefields = array();
        $renamefields['video_files'] = new xmldb_field('bruincast_url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $renamefields['audio_files'] = new xmldb_field('audio_url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $renamefields['title'] = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        foreach ($renamefields as $newname => $field) {
            if ($dbman->field_exists($table, $field)) {
                $dbman->rename_field($table, $field, $newname);
            }
        }

        // Define index unique_recording (unique) to be added to ucla_bruincast.
        $index = new xmldb_index('unique_recording', XMLDB_INDEX_UNIQUE, array('term', 'srs', 'date', 'title'));
        if (!$dbman->index_exists($table, $index)) {
            try {
                $dbman->add_index($table, $index);
            } catch (Exception $ex) {
                // If there is an error, there is a duplicate video. Need to
                // truncate table and try again.
                echo get_string('erraddingindex', 'block_ucla_media');
                $DB->execute('TRUNCATE TABLE {ucla_bruincast}');
                $dbman->add_index($table, $index);
            }
        }

        upgrade_block_savepoint(true, 2017101901, 'ucla_media');
    }
    
    // Change httpurl and rtmp to char, add new fields, add in unique key for
    // Digital audio reserves.
    if ($oldversion < 2017110500) {
        $table = new xmldb_table('ucla_library_music_reserves');

        // Changing type from text to char.
        $fields = array();
        $fields[] = new xmldb_field('httpurl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'title');
        $fields[] = new xmldb_field('rtmpurl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'httpurl');
        foreach ($fields as $field) {
            $dbman->change_field_type($table, $field);
        }

        // Adding new fields to get unique entry.
        $fields = array();
        $fields[] = new xmldb_field('workid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'composer');
        $fields[] = new xmldb_field('volume', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'workid');
        $fields[] = new xmldb_field('disc', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'volume');
        $fields[] = new xmldb_field('side', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'disc');
        $fields[] = new xmldb_field('tracknumber', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'side');
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Define index uniquemedia.
        $index = new xmldb_index('uniquemedia', XMLDB_INDEX_UNIQUE,
                array('term', 'srs', 'workid', 'volume', 'disc', 'side', 'tracknumber'));
        if (!$dbman->index_exists($table, $index)) {
            try {
                $dbman->add_index($table, $index);
            } catch (Exception $ex) {
                // If there is an error, there is a duplicate media. Need to
                // truncate table and try again.
                echo get_string('erraddingindex', 'block_ucla_media');
                $DB->execute('TRUNCATE TABLE {ucla_library_music_reserves}');
                $dbman->add_index($table, $index);
            }
        }

        upgrade_block_savepoint(true, 2017110500, 'ucla_media');
    }

    // Change fields to match library database schema.
    if ($oldversion < 2017110700) {
        $table = new xmldb_table('ucla_library_music_reserves');
        
        // First need to drop unique index before changing type.
        $index = new xmldb_index('uniquemedia', XMLDB_INDEX_UNIQUE, array('term', 'srs', 'workid', 'volume', 'disc', 'side', 'tracknumber'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Changing type from int to char.
        $fields = array();
        $fields[] = new xmldb_field('volume', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, '0', 'workid');
        $fields[] = new xmldb_field('disc', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, '0', 'volume');
        $fields[] = new xmldb_field('side', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, '0', 'disc');
        $fields[] = new xmldb_field('tracknumber', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, '0', 'side');
        foreach ($fields as $field) {
            $dbman->change_field_type($table, $field);
        }

        // Add back index.
        $dbman->add_index($table, $index);

        upgrade_block_savepoint(true, 2017110700, 'ucla_media');
    }
    
    // Add embedurl field.
    if ($oldversion < 2018030700) {
        // Define field embedurl to be added to ucla_library_music_reserves.
        $table = new xmldb_table('ucla_library_music_reserves');
        $field = new xmldb_field('embedurl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'rtmpurl');

        // Conditionally launch add field embedurl.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ucla_media savepoint reached.
        upgrade_block_savepoint(true, 2018030700, 'ucla_media');
    }

    return true;
}
