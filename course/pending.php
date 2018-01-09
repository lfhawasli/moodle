<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Allow the administrator to look through a list of course requests and approve or reject them.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package course
 */

require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/request_form.php');

require_login();
require_capability('moodle/site:approvecourse', context_system::instance());

$approve = optional_param('approve', 0, PARAM_INT);
$reject = optional_param('reject', 0, PARAM_INT);
$request = optional_param('request', 0, PARAM_INT);

$baseurl = $CFG->wwwroot . '/course/pending.php';
admin_externalpage_setup('coursespending');

/// Process approval of a course.
if (!empty($approve) and confirm_sesskey()) {
    /// Load the request.
    $course = new course_request($approve);
    $courseid = $course->approve();

    if ($courseid !== false) {
        // START UCLA MOD: CCLE-2389 - Approving site indicator site, and sending 'approved' param.
        siteindicator_manager::approve($courseid, $approve);
        // END UCLA MOD: CCLE-2389
        redirect(new moodle_url('/course/edit.php', ['id' => $courseid, 'returnto' => 'pending']));
    } else {
        print_error('courseapprovedfailed');
    }
}

/// Process rejection of a course.
if (!empty($reject)) {
    // Load the request.
    $course = new course_request($reject);

    // Prepare the form.
    $rejectform = new reject_request_form($baseurl);
    $default = new stdClass();
    $default->reject = $course->id;
    $rejectform->set_data($default);
    
/// Standard form processing if statement.
    if ($rejectform->is_cancelled()){
        redirect($baseurl);

    } else if ($data = $rejectform->get_data()) {
        // START UCLAMOD CCLE-2389 - reject a collab site request
        /// Reject the request
        if($data->email) {
            $course->reject($data->rejectnotice);
        } else {
            $course->delete();
        }
        siteindicator_manager::reject($course->id);

        /// Redirect back to the course listing.
        redirect($baseurl, get_string('courserejected', 'tool_uclasiteindicator'));
        // END UCLAMOD CCLE-2389
    }

/// Display the form for giving a reason for rejecting the request.
    echo $OUTPUT->header($rejectform->focus());
    $rejectform->display();
    echo $OUTPUT->footer();
    exit;
}

/// Print a list of all the pending requests.
echo $OUTPUT->header();

// START UCLA MOD CCLE-2389 - show only a requested course
if(!empty($request)) {
    $pending = $DB->get_records('course_request', array('id' => $request));
} else {
    $pending = $DB->get_records('course_request');
}
// END UCLA MOD CCLE-2389
if (empty($pending)) {
    echo $OUTPUT->heading(get_string('nopendingcourses', 'tool_uclasiteindicator'));
} else {
    echo $OUTPUT->heading(get_string('coursespending'));
    $role = $DB->get_record('role', array('id' => $CFG->creatornewroleid), '*', MUST_EXIST);
    echo $OUTPUT->notification(get_string('courserequestwarning', 'core', role_get_name($role)), 'notifyproblem');

/// Build a table of all the requests.
    $table = new html_table();
    $table->attributes['class'] = 'pendingcourserequests generaltable';
    $table->align = array('center', 'center', 'center', 'center', 'center', 'center');
    // START UCLA MOD: CCLE-6383 - Collab site requests need timestamp
    $table->head = array(get_string('timerequestedcourse'), get_string('shortnamecourse'), get_string('fullnamecourse'), get_string('requestedby'),
    // END UCLA MOD: CCLE-6383
            get_string('summary'), get_string('category'), get_string('requestreason'), get_string('action'));

    foreach ($pending as $course) {
        $course = new course_request($course);

        // Check here for shortname collisions and warn about them.
        $course->check_shortname_collision();
        
        // START UCLA MOD CCLE-2389 - Get site request obj
        $request = new siteindicator_request($course->id);
        // Skip requests in categories that user does not have Manager access.
        $categorycontext = context_coursecat::instance($request->request->categoryid, IGNORE_MISSING);
        if (empty($categorycontext)) {
            // Check default category.
            $categorycontext = context_coursecat::instance($CFG->defaultrequestcategory);
        }
        if (!has_capability('moodle/course:update', $categorycontext)) {
            continue;
        }

        $category = $course->get_category();

        $row = array();
        // START UCLA MOD: CCLE-6383 - Collab site requests need timestamp
        $row[] = !empty($request->request->timerequested) ? userdate($request->request->timerequested) : '';
        // END UCLA MOD: CCLE-6383
        $row[] = format_string($course->shortname);
        $row[] = format_string($course->fullname);
        $row[] = fullname($course->get_requester());
        $row[] = format_text($course->summary, $course->summaryformat);
        $row[] = $category->get_formatted_name();
        $row[] = format_string($course->reason);
        $row[] = $OUTPUT->single_button(new moodle_url($baseurl, array('approve' => $course->id, 'sesskey' => sesskey())), get_string('approve'), 'get') .
                 $OUTPUT->single_button(new moodle_url($baseurl, array('reject' => $course->id)), get_string('rejectdots'), 'get');

    /// Add the row to the table.
        $table->data[] = $row;
    }

/// Display the table.
    echo html_writer::table($table);

/// Message about name collisions, if necessary.
    if (!empty($collision)) {
        print_string('shortnamecollisionwarning');
    }
}

/// Finish off the page.
// START UCLA MOD CCLE-2389 - redirect to homepage instead
echo $OUTPUT->single_button($CFG->wwwroot . '/my/', get_string('backtocourselisting', 'tool_uclasiteindicator'));
// END UCLA MOD CCLE-2389
echo $OUTPUT->footer();
