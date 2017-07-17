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
 * Command line script to force auto-generated forums to be private after a given term.
 *
 * Documentation: https://ccle.ucla.edu/mod/page/view.php?id=395287
 *
 * @package    local_ucla
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/lib/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'all' => false
    ),
    array(
        'h' => 'help',
        'a' => 'all'
    )
);

if ($options['help']) {
    $help = "Command line script to force auto-generated forums to be private for a given
 term.

Options:
-h, --help          Print out this help
-a, --all           Process all course forums

Example:
\$sudo -u www-data /usr/bin/php local/ucla/scripts/pp_fixforums.php [TERM]
";
    echo $help;
    die;
}

// Check if we are processing all forums or just some for a given term.
$term = null;
if (!empty($options['all'])) {
    // Validate term.
    if (empty($unrecognized) || !ucla_validator('term', $unrecognized[0])) {
        die("Must pass in a valid term.\n");
    }
    $term = $unrecognized[0];
}

$params = array(
    'discussion' => 'Discussion forum',
    'announcement' => 'Announcements',
);

// NOTE: name field is NOT indexed.
$sql = "SELECT  cm.id, urc.courseid, c.shortname, f.name
        FROM    {forum} f
        JOIN    {modules} m ON (m.name='forum')
        JOIN    {course_modules} cm ON (cm.module=m.id AND cm.instance=f.id AND f.course=cm.course)
        JOIN    {course} c ON (cm.course=c.id)
        JOIN    {ucla_request_classes} urc ON (c.id=urc.courseid)
        WHERE   ((f.type='general' AND f.name LIKE :discussion)
                OR (f.type='news' AND f.name LIKE :announcement))";

if (!empty($term)) {
    $sql .= " AND urc.term=:term";
    $params['term'] = $term;
    echo "Processing term $term\n";
} else {
    echo "Processing all forums\n";
}

$records = $DB->get_recordset_sql($sql, $params);

if ($records->valid()) {

    $publicprivatelib = $CFG->dirroot . '/local/publicprivate/lib/module.class.php';
    require_once($publicprivatelib);

    $countforums = 0;
    foreach ($records as $record) {
        // Makes a module private (if pp is enabled) and rebuilds course cache.
        if (PublicPrivate_Course::build($record->courseid)->is_activated()) {
            $ppmopdule = PublicPrivate_Module::build($record->id);
            if (!$ppmopdule->is_private()) {
                $ppmopdule->enable();
                echo "Made '" . $record->name . "' for " . $record->shortname . " private\n";
                ++$countforums;
            }
        }
    }

    echo "Made $countforums forums private.\n";
} else {
    echo "No records found. Nothing was modified.\n";
}

echo "Finished.\n";
