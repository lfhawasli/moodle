<?php
// This file is part of the UCLA Senior Scholar site Invitation Plugin for Moodle - http://moodle.org/
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
 * Viewing senior scholar invitation history script.
 *
 * @package    tool_uclaseniorscholar
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/seniorscholar_invitation_form.php');

require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->libdir . '/tablelib.php');

// For distance_of_time_in_words.
require_once($CFG->dirroot . '/local/ucla/datetimehelpers.php');

$courseid = required_param('courseid', PARAM_INT);
$inviteid = optional_param('inviteid', 0, PARAM_INT);
$actionid = optional_param('actionid', 0, PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login();
$context = context_course::instance($courseid);
if (!seniorscholar_has_access($USER)) {
    print_error('nopermissions');
}

// Set up page.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/admin/tool/uclaseniorscholar/invitation_history.php', array('courseid' => $courseid)));
$PAGE->set_pagelayout('admin');
$PAGE->set_course($course);
$pagetitle = get_string('invitehistory', 'tool_uclaseniorscholar');
$PAGE->set_heading($pagetitle);
$PAGE->set_title($pagetitle);
$PAGE->navbar->add($pagetitle);

// Do not display the page if we are going to be redirecting the user.

if ($actionid != invitation_manager::INVITE_RESEND) {
    // OUTPUT form.
    echo $OUTPUT->header();

    // Print out a heading.
    echo $OUTPUT->heading($pagetitle, 2, 'headingblock');

    // OUTPUT page tabs.
    output_page_tabs('history', $courseid);
}
// Course must have invitation plugin installed (will give error if not found).
$invitationmanager = new seniorscholar_invitation_manager($courseid, true);
$userids = get_seniorscholar_admin_userid();
// Get invites and display them.
$invites = $invitationmanager->get_invites($courseid, $userids);

if (empty($invites)) {
    echo $OUTPUT->notification(
            get_string('noinvitehistory', 'tool_uclaseniorscholar'), 'notifymessage');
} else {

    // Update invitation if the user decided to revoke/extend/resend an invite.
    if ($inviteid && $actionid) {
        if (!$currinvite = $invites[$inviteid]) {
            print_error('invalidinviteid');
        }

        $invitationmanager->update_invitation($currinvite, $actionid);
        if ($actionid == invitation_manager::INVITE_REVOKE) {
            echo $OUTPUT->notification(get_string('revoke_invite_sucess', 'tool_uclaseniorscholar'), 'notifysuccess');
        } else if ($actionid == invitation_manager::INVITE_EXTEND) {
            echo $OUTPUT->notification(get_string('extend_invite_sucess', 'tool_uclaseniorscholar'), 'notifysuccess');
        } else if ($actionid != invitation_manager::INVITE_RESEND) {
            print_error('invalidactionid');
        }

        // Get the updated invites.
        $invites = $invitationmanager->get_invites($courseid, $userids);
    }

    // Columns to display.
    $columns = array(
        'tofrom'            => get_string('historytofrom', 'tool_uclaseniorscholar'),
        'role'              => get_string('historyrole', 'tool_uclaseniorscholar'),
        'status'            => get_string('historystatus', 'tool_uclaseniorscholar'),
        'datesent'          => get_string('historydatesent', 'tool_uclaseniorscholar'),
        'dateexpiration'    => get_string('historydateexpiration', 'tool_uclaseniorscholar'),
        'actions'           => get_string('historyactions', 'tool_uclaseniorscholar')
    );

    $table = new flexible_table('invitehistory');
    $table->define_columns(array_keys($columns));
    $table->define_headers(array_values($columns));
    $table->define_baseurl($PAGE->url);
    $table->set_attribute('class', 'generaltable');

    $table->setup();

    $rolecache = array();
    foreach ($invites as $invite) {
        /* Build display row:
         * [0] - invitee and inviter
         * [1] - role
         * [2] - status
         * [3] - dates sent
         * [4] - expiration date
         * [5] - actions
         */

        // Display invitee and inviter.  Inviter always show from $CFG->seniorscholarsupportemail email address.
        $row[0] = $invite->email . " - " . get_config('tool_uclaseniorscholar', 'seniorscholarsupportemail');

        // Figure out invited role.
        if (empty($rolecache[$invite->roleid])) {
            $role = $DB->get_record('role', array('id' => $invite->roleid));
            if (empty($role)) {
                // Cannot find role, give error.
                $rolecache[$invite->roleid] =
                        get_string('historyundefinedrole', 'tool_uclaseniorscholar');
            } else {
                $rolecache[$invite->roleid] = $role->name;
            }
        }
        $row[1] = $rolecache[$invite->roleid];

        // What is the status of the invite?
        $status = $invitationmanager->get_invite_status($invite);
        $row[2] = $status;

        // If status was used, figure out who used the invite.
        $result = $invitationmanager->who_used_invite($invite);
        if (!empty($result)) {
            $row[2] .= get_string('used_by', 'tool_uclaseniorscholar', $result);
        }

        // If user's enrollment expired or will expire, let viewer know.
        $result = $invitationmanager->get_access_expiration($invite);
        if (!empty($result)) {
            $row[2] .= ' ' . $result;
        }

        // When was the invite sent?
        $row[3] = date('M j, Y g:ia', $invite->timesent);

        // When does the invite expire?
        $row[4] = date('M j, Y g:ia', $invite->timeexpiration);

        // If status is active, then state how many days/minutes left.
        if ($status == get_string('status_invite_active', 'tool_uclaseniorscholar')) {
            $expirestext = sprintf('%s %s',
                get_string('historyexpires_in', 'tool_uclaseniorscholar'),
                distance_of_time_in_words(time(), $invite->timeexpiration, true));
            $row[4] .= ' ' . html_writer::tag('span', '(' . $expirestext . ')', array('expires-text'));
        }

        // Are there any actions user can do?
        $row[5] = '';
        $url = new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_history.php',
            array('courseid' => $courseid, 'inviteid' => $invite->id));
        // Same if statement as above, seperated for clarity.
        if ($status == get_string('status_invite_active', 'tool_uclaseniorscholar')) {
            // Create link to revoke an invite.
            $url->param('actionid', invitation_manager::INVITE_REVOKE);
            $row[5] .= html_writer::link($url, get_string('action_revoke_invite', 'tool_uclaseniorscholar'));
            $row[5] .= html_writer::start_tag('br');
            // Create link to extend an invite.
            $url->param('actionid', invitation_manager::INVITE_EXTEND);
            $row[5] .= html_writer::link($url, get_string('action_extend_invite', 'tool_uclaseniorscholar'));
        } else if ($status == get_string('status_invite_expired', 'tool_uclaseniorscholar')
            || $status == get_string('status_invite_revoked', 'tool_uclaseniorscholar')) {
            // Create link to resend invite.
            $url->param('actionid', invitation_manager::INVITE_RESEND);
            $row[5] .= html_writer::link($url, get_string('action_resend_invite', 'tool_uclaseniorscholar'));
        } else if ($status == get_string('status_invite_used', 'tool_uclaseniorscholar') 
                   && $invitationmanager->get_access_expiration($invite) != get_string('status_invite_used_noaccess', 'tool_uclaseniorscholar')) {
            $url = new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_unenroluser.php',
            array('courseid' => $courseid, 'ueid' => $invite->ueid, 'userid' => $invite->userid));
            $row[5] .= html_writer::link($url, get_string('action_unenroll', 'tool_uclaseniorscholar'));
        }

        $table->add_data($row);
    }

    $table->finish_output();
}

echo $OUTPUT->footer();