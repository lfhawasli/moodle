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

global $CFG;
require_once($CFG->libdir . '/grade/grade_item.php');

/**
 * Sends grade item data to MyUCLA.
 *
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
        global $CFG, $DB;

        $gradeinfo = $this->get_custom_data();

        // Person who made/changed grade.
        $transactioninfo = $gradeinfo->transactioninfo;

        // Some data we cannot afford to get during set_gradeinfo(), because
        // if involves database lookups, so we do them here. But the downside
        // is that the grade item/course module might have been deleted since
        // this task was issued.
        $deleted = $gradeinfo->deleted;

        // Get grade item so that we can query for the parent category.
        $gradeitem = \grade_item::fetch(array('id' => $gradeinfo->id));
        $gradeinfo->categoryname = '';
        if (empty($gradeitem)) {
            $deleted = true;
        } else {
            // Set category name.
            $parentcategory = $gradeitem->get_parent_category();
            if (!empty($parentcategory) && $parentcategory->fullname != '?') {
                $gradeinfo->categoryname = $parentcategory->fullname;
            }
        }

        // Get course module info from the module table, so that we can look up
        // the itemdue value.
        $gradeinfo->itemdue = '';
        if (!empty($gradeitem) && $gradeitem->itemtype == 'mod') {
            // Need to get actualy db record for module.
            $record = $DB->get_record($gradeitem->itemmodule,
                    array('id' => $gradeitem->iteminstance));
            if (!empty($record)) {
                $gradeinfo->itemdue = $this->get_itemdue($gradeitem->itemmodule, $record);
            }
        }

        // Create link to view grade item. Course modules have a special way to
        // get their view url.
        $gradeinfo->url = '';
        $cminfo = null;
        if (!empty($gradeitem)) {
            $modinfo = get_fast_modinfo($gradeitem->courseid);
            if (isset($modinfo->instances[$gradeitem->itemmodule][$gradeitem->iteminstance])) {
                $cminfo = $modinfo->instances[$gradeitem->itemmodule][$gradeitem->iteminstance];
                $gradeinfo->url = $cminfo->url;
            }
        }

        // Create link to edit grade item. We will link directly to QuickEdit.
        $gradeinfo->editurl = $this->get_editurl($gradeitem, $cminfo);

        // Set variables to notify deletion.
        if (!empty($deleted)) {
            $gradeinfo->isdeleted = 1;
        } else {
            $gradeinfo->isdeleted = 0;
        }

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
                'itemURL'           => $gradeinfo->url,
                'itemEditURL'       => $gradeinfo->editurl,
                'itemComment'       => $gradeinfo->comment,
                'itemMaxScore'      => $gradeinfo->grademax,
                'itemDue'           => $gradeinfo->itemdue,
                'isDeleted'         => $gradeinfo->isdeleted
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
     * Returns the proper URL to use for editing/viewing a given grade item.
     *
     * Borrows from the private method get_activity_link() in class
     * grade_structure in file grade/lib.php.
     *
     * @param object $gradeitem
     * @param cm_info $cminfo
     * @return string               Returns URL to edit grade.
     */
    private function get_editurl($gradeitem, $cminfo = null) {
        global $CFG;

        $retval = '';
        if (empty($gradeitem)) {
            return $retval;
        }

        // For manual grade items/non-modules, link to single view report.
        if ($gradeitem->itemtype != 'mod') {
            $retval = new \moodle_url('/grade/report/singleview/index.php',
                    array('id'      => $gradeitem->courseid,
                          'itemid'  => $gradeitem->id,
                          'item'    => 'grade'));
        } else {
            // If module has grade.php, link to that, otherwise view.php
            if (file_exists($CFG->dirroot . '/mod/' . $gradeitem->itemmodule . '/grade.php')) {
                $retval = new \moodle_url('/mod/' . $gradeitem->itemmodule . '/grade.php',
                        array('id' => $cminfo->id, 'itemnumber' => $gradeitem->itemnumber));
            } else {
                $retval = new \moodle_url('/mod/' . $gradeitem->itemmodule . '/view.php',
                        array('id' => $cminfo->id));
            }
        }
        // Return urls not-escaped. Will be escaped in web service layer.
        return $retval->out(false);
    }

    /**
     * Get due date. There isn't a standard due date field, so we have to hard 
     * code special case handling for certain modules.
     *
     * @param string $modname       Name of course module.
     * @param stdClass $coursemod   Record from the course module's data table.
     * @return string   Returns datetime formatted in ISO8601 format. Returns
     *                  empty string if no due date was found.
     */
    private function get_itemdue($modname, $coursemod) {
        $itemduefield = null;
        $itemdue = '';

        switch ($modname) {
            case 'assign':
                $itemduefield = 'duedate';
                break;
            case 'assignment':
            case 'kalvidassign':
            case 'nanogong':
                $itemduefield = 'timedue';
                break;
            case 'choice':
            case 'feedback':
            case 'quiz':
            case 'scorm':
                $itemduefield = 'timeclose';
                break;
            case 'data':
            case 'forum':
            case 'glossary':
                $itemduefield = 'assesstimefinish';
                break;
            case 'elluminate':
                $itemduefield = 'timeend';
                break;
            case 'lesson':
                $itemduefield = 'deadline';
                break;
            case 'questionnaire':
                $itemduefield = 'closedate';
                break;
            case 'turnitintool':
            case 'turnitintooltwo':
                $itemduefield = 'defaultdtdue';
                break;
            case 'workshop':
                $itemduefield = 'assessmentend';
                break;
            default:
                // Not a course module we can handle.
        }
        if (!empty($itemduefield) && isset($coursemod->$itemduefield) &&
                !empty($coursemod->$itemduefield)) {
            // MyUCLA is expecting the datetime to be in the following format:
            // yyyy-mm-dd hh:mm:ss.fff, where fff stands for milliseconds.
            $itemdue = date('Y-m-d H:i:s.000', $coursemod->$itemduefield);
        }
        return $itemdue;
    }

    /**
     * Given a grade_item object get the necessary data to later sent the
     * information to MyUCLA and store it.
     *
     * @param grade_item $gradeitem
     * @return boolean  Returns false if item should not be sent to MyUCLA,
     *                  because it is a course/category item or belongs to a
     *                  non-srs course.
     */
    public function set_gradeinfo($gradeitem) {
        if (get_class($gradeitem) != 'grade_item') {
            throw new \Exception(get_class($gradeitem).' must be a grade_item.');
        }

        if (!$this->should_send_to_myucla($gradeitem->courseid, $gradeitem->itemtype)) {
            return false;
        }

        // Set basic info.
        $gradeinfo              = new \stdClass();
        $gradeinfo->categoryid  = $gradeitem->categoryid;
        $gradeinfo->courseid    = $gradeitem->courseid;
        $gradeinfo->deleted     = isset($gradeitem->deleted) ? $gradeitem->deleted : false;
        $gradeinfo->grademax    = $gradeitem->grademax;
        $gradeinfo->hidden      = $gradeitem->is_hidden();
        $gradeinfo->id          = $gradeitem->id;
        $gradeinfo->itemname    = $gradeitem->itemname;
        $gradeinfo->itemtype    = $gradeitem->itemtype;

        // Get any comments for the item.
        if (isset($this->iteminfo)) {
            $gradeinfo->comment =
                    $this->trim_and_strip($this->iteminfo);
        } else {
            $gradeinfo->comment = '';
        }

        // Now store info on who made changes.
        $gradeinfo->transactioninfo = $this->get_transactioninfo($gradeitem);

        $this->set_custom_data($gradeinfo);
        return true;
    }
}
