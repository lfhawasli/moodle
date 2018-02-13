<?php
// This file is part of the UCLA gradebook customizations plugin for Moodle - http://moodle.org/
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
 * CLI script to manually push grade information to MyUCLA.
 *
 * Usage: php grade_push.php <term or courseid> <onlyitems (optional)>
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../../config.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/grade/constants.php');
require_once($CFG->libdir . '/grade/grade_category.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/grade/grade_item.php');

// Now get cli options.
list($options, $params) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help'] || empty($params)) {
    $help =
"CLI script to manually push grade information to MyUCLA.

Options:
-h, --help            Print out this help

Usage: php grade_push.php <term or courseid> <onlyitems (optional)>
";
    echo $help;
    die;
}

$courses = array();
if (ucla_validator('term', $params[0])) {
    // Checks if paramater is a term.
    $term = $params[0];
    $results = ucla_get_courses_by_terms($params[0]);
    $courses = array_keys($results);
} else if ($result = ucla_map_courseid_to_termsrses($params[0])) {
    // Checks if parameter is a courseid.
    $courses[] = $params[0];
} else {
    cli_error('ERROR: Invalid term or courseid did not belong to a SRS course');
}

$trace = new text_progress_trace();

// When pushing grades using this script, disable logging successful grade
// updates.
$CFG->gradebook_log_success = 0;

// Check if we only want to send grade items.
$onlyitems = false;
if (!empty($params[1]) && $params[1] == 'onlyitems') {
    $onlyitems = true;
    $trace->output('NOTICE: Only sending grade items');
}

$numgradessent = 0;
$numgradeitemssent = 0;
$numcourses = 0;
foreach ($courses as $courseid) {
    $course = get_course($courseid);

    // First get all grade items for a course.
    $gradeitems = grade_item::fetch_all(array('courseid' => $courseid));
    if (empty($gradeitems)) {
        $trace->output(sprintf('NOTICE: No grade items found for course %s (%d); skipping',
                $course->shortname, $courseid));
        continue;
    } else {
        $trace->output(sprintf('Processing course %s (%d)', $course->shortname,
                $courseid));
        ++$numcourses;
    }

    foreach ($gradeitems as $gradeitem) {
        $itemname = $gradeitem->itemname ? $gradeitem->itemname : $gradeitem->itemtype;
        $trace->output(sprintf('Processing grade item %s (%d)', $itemname,
                $gradeitem->id), 1);

        // Create grade item task.
        $itemtask = new \local_gradebook\task\send_myucla_grade_item();
        $result = $itemtask->set_gradeinfo($gradeitem);
        if (empty($result)) {
            // Skip sending grades for this item.
            $trace->output('Skipping grade item', 2);
            continue;
        }

        // Now send grade item.
        try {
            $itemtask->execute();
            $trace->output('Successfully sent grade item ', 2);
            ++$numgradeitemssent;
        } catch (Exception $e) {
            $trace->output(sprintf('Failed to send grade item %d', $gradeitem->id), 2);
            cli_error(sprintf('Exception %s', $e->getMessage()));
        }

        // Check if we only want to resent grade items.
        if (!empty($onlyitems)) {
            continue;
        }

        // Next, get grades.
        $gradegrades = grade_grade::fetch_all(array('itemid' => $gradeitem->id));
        if (empty($gradegrades)) {
            $trace->output(sprintf('No grades for grade item %s (%d); skipping',
                    $gradeitem->itemname, $gradeitem->id), 1);
            continue;
        }

        // Now push each grade.
        foreach ($gradegrades as $gradegrade) {
            $trace->output(sprintf('Processing gradeid %s for userid %d',
                    $gradegrade->id, $gradegrade->userid), 1);

            $gradetask = new \local_gradebook\task\send_myucla_grade();
            $result = $gradetask->set_gradeinfo($gradegrade);
            if (empty($result)) {
                // User shouldn't have had their grade sent, skip them.
                $trace->output('Skipping grade', 2);
                continue;
            }

            // Now send grade.
            try {
                $result = $gradetask->execute();
                if ($result) {
                    $trace->output('Successfully sent grade', 2);
                } else {
                    $trace->output('Did not sent grade', 2);
                }
                ++$numgradessent;
            } catch (Exception $e) {
                $trace->output(sprintf('Failed to send grade %d', $gradegrade->id), 2);
                cli_problem(sprintf('Exception %s', $e->getMessage()));
            }
        }
    }
}

$trace->output(sprintf('Processed %d courses, sent %d grade items and %d grades to MyUCLA',
        $numcourses, $numgradeitemssent, $numgradessent));
