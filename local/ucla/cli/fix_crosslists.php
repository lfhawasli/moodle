<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * CLI script to add missing cross-listed sections for a given term.
 *
 * @package    local_ucla
 * @copyright  2016 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/local/ucla/lib.php");
require_once("$CFG->dirroot/$CFG->admin/tool/uclacourserequestor/lib.php");
ucla_require_registrar();

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'fix' => false
    ),
    array(
        'h' => 'help',
        'f' => 'fix'
    )
);

if ($options['help']) {
    $help = "CLI script to add missing crosslists for a given term.

Options:
-h, --help      Print out this help
-f, --fix       Adds the missing cross-lists. If not passed, script will report
                what courses are missing crosslisted sections.

Example:
php local/ucla/cli/fix_crosslists.php <TERM>
";

    echo $help;
    die;
}

$trace = new text_progress_trace();

// Get term someone is passing us.
$term = null;
if ($unrecognized) {
    $param = array_pop($unrecognized);
    if (ucla_validator('term', $param)) {
        $term = $param;
    }
}
if (empty($term)) {
    cli_error('Missing term');
}

$trace->output('Processing term ' . $term);

// Get host courses.
$hostcourses = $DB->get_records_select('ucla_request_classes',
        'term = ? AND hostcourse=1', array($term));
$nummissing = 0;
$numfixed = 0;
foreach ($hostcourses as $hostcourse) {
    // Get crosslists that are listed at the Registrar.
    $remotecrosslists = get_crosslisted_courses($hostcourse->term, $hostcourse->srs);
    if (empty($remotecrosslists)) {
        continue;   // No need to check.
    }

    $trace->output(sprintf('Checking %s %s (%s)', $hostcourse->department, 
            $hostcourse->course, $hostcourse->srs));

    // Get crosslists that are listed in local DB.
    $localcrosslists = $DB->get_records('ucla_request_classes',
            array('setid' => $hostcourse->setid, 'hostcourse' => 0));

    // Are all remote crosslists in local crosslists?
    $missingcrosslists = array();
    foreach ($remotecrosslists as $remotecrosslist) {
        // Sometimes there is duplicate data from get_crosslisted_courses().
        if (in_array($remotecrosslist['srs'], $missingcrosslists)) {
            continue;
        }

        $foundmatch = false;
        foreach ($localcrosslists as $localcrosslist) {
            if ($localcrosslist->srs == $remotecrosslist['srs']) {
                $foundmatch = true;
                break;
            }
        }
        // Make sure it doesn't already exists in ucla_request_classes as a
        // separate request.
        if (empty($foundmatch)) {
            $exists = $DB->get_record('ucla_request_classes',
                    array('term' => $term, 'srs' => $remotecrosslist['srs']));            
            if (empty($exists)) {
                $missingcrosslists[] = $remotecrosslist['srs'];
                ++$nummissing;
            } else {
                // Report that cross-list already built.
                $trace->output(sprintf('Crosslist already built: %s %s (%s)', 
                        $exists->department, $exists->course, $exists->srs), 1);
            }
        }
    }

    if (!empty($missingcrosslists)) {
        $trace->output('Found missing crosslists: ' .
                implode(',', $missingcrosslists), 1);

        if ($options['fix']) {
            $trace->output('Fixing missing crosslists', 2);
            // Do we need to update MyUCLA urls?
            if ($hostcourse->nourlupdate == 0) {
                if (get_config('local_ucla', 'friendly_urls_enabled')) {
                    $shortname = $DB->get_field('course', 'shortname',
                            array('id' => $hostcourse->courseid));
                    $friendlyhostcourseurl = '/course/view/' . rawurlencode($shortname);
                    $classurl = $CFG->wwwroot . $friendlyhostcourseurl;
                } else {
                    $classurl = $CFG->wwwroot . '/course/view.php?id=' . $hostcourse->courseid;
                }
            }

            foreach ($missingcrosslists as $missingcrosslist) {
                $trace->output('Working on ' . $missingcrosslist, 2);

                // Add missing crosslists.
                crosslist_course_from_registrar($term, $missingcrosslist);
                $trace->output('Added to ucla_reg_classinfo', 3);

                // Now add to ucla_request_classes.
                $courseinfo = get_course_info_from_registrar($term, $missingcrosslist);
                $course = array_pop($courseinfo);
                $instrs = get_instructor_info_from_registrar($term, $missingcrosslist);

                // Follow similar setup as in prep_registrar_entry(), but we
                // cannot use that function directly.
                $request = (array)$hostcourse;
                unset($request['id']);
                $request['hostcourse'] = 0;
                $request['srs'] = $missingcrosslist;
                $request['department'] = $course['subj_area'];
                $request['course'] = get_course_from_reginfo($course);
                $request['enrolstat'] = $course['enrolstat'];
                $request['type'] = get_class_type($course);
                $DB->insert_record('ucla_request_classes', $request);
                $trace->output('Added to ucla_request_classes', 3);

                // Update MyUCLA urls for the newly updated crosslist.
                if ($hostcourse->nourlupdate == 0) {
                    update_myucla_urls($term, $missingcrosslist, $classurl);
                    $trace->output('Updated class url to ' . $classurl, 3);
                }

                ++$numfixed;
            }
        }
    }
}

$trace->output("Found $nummissing missing crosslists");
$trace->output("Fixed $numfixed crosslists");
