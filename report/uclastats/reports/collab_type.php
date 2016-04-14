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
 * Report to getcount of the different collaboration site types and totals for
 * each division.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;


require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasiteindicator/lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');
require_once($CFG->libdir . '/coursecatlib.php');

class collab_type extends uclastats_base {
    
    /**
     * Given a category id, try to work through the category and its parents
     * trying to find a corresponding name match in the given division list.
     *
     * Method starts look at the root category.
     *
     * @param int $categoryid     Category ID.
     * @param array $divisions  Array of divisions to match.
     * @return string           Returns matching division name. If there are no
     *                          matches, will return null.
     */
    private function find_division($categoryid, $divisions) {

        // Need to set $alwaysreturnhidden for coursecat::get to true, so this
        // report can be run from the command line.
        $category = coursecat::get($categoryid, MUST_EXIST, true);

        // First check if category has any parents.
        $parents = $category->get_parents();
        if (!empty($parents)) {
            // Has parents, so check the top most parent and then go down the
            // list looking for a name match with a division.
            foreach ($parents as $parentid) {
                // Top most parent is at the start of the array.
                $parent = coursecat::get($parentid, MUST_EXIST, true);
                // See if parent's name matches.
                if (in_array($parent->name, $divisions)) {
                    return $parent->name;
                }
            }
        }

        // If here, then either category does not have a parent or its parents
        // did not match any known division. Check current category name.
        if (in_array($category->name, $divisions)) {
            return $category->name;
        }

        return null;    // No matches found.
    }

    /**
     * Convert timestamps to user friendly dates.
     *
     * @param array $params
     * @return string
     */
    public function format_cached_params($params) {
        $param_list = array();
        foreach ($params as $name => $value) {
            if (empty($value)) {
                continue;
            }
            $param_list[] = get_string($name, 'report_uclastats') . ' = ' .
                    userdate($value, get_string('strftimedate', 'langconfig'));
        }
        return implode('<br />', $param_list);
    }

    /**
     * Instead of counting results, return a summarized result.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {        
        if (!empty($results)) {
            return $results['Total']['total'];
        }
        return get_string('nocachedresults', 'report_uclastats');
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('optionaldatepicker');
    }

    /**
     * Display two results tables. One, for the aggregated list of collaboration 
     * sites by division, and, two, a listing of those collaboration sites.
     *
     * @param uclastats_result $uclastats_result
     * @return string
     */
    protected function get_results_table(uclastats_result $uclastats_result) {
        $retval = '';

        $results = $uclastats_result->results;
        $courselisting = $results['courselisting'];
        unset($results['courselisting']);

        // Aggregated results.
        $resultstable = new html_table();
        $resultstable->id = 'uclastats-results-table';
        $resultstable->attributes = array('class' => 'generaltable results-table ' .
            get_class($this));

        $resultstable->head = $uclastats_result->get_header();
        $resultstable->data = $results;

        $retval = html_writer::table($resultstable);

        // Need to support old reports that don't have site listing.
        if (empty($courselisting)) {
            return $retval;
        }        
        
        $retval .= html_writer::tag('h3', get_string('collabsitelisting', 'report_uclastats'));
        
        // Collaboration site listing.
        $listingtable = new html_table();
        $listingtable->id = 'uclastats-courselisting-table';

        $listingtable->head = array(get_string('division', 'report_uclastats'),
                get_string('collabsitetype', 'report_uclastats'),
                get_string('course_shortname', 'report_uclastats'));
        $data = array();
        foreach ($courselisting as $division => $sitetypes) {
            foreach ($sitetypes as $sitetype => $course) {
                foreach ($course as $courseid => $shortname) {
                    $url = html_writer::link(
                            new moodle_url('/course/view.php',
                                    array('id' => $courseid)), $shortname,
                            array('target' => '_blank'));
                    $data[] = array($division, 
                                    get_string($sitetype, 'report_uclastats'),
                                    $url);
                }
            }
        }
        $listingtable->data = $data;

        $retval .= html_writer::table($listingtable);

        return $retval;
    }    

    /**
     * Write out the aggregated results and the list of collaboration sites.
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
        $courselisting = $results['courselisting'];
        unset($results['courselisting']);

        // Display aggregated results.
        $col = 0;
        $header = $uclastats_result->get_header();
        foreach ($header as $name) {
            $worksheet->write_string($row, $col, $name, $boldformat);
            ++$col;
        }

        // Now go through result set.
        foreach ($results as $result) {
            ++$row; $col = 0;
            foreach ($result as $value) {
                // Values might have HTML in them.
                $value = clean_param($value, PARAM_NOTAGS);
                if (is_numeric($value)) {
                    $worksheet->write_number($row, $col, $value);
                } else {
                    $worksheet->write_string($row, $col, $value);
                }
                ++$col;
            }
        }

        // Need to support old reports that don't have site listing.
        if (empty($courselisting)) {
            return $row;
        }        
        
        $row += 2; $col = 0;
        $worksheet->write_string($row, $col,
                get_string('collabsitelisting', 'report_uclastats'), $boldformat);
        $row++; 
        
        // Display course listings table header.
        $header = array(get_string('division', 'report_uclastats'),
                get_string('collabsitetype', 'report_uclastats'),
                get_string('course_shortname', 'report_uclastats'));
        foreach ($header as $name) {
            $worksheet->write_string($row, $col, $name, $boldformat);
            ++$col;
        }

        // Now go through courselisting set.
        foreach ($courselisting as $division => $sitetypes) {
            foreach ($sitetypes as $sitetype => $course) {
                foreach ($course as $courseid => $shortname) {                    
                    ++$row; $col = 0;

                    // Division.
                    $worksheet->write_string($row, $col, $division);
                    ++$col;
                    
                    // Site type.
                    $worksheet->write_string($row, $col, 
                            get_string($sitetype, 'report_uclastats'));
                    ++$col;
                    
                    // Shortname.
                    $worksheet->write_string($row, $col, $shortname);                    
                }
            }
        }

        return $row;
    }    

    /**
     * Helper method to setup the result array for a given division.
     * 
     * @param string $division
     * return array
     */
    private function init_division($division) {
        $retval = array();
        $formattedname = ucla_format_name($division, true);
        $retval['division'] = $formattedname;

        // Fill in the different site types.
        $retval['total'] = 0;
        $retval[siteindicator_manager::SITE_TYPE_INSTRUCTION] = 0;
        $retval[siteindicator_manager::SITE_TYPE_INSTRUCTION_NONIEI] = 0;
        $retval[siteindicator_manager::SITE_TYPE_NON_INSTRUCTION] = 0;
        $retval[siteindicator_manager::SITE_TYPE_RESEARCH] = 0;
        $retval[siteindicator_manager::SITE_TYPE_PRIVATE] = 0;
        $retval[siteindicator_manager::SITE_TYPE_TASITE] = 0;
        $retval[siteindicator_manager::SITE_TYPE_TEST] = 0;
        $retval['uncategorized'] = 0;

        return $retval;
    }

    /**
     * Query for collaboration sites and figure out what division they belong.
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;
        $retval = array();
        $courselisting = array();
        
        // First get list of divisions.
        $divisions = $DB->get_records_menu('ucla_reg_division', null,
                'fullname', 'code, fullname');

        // Format names of divisions, so that they match category names.
        foreach ($divisions as $division) {
            $initializeddiv = $this->init_division($division);
            $retval[$initializeddiv['division']] = $initializeddiv;
            $courselisting[$initializeddiv['division']] = array();
        }
        unset($divisions);
        $divisions = array_keys($retval);

        // Add "Other" and "Total" categories.
        $additionalcats = array('Other', 'Total');
        foreach ($additionalcats as $additionalcat) {
            $initializeddiv = $this->init_division($additionalcat);
            $retval[$initializeddiv['division']] = $initializeddiv;
        }

        // Then get list of collaboration sites.
        $sql = "SELECT c.id,
                       c.shortname,
                       c.category,
                       s.type
                  FROM {course} AS c
             LEFT JOIN {ucla_request_classes} AS urc ON (urc.courseid = c.id) 
             LEFT JOIN {ucla_siteindicator} AS s ON (s.courseid = c.id)
                 WHERE urc.id IS NULL";

        // Check if start and/or end time is specified.
        if (!empty($params['startdate'])) {
            $sql .= " AND c.timecreated>=:startdate";
        }
        if (!empty($params['enddate'])) {
            $sql .= " AND c.timecreated<=:enddate";
        }        
        $sql .= " ORDER BY c.shortname";

        $sites = $DB->get_recordset_sql($sql, $params);
        if ($sites->valid()) {
            foreach ($sites as $site) {
                $division = $this->find_division($site->category, $divisions);
                if (empty($division)) {
                    // Could not find division, so use other.
                    $division = 'Other';
                }
                
                if (empty($site->type)) {
                    $site->type = 'uncategorized';
                }

                // Increment division counts.
                ++$retval[$division]['total'];
                ++$retval[$division][$site->type];

                // Increment total counts.
                ++$retval['Total']['total'];
                ++$retval['Total'][$site->type];
                
                // Keep list of sites by department and type.
                $courselisting[$division][$site->type][$site->id] = $site->shortname;
            }

            // Prune any division that has no collaboration sites.
            foreach ($retval as $division => $data) {
                if (empty($data['total'])) {
                    unset($retval[$division]);
                }
                if (empty($courselisting[$division])) {
                    unset($courselisting[$division]);
                }
            }
        }

        $retval['courselisting'] = $courselisting;        
        return $retval;
    }

}