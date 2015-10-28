<?php
// This file is part of the UCLA stats console for Moodle - http://moodle.org/
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
 * Report to get the number of unique logins by division for a given term.
 *
 * @package    report_uclastats
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

/**
 * Logins by division report class.
 *
 * @package    report_uclastats
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logins_by_division extends uclastats_base {

    /**
     * Logged in users, indexed by userid.
     * @var array
     */
    private $loggedin = array();

    /**
     * Login counts, indexed by div code.
     * @var array
     */
    private $divisions = array();

    /**
     * Hold mapping of course to division(s), so that we don't do too many
     * queries.
     * @var array
     */
    private $coursetodivisions = array();

    /**
     * Term we are processing.
     * @var string
     */
    private $term = null;

    /**
     * Number of records to process at a time.
     *
     * @var int
     */
    const RECORDCHUNK = 5000000;

    /**
     * If user has never visited the course before for their session, then look
     * up course and increment a view count for all its related divisions.
     *
     * If the user has visited the course before, then ignore the event.
     *
     * @param object $record
     */
    private function courseviewed($record) {
        // Weird state in which we didn't catch a login event, but got a course
        // view for student. Ignore event.
        if (!isset($this->loggedin[$record->userid])) {
            // Invalid event state: User viewed before logging in.
            return;
        }

        if (!in_array($record->courseid, $this->loggedin[$record->userid])) {
            // Add visit.
            $this->loggedin[$record->userid][] = $record->courseid;
            // Find related divisions.
            $divisions = $this->get_course_divisions($record->courseid);

            // If something is returned, then we are dealing with a course site,
            // else it is a non-course and we should ignore it.
            if (!empty($divisions)) {
                // Increment view count for all related divisions for course.
                foreach ($divisions as $division) {
                    if (!isset($this->divisions[$division->code])) {
                        $this->divisions[$division->code] = 0;
                    }
                    ++$this->divisions[$division->code];
                }
            }
        }
    }

    /**
     * Instead of counting results, return a sumarized result.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        if (!empty($results)) {
            $divlogins = 0;
            foreach ($results as $result) {
                $divlogins += $result['logins'];
            }
            return $divlogins;
        }
        return get_string('nocachedresults', 'report_uclastats');
    }

    /**
     * Returns the related divisions for given course. Attempts to cache
     * information.
     *
     * @param int $courseid
     * @return array
     */
    private function get_course_divisions($courseid) {
        global $DB;
        // Is this cached?
        if (isset($this->coursetodivisions[$courseid])) {
            return $this->coursetodivisions[$courseid];
        }

        // Let's query it then.
        $sql = "SELECT DISTINCT urd.code, urd.fullname";
        $sql .= $this->from_filtered_courses();
        $sql .= $this->from_iei_courses();
        $sql .= " JOIN {ucla_reg_division} urd ON (urci.division=urd.code)
                 WHERE c.id=:courseid";
        $this->coursetodivisions[$courseid] = $DB->get_records_sql($sql, 
                array('term' => $this->term, 'courseid' => $courseid));
        return $this->coursetodivisions[$courseid];
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Querying on the Moodle logs can take a long time.
     * 
     * @return boolean
     */
    public function is_high_load() {
        return true;
    }

    /**
     * Query for logins by division for a term.
     *
     * @param array $params
     * @return array
     */
    public function query($params) {
        global $CFG, $DB;

        $retval = array();

        // Make sure that term parameter exists.
        if (!isset($params['term']) ||
                !ucla_validator('term', $params['term'])) {
            throw new moodle_exception('invalidterm', 'report_uclastats');
        }
        $this->term = $params['term'];

        // Get start and end dates for term.
        $terminfo = $this->get_term_info($params['term']);

        // Get all events matching start and end date of the term.
        $params['start'] = $terminfo['start'];
        $params['end'] = $terminfo['end'];
        $params['guestid'] = $CFG->siteguest;

        // Limit search to only events we want, to speed up query. Although
        // eventname isn't indexed, it is faster to process fewer records.
        list($inorequal, $inorequalparams) = $DB->get_in_or_equal(array(
            '\core\event\user_loggedin', '\core\event\user_loggedout',
            '\core\event\course_viewed'
        ), SQL_PARAMS_NAMED, 'eventname');
        $params = array_merge($params, $inorequalparams);

        // We cannot query all the events all at once, even with recordsets,
        // because the query will crash. We will split of the record processing
        // into chunks.
        $currentrecord = 0;
        while (true) {
            $sql = "SELECT log.id, log.eventname, log.courseid, log.userid
                      FROM {logstore_standard_log} log
                     WHERE log.timecreated >= :start
                           AND log.timecreated <= :end
                           AND log.userid > :guestid
                           AND log.eventname $inorequal
                  ORDER BY log.timecreated";
            $records = $DB->get_recordset_sql($sql, $params, $currentrecord, self::RECORDCHUNK);
            if (!$records->valid()) {
                break;
            }
            foreach ($records as $record) {
                /* The query works as follows:
                 * 1) On a user login, add user to $loggedin array
                 * 2) For any course visit event, find that courseid in the
                 *    user's entry in the $loggedin array.
                 * 3) If that courseid did not exist, add it and increment the
                 *    related divisions for the course.
                 * 4) If we see a logout or login event from the same user,
                 *    remove that user's entry from $loggedin array.
                 */
                // Have state machine to handle counting of user visits.
                switch ($record->eventname) {
                    case '\core\event\user_loggedin':
                        $this->userloggedin($record);
                        break;
                    case '\core\event\user_loggedout':
                        $this->userloggedout($record);
                        break;
                    case '\core\event\course_viewed':
                        $this->courseviewed($record);
                        break;
                    default:
                        // Ignore all other events.
                        break;
                }
            }
            $records->close();
            $currentrecord += self::RECORDCHUNK;
        }

        // Format results into alphabtized divisions.
        $divs = $DB->get_records('ucla_reg_division', null, 'fullname', 'code,fullname');
        foreach ($divs as $div) {
            // Only add to results array if there were logins for that division.
            if (!empty($this->divisions[$div->code])) {
                $retval[] = array('division' => $div->fullname,
                                  'logins'   => $this->divisions[$div->code]);
            }
        }

        return $retval;
    }

    /**
     * Add user's record from the $loggedin, if it doesn't exist. If it exists,
     * then replace it with a blank array.
     *
     * @param object $record
     */
    private function userloggedin($record) {
        if (isset($this->loggedin[$record->userid])) {
            unset($this->loggedin[$record->userid]);
        }
        $this->loggedin[$record->userid] = array();
    }

    /**
     * Remove user's record from the $loggedin, if it exists.
     *
     * @param object $record
     */
    private function userloggedout($record) {
        if (isset($this->loggedin[$record->userid])) {
            unset($this->loggedin[$record->userid]);
        }
    }

}
