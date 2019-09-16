<?php
// This file is part of the UCLA stats plugin for Moodle - http://moodle.org/
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
 * Report the number of student visits on Course reserves versus Research Guide tabs
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class library_reserves_visits extends uclastats_base {
    /**
     * Count results.
     *
     * @param array $results
     * @return int
     */
    public function format_cached_results($results) {
        if (!empty($results)) {
            return count($results);
        }
        return get_string('nocachedresults', 'report_uclastats');
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Querying on logstore_standard_log can take a long time.
     *
     * @return boolean
     */
    public function is_high_load() {
        return true;
    }

    /**
     * Query to get enrolled students in course and analyze their views on the two tabs.
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $sql = "SELECT
                    urc.courseid AS course_id,
                    urc.department AS subjarea,
                    urc.course AS coursenumber,
                    cs.countstudents AS totalstudents,
                    IFNULL(l_rg.rgv, 0) AS resguideviewcount,
                    CAST((IFNULL(l_rg.rgv, 0) * 100 / cs.countstudents) AS DECIMAL(5,2)) AS resguideviewpercent,
                    IFNULL(l_cr.crv, 0) AS coursereservesviewcount,
                    CAST((IFNULL(l_cr.crv, 0) * 100 / cs.countstudents) AS DECIMAL(5,2)) AS coursereservesviewpercent"

                  .$this->from_filtered_courses().
                  "JOIN (SELECT COUNT(DISTINCT ra.userid) AS countstudents, ctx.instanceid AS courseid
                          FROM {context} ctx
                          JOIN {role_assignments} ra ON ra.contextid=ctx.id
                          JOIN {user_enrolments} ue ON (ra.itemid=ue.enrolid AND ra.component='enrol_database')
                          JOIN {role} r ON r.id=ra.roleid
                         WHERE ctx.contextlevel=50 AND
                               r.shortname='student'
                      GROUP BY ctx.instanceid) cs
                    ON urc.courseid=cs.courseid
                  JOIN (SELECT COUNT(DISTINCT l.userid) AS rgv, l.courseid AS courseid
                          FROM {logstore_standard_log} l
                         WHERE l.target LIKE 'researchguide_index' AND
                               l.userid IN (SELECT ra.userid
                                              FROM {context} ctx
                                              JOIN {role_assignments} ra ON ra.contextid=ctx.id
                                              JOIN {user_enrolments} ue ON (ra.itemid=ue.enrolid AND ra.component='enrol_database')
                                              JOIN {role} r ON r.id=ra.roleid
                                             WHERE ctx.instanceid=l.courseid AND ctx.contextlevel=50 AND
                                                   r.shortname='student')
                      GROUP BY l.courseid) l_rg
                    ON urc.courseid=l_rg.courseid
             LEFT JOIN (SELECT COUNT(DISTINCT l.userid) AS crv, l.courseid AS courseid
                          FROM {logstore_standard_log} l
                         WHERE l.target LIKE 'coursereserves_index' AND
                               l.userid IN (SELECT ra.userid
                                              FROM {context} ctx
                                              JOIN {role_assignments} ra ON ra.contextid=ctx.id
                                              JOIN {user_enrolments} ue ON (ra.itemid=ue.enrolid AND ra.component='enrol_database')
                                              JOIN {role} r ON r.id=ra.roleid
                                             WHERE ctx.instanceid=l.courseid AND ctx.contextlevel=50 AND
                                                   r.shortname='student')
                      GROUP BY l.courseid) l_cr
                    ON l_rg.courseid=l_cr.courseid

              GROUP BY urc.courseid";

        $results = $DB->get_records_sql($sql, $params);

        return $results;
    }
}