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
$invitation = $DB->get_record('enrol_invitation',
        array('token' => $enrolinvitationtoken, 'tokenused' => false));

// Make sure that course exists.
$course = $DB->get_record('course', array('id' => $invitation->courseid), '*', MUST_EXIST);
$context = context_course::instance($course->id);

// If token is valid, enrol the user into the course.
if (empty($invitation) or empty($invitation->courseid) or $invitation->timeexpiration < time()) {
    $courseid = empty($invitation->courseid) ? $SITE->id : $invitation->courseid;
    $event = \enrol_invitation\event\invitation_expired::create(array(
            'objectid' => $invitation->id,
            'context' => context_course::instance($courseid),
            'other' => $course->fullname
            ));
    $event->trigger();
    throw new moodle_exception('expiredtoken', 'enrol_invitation');
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
    if ($invitation->email != $USER->email) {
        $event = \enrol_invitation\event\invitation_mismatch::create(array(
                'objectid' => $invitation->id,
                'context' => context_course::instance($invitation->courseid),
                'other' => $course->fullname
                ));
        $event->trigger();
    }
    // User confirmed, so add them.
    require_once($CFG->dirroot . '/enrol/invitation/locallib.php');
    $invitationmanager = new invitation_manager($invitation->courseid);
    $invitationmanager->enroluser($invitation);

    $event = \enrol_invitation\event\invitation_claimed::create(array(
            'objectid' => $invitation->id,
            'context' => context_course::instance($invitation->courseid),
            'other' => $course->fullname  
            ));
    $event->trigger();

    // Set token as used and mark which user was assigned the token.
    $invitation->tokenused = true;
    $invitation->timeused = time();
    $invitation->userid = $USER->id;
    $DB->update_record('enrol_invitation', $invitation);

    if (!empty($invitation->notify_inviter)) {
        // Send an email to the user who sent the invitation.
        $inviter = $DB->get_record('user', array('id' => $invitation->inviterid));

        // This is inviter's information.
        $contactuser = new object;
        $contactuser->email = $inviter->email;
        $contactuser->firstname = $inviter->firstname;
        $contactuser->lastname = $inviter->lastname;
        $contactuser->maildisplay = true;
        $contactuser->id = $inviter->id;
        // Moodle 2.7 introduced new username fields.
        $contactuser->alternatename = '';
        $contactuser->firstnamephonetic = '';
        $contactuser->lastnamephonetic = '';
        $contactuser->middlename = '';

        $emailinfo = prepare_notice_object($invitation);
        $emailinfo->userfullname = trim($USER->firstname . ' ' . $USER->lastname);
        $emailinfo->useremail = $USER->email;
        $courseenrolledusersurl = new moodle_url('/enrol/users.php',
                array('id' => $invitation->courseid));
        $emailinfo->courseenrolledusersurl = $courseenrolledusersurl->out(false);
        $invitehistoryurl = new moodle_url('/enrol/invitation/history.php',
                array('courseid' => $invitation->courseid));
        $emailinfo->invitehistoryurl = $invitehistoryurl->out(false);

        $course = $DB->get_record('course', array('id' => $invitation->courseid));
        $emailinfo->coursefullname = sprintf('%s: %s', $course->shortname, $course->fullname);
        $emailinfo->sitename = $SITE->fullname;
        $siteurl = new moodle_url('/');
        $emailinfo->siteurl = $siteurl->out(false);

        email_to_user($contactuser, get_admin(),
                get_string('emailtitleuserenrolled', 'enrol_invitation', $emailinfo),
                get_string('emailmessageuserenrolled', 'enrol_invitation', $emailinfo));
    }

    $courseurl = new moodle_url('/course/view.php', array('id' => $invitation->courseid));
    redirect($courseurl);
}
