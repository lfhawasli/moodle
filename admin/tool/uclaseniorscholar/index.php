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

/* UCLA senior scholar: filter classes
 *
 * @package     tool
 * @subpackage  uclaseniorscholar
 * @copyright   UC Regents 2015
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/lib.php');

$baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclaseniorscholar';
$filterterm = optional_param('filter_term', $CFG->currentterm, PARAM_TEXT);
$filterinstruid = optional_param('filter_instructor', '', PARAM_TEXT);
$filtersubj = optional_param('filter_subj', '', PARAM_TEXT);
$filter = optional_param('filter', '', PARAM_TEXT);

$syscontext = context_system::instance();

// Initialize $PAGE.
$PAGE->set_url('/admin/tool/uclaseniorscholar/index.php');
$PAGE->requires->js('/admin/tool/uclaseniorscholar/seniorscholar_invitation.js');

require_login();
if (!seniorscholar_has_access($USER)) {
    print_error('nopermissions');
}

$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', 'tool_uclaseniorscholar'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface.
admin_externalpage_setup('uclaseniorscholar');

// Render page.
echo $OUTPUT->header();
// Heading.
echo $OUTPUT->heading(get_string('pluginname_desc', 'tool_uclaseniorscholar'), 2, 'headingblock');
echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('p', get_string('mainmenu', 'tool_uclaseniorscholar'));

$subjlist = seniorscholar_get_subjarea();
$termlist = seniorscholar_get_terms();
$instrlist = seniorscholar_get_instructors_by_term($filterterm);

// Output.
echo html_writer::start_tag('div', array('id' => 'tool_uclaseniorscholar_filter'));

// Filter list by term.
echo html_writer::start_tag('div', array('class' => 'filter-item'));
echo html_writer::start_tag('form', array('id' => 'tool_uclaseniorscholar_course_by_term',
                                          'action' => $PAGE->url->out(),
                                          'method' => 'post'));
echo html_writer::select($termlist, 'filter_term', $filterterm, array('' => 'All term'));
echo html_writer::empty_tag('input', array('id' => 'course_by_term_btn',
                                           'name' => 'submit_button',
                                           'value' => get_string('submit_button', 'tool_uclaseniorscholar'),
                                           'type' => 'submit'));
echo html_writer::empty_tag('input', array('type' => 'hidden',
                                           'name' => 'filter',
                                           'value' => 'term'));
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

// Filter list by instructor.
echo html_writer::start_tag('div', array('class' => 'filter-item'));
echo html_writer::start_tag('form', array('id' => 'tool_uclaseniorscholar_course_by_instructor',
                                          'action' => $PAGE->url->out(),
                                          'method' => 'post'));
echo html_writer::select($termlist, 'filter_term', $filterterm,
                         array('' => 'All term'),
                         array('id' => 'tool_uclaseniorscholar_id_filter_term'));
echo html_writer::select($instrlist, 'filter_instructor', $filterinstruid,
                         array('' => 'Choose instructor'),
                         array('id' => 'tool_uclaseniorscholar_id_filter_instr'));
echo html_writer::empty_tag('input', array('id' => 'course_by_instructor_btn',
                                            'name' => 'submit_button',
                                            'value' => get_string('submit_button', 'tool_uclaseniorscholar'),
                                            'type' => 'button'));
echo html_writer::empty_tag('input', array('type' => 'hidden',
                                           'id' => 'id_filter',
                                           'name' => 'filter',
                                           'value' => 'instr_term'));
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

// Filter list by subject area and/or term.
echo html_writer::start_tag('div', array('class' => 'filter-item'));
echo html_writer::start_tag('form', array('id' => 'tool_uclaseniorscholar_course_by_subj',
                                          'action' => $PAGE->url->out(),
                                          'method' => 'post'));
echo html_writer::select($termlist, 'filter_term', $filterterm,
    array('' => 'All term'), array('id' => 'tool_uclaseniorscholar_id_filter_term_subj'));
echo html_writer::select($subjlist, 'filter_subj', $filtersubj,
    array('' => 'Choose subject area'), array('id' => 'tool_uclaseniorscholar_id_filter_subj'));
echo html_writer::empty_tag('input', array('id' => 'course_by_subj_btn',
                                           'name' => 'submit_button',
                                           'value' => get_string('submit_button', 'tool_uclaseniorscholar'),
                                           'type' => 'submit'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'filter', 'value' => 'subj_term'));
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

// Show course result.
echo html_writer::start_tag('div');
switch($filter) {
    case 'term':
        $list = seniorscholar_course_check(seniorscholar_get_courses_by_term($filterterm));
        break;
    case 'instr_term':
        $param = array('filter_term' => $filterterm, 'filter_instructor' => substr($filterinstruid, 1));
        $list = seniorscholar_course_check(seniorscholar_get_courses_by_instructor_term($param));
        break;
    case 'subj_term':
        $param = array('filter_term' => $filterterm, 'filter_subj' => $filtersubj);
        $list = seniorscholar_course_check(seniorscholar_get_courses_by_subject_term($param));
        break;
    case 'instr':
        $list = array();  // When try to filter instructor by term.
        break;
    default:
        $list = seniorscholar_course_check(seniorscholar_get_courses_by_term($filterterm));
}
if (empty($list) && $filter != 'instr') {
    echo html_writer::empty_tag('br');
    echo html_writer::tag('p', get_string('no_result', 'tool_uclaseniorscholar'));
} else {
    $a = new stdClass();
    $a->term = (empty($filterterm)) ? 'All terms' : ucla_term_to_text($filterterm);
    echo html_writer::tag('div', strtoupper(get_string('list_by_course_term', 'tool_uclaseniorscholar', $a)),
                          array('class' => 'linespacer'));
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->align = array('left', 'left');
    foreach ($list as $key => $course) {
        $row = array();
        $row[] = $course->subj_area.' '.$course->coursenum.' (section '.$course->sectnum.')';
        $row[] = $course->instructor;
        $row[] = html_writer::link(new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_invitation.php',
                                   array('courseid' => $course->courseid)),
                                   get_string('invite_link', 'tool_uclaseniorscholar'), array('target' => '_blank'));
        $row[] = html_writer::link(new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_history.php',
                                   array('courseid' => $course->courseid)),
                                   get_string('history_link', 'tool_uclaseniorscholar'), array('target' => '_blank'));
        $table->data[] = $row;
    }
    echo html_writer::table($table);
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo $OUTPUT->box_end();
echo $OUTPUT->footer();

