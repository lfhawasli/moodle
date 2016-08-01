<?php
// This file is part of the UCLA Site Invitation Plugin for Moodle - http://moodle.org/
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
 * This page will try to enrol the user.
 *
 * @package    enrol_invitation
 * @copyright  2013 UC Regents
 * @copyright  2011 Jerome Mouneyrac {@link http://www.moodleitandme.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require($CFG->dirroot . '/enrol/invitation/locallib.php');

require_login(null, false);

// Check if param token exist. Support checking for both old
// "enrolinvitationtoken" token name and new "token" parameters.
$enrolinvitationtoken = optional_param('enrolinvitationtoken', null, PARAM_ALPHANUM);
if (empty($enrolinvitationtoken)) {
    $enrolinvitationtoken = required_param('token', PARAM_ALPHANUM);
}

// Retrieve the token info.
$invitation = $DB->get_record('enrol_invitation', array('token' => $enrolinvitationtoken));

// Make sure that course exists.
$course = $DB->get_record('course', array('id' => $invitation->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

// If token is valid, enrol the user into the course.
$invalidinvite = (empty($invitation) or empty($invitation->courseid));
$oldinvite = ($invitation->timeexpiration < time() or $invitation->tokenused == true);
if ($invalidinvite or $oldinvite) {
    $courseid = empty($invitation->courseid) ? $SITE->id : $invitation->courseid;
    $event = \enrol_invitation\event\invitation_expired::create(array(
            'objectid' => $invitation->id,
            'context' => context_course::instance($courseid),
            'other' => $course->fullname
            ));
    $event->trigger();

    // Create error page for expired enrol token.
    $return = new moodle_url('/');
    $PAGE->set_url('/admin/enrol.php');
    $PAGE->set_context(context_system::instance());
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('expiredtoken','enrol_invitation'), 'enrol_invitation');
    echo $OUTPUT->continue_button($return);
    echo $OUTPUT->footer();
    exit;
}

// Set up page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/enrol/invitation/enrol.php',
        array('token' => $enrolinvitationtoken)));
$PAGE->set_pagelayout('course');
$PAGE->set_course($course);
$pagetitle = get_string('invitation_acceptance_title', 'enrol_invitation');
$PAGE->set_heading($pagetitle);
$PAGE->set_title($pagetitle);
$PAGE->navbar->add($pagetitle);

// Get.
$invitationmanager = new invitation_manager($invitation->courseid);
$instance = $invitationmanager->get_invitation_instance($invitation->courseid);

// First multiple check related to the invitation plugin config.
// @Todo better handle exceptions here.

if (isguestuser()) {
    // Can not enrol guest!!
    echo $OUTPUT->header();

    // Print out a heading.
    echo $OUTPUT->heading($pagetitle, 2, 'headingblock');

    echo $OUTPUT->box_start('generalbox', 'notice');

    $notice_object = prepare_notice_object($invitation);
    echo get_string('loggedinnot', 'enrol_invitation', $notice_object);
    $loginbutton = new single_button(new moodle_url($CFG->wwwroot
            . '/login/index.php'), get_string('login'));

    echo $OUTPUT->render($loginbutton);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

// Have invitee confirm their acceptance of the site invitation.
$confirm = optional_param('confirm', 0, PARAM_BOOL);
if (empty($confirm)) {
    echo $OUTPUT->header();

    // Print out a heading.
    echo $OUTPUT->heading($pagetitle, 2, 'headingblock');

    $event = \enrol_invitation\event\invitation_viewed::create(array(
            'objectid' => $invitation->id,
            'context' => context_course::instance($invitation->courseid),
            'other' => $course->fullname
            ));
    $event->trigger();

    $accepturl = new moodle_url('/enrol/invitation/enrol.php',
            array('token' => $invitation->token, 'confirm' => true));
    $accept = new single_button($accepturl,
            get_string('invitationacceptancebutton', 'enrol_invitation'), 'get');
    $cancel = new moodle_url('/');

    $notice_object = prepare_notice_object($invitation);

    $invitationacceptance = get_string('invitationacceptance',
            'enrol_invitation', $notice_object);

    $privacy_notice = invitation_manager::get_project_privacy_notice($course->id, true);
    if (!empty($privacy_notice)) {
        $invitationacceptance .= $privacy_notice;
    }

    // If invitation has "daysexpire" set, then give notice.
    if (!empty($invitation->daysexpire)) {
        $invitationacceptance .= html_writer::tag('p',
                get_string('daysexpire_notice', 'enrol_invitation',
                        $invitation->daysexpire));
    }

    echo $OUTPUT->confirm($invitationacceptance, $accept, $cancel);

    echo $OUTPUT->footer();
    exit;
} else {
    $invitationmanager->accept_invite($invitation, $course);

    $courseurl = new moodle_url('/course/view.php', array('id' => $invitation->courseid));
    redirect($courseurl);
}
