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
 * @package     ucla
 * @subpackage  tool_uclaseniorscholar
 * @author      Jun Wan
 */

require_once(dirname(__FILE__) . '/../../../config.php');

/**
 * check if user has access to the interface
 **/

function seniorscholar_has_access($user) {
    if (stripos(get_config('tool_uclaseniorscholar', 'seniorscholaradministrator'), $user->idnumber) == false
        && !is_siteadmin()) {
        return false;
    } else {
        return true;
    }
}

/**
 * return array of senior scholar administrator userid
 **/

function get_seniorscholar_admin_userid() {
    global $DB;
    $uidstringarray = explode(';', get_config('tool_uclaseniorscholar', 'seniorscholaradministrator'));
    $result = $DB->get_records_list('user', 'idnumber', $uidstringarray, null, 'id');
    return array_keys($result);
}

/**
 * filter courses that allow senior scholar to take
 **/

function seniorscholar_course_check($courses) {
    foreach ($courses as $k => $course) {
        // Only allow courses below 200.
        preg_match('/\d+/', $course->coursenum, $matches);
        if (empty($matches) || (!empty($matches) && $matches[0] >= 200)) {
            unset($courses[$k]);
        }
        if ($course->acttype != 'LEC') {
            unset($courses[$k]);
        }
    }
    return $courses;
}

/**
 * get list of terms
 **/

function seniorscholar_get_terms() {
    global $DB;
    $termlist = array();
    $sql = "SELECT DISTINCT term FROM {ucla_request_classes}";
    $result = $DB->get_records_sql($sql);
    foreach ($result as $item) {
        $termtext = ucla_term_to_text($item->term);
        $termlist[$item->term] = $termtext;
    }
    return $termlist;
}

/**
 * get subject area
 **/

function seniorscholar_get_subjarea() {
    global $DB;
    return $DB->get_records_menu('ucla_reg_subjectarea', null, 'subjarea', 'subjarea, subj_area_full');
}

/**
 * get instructor
 **/

function seniorscholar_get_instructors_by_term($term='') {
    global $DB;
    $instlist = array();
    $sql = "SELECT id, uid, lastname, firstname from {ucla_browseall_instrinfo}";
    if (!empty($term)) {
        $sql .= " WHERE term = '" . $term . "'";
    }
    $sql .= " ORDER BY lastname, firstname";
    $result = $DB->get_records_sql($sql);
    foreach ($result as $inst) {
        $instlist['i'.$inst->uid] = $inst->lastname . ', ' . $inst->firstname;
    }
    return $instlist;
}

/**
 * get course list by term
 */

function seniorscholar_get_courses_by_term($term) {
    global $DB;
    $list = array();
    $sql = "SELECT rc.id, rc.instructor, rc.courseid, reg.subj_area, reg.coursenum, reg.sectnum, reg.acttype
              FROM {course} c
              JOIN {ucla_request_classes} rc ON c.id = rc.courseid
              JOIN {ucla_reg_classinfo} reg ON reg.term = rc.term and reg.srs = rc.srs";
    if ($term) {
        $sql .= " WHERE rc.term = '" . $term . "'";
    }
    $sql .= " ORDER BY reg.subj_area, reg.coursenum, reg.sectnum";
    return $result = $DB->get_records_sql($sql);
}

/**
 * get all information of class list (display subject area, division and list by course)
 **/

function seniorscholar_get_courses_by_subject_term(&$param) {
    global $DB;
    $list = array();
    $sql = "SELECT rc.id, rc.instructor, rc.courseid, reg.subj_area, reg.coursenum, reg.sectnum, reg.acttype
              FROM {course} c
              JOIN {ucla_request_classes} rc on c.id = rc.courseid
              JOIN {ucla_reg_classinfo} reg on rc.srs = reg.srs and rc.term = reg.term
              JOIN {ucla_reg_subjectarea} subj on subj.subjarea = reg.subj_area";
    if ($param['filter_term'] && empty($param['filter_subj'])) {
        $sql .= " WHERE rc.term = '". $param['filter_term'] . "'";
    } else if ($param['filter_subj'] && empty($param['filter_term'])) {
        $sql .= " WHERE reg.subj_area = '". $param['filter_subj'] . "'";
    } else if ($param['filter_subj'] && $param['filter_term']) {
        $sql .= " WHERE rc.term = '". $param['filter_term'] . "' and reg.subj_area = '" . $param['filter_subj'] . "'";
    }
    $sql .= " ORDER BY reg.subj_area, reg.coursenum, reg.sectnum";
    return $result = $DB->get_records_sql($sql);
}

/**
 * get course list by instructor
 **/

function seniorscholar_get_courses_by_instructor_term(&$param) {
    global $DB;
    $list = array();
    $sql = "SELECT rc.id, rc.instructor, rc.courseid, reg.subj_area, reg.coursenum, reg.sectnum, reg.acttype
              FROM {course} c
              JOIN {ucla_request_classes} rc ON c.id = rc.courseid
              JOIN {ucla_reg_classinfo} reg ON reg.srs = rc.srs and reg.term=rc.term
              JOIN {ucla_browseall_instrinfo} bi ON bi.term = rc.term and bi.srs = rc.srs";
    if ($param['filter_term'] && empty($param['filter_instructor'])) {
        $sql .= " WHERE rc.term = '" . $param['filter_term'] . "'";
    } else if ($param['filter_instructor'] && empty($param['filter_term'])) {
        $sql .= " WHERE bi.uid = '" . $param['filter_instructor'] . "'";
    } else if ($param['filter_instructor'] && $param['filter_term']) {
        $sql .= " WHERE rc.term = '" . $param['filter_term'] . "' and bi.uid = '" . $param['filter_instructor'] . "'";
    }
    $sql .= " ORDER BY bi.lastname, bi.firstname, rc.department, rc.course";
    return $result = $DB->get_records_sql($sql);
}