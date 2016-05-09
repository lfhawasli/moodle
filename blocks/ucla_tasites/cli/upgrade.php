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
 * Upgrades TA sites to support TA specific groups, see CCLE-5715.
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
    $help = "Upgrades TA sites to support TA specific groups, see CCLE-5715.

Can upgrade TA sites for entire system, term, or course.

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

// Get TA sites that aren't upgraded, meaning they have customtext1 and
// customtext2 are NULL.
$sql = "SELECT e.*
        FROM {course} c
        JOIN {enrol} e ON (e.customint1=c.id AND e.customtext1 IS NULL AND e.customtext2 IS NULL)
        JOIN {course} tasite ON (tasite.id=e.courseid)";

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

$tasites = $DB->get_recordset_sql($sql, $params);
if ($tasites->valid()) {
    $trace->output('Processing TA sites');
    $numrecords = 0;
    $parentcourses = array();
    $groupmanager = new ucla_group_manager();
    foreach ($tasites as $tasite) {
        $trace->output('Processing enrol record ' . $tasite->id, 1);

        // Make sure parent course has section groups updated and with idnumbers set.
        if (!isset($parentcourses[$tasite->customint1])) {
            $trace->output('Updating parent course groups ' . $tasite->customint1, 2);
            ob_start(); // Don't want the output to clutter the screen.
            $groupmanager->sync_course($tasite->customint1);
            ob_clean();
            $parentcourses[$tasite->customint1] = true;
        }

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

        // Create TA section specific grouping.
        $mapping = block_ucla_tasites::get_tasection_mapping($tasite->customint1);

        // Check if TA has any sections assigned to them.
        $ta = $DB->get_record('user', array('id' => $tasite->customint4));
        $fullname = fullname($ta);
        if (!empty($mapping['byta'][$fullname]['secsrs'])) {
            $secnums = $srsarray = array();
            foreach ($mapping['byta'][$fullname]['secsrs'] as $secnum => $secinfo) {
                $secnums[] = block_ucla_tasites::format_sec_num($secnum);
                foreach ($secinfo as $srs) {
                    $srsarray[] = $srs;
                }
            }

            // Found sections, need to create TA specific grouping.
            $grouping = block_ucla_tasites::create_taspecificgrouping(
                    $tasite->customint1, $tasite->courseid, $srsarray);

            $trace->output('Created grouping ' . $grouping->id, 2);

            // Add sections SRSes to customtext2.
            $tasite->customtext2 = implode(',', $srsarray);
            $trace->output('Updated customtext2 to ' . $tasite->customtext2, 2);

            // Add section numbers to customchar1.
            $tasite->customchar1 = implode(',', $secnums);
            $trace->output('Updated customchar1 to ' . $tasite->customchar1, 2);
        }        

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