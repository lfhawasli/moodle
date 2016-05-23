<?php  //$Id: upgrade.php,v 1.2 2007/08/08 22:36:54 stronk7 Exp $

// This file keeps track of upgrades to
// the videoannotation module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_videoannotation_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;


    $result = true;

/// And upgrade begins here. For each one, you'll need one
/// block of code similar to the next one. Please, delete
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

if ($result && $oldversion < 2012051600) {

    /// Define field groupmode to be added to videoannotation
        $table = new XMLDBTable('videoannotation');
        $field = new XMLDBField('groupmode');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'clipselect');

    /// Launch add field groupmode
        $result = $result && add_field($table, $field);
    }

if ($result && $oldversion < 2012051600) {

    /// Define field groupid to be added to videoannotation_clips
        $table = new XMLDBTable('videoannotation_clips');
        $field = new XMLDBField('groupid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'userid');

    /// Launch add field groupid
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2012051600) {

    /// Define index videoannotationid_userid_groupid (not unique) to be added to videoannotation_clips
        $table = new XMLDBTable('videoannotation_clips');
        $index = new XMLDBIndex('videoannotationid_userid_groupid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('videoannotationid', 'userid', 'groupid'));

    /// Launch add index videoannotationid_userid_groupid
        $result = $result && add_index($table, $index);
    }

    if ($result && $oldversion < 2012051600) {

    /// Changing type of field userid on table videoannotation_events to int
        $table = new XMLDBTable('videoannotation_events');
        $field = new XMLDBField('userid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'tagid');

    /// Launch change of type for field userid
        $result = $result && change_field_type($table, $field);
    }

    if ($result && $oldversion < 2012051600) {

    /// Define field groupid to be added to videoannotation_events
        $table = new XMLDBTable('videoannotation_events');
        $field = new XMLDBField('groupid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'userid');

    /// Launch add field groupid
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2012051600) {

    /// Define index tagid_userid_groupid (not unique) to be added to videoannotation_events
        $table = new XMLDBTable('videoannotation_events');
        $index = new XMLDBIndex('tagid_userid_groupid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('tagid', 'userid', 'groupid'));

    /// Launch add index tagid_userid_groupid
        $result = $result && add_index($table, $index);
    }

    if ($result && $oldversion < 2012051600) {

    /// Define table videoannotation_locks to be created
        $table = new XMLDBTable('videoannotation_locks');

    /// Adding fields to table videoannotation_locks
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('videoannotationid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('locktype', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null);

    /// Adding keys to table videoannotation_locks
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table videoannotation_locks
        $table->addIndexInfo('videoannotationid_userid_groupid', XMLDB_INDEX_NOTUNIQUE, array('videoannotationid', 'userid', 'groupid'));

    /// Launch create table for videoannotation_locks
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2012051600) {

    /// Changing type of field userid on table videoannotation_submissions to int
        $table = new XMLDBTable('videoannotation_submissions');
        $field = new XMLDBField('userid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'videoannotationid');

    /// Launch change of type for field userid
        $result = $result && change_field_type($table, $field);
    }

    if ($result && $oldversion < 2012051600) {

    /// Define field groupid to be added to videoannotation_submissions
        $table = new XMLDBTable('videoannotation_submissions');
        $field = new XMLDBField('groupid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'userid');

    /// Launch add field groupid
        $result = $result && add_field($table, $field);
    }
    if ($result && $oldversion < 2012051600) {

    /// Define index videoannotationid_userid_groupid (not unique) to be dropped form videoannotation_submissions
        $table = new XMLDBTable('videoannotation_submissions');
        $index = new XMLDBIndex('videoannotationid_userid_groupid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('videoannotationid', 'userid', 'groupid'));

    /// Launch drop index videoannotationid_userid_groupid
        $result = $result && drop_index($table, $index);
    }
    if ($result && $oldversion < 2012051600) {

    /// Define index videoannotationid_userid_groupid (not unique) to be added to videoannotation_submissions
        $table = new XMLDBTable('videoannotation_submissions');
        $index = new XMLDBIndex('videoannotationid_userid_groupid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('videoannotationid', 'userid', 'groupid'));

    /// Launch add index videoannotationid_userid_groupid
        $result = $result && add_index($table, $index);
    }
    if ($result && $oldversion < 2012051600) {

    /// Changing type of field userid on table videoannotation_tags to int
        $table = new XMLDBTable('videoannotation_tags');
        $field = new XMLDBField('userid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'clipid');

    /// Launch change of type for field userid
        $result = $result && change_field_type($table, $field);
    }
    if ($result && $oldversion < 2012051600) {

    /// Define field groupid to be added to videoannotation_tags
        $table = new XMLDBTable('videoannotation_tags');
        $field = new XMLDBField('groupid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, null, 'userid');

    /// Launch add field groupid
        $result = $result && add_field($table, $field);
    }
    if ($result && $oldversion < 2012051600) {

    /// Define index clipid_userid_groupid (not unique) to be dropped form videoannotation_tags
        $table = new XMLDBTable('videoannotation_tags');
        $index = new XMLDBIndex('clipid_userid_groupid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('clipid', 'userid', 'groupid'));

    /// Launch drop index clipid_userid_groupid
        $result = $result && drop_index($table, $index);
    }
    if ($result && $oldversion < 2012051600) {

    /// Define index clipid_userid_groupid (not unique) to be added to videoannotation_tags
        $table = new XMLDBTable('videoannotation_tags');
        $index = new XMLDBIndex('clipid_userid_groupid');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('clipid', 'userid', 'groupid'));

    /// Launch add index clipid_userid_groupid
        $result = $result && add_index($table, $index);
    }
if ($result && $oldversion < 2014040800) {

    /// Define field latitude to be added to videoannotation_events
        $table = new XMLDBTable('videoannotation_events');
        $field = new XMLDBField('latitude');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 7', null, null, null, null, null, null, 'timemodified');

    /// Launch add field latitude
        $result = $result && add_field($table, $field);
    }
    if ($result && $oldversion < 20140408000) {

    /// Define field longitude to be added to videoannotation_events
        $table = new XMLDBTable('videoannotation_events');
        $field = new XMLDBField('longitude');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 7', null, null, null, null, null, null, 'latitude');

    /// Launch add field longitude
        $result = $result && add_field($table, $field);
    }


if ($result && $oldversion < 20140408000) {

    /// Define field scope to be added to videoannotation_events
        $table = new XMLDBTable('videoannotation_events');
        $field = new XMLDBField('scope');
        $field->setAttributes(XMLDB_TYPE_CHAR, '50', null, null, null, null, null, null, 'timemodified');

    /// Launch add field scope
        $result = $result && add_field($table, $field);
    }



/*
/// Lines below (this included)  MUST BE DELETED once you get the first version
/// of your module ready to be installed. They are here only
/// for demonstrative purposes and to show how the videoannotation
/// iself has been upgraded.

/// For each upgrade block, the file videoannotation/version.php
/// needs to be updated . Such change allows Moodle to know
/// that this file has to be processed.

/// To know more about how to write correct DB upgrade scripts it's
/// highly recommended to read information available at:
///   http://docs.moodle.org/en/Development:XMLDB_Documentation
/// and to play with the XMLDB Editor (in the admin menu) and its
/// PHP generation posibilities.

/// First example, some fields were added to the module on 20070400
    if ($result && $oldversion < 2007040100) {

    /// Define field course to be added to videoannotation
        $table = new XMLDBTable('videoannotation');
        $field = new XMLDBField('course');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'id');
    /// Launch add field course
        $result = $result && add_field($table, $field);

    /// Define field intro to be added to videoannotation
        $table = new XMLDBTable('videoannotation');
        $field = new XMLDBField('intro');
        $field->setAttributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, 'name');
    /// Launch add field intro
        $result = $result && add_field($table, $field);

    /// Define field introformat to be added to videoannotation
        $table = new XMLDBTable('videoannotation');
        $field = new XMLDBField('introformat');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'intro');
    /// Launch add field introformat
        $result = $result && add_field($table, $field);
    }

/// Second example, some hours later, the same day 20070401
/// two more fields and one index were added (note the increment
/// "01" in the last two digits of the version
    if ($result && $oldversion < 2007040101) {

    /// Define field timecreated to be added to videoannotation
        $table = new XMLDBTable('videoannotation');
        $field = new XMLDBField('timecreated');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'introformat');
    /// Launch add field timecreated
        $result = $result && add_field($table, $field);

    /// Define field timemodified to be added to videoannotation
        $table = new XMLDBTable('videoannotation');
        $field = new XMLDBField('timemodified');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timecreated');
    /// Launch add field timemodified
        $result = $result && add_field($table, $field);

    /// Define index course (not unique) to be added to videoannotation
        $table = new XMLDBTable('videoannotation');
        $index = new XMLDBIndex('course');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
    /// Launch add index course
        $result = $result && add_index($table, $index);
    }

/// Third example, the next day, 20070402 (with the trailing 00), some inserts were performed, related with the module
    if ($result && $oldversion < 2007040200) {
    /// Add some actions to get them properly displayed in the logs
        $rec = new stdClass;
        $rec->module = 'videoannotation';
        $rec->action = 'add';
        $rec->mtable = 'videoannotation';
        $rec->filed  = 'name';
    /// Insert the add action in log_display
        $result = insert_record('log_display', $rec);
    /// Now the update action
        $rec->action = 'update';
        $result = insert_record('log_display', $rec);
    /// Now the view action
        $rec->action = 'view';
        $result = insert_record('log_display', $rec);
    }

/// And that's all. Please, examine and understand the 3 example blocks above. Also
/// it's interesting to look how other modules are using this script. Remember that
/// the basic idea is to have "blocks" of code (each one being executed only once,
/// when the module version (version.php) is updated.

/// Lines above (this included) MUST BE DELETED once you get the first version of
/// yout module working. Each time you need to modify something in the module (DB
/// related, you'll raise the version and add one upgrade block here.

/// Final return of upgrade result (true/false) to Moodle. Must be
/// always the last line in the script
*/
    return $result;
}


?>
