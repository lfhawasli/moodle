<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Upgrade script.
 *
 * Can upgrade TA sites for entire system, term, or course.
 *
 * Will not create TA section specific grouping, see CCLE-5715.
 *
 * @package    block_ucla_tasites
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help']) {
    $help = "Upgrades TA sites to support new TA management code, see CCLE-5715.

Can upgrade TA sites for entire system, term, or course.

Will not create TA section specific grouping.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php blocks/ucla_tasites/cli/upgrade.php [TERM|COURSEID (OPTIONAL)]
";

    echo $help;
    die;
}

// This will take a while to run.
set_time_limit(0);
$trace = new text_progress_trace();

// Get TA sites that aren't upgraded, meaning they have customtext1 is NULL.
$sql = "SELECT e.*
         FROM {course} c
         JOIN {enrol} e ON (e.customint1=c.id AND e.customtext1 IS NULL)
         JOIN {ucla_siteindicator} tasite ON (tasite.courseid=e.courseid) ";

// See if user passed in term or courseid.
$params = array();
if ($unrecognized) {
    $value = array_pop($unrecognized);
    if (ucla_validator('term', $value)) {
        $sql .= "JOIN {ucla_request_classes} urc ON (urc.courseid=c.id)
                WHERE urc.term=?";
        $params[] = $value;
    } else if (intval($value) > SITEID) {
        $sql .= "WHERE c.id=?";
        $params[] = $value;
    } else {
        cli_error('Invalid parameter passed. Must be term or courseid.');
    }    
}

$sql .= " AND tasite.type='tasite'";

$tasites = $DB->get_recordset_sql($sql, $params);
$numrecords = 0;
if ($tasites->valid()) {
    $trace->output('Processing TA sites');    
    $parentcourses = array();
    $groupmanager = new ucla_group_manager();
    foreach ($tasites as $tasite) {
        $trace->output('Processing enrol record ' . $tasite->id, 1);

        // Find UID of TA.
        $uid = $DB->get_field('user', 'idnumber', array('id' => $tasite->customint4));

        // If there isn't a UID, then need to skip course.
        if (empty($uid)) {
            $trace->output('Skipping; Missing UID for user ' . $tasite->customint4, 2);
            continue;
        }

        // Save UID to customtext1.
        $tasite->customtext1 = $uid;
        $tasite->timemodified = time();

        $trace->output('Setting customtext1 to ' . $uid, 2);

        try {
            $DB->update_record('enrol', $tasite);
        } catch (Exception $ex) {
            $trace->output('Error; Could not migrate TA site ' . $tasite->courseid, 2);
            continue;
        }

        $trace->output('Migrated TA site ' . $tasite->courseid, 2);
        $numrecords++;
    }
}

$trace->output('DONE! Processed ' . $numrecords  . ' TA sites');