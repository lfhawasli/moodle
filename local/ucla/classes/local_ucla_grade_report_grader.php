<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Overrides the moodle grader report.
 *
 * @package    local_ucla
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class to allow for grouping filtering in grader report.
 *
 * @package     local_ucla
 * @copyright   2013 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_grade_report_grader extends grade_report_grader {

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     *
     * @param int $courseid
     * @param object $gpr grade plugin return tracking object
     * @param context $context
     * @param int $page The current page being viewed (when report is paged)
     * @param int $sortitemid The id of the grade_item by which to sort the table
     * @param int $activegrouping
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
        // Only filter if an active grouping is set and nonzero.
        if (isset($this->activegrouping) && $this->activegrouping) {
            $this->groupsql             = " JOIN {groups_members} gm ON gm.userid = u.id
                                            JOIN {groupings_groups} gg ON gg.groupid = gm.groupid";
            $this->groupwheresql        = " AND gg.groupingid = :grpng_id ";
            $this->groupwheresql_params = array('grpng_id' => $this->activegrouping);

            // Set the group that is currently selected in the filter menu.
            $selected = $this->activegrouping;
        } else {
            // There isn't a currently selected group.
            $selected = null;
        }

        // Create a new url for the options in the select.
        // Remove existing group param so it can be added for link to other group.
        $url = new moodle_url($this->pbarurl);
        $url->remove_params('grouping');

        // Create the group filter menu.
        $this->group_selector = groupings_print_filter_menu($this->course, $url, $selected);
    }
}
