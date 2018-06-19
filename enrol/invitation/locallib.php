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
 * Local library file to include classes and functions used.
 *
 * @package    enrol_invitation
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Invitation manager that handles the handling of invitation information.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class invitation_manager {
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
     * Constructor.
     *
     * @param int $courseid
     * @param boolean $instancemustexist
     */
    public function __construct($courseid, $instancemustexist = true) {
        $this->courseid = $courseid;
        $this->enrolinstance = $this->get_invitation_instance($courseid, $instancemustexist);
    }

    /**
     * Return HTML invitation menu link for a given course.
     *
     * It's mostly useful to add a link in a block menu - by default icon is
     * displayed.
     *
     * @param boolean $withicon set to false to not display the icon
     * @return string
     */
    public function get_menu_link($withicon = true) {
        global $OUTPUT;

        $inviteicon = '';
        $link = '';

        if (has_capability('enrol/invitation:enrol', context_course::instance($this->courseid))) {

            // Display an icon with requested (css can be changed in stylesheet).
            if ($withicon) {
                $inviteicon = html_writer::img($OUTPUT->pix_icon('invite', 'enrol_invitation'), "invitation",
                        array('class' => "enrol_invitation_item_icon",
                              'title' => "invitation"));
            }

            $link = html_writer::link(
                            new moodle_url('/enrol/invitation/invitation.php',
                                    array('courseid' => $this->courseid)), $inviteicon . get_string('inviteusers',
                                        'enrol_invitation'));
        }

        return $link;
    }

    /**
     * Helper function to get privacy notice for project sites.
     *
     * @param int $courseid
     * @return string       Returns null if there is no privacy notice.
     */
    public static function get_project_privacy_notice($courseid) {
        global $CFG;
        require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasiteindicator/lib.php');
        $retval = null;

        // Get current course's site type group.
        try {
            $siteindicatorsite = new siteindicator_site($courseid);
            $sitetype = $siteindicatorsite->property->type;
            $siteindicatormanager = new siteindicator_manager();
            $sitetypegroup = $siteindicatormanager->get_rolegroup_for_type($sitetype);

            // If site type group is project, then return some notice.
            if ($sitetypegroup == siteindicator_manager::SITE_GROUP_TYPE_PROJECT) {
                $retval = "\n\n" . get_string('project_privacy_notice', 'enrol_invitation');
            }
        } catch (Exception $e) {
            // Throws exception if no site type found.
            unset($e);
        }

        return $retval;
    }

    /**
     * Send invitation (create a unique token for each of them).
     *
     * @param obj $data         data processed from the invite form, or an invite if resending
     * @param bool $resend      resend the invite specified by $data
     * @return int $retval      id of invitation in enrol_invitation table
     */
    public function send_invitations($data, $resend = false) {
        global $DB, $CFG, $SITE, $USER;

        if (has_capability('enrol/invitation:enrol', context_course::instance($data->courseid))) {

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
                $invitation->fromemail = $data->fromemail;
                $invitation->courseid = $data->courseid;
                $invitation->token = $token;
                $invitation->tokenused = false;
                $invitation->roleid = $resend ? $data->roleid : $data->role_group['roleid'];
                $invitation->subject = '';

                // Set time.
                $timesent = time();
                $invitation->timesent = $timesent;

                if (!empty($data->invite_expiration_time)) {
                    $invitation->timeexpiration = $data->invite_expiration_time;
                } else {
                    $invitation->timeexpiration = $timesent + get_config('enrol_invitation', 'inviteexpiration');
                }

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

                $messageparams = new stdClass();
                $messageparams->instructormessage = '';
                // Construct message: custom (if any) + template.
                $message = '';
                if (!empty($data->message)) {
                    $messageparams->instructormessage = get_string('htmlinstructormsg', 'enrol_invitation', $data->message);
                    $message .= get_string('instructormsg', 'enrol_invitation',
                            $data->message);
                    $invitation->message = $data->message;
                }
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                $messageparams->courseurl = $courseurl->out(false);
                $messageparams->fullname = sprintf(
                    '%s: %s', $course->shortname, $course->fullname
                );
                $messageparams->expiration = date('M j, Y g:ia', $invitation->timeexpiration);
                $inviteurl = new moodle_url('/enrol/invitation/enrol.php',
                    array('token' => $token)
                );
                $inviteurl = $inviteurl->out(false);

                // Append privacy notice, if needed.
                $privacynotice = $this->get_project_privacy_notice($course->id);
                if (empty($privacynotice)) {
                    $privacynotice = '';
                }

                // Append days expired, if needed.
                if (get_config('enrol_invitation', 'enabletempparticipant')) {
                    $tempparticipant = $DB->get_record('role',
                            array('shortname' => 'tempparticipant'));
                }

                $roleexpiration = get_string('roleneverexpire_notice',
                        'enrol_invitation');
                // If daysexpire is set.
                if (!empty($data->daysexpire)) {
                    $daysexpire = $data->daysexpire;
                    $roleexpiration = get_string('daysexpire_notice',
                            'enrol_invitation', $daysexpire);
                    $invitation->daysexpire = $daysexpire;
                }

                $messageparams->inviteurl = $inviteurl;
                $messageparams->privacynotice = $privacynotice;
                $messageparams->supportemail = $data->fromemail;
                $messageparams->roleexpiration = $roleexpiration;
                $helpurl = new moodle_url('/blocks/ucla_help/index.php', array('course' => $this->courseid));
                $helpurl = $helpurl->out(false);
                $messageparams->helpurl = $helpurl;
                $message .= get_string('emailmsgtxt', 'enrol_invitation', $messageparams);
                $htmlmessage = get_string('htmlemailmsgtxt', 'enrol_invitation', $messageparams);
                if (!$resend) {
                    $objid = $DB->insert_record('enrol_invitation', $invitation);
                    $retval = $objid;
                } else {
                    $retval = $data->id;
                }

                // User sending invitation.
                $fromuser = new stdClass();
                $fromuser->email = $data->fromemail;
                $fromuser->firstname = '';
                $fromuser->lastname = '';
                $fromuser->maildisplay = true;
                $fromuser->id = $USER->id;
                // Moodle 2.7 introduced new username fields.
                $fromuser->alternatename = '';
                $fromuser->firstnamephonetic = '';
                $fromuser->lastnamephonetic = '';
                $fromuser->middlename = '';
                // Send invitation to the user.
                $contactuser = $this->get_contact_user($invitation->email);

                email_to_user($contactuser, $fromuser, $invitation->subject, $message, $htmlmessage);

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
                    new moodle_url('/course/view.php', array('id' => $data->courseid)));
        }
    }

    /**
     * Enrol the user in the course, update the database to mark the token as used,
     * and (optionally) notify inviter.
     *
     * @param obj $invitation   a enrol_invitation record
     * @param obj $course       a course object
     */
    public function accept_invite($invitation, $course) {
        global $DB, $USER, $SITE;
        if ($invitation->email != $USER->email) {
            $event = \enrol_invitation\event\invitation_mismatch::create(array(
                        'objectid' => $invitation->id,
                        'context' => context_course::instance($invitation->courseid),
                        'other' => $course->fullname
            ));
            $event->trigger();
        }

        $this->enroluser($invitation);

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
            $contactuser = $this->get_contact_user($inviter->email);

            $emailinfo = prepare_notice_object($invitation);
            $emailinfo->userfullname = trim($USER->firstname . ' ' . $USER->lastname);
            $emailinfo->useremail = $USER->email;
            $courseenrolledusersurl = new moodle_url('/user/index.php', array('id' => $invitation->courseid));
            $emailinfo->courseenrolledusersurl = $courseenrolledusersurl->out(false);
            $invitehistoryurl = new moodle_url('/enrol/invitation/history.php', array('courseid' => $invitation->courseid));
            $emailinfo->invitehistoryurl = $invitehistoryurl->out(false);

            $emailinfo->coursefullname = sprintf('%s: %s', $course->shortname, $course->fullname);
            $emailinfo->sitename = $SITE->fullname;
            $siteurl = new moodle_url('/');
            $emailinfo->siteurl = $siteurl->out(false);

            email_to_user($contactuser, get_admin(), get_string(
                'emailtitleuserenrolled', 'enrol_invitation', $emailinfo
            ), get_string('emailmessageuserenrolled', 'enrol_invitation', $emailinfo));
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
        $course = $DB->get_record('course', array('id' => $this->courseid));

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
            $redirect = new moodle_url('/enrol/invitation/invitation.php',
                    array('courseid' => $invite->courseid, 'inviteid' => $invite->id));
            redirect($redirect);
        }
    }

    /**
     * Checks if user who accepted invite has an access expiration for their
     * enrollment.
     *
     * @param object $invite    Database record
     *
     * @return string           Returns expiration string. Blank if no
     *                          restriction.
     */
    public function get_access_expiration($invite) {
        $expiration = '';

        if (empty($invite->userid)) {
            return $expiration;
        }

        // Check to see if user has a time restriction on their access.
        $timeend = enrol_get_enrolment_end($invite->courseid, $invite->userid);
        if ($timeend === false) {
            // No active enrollment now.
            $expiration = get_string('status_invite_used_noaccess', 'enrol_invitation');
        } else if ($timeend > 0) {
            // Access will end on a certain date.
            $expiration = get_string('status_invite_used_expiration',
                    'enrol_invitation', date('M j, Y', $timeend));
        }
        return $expiration;
    }

    /**
     * Given an email address, will return a user object that can be used in
     * email_to_user().
     *
     * @param string $email
     * @return object
     */
    protected function get_contact_user($email) {
        global $CFG, $DB;
        // There might be multiple users with the same email, but we just pick
        // the first one.
        $user = $DB->get_record('user', array('email' => $email), '*', IGNORE_MULTIPLE);
        if (empty($user)) {
            // Create our own user using guestid as base.
            $user = $DB->get_record('user', array('username' => 'guest',
                'mnethostid' => $CFG->mnet_localhost_id));
            $user->email = $email;
        }
        return $user;
    }

    /**
     * Returns status of given invite.
     *
     * @param object $invite    Database record
     *
     * @return string           Returns invite status string.
     */
    public function get_invite_status($invite) {
        global $DB;

        if (!is_object($invite)) {
            return get_string('status_invite_invalid', 'enrol_invitation');
        }

        // If duplicate invites found, at least one invite was resent.
        $conditions = array($invite->courseid, $invite->email, $invite->roleid);

        $mostrecenttime = $DB->get_field_sql("SELECT MAX(timeexpiration) FROM {enrol_invitation} WHERE
                                              courseid = ? AND email = ? AND roleid = ?", $conditions);

        // Invites sent before newest were resent.
        if ($invite->timeexpiration != $mostrecenttime) {
            return get_string('status_invite_resent', 'enrol_invitation');
        }

        if ($invite->tokenused) {
            // Invite was used already.
            $status = get_string('status_invite_used', 'enrol_invitation');
            return $status;
        } else if ($invite->timeexpiration == $invite->timesent) {
            // Invite is revoked if expiration equals time sent.
            return get_string('status_invite_revoked', 'enrol_invitation');
        } else if ($invite->timeexpiration < time()) {
            // Invite is expired.
            return get_string('status_invite_expired', 'enrol_invitation');
        } else {
            return get_string('status_invite_active', 'enrol_invitation');
        }
    }

    /**
     * Return all invites for given course.
     *
     * @param int $courseid
     * @return array
     */
    public function get_invites($courseid = null) {
        global $DB;

        if (empty($courseid)) {
            $courseid = $this->courseid;
        }

        $invites = $DB->get_records('enrol_invitation', array('courseid' => $courseid));

        return $invites;
    }

    /**
     * Return the invitation instance for a specific course.
     *
     * Note: as using $PAGE variable, this function can only be called in a
     * Moodle script page.
     *
     * @param int $courseid
     * @param boolean $mustexist when set, an exception is thrown if no instance is found
     * @return object
     */
    public function get_invitation_instance($courseid, $mustexist = false) {
        global $PAGE, $CFG, $DB;

        if (($courseid == $this->courseid) and !empty($this->enrolinstance)) {
            return $this->enrolinstance;
        }

        // Find enrolment instance.
        $instance = null;
        require_once("$CFG->dirroot/enrol/locallib.php");
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $manager = new course_enrolment_manager($PAGE, $course);
        foreach ($manager->get_enrolment_instances() as $tempinstance) {
            if ($tempinstance->enrol == 'invitation') {
                if ($instance === null) {
                    $instance = $tempinstance;
                }
            }
        }

        if ($mustexist and empty($instance)) {
            throw new moodle_exception('noinvitationinstanceset', 'enrol_invitation');
        }

        return $instance;
    }

    /**
     * Enrol the user following the invitation data.
     * @param object $invitation
     */
    public function enroluser($invitation) {
        global $USER;

        // Handle daysexpire by adding making the enrollment expiration be the
        // end of the day after daysexpire days.
        $timeend = 0;
        // If role expires, otherwise daysexpire is set to NULL.
        if (!empty($invitation->daysexpire)) {
            // Get today's date as a timestamp. Ignore the current time.
            $today = strtotime(date('Y/m/d'));
            // Get the day in the future.
            $timeend = strtotime(sprintf('+%d days', $invitation->daysexpire), $today);
            // But make sure the timestamp is for the end of that day. Remember
            // that 86400 is the total seconds in a day. So -1 that is right
            // before midnight.
            $timeend += 86399;
        }
        $enrol = enrol_get_plugin('invitation');
        $enrol->enrol_user($this->enrolinstance, $USER->id,
                $invitation->roleid, 0, $timeend);
    }

    /**
     * Figures out who used an invite.
     *
     * @param object $invite    Invitation record
     *
     * @return object           Returns an object with following values:
     *                          ['username'] - name of who used invite
     *                          ['useremail'] - email of who used invite
     *                          ['roles'] - roles the user has for course that
     *                                      they were invited
     *                          ['timeused'] - formatted string of time used
     *                          Returns false on error or if invite wasn't used.
     */
    public function who_used_invite($invite) {
        global $DB;
        $retval = new stdClass();

        if (empty($invite->userid) || empty($invite->tokenused) ||
                empty($invite->courseid) || empty($invite->timeused)) {
            return false;
        }

        // Find user.
        $user = $DB->get_record('user', array('id' => $invite->userid));
        if (empty($user)) {
            return false;
        }
        $retval->username = sprintf('%s %s', $user->firstname, $user->lastname);
        $retval->useremail = $user->email;

        // Find their roles for course.
        $retval->roles = get_user_roles_in_course($invite->userid, $invite->courseid);
        if (empty($retval->roles)) {
            // If no roles, then they must have been booted out later.
            return false;
        }
        $retval->roles = strip_tags($retval->roles);

        // Format string when invite was used.
        $retval->timeused = date('M j, Y g:ia', $invite->timeused);

        return $retval;
    }
}

/**
 * Setups the object used in the notice strings for when a user is accepting
 * a site invitation.
 *
 * @param object $invitation
 * @return object
 */
function prepare_notice_object($invitation) {
    global $CFG, $DB;

    $noticeobject = new stdClass();
    $noticeobject->email = $invitation->email;
    $noticeobject->coursefullname = $DB->get_field('course', 'fullname',
            array('id' => $invitation->courseid));
    $noticeobject->supportemail = $CFG->supportemail;

    // Get role name for use in acceptance message.
    $role = $DB->get_record('role', array('id' => $invitation->roleid));
    $noticeobject->rolename = $role->name;
    $noticeobject->roledescription = strip_tags($role->description);

    return $noticeobject;
}

/**
 * Prints out tabs and highlights the appropiate current tab.
 *
 * @param string $activetab Either 'invite' or 'history'
 * @return void
 */
function print_page_tabs($activetab) {
    global $CFG, $COURSE;

    $tabs[] = new tabobject('invite',
                    new moodle_url('/enrol/invitation/invitation.php',
                            array('courseid' => $COURSE->id)),
                    get_string('inviteusers', 'enrol_invitation'));
    $tabs[] = new tabobject('history',
                    new moodle_url('/enrol/invitation/history.php',
                            array('courseid' => $COURSE->id)),
                    get_string('invitehistory', 'enrol_invitation'));

    // Display tabs here.
    print_tabs(array($tabs), $activetab);
}

/**
 * Convert a string of emails into an array with a separate entry for each unique
 * email.
 *
 * @param string $emailstring   string containing one or more email addresses
 * @return array $email_list    array containing unique emails separated by delimiters
 */
function prepare_emails($emailstring) {
    // Check for the invitation of multiple users.
    $delimiters = "/[;, \r\n]/";
    $emaillist = invitation_form::parse_dsv_emails($emailstring, $delimiters);
    $emaillist = array_unique($emaillist);
    return $emaillist;
}