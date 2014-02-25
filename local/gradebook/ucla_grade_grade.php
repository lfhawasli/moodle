<?php
// This file is part of the UCLA local gradebook plugin for Moodle - http://moodle.org/
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

require_once($CFG->libdir . '/grade/grade_grade.php');

class ucla_grade_grade extends grade_grade {

    /**
     * Handler for grade updates.  This should only be called by a grade update
     * event handler.  
     * 
     * @global type $CFG
     * @return boolean 
     */
    public function webservice_handler() {
        global $CFG;

        if (!empty($CFG->gradebook_send_updates)) {
            $result = $this->send_to_myucla();

            if ($result !== grade_reporter::SUCCESS &&
                    $result !== grade_reporter::NOTSENT) {
                // Report failure if there was a problem on MyUCLA's end
                // NOTSENT is if a grade item isn't suppose to be sent via
                // processing on our end.
                return false;
            }
        }

        return true;
    }

    /**
     * Validates grade, queries for student data, and then sends info to the
     * MyUCLA gradebook service.
     *
     * @global object $DB
     * @global object $CFG
     * @return int          Returns a status value
     */
    public function send_to_myucla() {
        global $DB, $CFG;

        // Need access to the grade_item object.
        if (empty($this->grade_item)) {
            $this->load_grade_item();
        }

        // Don't push certain grade types.
        if ($this->grade_item->itemtype === 'course' ||
                $this->grade_item->itemtype === 'category') {
            return grade_reporter::NOTSENT;
        }

        // Get crosslisted SRS list.
        $courses = ucla_get_course_info($this->grade_item->courseid);

        if (empty($courses)) {
            // Course was not a srs course, so skip it.
            return grade_reporter::NOTSENT;
        }

        $srs_list = implode(',',
                array_map(function($o) {
                            return $o->srs;
                        }, $courses));
        $term = $courses[0]->term;

        if (empty($srs_list) || empty($term)) {
            // Course somehow got here and did not have term/srs, so skip it.
            return grade_reporter::NOTSENT;
        }

        // If this is a crosslisted course, find out through what SRS he/she
        // enrolled in. This info is in the ccle_roster_class_cache table.
        $sql = "SELECT  urc.id, urc.term, urc.srs,
                        urc.subj_area, urc.crsidx, urc.secidx,
                        u.idnumber as uidstudent
                FROM    {ccle_roster_class_cache} crcc,
                        {ucla_reg_classinfo} AS urc,
                        {user} AS u
                WHERE   u.id = $this->userid AND
                        u.idnumber = crcc.stu_id AND
                        urc.term = crcc.param_term AND
                        urc.srs = crcc.param_srs AND
                        crcc.param_term = '$term' AND
                        crcc.param_srs IN ($srs_list)";
        $enrolledcourses = $DB->get_records_sql($sql);

        // We should only get one record, but we should handle multiple.
        if (empty($enrolledcourses)) {
            // User is most likely the Instructor or TA or manually added guest
            // just skip user.
            return grade_reporter::NOTSENT;
        }

        // Want the transaction ID to be the last record in the _history table.
        list($transactionid, $loggeduser) =
                grade_reporter::get_transactionid($this->table, $this->id);

        $transaction_user = grade_reporter::get_transaction_user($this,
                        $loggeduser);

        // Prepare log variables.
        $log = array(
            'itemmodule' => $this->grade_item->itemmodule,
            'itemtype' => $this->grade_item->itemtype,
            'iteminstance' => $this->grade_item->iteminstance,
            'courseid' => $this->grade_item->courseid,
            'transactionuser' => $transaction_user->id,
        );

        foreach ($enrolledcourses as $course) {
            if (empty($course->uidstudent)) {
                // Ignore users with no uid.
                return grade_reporter::NOTSENT;
            }

            $param = $this->make_myucla_parameters($course, $transactionid);
            try {
                // Connect to MyUCLA and send data.
                $client = grade_reporter::get_instance();
                $result = $client->moodleGradeModify($param);

                // Check for status error.
                if (!$result->moodleGradeModifyResult->status) {
                    throw new Exception($result->moodleGradeModifyResult->message);
                }

                // Success is logged conditionally.
                if (!empty($CFG->gradebook_log_success)) {
                    $log['action'] = get_string('gradesuccess',
                            'local_gradebook');
                    $log['info'] = $result->moodleGradeModifyResult->message;
                    grade_reporter::add_to_log($log);
                }
            } catch (SoapFault $e) {
                // Catch a SOAP failure.
                $log['action'] = get_string('connectionfail', 'local_gradebook');
                $log['info'] = get_string('gradeconnectionfailinfo',
                        'local_gradebook', $this->id);
                $log['info'] .= ': ' . $e;  // Append exact SOAP error as well.

                grade_reporter::add_to_log($log);
                return grade_reporter::CONNECTION_ERROR;
            } catch (Exception $e) {
                // Catch a 'status' failure.
                $log['action'] = get_string('gradefail', 'local_gradebook');
                $log['info'] = get_string('gradefailinfo', 'local_gradebook',
                        $this->id);

                grade_reporter::add_to_log($log);
                return grade_reporter::BAD_REQUEST;
            }
        }

        return grade_reporter::SUCCESS;
    }

    /**
     * Creates array of values to be used when creating message about
     * grade_grades to MyUCLA.
     *
     * @global object $CFG
     * @param object $course    Info from ucla_reg_courseinfo and student UID.
     * @param int $transactionid
     * @return array    Returns an array to create the SOAP message that will
     *                  be sent to MyUCLA
     */
    public function make_myucla_parameters($course, $transactionid) {
        global $CFG;

        // Person who made/changed grade.
        $transaction_user = grade_reporter::get_transaction_user($this);

        // Trim long feedback.
        $comment = '';
        if (isset($this->feedback)) {
            if (strlen($this->feedback) > grade_reporter::MAX_COMMENT_LENGTH) {
                $comment = trim(textlib::substr($this->feedback, 0,
                                        grade_reporter::MAX_COMMENT_LENGTH)) .
                        get_string('continue_comments', 'local_gradebook');
            } else {
                $comment = trim($this->feedback);
            }
            $comment = $this->strip_invalid_xml_characters($comment);
        }

        // Set variables to notify deletion.
        if (!empty($this->deleted)) {
            $this->finalgrade = null;
            $this->feedback = 'Deleted';
        }

        // Create array with all the parameters and return it.
        return array(
            'mInstance' => array(
                'miID' => $CFG->gradebook_id,
                'miPassword' => $CFG->gradebook_password
            ),
            'mGrade' => array(
                'gradeID' => $this->id,
                'itemID' => $this->itemid,
                'term' => $course->term,
                'subjectArea' => $course->subj_area,
                'catalogNumber' => $course->crsidx,
                'sectionNumber' => $course->secidx,
                'srs' => $course->srs,
                'uidStudent' => $course->uidstudent,
                'viewableGrade' => $this->finalgrade,
                'comment' => $comment,
                'excused' => $this->excluded != '0'
            ),
            'mTransaction' => array(
                'userUID' => empty($transaction_user->idnumber) ?
                        '000000000' : $transaction_user->idnumber,
                'userName' => fullname($transaction_user, true),
                'userIpAddress' => empty($transaction_user->lastip) ?
                        '0.0.0.0' : $transaction_user->lastip,
                'moodleTransactionID' => $transactionid,
            )
        );
    }

    /**
     * Removes invalid XML characters.
     *
     * ASP.NET webservices use XML 1.0 which restricts the character set allowed
     * to the following chars:
     *  #x9 | #xA | #xD | x20-#xD7FF | xE000-#xFFFD | x10000-#x10FFFF
     *  (Source: http://www.w3.org/TR/REC-xml/#charsets)
     *
     * Function is from: http://stackoverflow.com/a/3466049/6001
     *
     * @param string $value
     * @return string
     */
    private function strip_invalid_xml_characters($value) {
        $ret = "";
        $current;
        if (empty($value)) {
            return $ret;
        }

        $length = textlib::strlen($value);
        for ($i=0; $i<$length; $i++) {
            $current = ord($value{$i});
            if (($current == 0x9) ||
                    ($current == 0xA) ||
                    ($current == 0xD) ||
                    (($current >= 0x20) && ($current <= 0xD7FF)) ||
                    (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                    (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                $ret .= chr($current);
            } else {
                $ret .= " ";
            }
        }
        return $ret;
    }

}
