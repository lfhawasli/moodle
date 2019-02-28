<?php
// This file is part of the UCLA public/private plugin for Moodle - http://moodle.org/
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
 * Combs through courses and cleans up public/private membership.
 *
 * @package    local_publicprivate
 * @copyright  2015 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');
require_once($CFG->dirroot.'/lib/accesslib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'courseid' => false,
        'days' => false,
        'help' => false
    ),
    array(
        'd' => 'days',
        'c' => 'courseid',
        'h' => 'help'
    )
);

if ($unrecognized) {
    echo "Unrecognized arguments found!\n";

    if (!empty($unrecognized)) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }
}

if ($options['help']) {
    $help =
    "Description:
Combs through courses and cleans up group membership based on roles and enrolment plugins.
By default, checks all courses with publicprivate enabled.

Options:
-c, --courseid=COURSEID Cleans specified course
-d, --days=DAYS         Cleans only publicprivate enabled courses whose enrolment methods
                        that have been modified in the last 'DAYS' days.
-h, --help              Print out this help

Examples:
\$cleanup.php -c=[COURSEID]
    cleans the specified course.
\$cleanup.php -d=[DAYS] -v
    only cleans courses whose enrolment methods have been modified within the last 'DAYS'
    days and prints progress information.
";

    echo $help;
    die;
}

$idgiven = !empty($options['courseid']) && $options['courseid'] !== true;
$daysgiven = !empty($options['days']) && $options['days'] !== true;

if ($idgiven) {
    // Get a single course.
    $c = get_course(intval($options['courseid']));
    if (empty($c)) {
        cli_error('Failed to get course, check course id.');
    }
    if ($c->enablepublicprivate != 1) {
        cli_error('Publicprivate not enabled on specified course');
    }
    $courses = $DB->get_recordset('course', array('id' => intval($options['courseid'])),
            'id,enablepublicprivate,grouppublicprivate,groupingpublicprivate');
} else if ($daysgiven) {
    $t = time() - ($options['days'] * DAYSECS);
    // We don't want to get newly created enrolment plugins, just updated ones.
    $sql = "SELECT c.id,
                   c.enablepublicprivate,
                   c.grouppublicprivate,
                   c.groupingpublicprivate
              FROM {course} c
              JOIN {enrol} e ON (e.courseid=c.id)
             WHERE e.timemodified >= ? AND
                   c.enablepublicprivate=1 AND
                   e.timecreated!=e.timemodified";
    $courses = $DB->get_recordset_sql($sql, array($t));
} else {
    // Get all courses.
    // We only need id, grouppublicprivate, enablepublicprivate, groupingpublicprivate.
    $courses = $DB->get_recordset('course', array('enablepublicprivate' => 1), '',
            'id,enablepublicprivate,grouppublicprivate,groupingpublicprivate');
}

$task = new \local_publicprivate\task\cleanup();
$task->fix_membership($courses);

cli_heading('DONE!');
