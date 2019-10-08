<?php
// This file is part of the UCLA MyEngineer plugin for Moodle - http://moodle.org/
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
 * Gradebook fix script.
 *
 * Some gradebook settings needed changing, so this will go through all the
 * engineering courses and set the changed config settings.
 *
 * See CCLE-8824 - Enable grade_report_user_showfeedback for Engineering courses
 *
 * @package    block_ucla_myengineer
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/course/format/lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help']) {
    $help = "Some gradebook settings needed changing, so this will go through
all the engineering courses and set the changed config settings.

See CCLE-8824 - Enable grade_report_user_showfeedback for Engineering courses

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php blocks/ucla_myengineer/cli/fix_gradebooks.php [TERM]
";

    echo $help;
    die;
}

// Get term.
$term = null;
if ($unrecognized) {
    $value = array_pop($unrecognized);
    if (ucla_validator('term', $value)) {
        $term = $value;
    }
}
if (empty($term)) {
    cli_error('Invalid term passed');
}

$trace = new text_progress_trace();

// Get all engineering courses for given term.
$sql = "SELECT DISTINCT urc.courseid
          FROM {ucla_request_classes} urc
          JOIN {ucla_reg_classinfo} urci ON (urc.term=urci.term AND urc.srs=urci.srs)
         WHERE urci.division='EN'
           AND urci.term=?";
$engineeringsites = $DB->get_recordset_sql($sql, [$term]);
$numrecords = 0;
if ($engineeringsites->valid()) {
    foreach ($engineeringsites as $course) {
        $courseid = $course->courseid;
        // Make sure that "show feedback" is set to default.
        grade_set_setting($courseid, 'report_user_showfeedback', null);

        // Only change the remaining settings of courses that have grades redirected.
        $options = course_get_format($courseid)->get_format_options();
        if ($options['myuclagradelinkredirect']) {
            grade_set_setting($courseid, 'report_user_showgradeandpercentage', 0);
            grade_set_setting($courseid, 'report_user_showcontributiontocoursetotal', 0);
        }
        ++$numrecords;
    }
}
$engineeringsites->close();
$trace->output('DONE! Processed ' . $numrecords  . ' engineering sites');
