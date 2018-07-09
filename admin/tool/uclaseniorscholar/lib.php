<?php
// This file is part of the UCLA senior scholar site invitation plugin for Moodle - http://moodle.org/
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
 * UCLA senior scholar
 *
 * @package     tool_uclaseniorscholar
 * @author      Jun Wan
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * Check if user has access to the interface.
 *
 * @param object $user
 * @return boolean
 */
function seniorscholar_has_access($user) {
    if (!isset($user->idnumber) ||
            stripos(get_config('tool_uclaseniorscholar', 'seniorscholaradministrator'), $user->idnumber) === false && !is_siteadmin()) {
        return false;
    } else {
        return true;
    }
}

/**
 * Return array of senior scholar administrator userid
 *
 * @return array
 */
function get_seniorscholar_admin_userid() {
    global $DB;
    $uidstring = get_config('tool_uclaseniorscholar', 'seniorscholaradministrator');
    if (empty($uidstring)) {
        return array();
    }
    $uidstringarray = explode(';', $uidstring);
    $result = $DB->get_records_list('user', 'idnumber', $uidstringarray, null, 'id');
    return array_keys($result);
}

/**
 * Filter courses that allow senior scholar to take and re-organize to display
 * cross listing courses.
 *
 * @param array $courses
 * @return array    Filtered list of courses.
 */
function seniorscholar_course_check($courses) {
    $output = array();
    foreach ($courses as $k => $course) {
        // Only allow courses below 200.
        preg_match('/\d+/', $course->coursenum, $matches);
        if (empty($matches) || (!empty($matches) && $matches[0] >= 200)) {
            // Nothing to do.
            continue;
        } else if ($course->acttype != 'LEC' && $course->acttype != 'SEM') {
            // Nothing to do.
            continue;
        } else if ($course->hostcourse == 1) {
            // Re-organize to display cross listing courses.
            // Only exact one course have the hostcourse value as 1 for cross listing courses.
            $output[$course->courseid][$course->hostcourse] = $course;
        } else if ($course->hostcourse == 0) {
            $output[$course->courseid][$course->hostcourse][] = $course;
        }
    }
    return $output;
}

/**
 * Get list of terms (only current and future terms).
 *
 * @return array
 */
function seniorscholar_get_terms() {
    $termlist = array();
    $result = get_active_terms();
    foreach ($result as $item) {
        $termtext = ucla_term_to_text($item);
        $termlist[$item] = $termtext;
    }
    return $termlist;
}

/**
 * Get subject areas.
 *
 * @return array
 */
function seniorscholar_get_subjarea() {
    global $DB;
    return $DB->get_records_menu('ucla_reg_subjectarea', null, 'subjarea', 'subjarea, subj_area_full');
}

/**
 * Get instructors by term.
 *
 * @param string $term  Optional.
 * @return array
 */
function seniorscholar_get_instructors_by_term($term = null) {
    global $DB;
    $params = array();
    if (!empty($term)) {
        $params['term'] = $term;
    }
    return $DB->get_records_menu('ucla_browseall_instrinfo', $params,
            'lastname, firstname', 'DISTINCT uid, CONCAT(lastname, ", ", firstname)');
}

/**
 * Get course list by term.  Limited only the courses that have invite from senior scholar program
 *
 * @param string $term
 * @return array
 */
function seniorscholar_get_courses_by_term($term) {
    global $DB;
    $listofinviters = get_seniorscholar_admin_userid();
    list($listofinviterssql, $params) = $DB->get_in_or_equal($listofinviters, SQL_PARAMS_NAMED);
    $params['term'] = $term;
    $sql = "SELECT DISTINCT (@row_num := @row_num + 1) AS id, rc.courseid, rc.instructor, rc.hostcourse,
            reg.subj_area, reg.coursenum, reg.sectnum, reg.acttype, reg.enrolstat, reg.term, reg.session_group
              FROM {course} c
              JOIN (SELECT DISTINCT courseid
                      FROM {enrol_invitation}
                     WHERE inviterid $listofinviterssql) AS i
                ON i.courseid = c.id
              JOIN {ucla_request_classes} rc ON c.id = rc.courseid
              JOIN {ucla_reg_classinfo} reg ON reg.term = rc.term and reg.srs = rc.srs
              JOIN (SELECT @row_num := 0) AS t
            WHERE rc.term = :term
            ORDER BY reg.subj_area, reg.coursenum, reg.sectnum";
    return $DB->get_records_sql($sql, $params);
}

/**
 * Get all information of class list (display subject area, division and list by course)
 *
 * @param array $param
 * @return array
 */
function seniorscholar_get_courses_by_subject_term(&$param) {
    global $DB;
    $sql = "SELECT DISTINCT (@row_num := @row_num + 1) AS id, rc.courseid, rc.instructor, rc.hostcourse,
            reg.subj_area, reg.coursenum, reg.sectnum, reg.acttype, reg.enrolstat, reg.term, reg.session_group
              FROM {course} c
              JOIN {ucla_request_classes} rc on c.id = rc.courseid
              JOIN {ucla_reg_classinfo} reg on rc.srs = reg.srs and rc.term = reg.term
              JOIN {ucla_reg_subjectarea} subj on subj.subjarea = reg.subj_area
              JOIN (SELECT @row_num := 0) AS t";

    if ($param['filter_term'] && empty($param['filter_subj'])) {
        $sql .= " WHERE rc.term = :filter_term";
    } else if ($param['filter_subj'] && empty($param['filter_term'])) {
        $sql .= " WHERE reg.subj_area = :filter_subj";
    } else if ($param['filter_subj'] && $param['filter_term']) {
        $sql .= " WHERE rc.term = :filter_term and reg.subj_area = :filter_subj";
    }
    $sql .= " ORDER BY reg.subj_area, reg.crsidx, reg.secidx";
    return $DB->get_records_sql($sql, $param);
}

/**
 * Get course list by instructor.
 *
 * @param array $param
 * @return array
 */
function seniorscholar_get_courses_by_instructor_term(&$param) {
    global $DB;
    $sql = "SELECT DISTINCT (@row_num := @row_num + 1) as id, rc.courseid, rc.instructor, rc.hostcourse,
            reg.subj_area, reg.coursenum, reg.sectnum, reg.acttype, reg.enrolstat, reg.term, reg.session_group
              FROM {course} c
              JOIN {ucla_request_classes} rc ON c.id = rc.courseid
              JOIN {ucla_reg_classinfo} reg ON reg.srs = rc.srs and reg.term=rc.term
              JOIN {ucla_browseall_instrinfo} bi ON bi.term = rc.term and bi.srs = rc.srs
              JOIN (SELECT @row_num := 0) AS t";

    if ($param['filter_term'] && empty($param['filter_instructor'])) {
        $sql .= " WHERE rc.term = :filter_term";
    } else if ($param['filter_instructor'] && empty($param['filter_term'])) {
        $sql .= " WHERE bi.uid = :filter_instructor";
    } else if ($param['filter_instructor'] && $param['filter_term']) {
        $sql .= " WHERE rc.term = :filter_term and bi.uid = :filter_instructor";
    }
    $sql .= " ORDER BY bi.lastname, bi.firstname, rc.department, rc.course";
    return $DB->get_records_sql($sql, $param);
}

/**
 * Gets invite history by term.
 *
 * @param string $term
 * @return array
 */
function seniorscholar_get_userinvitehistory_by_term($term) {
    global $DB;
    $output = array();
    // Get list invitations.
    $listofinviters = get_seniorscholar_admin_userid();
    list($listofinviterssql, $params) = $DB->get_in_or_equal($listofinviters, SQL_PARAMS_NAMED);
    $params['term'] = $term;
    $sql = "SELECT DISTINCT (@row_num := @row_num + 1) AS tid, i.*, rc.courseid, rc.instructor, rc.hostcourse,
            reg.subj_area, reg.coursenum, reg.sectnum, reg.acttype, reg.enrolstat, reg.term, reg.session_group
              FROM {course} c
              JOIN {enrol_invitation} i ON i.courseid = c.id
              JOIN {ucla_request_classes} rc ON c.id = rc.courseid
              JOIN {ucla_reg_classinfo} reg ON reg.term = rc.term and reg.srs = rc.srs
              JOIN (SELECT @row_num := 0) AS t
             WHERE i.inviterid $listofinviterssql AND
                   rc.term = :term
          ORDER BY i.email, reg.subj_area, reg.coursenum, reg.sectnum, rc.hostcourse, i.timeexpiration DESC";
    $invites = $DB->get_records_sql($sql, $params);

    // Get list of roles.
    foreach ($invites as $k => $v) {
        if ($v->hostcourse == 1) {
            $output[$v->email][$v->courseid][$v->hostcourse] = $v;
        } else if ($v->hostcourse == 0) {
            $output[$v->email][$v->courseid][$v->hostcourse][] = $v;
        }
    }
    return $output;
}

/**
 * get course list by srs and term.
 * Only support the senior scholar who does not have any invite for this course
 * including invites from the senior scholar administrator or course instructor.
 */
function seniorscholar_get_courses_by_srsterm($srs, $term) {
    global $DB;
    $sql = "SELECT DISTINCT (@row_num := @row_num + 1) AS id, rc.courseid, rc.instructor, rc.hostcourse,
                             reg.subj_area, reg.coursenum, reg.sectnum, reg.acttype, reg.enrolstat, reg.term, reg.session_group, i.email
              FROM {course} c
         LEFT JOIN {enrol_invitation} i
                ON i.courseid = c.id
              JOIN {ucla_request_classes} rc ON c.id = rc.courseid
              JOIN {ucla_reg_classinfo} reg ON reg.term = rc.term and reg.srs = rc.srs
              JOIN
                   (SELECT @row_num := 0) AS t
             WHERE rc.term = ?
               AND rc.srs = ?";
    return $result = $DB->get_records_sql($sql, array($term, $srs));
}
