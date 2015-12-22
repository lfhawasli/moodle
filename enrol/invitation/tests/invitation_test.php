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
 * PHPUnit site invitation tests.
 *
 * @package    enrol_invitation
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/invitation/invitation_form.php');
require_once($CFG->dirroot . '/enrol/invitation/lib.php');
require_once($CFG->dirroot . '/enrol/invitation/locallib.php');

/**
 * PHPunit tests for the invitation manager.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class invitation_manager_testcase extends advanced_testcase {
    /**
     * Invitation manager instance.
     * 
     * @var invitation_manager
     */
    private $courseinvitationmanager = null;

    /**
     * Course object.
     *
     * @var object
     */
    private $testcourse = null;

    /**
     * Invitee user object.
     *
     * @var object
     */
    private $testinvitee = null;

    /**
     * Inviter user object.
     *
     * @var object
     */
    private $testinviter = null;

    /**
     * Try to enroll a user with an invitation that has daysexpire set. Make
     * sure that the proper timeend is set.
     *
     * @dataProvider daystoexpire_provider
     */
    public function test_enroluser_withdaysexpire($daystoexpire) {
        global $DB;

        // When enrolling a user, the invitation user uses the currently logged
        // in user's id, so we need to set that to the invitee.
        $this->setUser($this->testinvitee);

        $invite = $this->create_invite();
        $invite->daysexpire = $daystoexpire;
        $this->courseinvitationmanager->enroluser($invite);

        // Check user_enrolments table and make sure endtime is $daystoexpire
        // days ahead.
        $enrolinstance = $this->courseinvitationmanager->get_invitation_instance($this->testcourse->id);
        $timeend = $DB->get_field('user_enrolments', 'timeend',
                array('userid' => $this->testinvitee->id,
                      'enrolid' => $enrolinstance->id));

        // Do not count today as one of the days.
        $today = strtotime(date('Y/m/d'));
        $expectedexpiration = strtotime(sprintf('+%d days', $daystoexpire+1), $today)-1;

        $this->assertEquals($expectedexpiration, intval($timeend));
    }

    /**
     * Makes sure that someone who was granted the role of Temorary Participant
     * and has their site invitation user enrollment expired, that they are
     * unenrolled from the course.
     */
    public function test_unenrolexpiredtempparticipant() {
        global $DB;

        // Create Temporary Participant role.
        $roleid = create_role('Temporary Participant', 'tempparticipant', '', 'student');
        $this->assertGreaterThan(0, $roleid);

        // Test setup.
        $invitation = enrol_get_plugin('invitation');
        $this->setUser($this->testinvitee);
        $context = context_course::instance($this->testcourse->id);
        set_config('enabletempparticipant', 1, 'enrol_invitation');

        $enrolinstance = $this->courseinvitationmanager->get_invitation_instance($this->testcourse->id);

        // Enrol user with an timeend in the past.
        $invitation->enrol_user($enrolinstance, $this->testinvitee->id, $roleid,
                0, strtotime('yesterday'));
        $hasrole = has_role_in_context('tempparticipant', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);

        // Run the enrollment plugin cron and make sure user is unenrolled.
        $invitation->cron();
        $hasrole = has_role_in_context('tempparticipant', $context);
        $this->assertFalse($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertFalse($isenrolled);

        // Now do the opposite, enroll a user with a timeend in the future.
        $invitation->enrol_user($enrolinstance, $this->testinvitee->id, $roleid,
                0, strtotime('tomorrow'));
        $hasrole = has_role_in_context('tempparticipant', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);

        // Run the enrollment plugin cron and make sure user is not unenrolled.
        $invitation->cron();
        $hasrole = has_role_in_context('tempparticipant', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);

        // Enroll someone with a role other than Temporary Participant and
        // make sure they are not unenrolled.
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $invitation->enrol_user($enrolinstance, $this->testinvitee->id, $studentroleid);
        $hasrole = has_role_in_context('student', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);
        $invitation->cron();
        $hasrole = has_role_in_context('student', $context);
        $this->assertTrue($hasrole);
        $isenrolled = is_enrolled($context, $this->testinvitee->id);
        $this->assertTrue($isenrolled);
    }

    /**
     * For project sites, tests that the invite has a warning message.
     */
    public function test_emailinvitemessage() {
        unset_config('noemailever');
        $this->setAdminUser();
        $sink = $this->redirectEmails();

        $localgen = $this->getDataGenerator()->get_plugin_generator('local_ucla');
        $courseinfo['type'] = 'non_instruction';
        // Force a different shortname so the new course doesn't conflict with $this->testcourse.
        $courseinfo['shortname'] = 'cc_1';
        $collabcourse = $localgen->create_collab($courseinfo);
        // Create a specific invitation manager for the collab course.
        $imanager = new invitation_manager($collabcourse->id);
        $data = $this->generate_invite_form_data($collabcourse->id);

        $site = new siteindicator_site($collabcourse->id);
        $imanager->send_invitations($data);
        $site->set_type('research');
        $imanager->send_invitations($data);
        $site->set_type('private');
        $imanager->send_invitations($data);

        $messages = $sink->get_messages();
        $warningtext = "Please be aware that if you accept this invitation your profile information"
                . " will be available to the other members of this project.";
        $timeexpire = time() + get_config('enrol_invitation', 'inviteexpiration');
        $timeexpire = date('M j, Y g:ia', $timeexpire);
        $expiretext = 'and will expire on (' . $timeexpire . ')';
        foreach ($messages as $message) {
            $cleanbody = preg_replace('/\s+/', ' ', $message->body);
            $this->assertContains($warningtext, $cleanbody);
            $this->assertContains($expiretext, $cleanbody);
        }
    }

    /**
     * Verifies that an inviter gets an email notification when an invitee accepts an
     * invitation. (Provided they check the box "Notify me at..." in the invite form.)
     */
    public function test_notifyinviter() {
        global $DB;
        unset_config('noemailever');
        $sink = $this->redirectEmails();

        $teacherroleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->role_assign($teacherroleid, $this->testinviter->id,
            context_course::instance($this->testcourse->id));
        $this->setUser($this->testinviter);

        $data = $this->generate_invite_form_data($this->testcourse->id);
        $data->notify_inviter = 1;
        $inviteid = $this->courseinvitationmanager->send_invitations($data);

        $this->setUser($this->testinvitee);
        $invitation = $DB->get_record('enrol_invitation', array('id' => $inviteid));
        $this->courseinvitationmanager->accept_invite($invitation, $this->testcourse);
        $messages = $sink->get_messages();
        $this->assertEquals(count($messages), 2);
    }
    /**
     * Test that invitation form accepts multiple email addresses by testing
     * enrol/invitation/invitation_form.php: parse_dsv_emails.
     */
    public function test_emailparsing() {
        // Check that a single email address gets successfully parsed
        $emails = 'user1@asd.com';
        $parsedemails = prepare_emails($emails);
        $this->assertCount(1, $parsedemails);
        $this->assertEquals('user1@asd.com', $parsedemails[0]);

        // Check that multiple email addresses get successfully parsed
        $emails .= ';user2@asd.com;user3@asd.com;user4@asd.com';
        $parsedemails = prepare_emails($emails);
        $this->assertCount(4, $parsedemails);
        $this->assertEquals('user1@asd.com', $parsedemails[0]);
        $this->assertEquals('user3@asd.com', $parsedemails[2]);

        $emails = preg_replace('/;/', ',', $emails);
        $parsedemails = prepare_emails($emails);
        $this->assertCount(4, $parsedemails);
        $this->assertEquals('user1@asd.com', $parsedemails[0]);
        $this->assertEquals('user3@asd.com', $parsedemails[2]);

        $emails = preg_replace('/,/', ' ', $emails);
        $parsedemails = prepare_emails($emails);
        $this->assertCount(4, $parsedemails);
        $this->assertEquals('user1@asd.com', $parsedemails[0]);
        $this->assertEquals('user3@asd.com', $parsedemails[2]);

        $emails = preg_replace('/ /', "\r\n", $emails);
        $parsedemails = prepare_emails($emails);
        $this->assertCount(4, $parsedemails);
        $this->assertEquals('user1@asd.com', $parsedemails[0]);
        $this->assertEquals('user3@asd.com', $parsedemails[2]);

        // Check that varying delimiters are successfully parsed.
        $emails = "user1@asd.com,user2@asd.com; user3@asd.com user4@asd.com\nuser1@asd.com";
        $parsedemails = prepare_emails($emails);
        $this->assertCount(4, $parsedemails);
        $this->assertEquals('user1@asd.com', $parsedemails[0]);
        $this->assertEquals('user3@asd.com', $parsedemails[2]);
    }

    /**
     * Verify that when the user specifies duplicate emails, only one invite is
     * sent to each unique email address.
     */
    public function test_multipleemails() {
        unset_config('noemailever');
        $this->setAdminUser();
        $sink = $this->redirectEmails();

        $emails = "user1@asd.com,user2@asd.com; user3@asd.com user4@asd.com\nuser1@asd.com";
        $parsedemails = prepare_emails($emails);
        $data = $this->generate_invite_form_data($this->testcourse->id);
        foreach ($parsedemails as $email) {
            $data->email = $email;
            $this->courseinvitationmanager->send_invitations($data);
        }
        $messages = $sink->get_messages();
        $this->assertCount(4, $messages);
    }

    /**
     * To verify when extend inviation is invoke, the expiration date is 
     * this many days (set in config) from today (the day sent)
     */
    public function test_extendinvite() {
        global $DB;
        
        // Settings avoid debug.
        unset_config('noemailever');
        $sink = $this->redirectEmails();

        $teacherroleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->role_assign($teacherroleid, $this->testinviter->id,
            context_course::instance($this->testcourse->id));
        $this->setUser($this->testinviter);

        $data = $this->generate_invite_form_data($this->testcourse->id);
        $inviteid = $this->courseinvitationmanager->send_invitations($data);
        
        // Set the invitation expired.
        $DB->set_field('enrol_invitation', 'timeexpiration', time()-1,
                    array('id' => $inviteid));
        $invitation = $DB->get_record('enrol_invitation', array('id' => $inviteid));

        // Extend the invite.
        $newinvitation = $this->courseinvitationmanager->update_invitation($invitation, invitation_manager::INVITE_EXTEND);

        // Time ends.
        $timeend = $DB->get_field('enrol_invitation', 'timeexpiration',
                array('id' => $inviteid));

        // If the invitation did sent out within 24 hours.
        $expectedexpiration = time() + get_config('enrol_invitation', 'inviteexpiration');
        $this->assertEquals(gmdate("Y-m-d", $expectedexpiration), gmdate("Y-m-d", $timeend));
     }

    /**
     * Provides array of days for invitation to expire after being accepted.
     *
     * @return array
     */
    public function daystoexpire_provider() {
        $retval = array();
        foreach (invitation_form::$daysexpire_options as $daysexpire) {
            $retval[] = array($daysexpire);
        }
        return $retval;
    }

    /**
     * Generates data object needed to submit to send_invitations()
     * 
     * @return obj $data
     */
    private function generate_invite_form_data($courseid) {
        global $DB;

        $data = new stdClass();
        $data->courseid = $courseid;
        $data->email = $this->testinvitee->email;
        $data->fromemail = $this->testinviter->email;
        $data->role_group['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $data->subject = 'Test invite';

        return $data;
    }

    /**
     * Helper method to create standard invite object. Can be customized later.
     *
     * @return object
     */
    private function create_invite() {
        global $DB;

        $invitation = new stdClass();
        $invitation->token = '517b12e81e212';
        $invitation->email = $this->testinvitee->email;
        $invitation->userid = 0;    // Do not have the invitee's id is yet.
        $invitation->roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $invitation->courseid = $this->testcourse->id;
        $invitation->tokenused = 0;
        $invitation->timesent = time();
        $invitation->timesent = strtotime('+2 weeks');
        $invitation->inviterid = $this->testinviter->id;
        $invitation->subject = 'Test';
        $invitation->notify_inviter = 0;
        $invitation->daysexpire = 0;

        return $invitation;
    }

    /**
     * Setup method.
     *
     * Create course, inviter, invitee, and invitation manager instances.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Make sure that created course has the invitation enrollment plugin.
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_invitation');

        // Create new course/users.
        $this->testcourse = $this->getDataGenerator()->create_course();
        $this->testinvitee = $this->getDataGenerator()->create_user();
        $this->testinviter = $this->getDataGenerator()->create_user();

        // Create manager that we want to test.
        $this->courseinvitationmanager = new invitation_manager($this->testcourse->id);
    }
}
