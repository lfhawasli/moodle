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
 * Page to fill out senior scholar invitation.
 *
 * @package    tool_seniorscholar
 * @copyright  2015 UC Regents
 * @copyright  2011 Jerome Mouneyrac {@link http://www.moodleitandme.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/seniorscholar_invitation_form.php');
require_once(dirname(__FILE__) . '/lib.php');
$courseid = required_param('courseid', PARAM_INT);
$inviteid = optional_param('inviteid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$fullname = $course->fullname;
$context = context_course::instance($courseid);

require_login();
if (!seniorscholar_has_access($USER)) {
    print_error('nopermissions');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/uclaseniorscholar/index.php'));
$PAGE->set_pagelayout('standard');
$pagetitle = get_string('pluginname_desc', 'tool_uclaseniorscholar');
$PAGE->set_heading($pagetitle);
$PAGE->set_title($pagetitle);
$PAGE->navbar->add($pagetitle);

echo $OUTPUT->header();

// Print out a heading.
echo $OUTPUT->heading($pagetitle, 2, 'headingblock');

output_page_tabs('invite', $courseid);  // OUTPUT page tabs.
$invitationmanager = new seniorscholar_invitation_manager($courseid, true);

// Make sure that site has invitation plugin installed.
$instance = $invitationmanager->get_invitation_instance($courseid, true);

// If the user was sent to this page by selecting 'resend invite', then
// prefill the form with the data used to resend the invite.
$prefilled = array();
if ($inviteid) {
    if ( $invite = $DB->get_record('enrol_invitation', array('courseid' => $courseid, 'id' => $inviteid)) ) {
        $prefilled['roleid'] = $invite->roleid;
        $prefilled['email'] = $invite->email;
        $prefilled['subject'] = $invite->subject;
        $prefilled['message'] = $invite->message;
        $prefilled['show_from_email'] = $invite->show_from_email;
        $prefilled['notify_inviter'] = $invite->notify_inviter;
    } else {
        print_error('invalidinviteid');
    }
}

$mform = new seniorscholar_invitation_form(null, array('course' => $course, 'prefilled' => $prefilled),
        'post', '', array('class' => 'mform-invite'));
$mform->set_data($invitationmanager);
$data = $mform->get_data();
if ($data and confirm_sesskey()) {
    $emaillist = prepare_emails($data->email);
    foreach ($emaillist as $email) {
        $data->email = $email;
        $invitationmanager->send_invitations($data);
    }

    $searchcourseurl = new moodle_url('/admin/tool/uclaseniorscholar/index.php');
    $searchcourseret = new single_button($searchcourseurl, get_string('returntosearchcourse',
                            'tool_uclaseniorscholar'), 'get');

    $secturl = new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_invitation.php',
                    array('courseid' => $courseid));
    $sectret = new single_button($secturl, get_string('returntoinvite',
                            'tool_uclaseniorscholar'), 'get');

    echo $OUTPUT->confirm(get_string('invitationsuccess', 'tool_uclaseniorscholar'),
            $sectret, $searchcourseret);

} else {
    $mform->display();
}

echo $OUTPUT->footer();