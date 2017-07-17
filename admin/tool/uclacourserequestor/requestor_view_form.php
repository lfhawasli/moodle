<?php
// This file is part of the UCLA course creator plugin for Moodle - http://moodle.org/
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
 * Contains the requestor_view_form, which extends requestor_shared_form.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__) . '/requestor_shared_form.php');

/**
 * The requestor view form, used for viewing entries already requested.
 *
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class requestor_view_form extends requestor_shared_form {
    /**
     * Determines which string to use as the submit button.
     * @var string
     */
    public $type = 'viewcourses';
    /**
     * Hack for conveniently not displaying terms for which there are no requests
     * @var boolean
     */
    public $noterm = true;
    /**
     * The page number.
     * @var int
     */
    public $page = 0;
    /**
     * Number of courses per page.
     * @var int
     */
    public $coursesperpage = 100;
    /**
     * Total number of courses.
     * @var int
     */
    public $totalcourses = null;

    /** Used to set toggle the $type to 'noviewcourses' instead of 'viewcourses'. **/
    const NOVIEWCOURSES = 'noviewcourses';

    /**
     * Returns an array of mForm elements to attach into the group.
     * @return array
     */
    public function specification() {
        global $DB;
        $rucr = 'tool_uclacourserequestor';
        $mf =& $this->_form;
        $filters = $this->_customdata['prefields'];
        $group = array();

        // Create the Term field.
        $options = array();
        $terms = $this->_customdata['terms'];
        foreach ($terms as $term) {
            $options[$term] = $term;
        }
        $group[] =& $mf->createElement('select', 'term', null, $options, $this->attributes);

        // Create the Department field.
        $options = array();
        $filterall = $this->get_all_filter('department');
        $options[$filterall] = get_string($filterall, $rucr);

        // Only display subject areas that have course built.
        $departments = $DB->get_records_menu('ucla_request_classes',
                array('term' => $this->_customdata['selterm']), 'department',
                'DISTINCT department AS idx, department AS subjarea');
        if (!empty($departments)) {
            foreach ($departments as $department) {
                $options[$department] = $department;
            }
        }
        $group[] =& $mf->createElement('select', 'department', null, $options);

        // Create the Action field.
        $options = array();
        $filterall = $this->get_all_filter('action');
        $options[$filterall] = get_string($filterall, $rucr);
        foreach ($filters['action'] as $action) {
            $optiontext = requestor_statuses_translate($action);
            if (empty($action)) {
                $optiontext = get_string('none');
            }
            $options[$action] = $optiontext;
        }
        $group[] =& $mf->createElement('select', 'action', null, $options);

        if (empty($group)) {
            $this->type = self::NOVIEWCOURSES;
            $group[] =& $mf->createElement('static', 'staticlabel',
                self::NOVIEWCOURSES);
        }

        $this->page = optional_param('page', 0, PARAM_INT);

        return $group;
    }
    /**
     * Adds additional functionality after the group has been added to the
     * quick form.
     */
    public function post_specification() {
        if ($this->type == self::NOVIEWCOURSES) {
            $this->_form->hardFreeze();
        }
    }

    /**
     * Build the Moodle DB API conditions and fetch requests from tables.
     *  Returns the set of courses that should respond to the request method
     *  and parameters. Called after all the data has been verified.
     * @param object $data responses from the fetch form.
     * @return array Sets of course-term-srs sets
     */
    public function respond($data) {
        global $DB;

        $filters = $this->_customdata['prefields'];
        $ci = $data->{$this->groupname};

        foreach ($filters as $filter => $result) {
            $all = $this->get_all_filter($filter);

            // Check if a non-"all" value is submitted for each filter.
            if (!empty($ci[$filter]) && $ci[$filter] == $all) {
                // For an "all" value, just remove it from the WHERE.
                unset($filters[$filter]);
            } else {
                $filters[$filter] = $ci[$filter];
            }
        }

        // No need to repeat courses if we're not searching for a specific course.
        if (!isset($filters['srs'])) {
            $filters['hostcourse'] = 1;
        }

        // Try to sort on ucla_reg_classinfo's crsidx/secidx columns, since they
        // allow us to properly sort courses.
        $sql = "FROM {ucla_request_classes} urc
           LEFT JOIN {ucla_reg_classinfo} urci
                     ON ( urc.term=urci.term
                     AND urc.srs=urci.srs )
               WHERE ";

        $firstentry = true;
        foreach ($filters as $name => $value) {
            $firstentry ? $firstentry = false : $sql .= ' AND ';
            $sql .= sprintf("urc.%s='%s'", $name, $value);
        }

        $sql .= ' ORDER BY urc.department, urci.crsidx, urci.secidx';

        $countsql = "SELECT  COUNT(urc.id) " . $sql;
        $this->totalcourses = $DB->count_records_sql($countsql);

        // Setup query to only return current page.
        $querysql = "SELECT  urc.* " . $sql;
        $reqs = $DB->get_records_sql($querysql, array(),
                $this->page * $this->coursesperpage,
                $this->coursesperpage);

        $sets = array();
        foreach ($reqs as $req) {
            $req = get_object_vars($req);
            $set = get_crosslist_set_for_host($req);
            $host = $set[set_find_host_key($set)];

            $sets[make_idnumber($host)] = $set;
        }

        return $sets;
    }

    /**
     * Convenience function to append "all_" to filter.
     * @param string $filter
     * @return string
     */
    public function get_all_filter($filter) {
        return 'all_' . $filter;
    }
}