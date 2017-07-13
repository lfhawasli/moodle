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
 * One off command line script to manually hide courses and related TA sites for
 * a given term. Makes sure that guest enrollment plugins are disabled and
 * will not touch past courses that were made unhidden.
 *
 * Documentation: https://ccle.ucla.edu/mod/page/view.php?id=395287
 *
 * @package    local_ucla
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/lib/clilib.php');

list($options, $unrecognized) = cli_get_params(
        array(
    'help' => false
        ), array(
    'h' => 'help'
        )
);

if ($options['help']) {
    $help = "Command line script to manually hide courses and related TA sites for a given
 term.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/scripts/rehide_courses.php [TERM]
";
    echo $help;
    die;
}

// Make sure that first parameter is a term.
if (empty($unrecognized) || !ucla_validator('term', $unrecognized[0])) {
    die("Must pass in a valid term.\n");
}
$term = $unrecognized[0];

echo "Hiding courses for term: " . $term . "\n";

list($numhiddencourses, $numhiddentasites, $numproblemcourses,
        $numskippedcourses, $errormessages) = rehide_courses($term);

echo sprintf("Hid %d courses.\n", $numhiddencourses);
echo sprintf("Hid %d TA sites.\n", $numhiddentasites);
echo sprintf("Had %d problem courses.\n", $numproblemcourses);
echo sprintf("Had %d skipped courses.\n", $numskippedcourses);
echo $errormessages;
die("\nDONE!\n");

/**
 * Exactly like hide_courses, but will not touch unhidden courses.
 *
 * @param string $term
 *
 * @return mixed            Returns false on invalid term. Otherwise returns an
 *                          array of $numhiddencourses, $numhiddentasites,
 *                          $numproblemcourses, $numskippedcourses,
 *                          $errormessages.
 */
function rehide_courses($term) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
    require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');

    if (!ucla_validator('term', $term)) {
        return false;
    }

    // Track some stats.
    $numhiddencourses = 0;
    $numhiddentasites = 0;
    $numproblemcourses = 0;
    $numskippedcourses = 0;
    $errormessages = '';

    // Get list of courses to hide.
    $courses = ucla_get_courses_by_terms(array($term));

    if (empty($courses)) {
        // No courses to hide.
        return array($numhiddencourses, $numhiddentasites,
            $numproblemcourses, $numskippedcourses,
            $errormessages);
    }

    $enrolguestplugin = enrol_get_plugin('guest');

    // Now run command to hide all courses for given term. Don't worry about
    // updating visibleold (don't care) and we aren't using update_course,
    // because if might be slow and trigger unnecessary events.
    $courseobj = new stdClass();
    $courseobj->visible = 0;
    foreach ($courses as $courseid => $courseinfo) {
        $coursesprocessed = array($courseid);
        $courseobj->id = $courseid;
        try {
            ++$numhiddencourses;

            // Try to see if course had any TA sites.
            $existingtasites = block_ucla_tasites::get_tasites($courseid);
            if (!empty($existingtasites)) {
                foreach ($existingtasites as $tasite) {
                    ++$numhiddentasites;
                    $coursesprocessed[] = $tasite->id;
                }
            }

            // Hide courses and guest plugins.
            foreach ($coursesprocessed as $courseid) {
                $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
                if ($course->visible == 1) {
                    ++$numskippedcourses;
                    continue;
                }

                $courseobj->id = $courseid;
                $DB->update_record('course', $courseobj, true);

                PublicPrivate_Course::set_guest_plugin($course, ENROL_INSTANCE_DISABLED);
            }
        } catch (dml_exception $e) {
            $errormessages .= sprintf("Could not hide courseid %d\n%s\n",
                    $courseobj->id, $e->getMessage());
            ++$numproblemcourses;
        }
    }

    return array($numhiddencourses, $numhiddentasites,
        $numproblemcourses, $numskippedcourses, $errormessages);
}
