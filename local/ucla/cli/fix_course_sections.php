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

/**
 * CLI script to fix course sections.
 *
 * To run the script, just enter in a courseid as a parameter.
 *
 * @package    local_ucla
 * @copyright  2013 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/local/ucla/lib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
        array('help' => false, 'fix' => false, 'all' => false,
            'method' => false, 'showsql' => false, 'showdebugging' => false),
        array('h' => 'help', 'f' => 'fix', 'a' => 'all', 'm' => 'method'));

if ($options['help']) {
    $help =
"Fix course's sections.

Will attempt to fix a course's sections by:
* Extra sections above the numsections for a course are deleted.
* Sections are in sequential order.
* Making sure that all sections exist as expected.

Options:
-h, --help          Print out this help
-f, --fix           Fix. Fix reported issues. By default will do a dry run.
-a, --all           Instead of specifying a course, this will check all courses.
-m, --method        Specify the type of fix to run ('extra_sections', 'section_order', 'sections_exist')
--showsql           Shows sql queries before they are executed
--showdebugging     Shows developer debugging info

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/fix_course_sections.php [COURSEID|TERM|COLLAB]
";

    echo $help;
    die;
}

if ($options['showsql']) {
        $DB->set_debug(true);
}
if ($options['showdebugging']) {
        set_debugging(DEBUG_DEVELOPER, true);
}

// This script will run a long time.
core_php_time_limit::raise();
raise_memory_limit(MEMORY_EXTRA);

$courses = array();
if (!empty($options['all'])) {
    // Process every course on system.
    $courses = $DB->get_recordset('course');
    if (!$courses->valid()) {
        $courses = array();
    }
} else if (!empty($unrecognized)) {

    $argument = (array_pop($unrecognized));

    // Check if someone is passing in a term.
    if (ucla_validator('term', $argument)) {
        // Get all courses for that term.
        $sql = "SELECT DISTINCT c.*
                  FROM {course} c
                  JOIN {ucla_request_classes} urc ON (urc.courseid=c.id)
                 WHERE urc.term=:term";
        $courses = $DB->get_records_sql($sql, array('term' => $argument));
    // Check if someone is passing in a valid courseid.
    } else if (intval($argument) > 0) {
        try {
            $course = get_course($argument);
        } catch (Exception $e) {
            cli_error("Cannot find courseid $argument");
        }
        $courses[] = $course;
    } else if (strtolower($argument) == 'collab') {
        // Get all collab sites.
        $sql = "SELECT c.*
                  FROM {course} c
             LEFT JOIN {ucla_request_classes} urc  ON (urc.courseid=c.id)
                 WHERE urc.id IS NULL
                   AND c.id!=:siteid";
        $courses = $DB->get_records_sql($sql, array('term' => $argument,
            'siteid' => SITEID));
    } else {
        cli_error('Invalid argument passed in.');
    }
}

if (empty($courses)) {
    cli_error("No parameter passed, need courseid to run or pass in --all");
}

$trace = new text_progress_trace();

// Run the checker and fixer methods separately because we want to give verbose
// feedback to the user.
$methods = array('extra_sections', 'section_order', 'sections_exist');
if (!empty($options['method']) && in_array($options['method'], $methods)) {
    // See if user wants a particular method to be run.
    $methods = array($options['method']);
}

// Run checkers on a set of courses.
$problemcourses = 0; $totalcourses = 0;
foreach ($courses as $course) {
    $changesmade = false;
    $problemfound = false;
    foreach ($methods as $method) {
        $checkmethod = 'check_'.$method;
        $result = local_ucla_course_section_fixer::$checkmethod($course);
        if (!$result) {
            if (!empty($options['fix'])) {
                $trace->output(sprintf('%s returned problem for course %s (%d), attemping to fix it',
                        $checkmethod, $course->shortname, $course->id));

                $handlemethod = 'handle_'.$method;
                $retval = local_ucla_course_section_fixer::$handlemethod($course);
                $trace->output(sprintf("Added: %d, Deleted: %d, Updated: %d",
                        $retval['added'], $retval['deleted'], $retval['updated']), 1);

                if ($retval['added'] > 0 || $retval['deleted'] > 0 ||
                        $retval['updated'] > 0) {
                    $changesmade = true;
                }
            } else {
                $trace->output(sprintf('%s returned problem for course %s (%d), rerun script with --fix to fix it',
                        $checkmethod, $course->shortname, $course->id));
            }
            $problemfound = true;
        }
    }
    if ($problemfound) {
        ++$problemcourses;
    }
    ++$totalcourses;

    // If any changes were made, then we need to rebuild the course cache.
    if ($changesmade) {
        rebuild_course_cache($course->id);
    }
}

$trace->output("Processed $totalcourses courses and found $problemcourses problem courses");