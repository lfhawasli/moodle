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
 * Events test.
 *
 * @package    enrol_invitation
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/invitation/locallib.php');

/**
 * Invitation events test cases.
 *
 * @package    enrol_invitation
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_invitation_events_testcase extends advanced_testcase {

    /**
     * Invitation manager instance.
     * 
     * @var invitation_manager
     */
    private $invitationmanager = null;

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
     * Setup is called before calling test case.
     */
    public function setUp() {
        $this->resetAfterTest();
        global $DB;

        // Make sure that created course has the invitation enrollment plugin.
        set_config('status', ENROL_INSTANCE_ENABLED, 'enrol_invitation');

        // Create new course/users.
        $this->testcourse = $this->getDataGenerator()->create_course();
        $this->testinvitee = $this->getDataGenerator()->create_user();
        $this->testinviter = $this->getDataGenerator()->create_user();
        $teacherroleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->role_assign($teacherroleid, $this->testinviter->id, context_course::instance($this->testcourse->id));

        // Create manager that we want to test.
        $this->invitationmanager = new invitation_manager($this->testcourse->id);

        // Make sure we don't send out email.
        set_config('noemailever', 1);
    }

    /**
     * Test invitation sent event.
     */
    public function test_invitation_sent() {
        $sink = $this->redirectEvents();

        // Invoke call to trigger event.
        $this->setUser($this->testinviter->id);
        $data = $this->generate_invite_form_data();
        $this->invitationmanager->send_invitations($data);
        $this->assertDebuggingCalled('Not sending email due to $CFG->noemailever config setting');

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\enrol_invitation\event\invitation_sent', $event);
        $this->assertEquals(context_course::instance($this->testcourse->id), $event->get_context());
        $logurl = new \moodle_url('/enrol/invitation/history.php', array('courseid' => $this->testcourse->id));
        $expected = array($this->testcourse->id, 'enrol_invitation', 'invitation send',
            $logurl, $this->testcourse->fullname);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test invitation revoked event.
     */
    public function test_invitation_revoked() {
        $this->setUser($this->testinviter->id);
        $invitation = $this->generate_invitation();
        $this->assertDebuggingCalled('Not sending email due to $CFG->noemailever config setting');

        // Invoke call to trigger event.
        $sink = $this->redirectEvents();
        $this->invitationmanager->update_invitation($invitation, invitation_manager::INVITE_REVOKE);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\enrol_invitation\event\invitation_revoked', $event);
        $this->assertEquals(context_course::instance($this->testcourse->id), $event->get_context());
        $logurl = new \moodle_url('/enrol/invitation/history.php', array('courseid' => $this->testcourse->id));
        $expected = array($this->testcourse->id, 'enrol_invitation', 'invitation revoke',
            $logurl, $this->testcourse->fullname);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test invitation extended event.
     */
    public function test_invitation_extended() {
        $this->setUser($this->testinviter->id);
        $invitation = $this->generate_invitation();
        $this->assertDebuggingCalled('Not sending email due to $CFG->noemailever config setting');

        // Invoke call to trigger event.
        $sink = $this->redirectEvents();
        $this->invitationmanager->update_invitation($invitation, invitation_manager::INVITE_EXTEND);
        $this->assertDebuggingCalled('Not sending email due to $CFG->noemailever config setting');

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\enrol_invitation\event\invitation_extended', $event);
        $this->assertEquals(context_course::instance($this->testcourse->id), $event->get_context());
        $logurl = new \moodle_url('/enrol/invitation/history.php', array('courseid' => $this->testcourse->id));
        $expected = array($this->testcourse->id, 'enrol_invitation', 'invitation extend',
            $logurl, $this->testcourse->fullname);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test invitation viewed event.
     */
    public function test_invitation_viewed() {
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.
        $this->setUser($this->testinviter->id);
        $invitation = $this->generate_invitation();
        $this->assertDebuggingCalled('Not sending email due to $CFG->noemailever config setting');
        $testevent = \enrol_invitation\event\invitation_viewed::create(array(
                    'objectid' => $invitation->id,
                    'context' => context_course::instance($this->testcourse->id),
                    'other' => $this->testcourse->fullname
        ));

        // Triggering the event.
        $sink = $this->redirectEvents();
        $testevent->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\enrol_invitation\event\invitation_viewed', $event);
        $this->assertEquals(context_course::instance($this->testcourse->id), $event->get_context());
        $logurl = new \moodle_url('/enrol/invitation/history.php', array('courseid' => $this->testcourse->id));
        $expected = array($this->testcourse->id, 'enrol_invitation', 'invitation view',
            $logurl, $this->testcourse->fullname);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test invitation claimed event.
     */
    public function test_invitation_claimed() {
        $this->setUser($this->testinviter->id);
        $invitation = $this->generate_invitation();
        $this->assertDebuggingCalled('Not sending email due to $CFG->noemailever config setting');

        // Invoke call to trigger event.
        $sink = $this->redirectEvents();
        $this->setUser($this->testinvitee->id);
        $this->invitationmanager->accept_invite($invitation, $this->testcourse);

        $events = $sink->get_events();
        $this->assertCount(3, $events);
        // There are multiple events that are triggered when a user is enrolled.
        // The last to be triggered should be invitation_claimed, since it is triggered
        // after successful enrollment, so it should be at the end of the array.
        $event = end($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\enrol_invitation\event\invitation_claimed', $event);
        $this->assertEquals(context_course::instance($this->testcourse->id), $event->get_context());
        $logurl = new \moodle_url('/enrol/invitation/history.php', array('courseid' => $this->testcourse->id));
        $expected = array($this->testcourse->id, 'enrol_invitation', 'invitation claim',
            $logurl, $this->testcourse->fullname);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test invitation mismatch event.
     */
    public function test_invitation_mismatch() {
        $this->setUser($this->testinviter->id);
        $invitation = $this->generate_invitation();
        $this->assertDebuggingCalled('Not sending email due to $CFG->noemailever config setting');

        // Invoke call to trigger event.
        $sink = $this->redirectEvents();
        $this->invitationmanager->accept_invite($invitation, $this->testcourse);

        $events = $sink->get_events();
        $this->assertCount(4, $events);
        // There are multiple events that are triggered when a user is enrolled.
        // The first to be triggered should be invitation_mismatch, since it is 
        // triggered before enrollment, so it should be at the start of the array.
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\enrol_invitation\event\invitation_mismatch', $event);
        $this->assertEquals(context_course::instance($this->testcourse->id), $event->get_context());
        $logurl = new \moodle_url('/enrol/invitation/history.php', array('courseid' => $this->testcourse->id));
        $expected = array($this->testcourse->id, 'enrol_invitation', 'invitation mismatch',
            $logurl, $this->testcourse->fullname);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test invitation expired event.
     */
    public function test_invitation_expired() {
        // There is no proper API to call to trigger this event, so what we are
        // doing here is simply making sure that the events returns the right information.
        $this->setUser($this->testinviter->id);
        $invitation = $this->generate_invitation();
        $this->assertDebuggingCalled('Not sending email due to $CFG->noemailever config setting');

        $testevent = \enrol_invitation\event\invitation_expired::create(array(
                    'objectid' => $invitation->id,
                    'context' => context_course::instance($this->testcourse->id),
                    'other' => $this->testcourse->fullname
        ));

        // Triggering the event.
        $sink = $this->redirectEvents();
        $testevent->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\enrol_invitation\event\invitation_expired', $event);
        $this->assertEquals(context_course::instance($this->testcourse->id), $event->get_context());
        $logurl = new \moodle_url('/enrol/invitation/history.php', array('courseid' => $this->testcourse->id));
        $expected = array($this->testcourse->id, 'enrol_invitation', 'invitation expired',
            $logurl, $this->testcourse->fullname);
        $this->assertEventLegacyLogData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Generates data object needed to submit to send_invitations()
     * with data from this test class.
     * 
     * @return obj $data
     */
    public function generate_invite_form_data() {
        global $DB;

        $data = new stdClass();
        $data->courseid = $this->testcourse->id;
        $data->email = $this->testinvitee->email;
        $data->fromemail = $this->testinviter->email;
        $data->role_group['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $data->subject = 'Test invite';

        return $data;
    }

    /**
     * Generates an invitation instance relevant to this test class.
     * 
     * @return obj $invitation
     */
    public function generate_invitation() {
        global $DB;

        $data = $this->generate_invite_form_data();
        // Create an invitation from data and enter it into the DB with send_invitations.
        $invitationid = $this->invitationmanager->send_invitations($data);
        $invitation = $DB->get_record('enrol_invitation', array('id' => $invitationid));
        return $invitation;
    }
}
