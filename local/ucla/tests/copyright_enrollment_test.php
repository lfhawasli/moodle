<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Unit tests for class local_ucla_copyright_enrollment.
 *
 * @package    local_ucla
 * @subpackage test
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class copyright_enrollment_test extends advanced_testcase {
    /**
     * Setup.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Make sure error handling is properly working.
     */
    public function test_errors() {
        // No currentterm is set.
        $this->expectOutputRegex("/No currentterm set/");
        $this->trigger_event();
        set_config('currentterm', '19F');

        // Missing copyright course.
        $this->expectOutputRegex("/No CopyrightBasicsInstructors found/");
        $this->trigger_event();
        $course = $this->getDataGenerator()->create_course(
                ['shortname' => 'CopyrightBasicsInstructors']);

        // Missing role.
        $this->expectException('dml_missing_record_exception');
        $this->trigger_event(true);
        $this->getDataGenerator()->create_role(['shortname' => 'participant']);

        // Missing self enrollment plugin.
        $this->expectException('moodle_exception');
        $this->trigger_event(true);
        $self = enrol_get_plugin('self');
        $self->add_instance($course);

        // No more errors.
        $this->trigger_event();
    }

    /**
     * Make sure sync is properly working.
     */
    public function test_syncs() {
        $uclagen = $this->getDataGenerator()->get_plugin_generator('local_ucla');

        // Test email gets sent.
        unset_config('noemailever');
        set_config('admin_email', 'ccle@ucla.edu', 'local_ucla');
        $sink = $this->redirectEmails();

        // Setup course.
        $course = $this->getDataGenerator()->create_course(
                ['shortname' => 'CopyrightBasicsInstructors']);
        $self = enrol_get_plugin('self');
        $self->add_instance($course);
        $roles = $uclagen->create_ucla_roles(['participant', 'editinginstructor',
                'ta_instructor', 'student']);

        // Create Fall and Winter courses with enrollments.
        $tainstructor = $uclagen->create_user();
        $instructor = $uclagen->create_user();
        $student = $uclagen->create_user();
        $fallclass = $uclagen->create_class(['term' => '19F']);
        $courseid = array_pop($fallclass)->courseid;
        $uclagen->enrol_reg_user($instructor->id, $courseid, $roles['editinginstructor']);
        $uclagen->enrol_reg_user($student->id, $courseid, $roles['student']);
        $winterclass = $uclagen->create_class(['term' => '20W']);
        $courseid = array_pop($winterclass)->courseid;
        $uclagen->enrol_reg_user($tainstructor->id, $courseid, $roles['ta_instructor']);

        // Run sync for Fall.
        set_config('currentterm', '19F');
        $this->trigger_event();
        $context = context_course::instance($course->id);
        $users = get_enrolled_users($context);
        $foundstudent = $foundinstructor = $foundta = false;
        foreach ($users as $user) {
            $foundstudent = $user->id == $student->id ? true : false;
            $foundinstructor = $user->id == $instructor->id ? true : false;
            $foundta = $user->id == $tainstructor->id ? true : false;
        }
        $this->assertFalse($foundstudent);
        $this->assertTrue($foundinstructor);
        $this->assertFalse($foundta);

        // Check success email.
        $emails = $sink->get_messages();
        $this->assertEquals(1, count($emails));
        $email = array_pop($emails);
        $this->assertStringEndsWith('19F', $email->subject);
        $this->assertNotFalse(strpos($email->body, 'Unenrolled 0 users'));
        $this->assertTrue(strpos($email->body, 'Enrolled 1 users') !== false);
        $sink->clear();

        // Run sync for Winter.
        set_config('currentterm', '20W');
        $this->trigger_event();
        $users = get_enrolled_users($context);
        $foundstudent = $foundinstructor = $foundta = false;
        foreach ($users as $user) {
            $foundstudent = $user->id == $student->id ? true : false;
            $foundinstructor = $user->id == $instructor->id ? true : false;
            $foundta = $user->id == $tainstructor->id ? true : false;
        }
        $this->assertFalse($foundstudent);
        $this->assertFalse($foundinstructor);   // Should get unenrolled.
        $this->assertTrue($foundta);

        // Check success email.
        $emails = $sink->get_messages();
        $this->assertEquals(1, count($emails));
        $email = array_pop($emails);
        $this->assertStringEndsWith('20W', $email->subject);
        $this->assertNotFalse(strpos($email->body, 'Unenrolled 1 users'));
        $this->assertTrue(strpos($email->body, 'Enrolled 1 users') !== false);
    }

    /**
     * Triggers event to invoke local_ucla_copyright_enrollment::sync().
     *
     * @param boolean $bypasseventmanager   Set to true to test exceptions,
     *                                      because event manager catches all
     *                                      exceptions.
     */
    private function trigger_event($bypasseventmanager = false) {
        $event = block_ucla_weeksdisplay\event\week_changed::create(
                ['other' => ['week' => 1]]);
        if ($bypasseventmanager) {
            local_ucla_copyright_enrollment::sync($event);
        } else {
            $event->trigger();
        }
    }
}