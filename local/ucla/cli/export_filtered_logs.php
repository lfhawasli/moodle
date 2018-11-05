<?php
// This file is part of the local UCLA plugin for Moodle - http://moodle.org/
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
 * CLI script to filter and dump filtered logs into new log tables.
 *
 * @package    local_ucla
 * @copyright  2018 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help'] || empty($unrecognized)) {
    $help = "CLI script to filter and dump filtered logs into new log tables.

Goes through log tables mdl_logstore_standard_log and mdl_log and exports only
rows that belong to a Registrar course and a student into new tables postpended
with _filtered.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/export_filtered_logs.php <directory to put SQL files>
";
    cli_error($help);
}

// Require parameter to be valid directory that is writable.
$exportdir = array_pop($unrecognized);
if (!is_writable($exportdir)) {
    cli_error($exportdir . ' is not writable');
}

// Create export database. Use same connection details but change prefix.
$exportdb = moodle_database::get_driver_instance($CFG->dbtype, $CFG->dblibrary);
$exportdb->connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname, 'export_', $CFG->dboptions);
$dbman = $exportdb->get_manager();

// Make sure tables do not already exists.
$exportables = ['log', 'user', 'logstore_standard_log', 'ucla_request_classes', 'rolemapping'];
foreach ($exportables as $exportable) {
    if ($dbman->table_exists($exportable)) {
        $dbman->drop_table(new xmldb_table($exportable));
    }
}

// Create export log tables.
mtrace('Creating export log tables');
$dbman->install_one_table_from_xmldb_file($CFG->dirroot . '/lib/db/install.xml', 'log');
$dbman->install_one_table_from_xmldb_file($CFG->dirroot . '/lib/db/install.xml', 'user');
$dbman->install_one_table_from_xmldb_file($CFG->dirroot . '/' . $CFG->admin .
        '/tool/log/store/standard/db/install.xml', 'logstore_standard_log');
$dbman->install_one_table_from_xmldb_file($CFG->dirroot . '/' . $CFG->admin .
        '/tool/uclacourserequestor/db/install.xml', 'ucla_request_classes');
create_rolemapping_table($dbman);

// Store student roleid.
$studentid = $DB->get_field('role', 'id', ['shortname' => 'student']);
if (empty($studentid)) {
    cli_error('Cannot find student role');
}

// Copy ucla_request_classes entries that do not belong to cancelled courses.
// Do not get Fall 2018+ courses since they are still in progress as of the
// writing of this script.
mtrace('Exporting ucla_request_classes');
$sql = "INSERT INTO export_ucla_request_classes (
            SELECT urc.*
             FROM {ucla_request_classes} urc
             JOIN {ucla_reg_classinfo} urci ON (urc.term=urci.term AND urc.srs=urci.srs)
            WHERE urci.enrolstat!='X'
              AND urc.term!='18F'
              AND urc.term!='19W'
        )";
$DB->execute($sql);

// Get all non-cancelled courses.
$sql = "SELECT DISTINCT urc.courseid AS id
          FROM {ucla_request_classes} urc
          JOIN {ucla_reg_classinfo} urci ON (urc.term=urci.term AND urc.srs=urci.srs)
         WHERE urci.enrolstat!='X'
           AND urc.term!='18F'
           AND urc.term!='19W'";
$classes = $DB->get_recordset_sql($sql);
if (!$classes->valid()) {
    cli_error('Cannot query classes');
}

mtrace('Exporting logs');
// Copy over entries from mdl_log that belong to registrar courses.
$sql = "INSERT INTO export_log (
            SELECT DISTINCT l.*
              FROM {log} l
              JOIN export_ucla_request_classes urc ON (l.course=urc.courseid)
             WHERE 1
        )";
$DB->execute($sql, ['studentid' => $studentid]);

// Copy over entries from mdl_log that belong to registrar courses.
$sql = "INSERT INTO export_logstore_standard_log (
            SELECT DISTINCT l.*
              FROM {logstore_standard_log} l
              JOIN export_ucla_request_classes urc ON (l.courseid=urc.courseid)
             WHERE 1
        )";
$DB->execute($sql, ['studentid' => $studentid]);

mtrace('Exporting role mappings');
foreach ($classes as $class) {
    $courseid = $class->id;

    // Copy over rolemapping information. A student is dropped if their status
    // in user_enrolments is 1.
    $sql = "INSERT INTO export_rolemapping (
                courseid,
                userid,
                dropped
            )
            SELECT DISTINCT e.courseid,
                   ue.userid,
                   ue.status
              FROM {user_enrolments} ue
              JOIN {enrol} e ON (ue.enrolid=e.id)
              JOIN {role_assignments} ra ON (ue.userid=ra.userid)
              JOIN {context} cxt ON (ra.contextid=cxt.id)
             WHERE e.enrol='database'
               AND e.courseid=:courseid
               AND cxt.contextlevel=50
               AND cxt.instanceid=e.courseid
               AND ra.roleid=:studentid";
    $DB->execute($sql, ['courseid' => $courseid, 'studentid' => $studentid]);
}

mtrace('Exporting users');
// Copy all users that have had a student role. Need to specify columns, because
// else we get "Error Code: 1265. Data truncated for column 'currentlogin'".
$sql = "INSERT INTO export_user (
                    id,
                    auth,
                    username,
                    idnumber,
                    firstname,
                    lastname,
                    email,
                    maildigest,
                    autosubscribe,
                    trackforums,
                    timecreated,
                    timemodified,
                    middlename,
                    alternatename
        ) SELECT id,
                 auth,
                 username,
                 idnumber,
                 firstname,
                 lastname,
                 email,
                 maildigest,
                 autosubscribe,
                 trackforums,
                 timecreated,
                 timemodified,
                 middlename,
                 alternatename
            FROM {user} u
           WHERE id IN (
                SELECT DISTINCT userid
                  FROM {role_assignments}
                 WHERE roleid=:studentid
            )";
$DB->execute($sql, ['studentid' => $studentid]);

// Now export tables into SQL files.
$prefixedexporttables = implode(' ', preg_filter('/^/', 'export_', $exportables));
$exportfile = $exportdir . '/export_tables.sql';
mtrace('Exporting into ' . $exportfile);
exec(sprintf('mysqldump --user=%s --password=%s --host=%s %s %s > %s',
        $CFG->dbuser, $CFG->dbpass, $CFG->dbhost, $CFG->dbname, $prefixedexporttables, $exportfile),
        $output, $return);
if ($return !== 0) {
    cli_error('The mysqldump failed.');
}

// Cleanup.
mtrace('Cleaning up created tables');
foreach ($exportables as $exportable) {
    if ($dbman->table_exists($exportable)) {
        $dbman->drop_table(new xmldb_table($exportable));
    }
}

/**
 * Creates custom role mapping table and includes information if student has
 * dropped course or not.
 *
 * @param database_manager $dbman
 */
function create_rolemapping_table(database_manager $dbman) {
    $table = new xmldb_table('rolemapping');

    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('courseid', XMLDB_TYPE_CHAR, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('userid', XMLDB_TYPE_CHAR, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('dropped', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    $dbman->create_table($table);
}