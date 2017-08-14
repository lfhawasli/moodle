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
require_once($CFG->dirroot . '/enrol/invitation/locallib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/local/ucla/datetimehelpers.php');
require_once($CFG->dirroot . '/admin/tool/uclacoursecreator/uclacoursecreator.class.php');
require_once($CFG->libdir . '/weblib.php');

$baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclaseniorscholar';
$filterterm = optional_param('filter_term', $CFG->currentterm, PARAM_ALPHANUM);
$filterinstruid = optional_param('filter_instructor', '', PARAM_ALPHANUM);
$filtersubj = optional_param('filter_subj', '', PARAM_TEXT);
$filter = optional_param('filter', '', PARAM_TEXT);
$mode = optional_param('mode', '', PARAM_TEXT);

$syscontext = context_system::instance();
$PAGE->set_context($syscontext);

if ($mode == 'Printer friendly') {
    $PAGE->set_pagelayout('print');
} else {
    $PAGE->set_pagetype('admin-*');
    $PAGE->set_pagelayout('admin');
    // Prepare and load Moodle Admin interface.
    admin_externalpage_setup('uclaseniorscholar');
    $PAGE->set_heading(get_string('pluginname', 'tool_uclaseniorscholar'));
}

// Initialize $PAGE.
$PAGE->set_url('/admin/tool/uclaseniorscholar/index.php');
$PAGE->requires->js('/admin/tool/uclaseniorscholar/seniorscholar_invitation.js');

require_login();
if (!seniorscholar_has_access($USER)) {
    print_error('nopermissions');
}


// Render page.
echo $OUTPUT->header();

// Output.
// Output form.
if (empty($mode)) {
    $subjlist = seniorscholar_get_subjarea();
    $termlist = seniorscholar_get_terms();
    $instrlist = seniorscholar_get_instructors_by_term($filterterm);
    // Heading.
    echo $OUTPUT->heading(get_string('pluginname_desc', 'tool_uclaseniorscholar'), 2, 'headingblock');
    echo html_writer::tag('p', get_string('mainmenu_course', 'tool_uclaseniorscholar'));

    // Filter list by instructor.
    echo html_writer::start_tag('div', array('class' => 'filter-item'));
    echo html_writer::start_tag('form', array('id' => 'tool_uclaseniorscholar_course_by_instructor',
                                              'action' => $PAGE->url->out(),
                                              'method' => 'post'));
    echo html_writer::select($termlist, 'filter_term', $filterterm, '',
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
    echo html_writer::select($termlist, 'filter_term', $filterterm, '',
                             array('id' => 'tool_uclaseniorscholar_id_filter_term_subj'));
    echo html_writer::select($subjlist, 'filter_subj', $filtersubj,
                             array('' => 'Choose subject area'), array('id' => 'tool_uclaseniorscholar_id_filter_subj'));
    echo html_writer::empty_tag('input', array('id' => 'course_by_subj_btn',
                                               'name' => 'submit_button',
                                               'value' => get_string('submit_button', 'tool_uclaseniorscholar'),
                                               'type' => 'submit'));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'filter', 'value' => 'subj_term'));
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('div');

    // Output for invite history by user.
    echo html_writer::empty_tag('br');
    echo html_writer::tag('p', get_string('mainmenu_history', 'tool_uclaseniorscholar'));

    // Filter list by instructor.
    echo html_writer::start_tag('div', array('class' => 'filter-item'));
    echo html_writer::start_tag('form', array('id' => 'tool_uclaseniorscholar_history_by_user',
                                              'action' => $PAGE->url->out(),
                                              'method' => 'post'));
    echo html_writer::select($termlist, 'filter_term', $filterterm, '',
                             array('id' => 'tool_uclaseniorscholar_id_filter_term'));
    echo html_writer::empty_tag('input', array('id' => 'history_by_term_btn',
                                               'name' => 'submit_button',
                                               'value' => get_string('submit_button', 'tool_uclaseniorscholar'),
                                               'type' => 'submit'));

    // Printer friendly version.
    $printurl = new moodle_url('', array('mode' => 'Printer friendly'));
    $printtitle = get_string('printerfriendly', 'tool_uclaseniorscholar');
    $printattributes = array('class' => 'printicon');
    echo html_writer::link($printurl, $printtitle, $printattributes);

    echo html_writer::empty_tag('input', array('type' => 'hidden',
                                               'id' => 'id_filter',
                                               'name' => 'filter',
                                               'value' => 'history_by_user'));
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('div');
    // Bulk upload by term.
    echo html_writer::empty_tag('br');
    echo html_writer::tag('p', get_string('bulkupload_byterm', 'tool_uclaseniorscholar'));
    echo html_writer::start_tag('div', array('class' => 'filter-item'));
    echo html_writer::start_tag('form', array('id' => 'tool_uclaseniorscholar_bulk_upload',
                                              'action' => new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_import.php'),
                                              'method' => 'post'));
    echo html_writer::select($termlist, 'filter_term', $filterterm, '',
                             array('id' => 'tool_uclaseniorscholar_id_filter_term'));
    echo html_writer::empty_tag('input', array('id' => 'bulk_upload_btn',
                                               'name' => 'submit_button',
                                               'value' => get_string('bulkupload_button', 'tool_uclaseniorscholar'),
                                               'type' => 'submit'));
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('div');
}

switch($filter) {
    case 'term':
        $list = seniorscholar_course_check(seniorscholar_get_courses_by_term($filterterm));
        break;
    case 'instr_term':
        $param = array('filter_term' => $filterterm, 'filter_instructor' => $filterinstruid);
        $list = seniorscholar_course_check(seniorscholar_get_courses_by_instructor_term($param));
        break;
    case 'subj_term':
        $param = array('filter_term' => $filterterm, 'filter_subj' => $filtersubj);
        $list = seniorscholar_course_check(seniorscholar_get_courses_by_subject_term($param));
        break;
    case 'instr':
        $list = array();  // When try to filter instructor by term.
        break;
    case 'history_by_user':
        $list = seniorscholar_get_userinvitehistory_by_term($filterterm);
        break;
    default:
        $list = seniorscholar_course_check(seniorscholar_get_courses_by_term($filterterm));
}
// Show course result.
$a = new stdClass();
$a->term = ucla_term_to_text($filterterm);
$maxcrosslistshown = get_config('local_ucla', 'maxcrosslistshown');

echo html_writer::start_tag('div');
if (empty($list) && $filter != 'instr') {

    echo html_writer::empty_tag('br');
    echo html_writer::tag('p', get_string('no_result', 'tool_uclaseniorscholar'));

} else if ($filter == 'history_by_user') {

    echo html_writer::tag('div', strtoupper(get_string('list_by_course_term', 'tool_uclaseniorscholar', $a)),
                          array('class' => 'linespacer'));

    // Columns to display.
    /* Build display row:
     * [0] - invitee email
     * [1] - course
     * [2] - instructor
     * [1] - role
     * [2] - status
     * [3] - dates sent
     * [4] - expiration date
     */
    $columns = array(
            'email'             => get_string('email', 'tool_uclaseniorscholar'),
            'course'            => get_string('course', 'tool_uclaseniorscholar'),
            'instructor'        => get_string('instructor', 'tool_uclaseniorscholar'),
            'role'              => get_string('historyrole', 'enrol_invitation'),
            'status'            => get_string('historystatus', 'enrol_invitation'),
            'datesent'          => get_string('historydatesent', 'enrol_invitation'),
            'dateexpiration'    => get_string('historydateexpiration', 'enrol_invitation')
    );

    $table = new flexible_table('invitehistorybyuser');
    $table->define_columns(array_keys($columns));
    $table->define_headers(array_values($columns));
    $table->define_baseurl($PAGE->url);
    $table->set_attribute('class', 'generaltable');

    $table->setup();

    $rolecache = array();  // To store roles.

    // Loop throught output senior scholar.
    foreach ($list as $email => $record) {
        $row = array();
        $emailcolumn = new html_table_cell();
        $emailcolumn->rowspan = count($record);
        $emailcolumn = $email;
        $row[0] = $emailcolumn;
        // Loop through the courses this person taken and being invited.
        foreach ($record as $courseid => $courselist) {
            $courseoutput = uclacoursecreator::make_course_shortname($courselist[1]);
            // Loop cross listed courses.
            $i = 0;
            while (!empty($courselist[0]) && $i <= $maxcrosslistshown) {
                $course = array_shift($courselist[0]);
                $courseoutput .= ' / ' . uclacoursecreator::make_course_shortname($course);
                $i++;
            }

            // If the course is cancelled, mark it.
            if ($courselist[1]->enrolstat == 'X') {
                $courseoutput .= html_writer::start_span('coursecancelled') . ' (' .
                                 get_string('coursecanlled', 'tool_uclaseniorscholar') . ')' .
                                 html_writer::end_span();
            }

            // Course.
            $row[1] = html_writer::link(new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_history.php',
                                   array('courseid' => $courselist[1]->courseid)), $courseoutput, array('target' => '_blank'));
            // Instructor.
            $row[2] = $courselist[1]->instructor;
            // Role.
            if (empty($rolecache[$courselist[1]->roleid])) {
                $role = $DB->get_record('role', array('id' => $courselist[1]->roleid));
                if (empty($role)) {
                    // Cannot find role, give error.
                    $rolecache[$courselist[1]->roleid] =
                            get_string('historyundefinedrole', 'enrol_invitation');
                } else {
                    $rolecache[$courselist[1]->roleid] = $role->name;
                }
            }
            $row[3] = $rolecache[$courselist[1]->roleid];
            // Status.
            $invitationmanager = new invitation_manager($courseid, true);
            $status = $invitationmanager->get_invite_status($courselist[1]);
            $row[4] = $status;

            // If status was used, figure out who used the invite.
            $result = $invitationmanager->who_used_invite($courselist[1]);
            if (!empty($result)) {
                $row[4] .= get_string('used_by', 'enrol_invitation', $result);
            }

            // If user's enrollment expired or will expire, let viewer know.
            $result = $invitationmanager->get_access_expiration($courselist[1]);
            if (!empty($result)) {
                $row[4] .= ' ' . $result;
            }

            // When was the invite sent?
            $row[5] = date('M j, Y g:ia', $courselist[1]->timesent);

            // When does the invite expire?
            $row[6] = date('M j, Y g:ia', $courselist[1]->timeexpiration);

            // If status is active, then state how many days/minutes left.
            if ($status == get_string('status_invite_active', 'enrol_invitation')) {
                $expirestext = sprintf('%s %s',
                        get_string('historyexpires_in', 'enrol_invitation'),
                        distance_of_time_in_words(time(), $courselist[1]->timeexpiration, true));
                $row[6] .= ' ' . html_writer::tag('span', '(' . $expirestext . ')', array('expires-text'));
            }
            $table->add_data($row);
        }
    }

    $table->finish_output();

} else {
    echo html_writer::tag('div', strtoupper(get_string('list_by_course_term', 'tool_uclaseniorscholar', $a)),
                          array('class' => 'linespacer'));

    $columns = array(
            'course'            => get_string('course', 'tool_uclaseniorscholar'),
            'instructor'        => get_string('instructor', 'tool_uclaseniorscholar'),
            'invite'            => '',
            'history'           => '');
    $table = new flexible_table('courselistbyinstr');
    $table->define_columns(array_keys($columns));
    $table->define_headers(array_values($columns));
    $table->define_baseurl($PAGE->url);
    $table->set_attribute('class', 'generaltable');
    $table->setup();

    // For cross listed courses.
    foreach ($list as $key => $courselist) {
        // Output course.
        // List host course.
        $courseoutput = uclacoursecreator::make_course_shortname($courselist[1]);
        // Loop cross listed courses.
        $i = 0;
        while (!empty($courselist[0]) && $i <= $maxcrosslistshown) {
            $course = array_shift($courselist[0]);
            $courseoutput .= ' / ' . uclacoursecreator::make_course_shortname($course);
            $i++;
        }

         // If the course is cancelled, mark it.
        if ($courselist[1]->enrolstat == 'X') {
            $courseoutput .= html_writer::start_span('coursecancelled') . ' (' .
                             get_string('coursecanlled', 'tool_uclaseniorscholar') . ')' .
                             html_writer::end_span();
        }

        $row = array();
        $row[0] = $courseoutput;
        $row[1] = $courselist[1]->instructor;
        $row[2] = html_writer::link(new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_invitation.php',
                                   array('courseid' => $courselist[1]->courseid)),
                                   get_string('invite_link', 'tool_uclaseniorscholar'), array('target' => '_blank'));
        $row[3] = html_writer::link(new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_history.php',
                                   array('courseid' => $courselist[1]->courseid)),
                                   get_string('history_link', 'tool_uclaseniorscholar'), array('target' => '_blank'));
        $table->add_data($row);
    }
    $table->finish_output();
}

echo html_writer::end_tag('div');
echo $OUTPUT->footer();
