<?php

/*
 * Class to override default moodle grader report to allow for grouping filtering
 * @package local_ucla
 * @copyright  2013 UC Regents
 */
class local_ucla_grade_report_grader extends grade_report_grader {

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * 
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     * @param int $page The current page being viewed (when report is paged)
     * @param int $sortitemid The id of the grade_item by which to sort the table
     */
    public function __construct($courseid, $gpr, $context, $page=null, $sortitemid=null, $activegrouping=null) {
        $this->activegrouping = $activegrouping;

        parent::__construct($courseid, $gpr, $context, $page, $sortitemid);

        // Add group params to the urls sorting and pages if active group is set.
        if (isset($activegrouping)) {
            $this->baseurl->params(array('grouping' => $activegrouping));
            $this->pbarurl->params(array('grouping' => $activegrouping));
        }
    }

    /**
     * Override of the report class' setup_groups() to allow for grouping filtering
     * regardless of the course's groupmode.
     */
    protected function setup_groups() {
        // Only filter if an active grouping is set.
        if (isset($this->activegrouping)) {
            $this->groupsql             = " JOIN {groups_members} gm ON gm.userid = u.id
                                            JOIN {groupings_groups} gg ON gg.groupid = gm.groupid";
            $this->groupwheresql        = " AND gg.groupingid = :grpng_id ";
            $this->groupwheresql_params = array('grpng_id'=>$this->activegrouping);

            // Set the group that is currently selected in the filter menu.
            $selected = $this->activegrouping;  
        } else {
            // There isn't a currently selected group.
            $selected = NULL;
        }

        // Create a new url for the options in the select.  
        // Remove existing group param so it can be added for link to other group.
        $url = new moodle_url($this->pbarurl);
        $url->remove_params('grouping');

        // Create the group filter menu.
        $this->group_selector = groupings_print_filter_menu($this->course, $url, $selected);
    }
}
?>
