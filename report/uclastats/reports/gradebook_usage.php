<?php
/**
 * Report to get the number of courses using the Gradebook.
 *
 * @package    report_uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class gradebook_usage extends uclastats_base {
    /**
     * Instead of counting results, but return actual count.
     *
     * @param array $results
     * @return int
     */
    public function format_cached_results($results) {
        if (!empty($results)) {
            return $results['SYSTEM']['usedgradebook'];
        }
        return get_string('nocachedresults', 'report_uclastats');
    }

    /**
     * Returns list of courses for a given term.
     *
     * @param string $term
     *
     * @return array
     */
    private function get_courses($term) {
        global $DB;

        $sql = "SELECT  DISTINCT c.id,
                        rd.code,
                        rd.fullname" .
                $this->from_filtered_courses(true)
                ."
                JOIN    {ucla_reg_division} rd ON rd.code=urci.division
                WHERE   1
                ORDER BY    rd.fullname";
        $results = $DB->get_records_sql($sql, array('term' => $term));

        return $results;
    }
    /**
     * Returns list of courses for a given term that have exported grades.
     *
     * @param string $term
     *
     * @return array
     */
    private function get_exported_grades($term) {
        global $DB;

        $sql = "SELECT  DISTINCT c.id,
                        c.shortname,
                        urci.division " .
                $this->from_filtered_courses(true)
                ."
                JOIN    {logstore_standard_log} l ON l.courseid=c.id
                WHERE   l.target='grades_exported'";
        $results = $DB->get_records_sql($sql, array('term' => $term));

        return $results;
    }

    /**
     * Returns list of courses for a given term that have graded grade items.
     *
     * @param string $term
     *
     * @return array
     */
    private function get_graded_items($term) {
        global $DB;

        $sql = "SELECT  DISTINCT c.id,
                        c.shortname,
                        urci.division " .
                $this->from_filtered_courses(true)
                ."
                JOIN    {grade_items} gi ON gi.courseid=c.id
                JOIN    {grade_grades} gg ON gi.id=gg.itemid
                WHERE   gg.rawgrade IS NOT NULL";
        $results = $DB->get_records_sql($sql, array('term' => $term));

        return $results;
    }

    /**
     * Returns list of courses for a given term that have overridden grades or
     * grade items.
     *
     * @param string $term
     *
     * @return array
     */
    private function get_overridden_grades($term) {
        global $DB;

        $sql = "SELECT  DISTINCT c.id,
                        c.shortname,
                        urci.division " .
                $this->from_filtered_courses(true)
                ."
                JOIN    {grade_items} gi ON gi.courseid=c.id
                JOIN    {grade_grades} gg ON gi.id=gg.itemid
                WHERE   gg.overridden!=0";
        $results = $DB->get_records_sql($sql, array('term' => $term));

        return $results;
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Query to get the courses using the Moodle gradebook for a given term
     * broken down by division.
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $results = array();
        $courselisting = array();

        // Make sure that term parameter exists.
        if (!isset($params['term']) ||
                !ucla_validator('term', $params['term'])) {
            throw new moodle_exception('invalidterm', 'report_uclastats');
        }

        // Get list of all courses.
        $allcourses = $this->get_courses($params['term']);
        if (empty($allcourses)) {
            return $results;    // Running on a site with no courses built.
        }
        // Get courses that match the following gradebook "usage" scenario.

        // Scenario 1: Courses that have graded grade items.
        $grades = $this->get_graded_items($params['term']);

        // Scenario 2: Courses that have overridden grades.
        $overridden = $this->get_overridden_grades($params['term']);

        // Scenario 3: Courses that have had their grades exported.
        $exported = $this->get_exported_grades($params['term']);

        $usedcourses = array_merge($grades, $overridden, $exported);
        if (empty($usedcourses)) {
            return $results;
        }

        // Built results array. Each array row should have courseid and
        // division, so built array of results indexed by division code. Create
        // array of courseids for used and total. Then we will do an
        // array_unique on each division's used/total columns and replace the
        // value with the array size.

        // Get total counts.
        foreach ($allcourses as $course) {
            if (!isset($results[$course->code])) {
                $results[$course->code]['division']
                        = ucla_format_name($course->fullname, true);
                $results[$course->code]['gradeditems'] = array();
                $results[$course->code]['overriddengrades'] = array();
                $results[$course->code]['exportedgrades'] = array();
                $results[$course->code]['usedgradebook'] = array();
            }
            $results[$course->code]['totalcourses'][] = $course->id;
        }

        // Then use those division totals to count stats counts.
        foreach ($grades as $course) {
            $results[$course->division]['gradeditems'][] = $course->id;
        }
        foreach ($overridden as $course) {
            $results[$course->division]['overriddengrades'][] = $course->id;
        }
        foreach ($exported as $course) {
            $results[$course->division]['exportedgrades'][] = $course->id;
        }
        foreach ($usedcourses as $course) {
            $results[$course->division]['usedgradebook'][] = $course->id;

            // Get data for courselisting.
            $division = ucla_format_name($allcourses[$course->id]->fullname, true);
            $courselisting[$course->id] = array('division' => $division,
                        'shortname' => $course->shortname, 'grades' => 'N',
                        'overridden' => 'N', 'exported' => 'N');
            if (in_array($course->id,  $results[$course->division]['gradeditems'])) {
                    $courselisting[$course->id]['grades'] = 'Y';
            }
            if (in_array($course->id,  $results[$course->division]['overriddengrades'])) {
                    $courselisting[$course->id]['overridden'] = 'Y';
            }
            if (in_array($course->id,  $results[$course->division]['exportedgrades'])) {
                    $courselisting[$course->id]['exported'] = 'Y';
            }
        }

        // Now unique and sum of counts.
        $numgradeditems = $numoverriddengrades = $numexportedgrades =
                $numusedgradebook = $numtotalcourse = 0;

        foreach ($results as &$result) {
            $count = array_unique($result['gradeditems']);
            $result['gradeditems'] = count($count);

            $count = array_unique($result['overriddengrades']);
            $result['overriddengrades'] = count($count);

            $count = array_unique($result['exportedgrades']);
            $result['exportedgrades'] = count($count);

            $count = array_unique($result['usedgradebook']);
            $result['usedgradebook'] = count($count);

            $count = array_unique($result['totalcourses']);
            $result['totalcourses'] = count($count);

            $numgradeditems += $result['gradeditems'];
            $numoverriddengrades += $result['overriddengrades'];
            $numexportedgrades += $result['exportedgrades'];
            $numusedgradebook += $result['usedgradebook'];
            $numtotalcourse += $result['totalcourses'];
        }

        // Sort courselisting by id.
        ksort($courselisting);

        // Last row should be system totals.
        $results['SYSTEM']['division'] = 'SYSTEM TOTALS';
        $results['SYSTEM']['gradeditems'] = $numgradeditems;
        $results['SYSTEM']['overriddengrades'] = $numoverriddengrades;
        $results['SYSTEM']['exportedgrades'] = $numexportedgrades;
        $results['SYSTEM']['usedgradebook'] = $numusedgradebook;
        $results['SYSTEM']['totalcourses'] = $numtotalcourse;
        $results['courselisting'] = $courselisting;

        return $results;
    }



    /**
     * Display two results tables. One, for the inactive courses by division,
     * and, two, a list of the inactive courses for spot checking.
     *
     * @param uclastats_result $uclastatsresult
     * @return string
     */
    protected function get_results_table(uclastats_result $uclastatsresult) {
        $retval = '';

        $results = $uclastatsresult->results;
        $courselisting = $results['courselisting'];
        unset($results['courselisting']);

        // Aggregated results.
        $resultstable = new html_table();
        $resultstable->id = 'uclastats-results-table';
        $resultstable->attributes = array('class' => 'generaltable results-table ' .
            get_class($this));

        $resultstable->head = $uclastatsresult->get_header();
        $resultstable->data = $results;

        $retval = html_writer::table($resultstable);

        $retval .= html_writer::tag('h3', get_string('gradebook_usage', 'report_uclastats'));

        // Course listing.
        $listingtable = new html_table();
        $listingtable->id = 'uclastats-courselisting-table';

        $listingtable->head = array(get_string('division', 'report_uclastats'),
                get_string('course_shortname', 'report_uclastats'),
                get_string('gradeditems', 'report_uclastats')."&nbsp;&nbsp;&nbsp;&nbsp;",
                get_string('overriddengrades', 'report_uclastats')."&nbsp;&nbsp;&nbsp;&nbsp;",
                get_string('exportedgrades', 'report_uclastats'));
        foreach ($courselisting as $courseid => $course) {
            $courselisting[$courseid]['shortname'] = html_writer::link(
                    new moodle_url('/course/view.php',
                            array('id' => $courseid)), $course['shortname'],
                    array('target' => '_blank'));
        }
        $listingtable->data = $courselisting;
        $retval .= html_writer::table($listingtable);

        return $retval;
    }
}
