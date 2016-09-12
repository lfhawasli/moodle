<?php
// This file is part of the UCLA Senior Scholar Site Invitation Plugin for Moodle - http://moodle.org/
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
 * Local library file to include classes and functions used.
 *
 * @package    tool_uclaseniorscholar
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Invitation manager that handles the handling of senior scholar invitation information.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/enrol/invitation/locallib.php');

class seniorscholar_invitation_manager extends invitation_manager {

    /**
     * Course id.
     * @var int
     */
    private $courseid = null;

    /**
     * The invitation enrol instance of a course.
     *
     * @var int
     */
    private $enrolinstance = null;

    /**
     * Constant for revoking an active invitation.
     */
    const INVITE_REVOKE = 1;

    /**
     * Constant for extending the expiration time of an active invitation.
     */
    const INVITE_EXTEND = 2;

    /**
     * Constant for resending an expired or revoked invite.
     */
    const INVITE_RESEND = 3;

    /**
     * Send invitation (create a unique token for each of them).
     *
     * @param obj $data         data processed from the invite form, or an invite if resending
     * @param bool $resend      resend the invite specified by $data
     * @return int $retval      id of invitation in enrol_invitation table
     */
    public function send_invitations($data, $resend = false) {
        global $DB, $CFG, $SITE, $USER;

        if (seniorscholar_has_access($USER)) {
            // Get course record, to be used later.
            $course = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);

            if (!empty($data->email)) {

                // Create a new token only if we are not resending an active invite.
                if ($resend) {
                    $token = $data->token;
                } else {
                    // Create unique token for invitation.
                    do {
                        $token = uniqid();
                        $existingtoken = $DB->get_record('enrol_invitation', array('token' => $token));
                    } while (!empty($existingtoken));
                }

                // Save token information in config (token value, course id, TODO: role id).
                $invitation = new stdClass();
                $invitation->email = $data->email;
                $invitation->courseid = $data->courseid;
                $invitation->token = $token;
                $invitation->tokenused = false;
                $invitation->roleid = $resend ? $data->roleid : $data->role_group['roleid'];
                $invitation->subject = '';

                // Set time.
                $timesent = time();
                $invitation->timesent = $timesent;
                $invitation->timeexpiration = $timesent +
                        get_config('enrol_invitation', 'inviteexpiration');

                // Update invite to have the proper timesent/timeexpiration.
                if ($resend) {
                    $DB->set_field('enrol_invitation', 'timeexpiration', $invitation->timeexpiration,
                                  array('courseid' => $data->courseid,  'id' => $data->id));

                    // Prepend subject heading with a 'Reminder' string.
                    $invitation->subject = get_string('reminder', 'enrol_invitation');
                }

                $invitation->subject .= $data->subject;

                $invitation->inviterid = $USER->id;
                $invitation->notify_inviter = empty($data->notify_inviter) ? 0 : 1;

                // Construct message: custom (if any) + template.
                $messagehtml = '';
                $messagetxt = '';
                if (!empty($data->message)) {
                    $messagehtml .= get_string('administratormsghtml', 'tool_uclaseniorscholar',
                            $data->message);
                    $messagetxt .= get_string('administratormsgtxt', 'tool_uclaseniorscholar',
                            $data->message);
                    $invitation->message = $data->message;
                }

                $messageparams = new stdClass();
                $messageparams->fullname =
                        sprintf('%s: %s', $course->shortname, $course->fullname);
                $messageparams->expiration = date('M j, Y g:ia', $invitation->timeexpiration);
                $inviteurl = new moodle_url('/enrol/invitation/enrol.php',
                                array('token' => $token));
                $inviteurl = $inviteurl->out(false);
                $sitelink = new moodle_url('/course/view.php', array('id' => $course->id));
                $sitelink = $sitelink->out(false);

                // Append privacy notice, if needed.
                $privacynotice = $this->get_project_privacy_notice($course->id);
                if (!empty($privacynotice)) {
                    $inviteurl .= $privacynotice;
                }

                $messageparams->inviteurl = $inviteurl;
                $messageparams->sitelink = $sitelink;
                $messageparams->seniorscholarsupportemail = get_config('tool_uclaseniorscholar', 'seniorscholarsupportemail');
                $messagehtml .= get_string('emailmsghtml', 'tool_uclaseniorscholar', $messageparams);
                $messagetxt .= get_string('emailmsgtxt', 'tool_uclaseniorscholar', $messageparams);

                if (!$resend) {
                    $objid = $DB->insert_record('enrol_invitation', $invitation);
                    $retval = $objid;
                } else {
                    $retval = $data->id;
                }

                // Always show FROM $CFG->seniorscholarsupportemail email address.
                $fromuser = new stdClass();
                $fromuser->email = get_config('tool_uclaseniorscholar', 'seniorscholarsupportemail');
                $fromuser->firstname = '';
                $fromuser->lastname = get_string('fromlastname', 'tool_uclaseniorscholar');
                $fromuser->maildisplay = true;
                // Moodle 2.7 introduced new username fields.
                $fromuser->alternatename = '';
                $fromuser->firstnamephonetic = '';
                $fromuser->lastnamephonetic = '';
                $fromuser->middlename = '';

                // Send invitation to the user.
                $contactuser = new stdClass();
                $contactuser->email = $invitation->email;
                $contactuser->firstname = '';
                $contactuser->lastname = '';
                $contactuser->maildisplay = true;
                $contactuser->id = 0;
                // Moodle 2.7 introduced new username fields.
                $contactuser->alternatename = '';
                $contactuser->firstnamephonetic = '';
                $contactuser->lastnamephonetic = '';
                $contactuser->middlename = '';
                $contactuser->mailformat = 1;

                email_to_user($contactuser, $fromuser, $invitation->subject, $messagetxt, $messagehtml);

                // Log activity after sending the email.
                if ($resend) {
                    $event = \enrol_invitation\event\invitation_extended::create(array(
                            'objectid' => $data->id,
                            'context' => context_course::instance($course->id),
                            'other' => $course->fullname
                        ));
                    $event->trigger();
                } else {
                    $event = \enrol_invitation\event\invitation_sent::create(array(
                            'objectid' => $objid,
                            'context' => context_course::instance($course->id),
                            'other' => $course->fullname
                        ));
                    $event->trigger();
                }
            }
            return $retval;
        } else {
            throw new moodle_exception('cannotsendinvitation', 'enrol_invitation',
                    new moodle_url('/admin/tool/uclaseniorscholar/index.php'));
        }
    }

    /**
     * Updates invitation if inviter chooses to revoke, extend, or resend
     *
     * @param obj $invite       an instance of an enrol_invitation record
     * @param int $actionid     either INVITE_REVOKE, INVITE_EXTEND, or INVITE_RESEND
     */
    public function update_invitation($invite, $actionid) {
        global $DB;
        $course = $DB->get_record('course', array('id' => $invite->courseid));

        if ($actionid == self::INVITE_REVOKE) {
            $DB->set_field('enrol_invitation', 'timeexpiration', $invite->timesent,
                    array('courseid' => $invite->courseid, 'id' => $invite->id));

            $event = \enrol_invitation\event\invitation_revoked::create(array(
                    'objectid' => $invite->id,
                    'context' => context_course::instance($invite->courseid),
                    'other' => $course->fullname
            ));
            $event->trigger();
        } else if ($actionid == self::INVITE_EXTEND) {
            $this->send_invitations($invite, true);
        } else if ($actionid == self::INVITE_RESEND) {
            $redirect = new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_invitation.php',
                    array('courseid' => $invite->courseid, 'inviteid' => $invite->id));
            redirect($redirect);
        }
    }

    /**
     * Return all invites for given course.
     *
     * @param int $courseid
     * @return array
     */
    public function get_invites($courseid = null, $userids = null) {
        global $DB;

        if (empty($userids)) {
            $returnurl = new moodle_url('/admin/tool/uclaseniorscholar/index.php');
            throw new moodle_exception('nopermissiontosendinvitation' ,
            'tool_uclaseniorscholar', $returnurl);
        }
        if (empty($courseid)) {
            $invites = $DB->get_records_list('enrol_invitation', 'inviterid', $userids);
        } else {
            list($listofinviterssql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $params['courseid'] = $courseid;
            $sql = "SELECT i.*, ue.id as ueid, ue.enrolid
                      FROM {enrol_invitation} i
                 LEFT JOIN {user_enrolments} ue
                        ON i.userid = ue.userid
                 LEFT JOIN {enrol} e
                        ON ue.enrolid = e.id
                     WHERE i.courseid = :courseid
                       AND i.inviterid $listofinviterssql";
            $invites = $DB->get_records_sql($sql, $params);
        }
        return $invites;
    }
}

/**
 * Prints out tabs and highlights the appropiate current tab.
 *
 * @param string $active_tab  Either 'invite' or 'history'
 */
function output_page_tabs($activetab, $courseid) {
    global $CFG;

    $tabs[] = new tabobject('invite',
                    new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_invitation.php',
                            array('courseid' => $courseid)),
                    get_string('inviteusers', 'tool_uclaseniorscholar'));
    $tabs[] = new tabobject('history',
                    new moodle_url('/admin/tool/uclaseniorscholar/seniorscholar_history.php',
                            array('courseid' => $courseid)),
                    get_string('invitehistory', 'tool_uclaseniorscholar'));

    // Display tabs here.
    print_tabs(array($tabs), $activetab);
}