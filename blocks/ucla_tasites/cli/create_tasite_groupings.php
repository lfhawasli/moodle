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
 * This script creates TA-specific grouping for TA sites.
 *
 * @package    block_ucla_tasites
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');

// Get CLI options.
list($options, $unrecognized) = cli_get_params(
    array(
        'courseid'      => false,
        'current-term'  => false,
        'term'          => false,
        'help'          => false
    ),
    array(
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Create \"TA Section Materials\" grouping for TA sites.

Options:
--courseid          Course ID(s).
--current-term      Current term.
--term              Term(s).
-h, --help          Print out this help.

Examples:
php blocks/ucla_tasites/cli/create_tasite_groupings.php
php blocks/ucla_tasites/cli/create_tasite_groupings.php --courseid=1234
php blocks/ucla_tasites/cli/create_tasite_groupings.php --current-term
php blocks/ucla_tasites/cli/create_tasite_groupings.php --term=19W
";

    echo $help;
    die;
}

$where = "WHERE e.enrol = 'meta'";
$join = '';
$courseparams = array();
$termparams = array();

// Process course ID.
$courselist = explode(',', $options['courseid']);
if (!empty($courselist)) {
    list($coursesql, $courseparams) = $DB->get_in_or_equal($courselist, SQL_PARAMS_NAMED, 'courseid');
    $where .= ' AND e.courseid ' . $coursesql;
}

// Process term.
// If no term specified, skip joining ucla_request_classes table and query all meta sites.
if ($options['term'] || $options['current-term'] && !empty($CFG->currentterm)) {
    $termlist = explode(',', $options['term']);
    // If no term option, then current-term must be true.
    if (empty($termlist)) {
        $termlist = array($CFG->currentterm);
    }
    list($termsql, $termparams) = $DB->get_in_or_equal($termlist, SQL_PARAMS_NAMED, 'term');
    $join = "JOIN {course} c ON c.id = e.customint1
             JOIN {ucla_request_classes} urc ON urc.courseid = c.id ";
    $where .= ' AND urc.term ' . $termsql;
}

// Make DB call.
$params = array_merge($courseparams, $termparams);
$courses = $DB->get_records_sql('SELECT DISTINCT e.courseid,e.customint1,e.customtext1
    FROM {enrol} e ' . $join . $where, $params);
if (!$courses) {
    cli_error('No courses found.');
}

foreach ($courses as $course) {
    $courseid = $course->courseid;
    if (block_ucla_tasites::is_tasite($courseid)) {
        if (block_ucla_tasites::has_tagrouping($courseid)) {
            echo "TA-specific grouping for course {$courseid} already exists.\n";
        } else {
            $parentcourseid = $course->customint1;

            // Get TA info from mappings.
            $tasitemapping = block_ucla_tasites::get_tasection_mapping($parentcourseid);
            $tafullname = block_ucla_tasites::get_tafullname($course->customtext1);

            $groupingcreated = false;
            if (!empty($tasitemapping['byta'][$tafullname])) {
                $tasecsrs = $tasitemapping['byta'][$tafullname]['secsrs'];
                if (!empty($tasecsrs)) {
                    $srsarray = array();
                    // If course has sections, then handle multiple srs numbers.
                    foreach ($tasecsrs as $secnum => $srsnum) {
                        $secnums[] = block_ucla_tasites::format_sec_num($secnum);
                        $srsarray[$secnum] = $srsnum;
                    }
                    if (!empty($srsarray)) {
                        // Create grouping.
                        block_ucla_tasites::create_taspecificgrouping($parentcourseid,
                            $courseid, $srsarray, null, false);
                        $groupingcreated = true;
                        echo "Created TA-specific grouping for course {$courseid}.\n";
                    }
                }
            }
            if (!$groupingcreated) {
                echo "Could not create TA-specific grouping for course {$courseid}.\n";
            }
        }
    } else {
        echo "Course {$course} is not a TA site.\n";
    }
}
