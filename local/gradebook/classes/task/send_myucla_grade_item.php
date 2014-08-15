<?php
// This file is part of the UCLA Gradebook customizations plugin for Moodle - http://moodle.org/
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
 * Ad-hoc task class for sending grade items to MyUCLA.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradebook\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Processes information from the grade_item object and produces data that can
 * be used to communication with the MyUCLA gradebook webservice.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_myucla_grade_item extends send_myucla_base {

    /**
     * @var string  We are updating grade items.
     */
    const WEBSERVICECALL = 'moodleItemModify';

    /**
     * Creates array of values to be used when creating message about
     * grade_items to MyUCLA.
     *
     * @param stdClass $courseinfo
     * @return array    Returns an array to create the SOAP message that will
     *                  be sent to MyUCLA
     */
    public function format_myucla_parameters($courseinfo) {
        global $CFG;

        $gradeinfo = $this->get_custom_data();

        // Person who made/changed grade.
        $transactioninfo = $gradeinfo->transactioninfo;

        return array(
            'mInstance' => array(
                'miID'          => $CFG->gradebook_id,
                'miPassword'    => $CFG->gradebook_password
            ),
            'mItem' => array(
                'itemID'            => $gradeinfo->id,
                'itemName'          => $gradeinfo->itemname,
                'categoryID'        => $gradeinfo->categoryid,
                'categoryName'      => $gradeinfo->categoryname,
                'itemReleaseScores' => !($gradeinfo->hidden),
                // The itemDue field shouldn't be sent right now, but in the
                // future change this to be the real due date for an activity.
                //'itemDue'           => $gradeinfo->itemdue,
                'itemURL'           => $gradeinfo->url,
                'itemComment'       => $gradeinfo->comment,
            ),
            'mClassList' => array(
                array(
                    'term'          => $courseinfo->term,
                    'subjectArea'   => $courseinfo->subj_area,
                    'catalogNumber' => $courseinfo->crsidx,
                    'sectionNumber' => $courseinfo->secidx,
                    'srs'           => $courseinfo->srs
                )
            ),
            'mTransaction' => array(
                'userUID'               => $transactioninfo->idnumber,
                'userName'              => $transactioninfo->name,
                'userIpAddress'         => $transactioninfo->lastip,
                'moodleTransactionID'   => $transactioninfo->transactionid,
            )
        );
    }

    /**
     * Should return the necessary information for courses to be used in
     * creating the MyUCLA parameters.
     *
     * @return array    Returns array of database objects containing courseinfo
     *                  needed to produce parameters for the MyUCLA webservice.
     */
    public function get_courses_info() {
        $gradeinfo = $this->get_custom_data();
        return ucla_get_course_info($gradeinfo->courseid);
    }

    /**
     * Given a grade object (grade_item or grade_grade) get the necessary
     * data to later sent the information to MyUCLA and store it.
     *
     * @param grade_item $gradeitem
     * @return boolean  Returns false if item should not be sent to MyUCLA,
     *                  because it is a course/category item or belongs to a
     *                  non-srs course.
     */
    public function set_gradeinfo($gradeitem) {
        global $CFG;

        if (get_class($gradeitem) != 'grade_item') {
            throw new \Exception(get_class($gradeitem).' must a grade_item.');
        }

        if (!$this->should_send_to_myucla($gradeitem->courseid, $gradeitem->itemtype)) {
            return false;
        }

        // Set basic info.
        $gradeinfo              = new \stdClass();
        $gradeinfo->categoryid  = $gradeitem->categoryid;
        $gradeinfo->courseid    = $gradeitem->courseid;
        $gradeinfo->hidden      = $gradeitem->hidden;
        $gradeinfo->id          = $gradeitem->id;
        $gradeinfo->itemname    = $gradeitem->itemname;
        $gradeinfo->itemtype    = $gradeitem->itemtype;

        // Set category name.
        $parentcategory = $gradeitem->get_parent_category();
        $gradeinfo->categoryname = empty($parentcategory) ? '' : $parentcategory->fullname;

        // Create link to grade item.
        $url = new \moodle_url('/grade/edit/tree/item.php',
                array('courseid'        => $gradeitem->courseid,
                      'id'              => $gradeitem->id,
                      'gpr_type'        => 'edit',
                      'gpr_plugin'      => 'tree',
                      'gpr_courseid'    => $gradeitem->courseid));
        $gradeinfo->url = $url->out();

        // Get any comments for the item.
        if (isset($this->iteminfo)) {
            $gradeinfo->comment =
                    $this->trim_and_strip($this->iteminfo);
        } else {
            $gradeinfo->comment = '';
        }

        // Set variables to notify deletion.
        if (!empty($gradeitem->deleted)) {
            $gradeinfo->comment    = get_string('deleted', 'local_gradebook');
            $gradeinfo->hidden      = 1;
        }

        // Now store info on who made changes.
        $gradeinfo->transactioninfo = $this->get_transactioninfo($gradeitem);

        $this->set_custom_data($gradeinfo);
        return true;
    }
}
