<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Ensures "Course Members" group is not in the "TA Section Materials" grouping.
 *
 * See CCLE-8805.
 *
 * @package    block_ucla_tasites
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help']) {
    $help = "Ensures \"Course Members\" group is not in the \"TA Section Materials\" grouping.

See CCLE-8805.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php blocks/ucla_tasites/cli/fix_tasitegrouping.php
";

    echo $help;
    die;
}

$trace = new text_progress_trace();

$tasitegroupingid = block_ucla_tasites::GROUPINGID;
$ppgroup = get_string('publicprivategroupname', 'local_publicprivate');

// Get "TA Section Materials" grouping containing "Course Members" group.
$sql = "SELECT groupings.id AS groupingid,
               gg.id AS ggid,
               groups.id AS groupid,
               groupings.courseid
          FROM {groupings} groupings
          JOIN {groupings_groups} gg ON (gg.groupingid=groupings.id)
          JOIN {groups} groups ON (gg.groupid=groups.id)
         WHERE groupings.idnumber=?
           AND groups.name=?";
$results = $DB->get_records_sql($sql, [$tasitegroupingid, $ppgroup]);

$trace->output(sprintf('Found %d invalid entries', count($results)));

if (!empty($results)) {
    $coursesprocesed = [];
    $tasitegroupingname = get_string('tasitegroupingname', 'block_ucla_tasites');
    foreach ($results as $result) {
        $coursesprocesed[$result->courseid] = $result->courseid;
        $course = get_course($result->courseid);
        $trace->output(sprintf('%s: Deleting "%s" group from "%s" grouping',
                $course->shortname, $ppgroup, $tasitegroupingname));
        $DB->delete_records('groupings_groups', ['id' => $result->ggid]);
    }

    // Invalidate caches.
    cache_helper::invalidate_by_definition('core', 'groupdata', array(), $coursesprocesed);
    cache_helper::purge_by_definition('core', 'user_group_groupings');
}

$trace->output('DONE!');
