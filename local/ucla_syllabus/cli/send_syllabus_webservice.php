<?php
// This file is part of the UCLA syllabus plugin for Moodle - http://moodle.org/
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
 * Command line script to send bulk 'course alerts' via syllaubs web service.
 *
 * @package    local_ucla_syllabus
 * @copyright  2014 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/local/ucla/lib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false), array('h' => 'help'));

if ($options['help']) {
    $help = <<<HELPTEXT
Updates links to syllabi for given type and term and subject area, or courseid.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla_syllabus/cli/send_syllabus_webservice.php TYPE TERM SUBJ_AREA
\$sudo -u www-data /usr/bin/php local/ucla_syllabus/cli/send_syllabus_webservice.php TYPE COURSEID

Where type = {alert|syllabus|both}

\$sudo -u www-data /usr/bin/php local/ucla_syllabus/cli/send_syllabus_webservice.php alert 14F MGMT
\$sudo -u www-data /usr/bin/php local/ucla_syllabus/cli/send_syllabus_webservice.php syllabus 141 "A&O SCI"
\$sudo -u www-data /usr/bin/php local/ucla_syllabus/cli/send_syllabus_webservice.php both 1234

HELPTEXT;
    cli_error($help);
}

$type = $term = $subjarea = $courseid = null;
$progress = new text_progress_trace();

if (!empty($unrecognized) && count($unrecognized) > 1 && count($unrecognized) < 4) {
    // Expecting the first parameter to be type.
    if (isset($unrecognized[0]) &&
            in_array($unrecognized[0], array('alert', 'syllabus', 'both'))) {
        $type = $unrecognized[0];
    }

    // Next parameter can be courseid or term/subject area.
    if (isset($unrecognized[1]) && ucla_validator('term', $unrecognized[1])) {
        // Second parameter is a term, so the third must be a subject area.
        if (isset($unrecognized[2]) && preg_match('/^[A-Z].*/', $unrecognized[2])) {
            $term = $unrecognized[1];
            $subjarea = $unrecognized[2];
        }
    } else if (isset($unrecognized[1]) && intval($unrecognized[1]) > SITEID) {
        // Second parameter is a courseid.
        $courseid = $unrecognized[1];
    }
}

if (empty($type) || ((empty($term) || empty($subjarea)) && empty($courseid))) {
    cli_error("Invalid parameters");
}

// Determine the WHERE clause of either term/subject area or courseid.
$whereclause = '';
$whereparams = array();
if (!empty($courseid)) {
    $whereclause = "urc.courseid = :courseid";
    $whereparams = array('courseid' => $courseid);
} else {
    $whereclause = "urc.term = :term AND urc.department = :subjarea";
    $whereparams = array('term' => $term, 'subjarea' => $subjarea);
}

if ($type === 'alert' || $type === 'both') {
    $progress->output("Sending course alerts");

    // Get all courses from term and subjarea.
    $sql = "SELECT DISTINCT c.*
              FROM {course} c
              JOIN {ucla_request_classes} urc ON urc.courseid = c.id
             WHERE $whereclause";
    $courses = $DB->get_records_sql($sql, $whereparams);

    // Send alerts.
    if (empty($courses)) {
        $progress->output("...No courses found");
    } else {
        $progress->output(sprintf("...Processing %d courses", count($courses)));
        foreach ($courses as $course) {
            $progress->output(sprintf("Processing %s", $course->shortname), 1);

            $task = new \local_ucla_syllabus\task\ucla_course_alert_task();
            $task->set_custom_data(
                array(
                    'courseid' => $course->id
                )
            );

            try {
                $task->execute();
                $progress->output("...SUCCESS", 2);
            } catch (Exception $e) {
                $progress->output("...FAILURE!", 2);
            }
        }
    }
}

if ($type === 'syllabus' || $type === 'both') {
    $progress->output("Sending syllabi");

    // Get all syllabi from term and subjarea.
    $sql = "SELECT s.*,
                   c.shortname,
                   urc.srs
              FROM {ucla_syllabus} s
              JOIN {course} c ON c.id = s.courseid
              JOIN {ucla_request_classes} urc ON urc.courseid = c.id
             WHERE $whereclause";
    $syllabi = $DB->get_records_sql($sql, $whereparams);

    // Send alerts.
    if (empty($syllabi)) {
        $progress->output("...No syllabi found");
    } else {
        $progress->output(sprintf("...Processing %d syllabi", count($syllabi)));
        foreach ($syllabi as $syllabus) {
            $progress->output(sprintf("Processing %s (%s)", $syllabus->shortname,
                    $syllabus->srs), 1);

            $task = new \local_ucla_syllabus\task\ucla_syllabus_updated_task();
            $task->set_custom_data(
                array(
                    'objectid' => $syllabus->id
                )
            );

            try {
                $task->execute();
                $progress->output("...SUCCESS", 2);
            } catch (Exception $e) {
                $progress->output("...FAILURE!", 2);
            }
        }
    }
}

$progress->output("Done");
