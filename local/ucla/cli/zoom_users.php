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
 * CLI script to generate CSV file with users in given categories that have a
 * Zoom meeting since given date.
 *
 * @package    local_ucla
 * @copyright  2018 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->libdir.'/coursecatlib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

// Process parameters.
$categories = $sincedate = $outputfile = null;
if (!empty($unrecognized[0])) {
    // Comma separated list of category idnumbers.
    $categorylist = $unrecognized[0];
    // Make sure category idnumbers exists.
    $categorylist = explode(',', $categorylist);
    foreach ($categorylist as $idnumber) {
        $id = $DB->get_field('course_categories', 'id', ['idnumber' => $idnumber], MUST_EXIST);
        $categories[] = coursecat::get($id, MUST_EXIST, true);
    }
}
if (!empty($unrecognized[1])) {
    // Must be valid date.
    $sincedate = strtotime($unrecognized[1]);
    if ($sincedate === false) {
        cli_error('Invalid date give: ' . $sincedate);
    }
}
if (!empty($unrecognized[2])) {
    // Output CSV file.
    $outputfile = validate_param($unrecognized[2], PARAM_PATH);
    if (fopen($outputfile, 'w') === false) {
        cli_error('Unable to write to: ' . $outputfile);
    }
}

if ($options['help'] || empty($categories) || empty($sincedate) || empty($outputfile)) {
    $help = "CLI script to generate CSV file with users in given categories that have a Zoom meeting since given date.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/zoom_users.php <categories idnumbers> <date> <CSV to export>
";
    cli_error($help);
    die;
}

// Might be resource intensive since we are querying lots of courses.
core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

$trace = new text_progress_trace();

// Go through each category and find all the courses recursively.
$zoomusers = [];    // Used for report later on.
foreach ($categories as $category) {
    $trace->output('Working on category ' . $category->name);
    $courses = $category->get_courses(['recursive' => 1]);
    $trace->output(sprintf('Processing %d courses...', count($courses)), 1);

    foreach ($courses as $course) {
        $modinfo = course_modinfo::instance($course->id, -1);
        if ($zooms = $modinfo->get_instances_of('zoom')) {
            // Get instructor name.
            $format = course_get_format($course);
            if (method_exists($format, 'display_instructors')) {
                $instructors = $format->display_instructors();
                if (empty($instructors)) {
                    $trace->output(sprintf('No instructors for %s', $course->shortname), 2);
                    continue;
                }
                $zoomcount = count($zooms);
                $trace->output(sprintf('Found %d instructor(s) with %d Zoom meetings',
                        count($instructors), $zoomcount), 2);

                foreach ($instructors as $instructor) {
                    // Record course, instructor, and number of Zoom instances.
                    $zoomuser = [];
                    $zoomuser['course'] = $course->shortname;
                    $zoomuser['instructor'] = $instructor->lastname . ', ' . $instructor->firstname;
                    $zoomuser['email'] = $instructor->email;
                    $zoomuser['numzoom'] = $zoomcount;
                    $zoomusers[] = $zoomuser;
                }
            } else {
                $trace->output(sprintf('Course %s is not using UCLA format', $course->shortname), 2);
            }
        }
    }
}

// Open export file so that we can write the new CSV with UID added.
$writer = new csv_export_writer();
$writer->set_filename($outputfile);
$columns = ['Course', 'Instructor', 'Email', 'Zoom mtgs'];
$writer->add_data($columns);

// Output zoom users.
foreach ($zoomusers as $zoomuser) {
    $writer->add_data($zoomuser);
}

if (file_put_contents($outputfile, $writer->print_csv_data(true)) === false) {
    cli_error('Cannot write to output file: ' . $outputfile);
}

cli_writeln(sprintf('DONE! Wrote to ' . $outputfile));
