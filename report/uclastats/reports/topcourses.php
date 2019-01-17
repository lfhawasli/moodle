<?php
// This file is part of the UCLA stats report for Moodle - http://moodle.org/
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
 * Lists the top ten courses for each division.
 *
 * A top course is determined by the number of student activity divided by the
 * number of students. For each of these courses, the number and types of
 * activities will be listed.
 *
 * Please see CCLE-5808 for more details.
 * 
 * @package    report_uclastats
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/format/lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

/**
 * Class to perform top courses report.
 *
 * @package     report_uclastats
 * @copyright  2016 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class topcourses extends uclastats_base {

    /**
     * How many courses to choose for the top listing.
     */
    const TOPCOURSES = 10;

    /**
     * Stores what modules are activity modules and not resource. Used in
     * get_activity_mods().
     *
     * This is an array of module ids.
     *
     * @var array 
     */
    var $activitymods = null;

    /**
     * Stores the student role id. Used in get_hits_ratio().
     *
     * @var int
     */
    var $studentroleid = null;

    /**
     * Formats the stats used to generate the hits ratio.
     *
     * Sets 'hitsratio' field to a formatted string. Also will unset 'hits' and
     * 'students' values from passed in array.
     *
     * @param array     Passed by reference. Expecting array with index 'hits', 
     *                  'students', and 'hitsratio'.
     */
    private function format_hits_ratio(&$course) {
        $course['hitsratio'] = sprintf('%d (%d/%d)', round($course['hitsratio']),
                $course['hits'], $course['students']);
        unset($course['hits']);
        unset($course['students']);
    }

    /**
     * Returns ratio of student activity (log entries) divided by number of
     * students in course.
     *
     * @param int $courseid
     * @return array    Returns array with keys 'hits', 'students', and
     *                  'hitsratio'. Returns null if there are no students.
     */
    public function get_hits_ratio($courseid) {
        global $DB;

        // Get student role ID.
        if (!isset($this->studentroleid)) {
            $this->studentroleid = $DB->get_field('role', 'id',
                    array('shortname' => 'student'));        
        }

        // First get the enrolled students.
        $context = context_course::instance($courseid);
        $userfields = 'u.id, ' . get_all_user_name_fields(true, 'u');
        $students = get_role_users($this->studentroleid, $context, false, $userfields);

        if (empty($students)) {
            return null;
        }

        // We only want userids.
        $students = array_keys($students);

        // Filter out suspended users.
        $inactive = get_suspended_userids($context, false);
        $activestudents = array_diff($students, $inactive);

        if (empty($activestudents)) {
            return null;
        }

        // Count all log entries for students.
        list($useridsql, $params) = $DB->get_in_or_equal($activestudents,
                SQL_PARAMS_NAMED, 'userid');
        $params['courseid'] = $courseid;
        $sql = "SELECT COUNT(*)
                  FROM {logstore_standard_log}
                 WHERE userid $useridsql 
                   AND courseid = :courseid";
        $count = $DB->count_records_sql($sql, $params);

        return array('hits' => $count,
            'students' => count($activestudents),
            'hitsratio' => $count / count($activestudents));
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Display a table for each division.
     *
     * @param uclastats_result $uclastats_result
     * @return string
     */
    protected function get_results_table(uclastats_result $uclastats_result) {
        $retval = '';

        // Display each division in its own table.
        foreach ($uclastats_result->results as $division => $courses) {
            $retval .= html_writer::tag('h3', $division);
            $resultstable = new html_table();
            $resultstable->attributes = array('class' => 'generaltable results-table ' .
                get_class($this));

            // Add course link.
            foreach ($courses as &$course) {
                $course['shortname'] = html_writer::link(
                        new moodle_url('/course/view.php',
                                array('id' => $course['id'])), $course['shortname'],
                        array('target' => '_blank'));
                unset($course['id']);

                $this->format_hits_ratio($course);
            }

            $head = reset($courses);
            $resultstable->head = array_keys($head);
            $resultstable->data = $courses;

            $retval .= html_writer::table($resultstable);
        }

        return $retval;
    }

    /**
     * Write out results for each division.
     *
     * @param MoodleExcelWorksheet $worksheet
     * @param MoodleExcelFormat $boldformat
     * @param uclastats_result $uclastats_result
     * @param int $row      Row to start writing.
     *
     * @return int          Return row we stopped writing.
     */
    protected function get_results_xls(MoodleExcelWorksheet $worksheet,
            MoodleExcelFormat $boldformat, uclastats_result $uclastats_result, $row) {

        // Write each division in its own section.        
        foreach ($uclastats_result->results as $division => $courses) {
            $col = 0;
            $worksheet->write_string($row, $col, $division, $boldformat);
            ++$row;

            // Add course link.
            foreach ($courses as &$course) {
                unset($course['id']);
                $this->format_hits_ratio($course);
            }

            // Display table header.
            $header = reset($courses);
            $col = 0;
            foreach ($header as $name => $value) {
                $worksheet->write_string($row, $col, $name, $boldformat);
                ++$col;
            }

            // Display results.
            foreach ($courses as $course) {
                ++$row; $col = 0;
                foreach ($course as $value) {
                    // Values might have HTML in them.
                    //$value = clean_param($value, PARAM_NOTAGS);
                    if (is_numeric($value)) {
                        $worksheet->write_number($row, $col, $value);
                    } else {
                        $worksheet->write_string($row, $col, $value);
                    }
                    ++$col;
                }
            }
            $row += 2; // Put space between divisions.
        }

        return $row;
    }

    /**
     * Since this query joins on the log table, it will take a long time.
     *
     * @return boolean
     */
    public function is_high_load() {
        return true;
    }

    /**
     * Query to get the top courses by division.
     *
     * @param array $params
     * @return array
     */
    public function query($params) {
        global $DB;
        $retval = array();  // Stores the top courses, indexed by division.

        // Get list of courseids for a given term by division.
        $sql = "SELECT  c.id,
                        c.shortname,
                        urd.fullname AS division " .
                $this->from_filtered_courses(true) . "
                JOIN    {ucla_reg_division} urd ON (
                        urci.division=urd.code
                        )
                WHERE   1
                ORDER BY urd.fullname, urci.subj_area";
        $rs = $DB->get_recordset_sql($sql, $params);

        if ($rs->valid()) {
            foreach ($rs as $course) {
                $division = ucla_format_name($course->division, true);
                // Get the count of hits / enrolled students.
                $hitsratio = $this->get_hits_ratio($course->id);
                // If null, then skip it.
                if (empty($hitsratio)) {
                    continue;
                }
                $retval[$division][$course->id] = array('id' => $course->id, 
                    'shortname' => $course->shortname) + $hitsratio;
            }
            $rs->close();   // Don't need all this data anymore.

            // Order result by division and hits ratio.
            foreach ($retval as $division => $courses) {                
                uasort($courses, function ($a, $b) {
                    // Want to sort decending, with greatest first.
                    return $b['hitsratio'] - $a['hitsratio'];
                });

                // Prune divisions so that we only get the top courses.
                $courses = array_slice($courses, 0, self::TOPCOURSES, true);

                // Go through listing and query for activities.
                $this->set_activity_mods($courses);
                
                $retval[$division] = $courses;
            }
        }

        return $retval;
    }

    /**
     * Adds activity module counts for each course.
     *
     * @param array $courses Passed by reference.
     */
    public function set_activity_mods(&$courses) {
        global $DB;

        // Get list of activity modules.
        if (!isset($this->activitymods)) {
            $mods = $DB->get_records('modules', null, 'name');
            foreach ($mods as $mod) {
                // Check if module is a resource or activity.
                if (plugin_supports('mod', $mod->name, FEATURE_MOD_ARCHETYPE) !==
                        MOD_ARCHETYPE_RESOURCE) {
                    // Only resource modules return something for FEATURE_MOD_ARCHETYPE.
                    $this->activitymods[$mod->id] = $mod->name;
                }
            }
        }

        // Query for list of activities for courses.
        list($courseidsql, $cparams) = $DB->get_in_or_equal(array_keys($courses),
                SQL_PARAMS_NAMED, 'modid');
        list($modidsql, $mparams) = $DB->get_in_or_equal(array_keys($this->activitymods),
                SQL_PARAMS_NAMED, 'modid');
        $params = array_merge($cparams, $mparams);
        $sql = "SELECT CONCAT(cm.id, '-', m.id) AS idx,
                       cm.course,
                       m.id,
                       m.name,
                       COUNT(cm.id) AS count
                 FROM {course_modules} cm
                 JOIN {modules} m ON (cm.module=m.id)
                WHERE cm.course $courseidsql
                  AND m.id $modidsql
             GROUP BY cm.course, m.id";
        $activitycounts = $DB->get_records_sql($sql, $params);

        // Initial course array to include all activity mods.
        $activitymods = $this->activitymods;    // Make copy for use later.
        foreach ($courses as &$course) {
            // Add module names as keys and zero count to 0.
            foreach ($activitymods as $name) {
                $course[$name] = 0;
            }
        }

        // Go through counts and increment values. Track which mods are used.
        $usedmods = array();
        foreach ($activitycounts as $activitycount) {
            $courses[$activitycount->course][$activitycount->name] = $activitycount->count;
            $usedmods[$activitycount->name] = true;
        }

        // Prune unused modules.
        foreach ($activitymods as $activitymod) {
            if (empty($usedmods[$activitymod])) {
                foreach ($courses as &$course) {
                    unset($course[$activitymod]);
                }
            }
        }
    }

}