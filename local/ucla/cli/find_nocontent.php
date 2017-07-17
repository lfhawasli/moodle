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
 * Generates CSV of instructors of courses with no content, including syllabi.
 *
 * @package    local_ucla
 * @copyright  2015 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/clilib.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
        array('help' => false, 'terms' => false, 'file' => false),
        array('h' => 'help', 't' => 'terms', 'f' => 'file'));

if ($options['help'] || empty($options['terms']) || empty($options['file']) || empty($options)) {
    $help = "Generates CSV of instructors of courses with no content, including syllabi.

Options:
-h, --help            Print out this help
-t, --terms           List of terms (comma delineated)
-f, --file            Location to output file

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/find_nocontent.php -t=14F,15W,15S -f=/tmp/no_content.csv
";
    cli_error($help);
}

// Get terms.
$termlist = $options['terms'];
$terms = explode(',', $termlist);
foreach ($terms as $term) {
    if (!ucla_validator('term', $term)) {
        cli_error('Invalid term: ' . $term);
    }
}

// Get file location.
$filelocation = $options['file'];
if (!is_writable(dirname($filelocation))) {
    cli_error('Cannot write to ' . dirname($filelocation));
}
$file = fopen($filelocation, 'w');
if ($file === false) {
    cli_error('Cannot write to ' . $filelocation);
}

// Get all the instructors teaching for a given list of terms.
list($termsql, $params) = $DB->get_in_or_equal($terms, SQL_PARAMS_NAMED);
$sql = "SELECT DISTINCT u.id,
               CONCAT(u.lastname, ', ', u.firstname) name,
               u.email
          FROM {user} u
          JOIN {role_assignments} ra ON (ra.userid=u.id)
          JOIN {role} r ON (r.id=ra.roleid)
          JOIN {context} cxt ON (cxt.id=ra.contextid AND cxt.contextlevel=50)
          JOIN {course} c ON (cxt.instanceid=c.id)
          JOIN {ucla_request_classes} urc ON (c.id=urc.courseid)
          JOIN {ucla_reg_classinfo} urci ON (
                    urci.term = urc.term AND
                    urci.srs = urc.srs AND
                    urci.enrolstat <> 'X'
                  )
         WHERE r.shortname='editinginstructor' AND
               urc.term $termsql";
$instructors = $DB->get_records_sql($sql, $params);
if (empty($instructors)) {
    cli_error('No instructors found for terms ' . $termlist);
}

cli_heading('Working on ' . count($instructors) . ' instructors for terms ' . $termlist);

// Get all courses with content for given terms.
$sql = "SELECT DISTINCT cm.course
          FROM {course_modules} cm
          JOIN {ucla_request_classes} urc ON (cm.course=urc.courseid)
         WHERE cm.id NOT IN (
                SELECT cm.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON (m.id=cm.module)
                  JOIN {forum} f ON (cm.instance=f.id)
                 WHERE m.name='forum' AND
                       ((f.type='news' AND f.name='Announcements') OR
                        (f.type='general' AND f.name='Discussion forum')
                       )
                ) AND
               urc.term $termsql";
$courseswithcontent = $DB->get_fieldset_sql($sql, $params);

// Get all courses with syllabi for given terms.
$sql = "SELECT DISTINCT us.courseid
          FROM {ucla_syllabus} us
          JOIN {ucla_request_classes} urc ON (us.courseid=urc.courseid)
         WHERE urc.term $termsql";
$courseswithsyllabi = $DB->get_fieldset_sql($sql, $params);

// Go through each instructor and find the courses they teach.
$numentries = 0;
foreach ($instructors as $instructor) {
    // Add instructor id to term param list.
    $params['instructor'] = $instructor->id;

    $sql = "SELECT DISTINCT c.id
              FROM {user} u
              JOIN {role_assignments} ra ON (ra.userid=u.id)
              JOIN {role} r ON (r.id=ra.roleid)
              JOIN {context} cxt ON (cxt.id=ra.contextid AND cxt.contextlevel=50)
              JOIN {course} c ON (cxt.instanceid=c.id)
              JOIN {ucla_request_classes} urc ON (c.id=urc.courseid)
              JOIN {ucla_reg_classinfo} urci ON (
                        urci.term = urc.term AND
                        urci.srs = urc.srs AND
                        urci.enrolstat <> 'X'
                      )
             WHERE r.shortname='editinginstructor' AND
                   urc.term $termsql AND
                   u.id=:instructor";
    $courses = $DB->get_fieldset_sql($sql, $params);

    // See if instructor has zero courses with either content or syllabi.
    $hasnocontent = true;
    foreach ($courses as $courseid) {
        if (in_array($courseid, $courseswithcontent) ||
                in_array($courseid, $courseswithsyllabi)) {
            $hasnocontent = false;
            break;
        }
    }

    if ($hasnocontent) {
        fwrite($file, sprintf("\"%s\",%s\n", $instructor->name, $instructor->email));
        ++$numentries;
    }
}

cli_heading('DONE! Wrote ' . $numentries . ' entries to ' . $filelocation);
