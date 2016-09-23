<?php

require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__) . '/requestor_shared_form.php');

// View entries already requested
class requestor_view_form extends requestor_shared_form {
    var $type = 'viewcourses';
    var $noterm = true;
    var $page = 0;
    var $coursesperpage = 100;
    var $totalcourses = null;

    const noviewcourses = 'noviewcourses';

    function specification() {
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
            $this->type = self::noviewcourses;
            $group[] =& $mf->createElement('static', 'staticlabel',
                self::noviewcourses);
        }

        $this->page = optional_param('page', 0, PARAM_INT);

        return $group;
    }

    function post_specification() {
        if ($this->type == self::noviewcourses) {
            $this->_form->hardFreeze();
        }
    }

    /**
     *  Build the Moodle DB API conditions and fetch requests from tables.
     **/
    function respond($data) {
        global $DB;

        $filters = $this->_customdata['prefields'];
        $ci = $data->{$this->groupname};
        
        foreach ($filters as $filter => $result) {
            $all = $this->get_all_filter($filter);
            
            // Check if a non-"all" value is submitted for each filter.
            if (!empty($ci[$filter]) && $ci[$filter] == $all) {
                // For an "all" value, just remove it from the WHERE
                unset($filters[$filter]);
            } else {
                $filters[$filter] = $ci[$filter];
            }
        }

        // No need to repeat courses if we're not searching for a specific
        // course
        if (!isset($filters['srs'])) {
            $filters['hostcourse'] = 1;
        }

        // try to sort on ucla_reg_classinfo's crsidx/secidx columns, since they
        // allow us to properly sort courses
        $sql = "FROM    {ucla_request_classes} AS urc
                LEFT JOIN   {ucla_reg_classinfo} AS urci ON (
                            urc.term=urci.term AND
                            urc.srs=urci.srs
                        )
                WHERE   ";

        $first_entry = true;
        foreach ($filters as $name => $value) {
            $first_entry ? $first_entry = false : $sql .= ' AND ';
            $sql .= sprintf("urc.%s='%s'", $name, $value);
        }

        $sql .= ' ORDER BY urc.department, urci.crsidx, urci.secidx';

        $countsql = "SELECT  COUNT(urc.id) " . $sql;
        $this->totalcourses = $DB->count_records_sql($countsql);

        // Setup query to only return current page.
        $querysql = "SELECT  urc.* " . $sql;
        $reqs = $DB->get_records_sql($querysql, array(),
                $this->page*$this->coursesperpage,
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

    function get_all_filter($filter) {
        return 'all_' . $filter;
    }
}

