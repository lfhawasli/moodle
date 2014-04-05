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
 * Report to get the number of syllabi by division, term, ugrad/grad, and type.
 * 
 * @package     report_uclastats
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

/**
 * Class to perform syllabus by division report.
 *
 * @package     report_uclastats
 * @copyright   2014 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class syllabus_by_division extends uclastats_base {

    /**
     * Given a row for division or system totals, calculate percentages.
     *
     * @param array $row
     * @return array        Returns $row with percentages calculated.
     */
    protected function calculate_percentages($row) {
        $retval = $this->init_row($row['division']);

        // Syllabus/Courses column.
        if (!empty($row['totalsyllabuscourses'])) {
            $retval['totalsyllabuscourses'] = sprintf('%d/%d (%d%%)',
                    $row['syllabuscourses'],
                    $row['totalsyllabuscourses'],
                    round(($row['syllabuscourses']/
                           $row['totalsyllabuscourses'])*100));
        }

        // For the syllabus types, calulate percentages using syllabuscourses.
        foreach (array('syllabuspublic',  'syllabusloggedin',
                       'syllabusprivate', 'syllabusmanual') as $type) {
            if (!empty($row['syllabuscourses'])) {
                $retval[$type] = sprintf('%d/%d (%d%%)',
                        $row[$type],
                        $row['syllabuscourses'],
                        round(($row[$type]/
                               $row['syllabuscourses'])*100));
            }
        }

        // For preview syllabi, calculate percentages using syllabuspublic +
        // syllabusloggedin totals.
        $totalpublic = $row['syllabuspublic'] + $row['syllabusloggedin'];
        if (!empty($totalpublic)) {
            $retval['syllabuspreview'] = sprintf('%d/%d (%d%%)',
                    $row['syllabuspreview'],
                    $totalpublic,
                    round(($row['syllabuspreview']/
                           $totalpublic)*100));            
        }

        // Remove 'syllabuscourses', since it is now part of the other columns.
        unset($retval['syllabuscourses']);

        return $retval;
    }

    /**
     * Display start/end dates in a human-readable format.
     *
     * @param array $params
     * @return string
     */
    public function format_cached_params($params) {
        $param_list = array();
        foreach ($params as $name => $value) {
            // Skip empty start/end dates.
            if (empty($value)) {
                continue;
            }
            
            // If a start/end date is given, the format it to be human readable.
            if ($name == 'startdate' || $name == 'enddate') {
                $value = date('M j, Y', $value);
            }

            $param_list[] = get_string($name, 'report_uclastats') . ' = ' .
                    $value;
        }
        return implode(', ', $param_list);
    }

    /**
     * Display totals for ugrad and grad.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        $a = new stdClass();
        $a->ugrad = $results['ugrad']['SYSTEM']['totalsyllabuscourses'];
        $a->grad = $results['grad']['SYSTEM']['totalsyllabuscourses'];
        
        return get_string('syllabuscachedresult', 'report_uclastats', $a);
    }

    /**
     * Returns header to use for results tables.
     *
     * @return array
     */
    protected function get_header() {
        $retval = array();
        $row = $this->init_row('');
        unset($row['syllabuscourses']);
        foreach(array_keys($row) as $column) {
            $retval[] = get_string($column, 'report_uclastats');
        }

        return $retval;
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term', 'optionaldatepicker');
    }

    /**
     * Display two results tables. One for ugrad and the other for grad.
     *
     * @param uclastats_result $uclastats_result
     * @return string
     */
    protected function get_results_table(uclastats_result $uclastats_result) {
        $retval = '';
        $results = $uclastats_result->results;

        // Process ugrad and ugrad.
        foreach ($results as $type => $data) {
            $retval .= html_writer::tag('h3', get_string($type, 'report_uclastats'));

            $resultstable = new html_table();
            $resultstable->head = $this->get_header();
            $resultstable->data = $data;

            $retval .= html_writer::table($resultstable);
        }       

        return $retval;
    }

    /**
     * Display two results tables. One for ugrad and the other for grad.
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

        $results = $uclastats_result->results;

        // Process ugrad and ugrad.
        $firstentry = true;
        foreach ($results as $type => $data) {
            $col = 0;

            // Give space between tables.
            $firstentry ? $firstentry = false : $row += 2;

            // Table header.
            $worksheet->write_string($row, $col,
                    get_string($type, 'report_uclastats'), $boldformat);
            $row++;

            // Table results.
            $header = $this->get_header();
            foreach ($header as $name) {
                $worksheet->write_string($row, $col, $name, $boldformat);
                ++$col;
            }

            // Now go through result set.
            foreach ($data as $result) {
                ++$row; $col = 0;
                foreach ($result as $value) {
                    $worksheet->write_string($row, $col, $value);
                    ++$col;
                }
            }
        }

        return $row;
    }

    /**
     * Initialize a syllabus row for given division.
     *
     * @param string $division
     * @return array
     */
    protected function init_row($division) {
        return array('division'         => $division, 'totalsyllabuscourses' => 0,
                     'syllabuscourses'  => 0,         'syllabuspublic'       => 0,
                     'syllabusloggedin' => 0,         'syllabuspreview'      => 0,
                     'syllabusprivate'  => 0,         'syllabusmanual'       => 0);
    }

    /**
     * Query get the number of active/inactive course sites by division.
     *
     * @param array $params
     * @return array
     */
    public function query($params) {
        global $DB;
        $retval = array();
        $grad = $ugrad = array();

        // Handle any time restrictions.
        $timestart = $timeend = null;
        if (!empty($params['startdate'])) {
            $timestart = $params['startdate'];
        }
        if (!empty($params['enddate'])) {
            $timeend = $params['enddate'];
        }        

        // Get list of courseids for a given term by division.
        $sql = "SELECT DISTINCT urci.id,
                       c.id AS courseid,
                       urci.subj_area,
                       urci.crsidx AS catalognum,
                       urd.fullname AS division " 
                     . $this->from_filtered_courses(false) .
                " JOIN {ucla_reg_division} urd ON (urci.division=urd.code)
                 WHERE urci.acttype != 'TUT'
              ORDER BY division";
        $rs = $DB->get_recordset_sql($sql, array('term' => $params['term']));

        if ($rs->valid()) {
            // We might be querying the same courseid multiple times.
            $syllabuscache = array();
            // Pointer to array we are incrementing.
            $workingbin = null;
            foreach ($rs as $course) {
                // Is this a grad course?
                if (intval(preg_replace("/[a-zA-Z]+/", '', $course->catalognum)) >= 200) {
                    $workingbin = &$grad;
                } else {
                    $workingbin = &$ugrad;
                }

                // Initialize array for a given division.
                $division = ucla_format_name($course->division, true);
                if (!isset($workingbin[$division])) {
                    // We want the result columns to display in a certain order.
                    $workingbin[$division] = $this->init_row($division);
                }

                // Increment course count.
                ++$workingbin[$division]['totalsyllabuscourses'];

                // Properly bin syllabus information.
                if (!isset($syllabuscache[$course->courseid])) {
                    $select = 'courseid = :courseid';
                    $selectparams = array('courseid' => $course->courseid);

                    // Handle any time restrictions.
                    if (!empty($timestart)) {
                        $select .= ' AND timecreated >= :timestart';
                        $selectparams['timestart'] = $timestart;
                    }
                    if (!empty($timeend)) {
                        $select .= ' AND timecreated <= :timeend';
                        $selectparams['timeend'] = $timeend;
                    }

                    $syllabuscache[$course->courseid] = 
                            $DB->get_records_select('ucla_syllabus', $select,
                                    $selectparams);
                }

                $syllabi = $syllabuscache[$course->courseid];
                if (!empty($syllabi)) {
                    // course has a syllabus, let's count it
                    ++$workingbin[$division]['syllabuscourses'];

                    foreach ($syllabi as $syllabus) {
                        if (!empty($syllabus->is_preview)) {
                            ++$workingbin[$division]['syllabuspreview'];
                        }
                        switch ($syllabus->access_type) {
                            case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
                                ++$workingbin[$division]['syllabuspublic'];
                                break;
                            case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
                                ++$workingbin[$division]['syllabusloggedin'];
                                break;
                            case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
                                ++$workingbin[$division]['syllabusprivate'];
                                break;
                            default:
                                break;
                        }
                    }
                }

                // Check if there were any manual syllabi.
                $courseobj = new stdClass();
                $courseobj->id = $course->courseid;
                $courseobj->maxbytes = 0;
                $syllabusmanager = new ucla_syllabus_manager($courseobj);
                $manualsyllabi = $syllabusmanager->get_all_manual_syllabi($timestart, $timeend);
                if (!empty($manualsyllabi)) {
                    ++$workingbin[$division]['syllabusmanual'];
                    // Only increment number of courses that have syllabi if
                    // syllabus tool wasn't used.
                    if (empty($syllabi)) {
                        ++$workingbin[$division]['syllabuscourses'];
                    }
                }
                unset($syllabusmanager);
            }

            // Now figure out percentages and system totals.
            $retval = array('ugrad' => $ugrad, 'grad' => $grad);
            foreach ($retval as &$data) {
                // Prepare system totals.
                $system = $this->init_row('SYSTEM');

                foreach ($data as $index => $row) {
                    // Add row to system totals.
                    $columns = array_keys($system);
                    unset($columns[0]); // Skip division.
                    foreach ($columns as $column) {
                        $system[$column] += $row[$column];
                    }

                    $data[$index] = $this->calculate_percentages($row);
                }

                // Add system totals at the end.
                $data['SYSTEM'] = $this->calculate_percentages($system);
            }
        }

        return $retval;
    }
}
