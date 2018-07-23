<?php
// This file is part of the UCLA gradebook customizations plugin for Moodle - http://moodle.org/
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
 * Ad-hoc task class for sending grades to MyUCLA.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradebook\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Sends grade data to MyUCLA.
 *
 * Processes information from the grade_grade object and produces data that can
 * be used to communication with the MyUCLA gradebook webservice.
 *
 * @package    local_gradebook
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_myucla_grade extends send_myucla_base {

    /**
     * @var string  We are calling moodleGradeModify.
     */
    const WEBSERVICECALL = 'moodleGradeModify';

    /**
     * Create the array that will be sent to the MyUCLA webservice.
     *
     * @param object $courseinfo   Necessary course information to generate
     *                             parameters for MyUCLA webservice call.
     * @return array    Returns an array used to create the SOAP message that
     *                  will be sent to MyUCLA.
     */
    public function format_myucla_parameters($courseinfo) {
        global $CFG;

        $gradeinfo = $this->get_custom_data();

        // Person who made/changed grade.
        $transactioninfo = $gradeinfo->transactioninfo;

        // Create array with all the parameters and return it.
        return array(
            'mInstance' => array(
                'miID'          => $CFG->gradebook_id,
                'miPassword'    => $CFG->gradebook_password
            ),
            'mGrade' => array(
                'gradeID'       => $gradeinfo->id,
                'itemID'        => $gradeinfo->itemid,
                'term'          => $courseinfo->term,
                'subjectArea'   => $courseinfo->subj_area,
                'catalogNumber' => $courseinfo->crsidx,
                'sectionNumber' => $courseinfo->secidx,
                'srs'           => $courseinfo->srs,
                'uidStudent'    => $courseinfo->uidstudent,
                'viewableGrade' => $gradeinfo->finalgrade,
                'comment'       => $gradeinfo->comment,
                'excused'       => $gradeinfo->excluded != '0'
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
     *                  Null is returned if course is not a SRS course.
     *                  An empty set is returned if no enrollment records are
     *                  found. User is most likely the Instructor/TA or manually
     *                  added guest.
     */
    public function get_courses_info() {
        global $DB;

        $gradeinfo = $this->get_custom_data();

        // Get crosslisted SRS list.
        $courses = ucla_get_course_info($gradeinfo->courseid);
        if (empty($courses)) {
            // Course was not a srs course, so skip it.
            mtrace(sprintf('...Course %d was not srs course; skipping', $gradeinfo->courseid));
            return null;
        }
        $srslist = array_map(function($o) { return $o->srs; }, $courses);
        $params['term'] = $courses[0]->term;
        list($srssql, $sqlparams) = $DB->get_in_or_equal($srslist,
                SQL_PARAMS_NAMED, 'srs');
        $params = array_merge($params, $sqlparams);

        // Student we are searching on.
        $params['userid'] = $gradeinfo->userid;

        // If this is a crosslisted course, find out through what SRS student is
        // enrolled in. This info is in the ccle_roster_class_cache table.
        $sql = "SELECT urc.id,
                       urc.term,
                       urc.srs,
                       urc.subj_area,
                       urc.crsidx,
                       urc.secidx,
                       u.idnumber as uidstudent
                  FROM {ccle_roster_class_cache} crcc,
                       {ucla_reg_classinfo} urc,
                       {user} u
                 WHERE u.id = :userid AND
                       u.idnumber = crcc.stu_id AND
                       urc.term = crcc.param_term AND
                       urc.srs = crcc.param_srs AND
                       crcc.param_term = :term AND
                       crcc.param_srs $srssql AND
                       (crcc.enrl_stat_cd!='D' AND crcc.enrl_stat_cd!='C')";

        // We should only get one record, but we should handle multiple.
        $enrolledcourses = $DB->get_records_sql($sql, $params);

        // If we get multiple records might be an enrollment problem.
        if (count($enrolledcourses) > 1) {
            mtrace(sprintf('...WARNING: Multiple records returned for user in course %s|%s',
                    $gradeinfo->userid, $params['term'], $srslist));
        }

        // Give error if we got empty result and user is a student.
        if (empty($enrolledcourses)) {
            $context = \context_course::instance($gradeinfo->courseid);

            // Make sure user is not suspended.
            $suspended = get_suspended_userids($context, true);
            if (!in_array($gradeinfo->userid, $suspended)) {
                $roles = get_user_roles($context, $gradeinfo->userid);
                foreach ($roles as $role) {
                    if ($role->shortname == 'student') {
                        mtrace('...WARNING: Student role found but enrolled via ' .
                                'Registrar not found for userid ' . $gradeinfo->userid);
                        break;
                    }
                }
            } else {
                mtrace('...NOTICE: Student is suspended');
            }
        }

        return $enrolledcourses;
    }

    /**
     * Given a grade_grade object get the necessary data to later sent the
     * information to MyUCLA and store it.
     *
     * @param grade_grade $gradegrade
     * @return boolean  Returns false if item should not be sent to MyUCLA,
     *                  because it is a course/category item or belongs to a
     *                  non-srs course.
     */
    public function set_gradeinfo($gradegrade) {
        if (get_class($gradegrade) != 'grade_grade') {
            throw new \Exception(get_class($gradegrade).' must a grade_grade.');
        }

        // Need access to the grade_item object.
        if (empty($gradegrade->grade_item)) {
            $gradegrade->load_grade_item();
        }

        if (!$this->should_send_to_myucla($gradegrade->grade_item->courseid,
                $gradegrade->grade_item->itemtype)) {
            return false;
        }

        // Set basic info.
        $gradeinfo              = new \stdClass();
        $gradeinfo->id          = $gradegrade->id;
        $gradeinfo->courseid    = $gradegrade->grade_item->courseid;
        $gradeinfo->excluded    = $gradegrade->excluded;
        $gradeinfo->finalgrade  = $gradegrade->finalgrade;
        $gradeinfo->itemid      = $gradegrade->grade_item->id;
        $gradeinfo->itemtype    = $gradegrade->grade_item->itemtype;
        $gradeinfo->userid      = $gradegrade->userid;

        if (isset($gradegrade->feedback)) {
            $gradeinfo->comment =
                    $this->trim_and_strip($gradegrade->feedback);
        } else {
            $gradeinfo->comment = '';
        }

        // Set variables to notify deletion.
        if (!empty($gradegrade->deleted)) {
            $gradeinfo->finalgrade = null;
            $gradeinfo->comment = get_string('deleted', 'local_gradebook');
        }

        // Now store info on who made changes.
        $gradeinfo->transactioninfo = $this->get_transactioninfo($gradegrade);

        $this->set_custom_data($gradeinfo);
        return true;
    }
}
