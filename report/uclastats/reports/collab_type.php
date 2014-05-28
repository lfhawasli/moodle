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

        // First get list of divisions.
        $retval = array();
        $divisions = $DB->get_records_menu('ucla_reg_division', null,
                'fullname', 'code, fullname');

        // Format names of divisions, so that they match category names.
        foreach ($divisions as $division) {
            $initializeddiv = $this->init_division($division);
            $retval[$initializeddiv['division']] = $initializeddiv;
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
        $sql = "SELECT  c.id,
                        c.shortname,
                        c.category,
                        s.type
                FROM    {course} AS c,
                        {ucla_siteindicator} AS s
                WHERE   s.courseid = c.id";

        // Check if start and/or end time is specified.
        if (!empty($params['startdate'])) {
            $sql .= " AND c.timecreated>=:startdate";
        }
        if (!empty($params['enddate'])) {
            $sql .= " AND c.timecreated<=:enddate";
        }

        $sites = $DB->get_recordset_sql($sql, $params);
        if ($sites->valid()) {
            foreach ($sites as $site) {
                $division = $this->find_division($site->category, $divisions);
                if (empty($division)) {
                    // Could not find division, so use other.
                    $division = 'Other';
                }

                // Increment division counts.
                ++$retval[$division]['total'];
                ++$retval[$division][$site->type];

                // Increment total counts.
                ++$retval['Total']['total'];
                ++$retval['Total'][$site->type];               
            }

            // Prune any division that has no collaboration sites.
            foreach ($retval as $division => $data) {
                if (empty($data['total'])) {
                    unset($retval[$division]);
                }
            }
        }

        return $retval;
    }

}