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
 * Report to gather the number of courses from each of the 4 areas for a full
 * academic year:
 * Undergraduate, Graduate, Summer Session, and Administration
 *
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class moodle_usage extends uclastats_base {

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('academicyear');
    }

    /**
     * Query for undergraduate, graduate, summer session, and administrative
     * courses for given academic year.
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        // Make sure that academicyear parameter exists.
        if (!isset($params['academicyear']) ||
                !ucla_validator('academicyear', $params['academicyear'])) {
            throw new moodle_exception('invalidacademicyear', 'report_uclastats');
        }

        // Map academic year to terms.
        $terms = array();
        $terms[] = substr($params['academicyear'], 2, 2) . '1';
        $terms[] = substr($params['academicyear'], 2, 2) . 'F';
        $terms[] = substr($params['academicyear'], 7, 2) . 'W';
        $terms[] = substr($params['academicyear'], 7, 2) . 'S';

        // Get number of undergraduate, graduate, and summer courses.
        list($where, $sqlparams) = $DB->get_in_or_equal($terms, SQL_PARAMS_NAMED);
        $sql = "SELECT urc.courseid AS courseid, urci.crsidx AS crsidx,
                       urc.term AS term
                  FROM {course} c
                  JOIN {ucla_request_classes} urc
                       ON (
                           c.id = urc.courseid AND 
                           urc.hostcourse = 1
                          )
                  JOIN {ucla_reg_classinfo} urci
                       ON (
                           urci.term = urc.term AND
                           urci.srs = urc.srs AND
                           urci.enrolstat <> 'X'
                          )
                 WHERE urc.term " . $where;
        $courses = $DB->get_records_sql($sql, $sqlparams);

        $ugradcount = 0;
        $gradcount = 0;
        $summercount = 0;
        foreach ($courses as $key => $record) {
            if (is_summer_term($record->term)) {
                $summercount++;
                continue;
            }
            if (substr($record->crsidx, 1, 3) >= 200) {
                $gradcount++;
            } else {
                $ugradcount++;
            }
        }
        $retval['undergraduate'] = $ugradcount;
        $retval['graduate'] = $gradcount;
        $retval['summer'] = $summercount;

        // Get number of administrative courses.
        // Get guest role, so that we can filter out that id.
        $guestrole = get_guest_role();
        $sqlparams['guestid'] = $guestrole->id;

        $sql = "SELECT COUNT(DISTINCT c.id) AS administrative
                  FROM {course} c
                  JOIN {ucla_siteindicator} si ON (c.id = si.courseid)
                 WHERE si.type IN ('research', 'non_instruction', 'private', 'other')
                       AND c.id IN (
                           SELECT courseid
                             FROM {logstore_standard_log} l
                            WHERE userid != :guestid AND
                                  timecreated > :starttime AND
                                  timecreated < :endtime
                )";

        // Get term info.
        $summerterminfo = $this->get_term_info(substr($params['academicyear'], 2, 2) . '1');
        $sqlparams['starttime'] = $summerterminfo['start'];
        $springterminfo = $this->get_term_info(substr($params['academicyear'], 7, 2) . 'S');
        $sqlparams['endtime'] = $springterminfo['end'];

        $retval['administrative'] = $DB->count_records_sql($sql, $sqlparams);

        return array($retval);
    }

}