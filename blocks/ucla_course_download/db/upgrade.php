<?php

/**
 * Holds requests for course content archives
 *
 * @package    ucla
 * @subpackage course_download
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_block_ucla_course_download_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

     if ($oldversion < 2014050713) {

        // Define table ucla_archives to be created.
        $table = new xmldb_table('ucla_archives');

        // Adding fields to table ucla_archives.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '15', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('contexthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fileid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timerequested', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timedownloaded', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table ucla_archives.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('fileid', XMLDB_KEY_FOREIGN, array('fileid'), 'files', array('id'));
        $table->add_key('courseid_userid', XMLDB_KEY_UNIQUE, array('courseid', 'userid'));

        // Adding indexes to table ucla_archives.
        $table->add_index('ch', XMLDB_INDEX_NOTUNIQUE, array('contexthash'));

        // Conditionally launch create table for ucla_archives.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_block_savepoint(true, 2014050713, 'ucla_course_download');
    }

    // Rename and adjust unique index courseid_userid => course_user_type.
    if ($oldversion < 2014053000) {
        // Drop old key courseid_userid.
        $table = new xmldb_table('ucla_archives');
        $key = new xmldb_key('courseid_userid', XMLDB_KEY_UNIQUE,
                array('courseid', 'userid'));
        $dbman->drop_key($table, $key);

        // Add new key course_user_type.
        $key = new xmldb_key('course_user_type', XMLDB_KEY_UNIQUE,
                array('courseid', 'userid', 'type'));
        $dbman->add_key($table, $key);

        // Savepoint reached.
        upgrade_block_savepoint(true, 2014053000, 'ucla_course_download');
    }

    // CCLE-4570 - Implement Report of Request History.
    if ($oldversion < 2015071300) {

        // Define fields to be added to ucla_archives.
        $table = new xmldb_table('ucla_archives');
        $fields[] =  new xmldb_field('numdownloaded', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'timedownloaded');
        $fields[] = new xmldb_field('active', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'numdownloaded');

        // Conditionally add fields.
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Ucla_course_download savepoint reached.
        upgrade_block_savepoint(true, 2015071300, 'ucla_course_download');
    }

    return true;
}
