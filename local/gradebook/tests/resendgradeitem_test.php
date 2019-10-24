<?php
// This file is part of the UCLA gradebook customizations plugin for Moodle - http://moodle.org/
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
 * Tests the MyUCLA gradebook webservice task for resending grade items.
 *
 * @package    local_gradebook
 * @category   test
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * PHPunit testcase class.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_gradebook
 */
class resendgradeitem_test extends advanced_testcase {

    /**
     * @var stdClass  Course record from the database.
     */
    private $course;

    /**
     * @var stdClass  User object from the database.
     */
    private $instructor;

    /**
     * @return \local_gradebook\task\resend_myucla_grade_item
     */
    private function get_mock_myucla_task() {
        // Only stub the query_registrar method.
        $mockgradeitemtask = $this->getMockBuilder('\local_gradebook\task\resend_myucla_grade_item')
                ->setMethods(array('get_webservice_client'))
                ->getMock();
        return $mockgradeitemtask;
    }

    /**
     * Creates test course and instructor.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Create course.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = array_pop($class);
        $this->course = get_course($course->courseid);

        // Create instructor.
        $roles = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_ucla_roles(array('editinginstructor'));
        $this->instructor = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user();
        $this->getDataGenerator()
                ->enrol_user($this->instructor->id,
                             $this->course->id,
                             $roles['editinginstructor']);

        // Set instructor as the person modifying grade items.
        $this->setUser($this->instructor);

        // Set MyUCLA gradebook settings.
        set_config('gradebook_id', 99);
        set_config('gradebook_password', 'test');
        set_config('gradebook_send_updates', 1);
    }

    /**
     * Do a simple test to make sure that parameters were sent.
     */
    public function test_execute_success() {
        global $DB;

        // Create graded activity and get grade item.

        $assign = $this->getDataGenerator()
                ->create_module('assign', array('course' => $this->course->id));
        $gradeitem = grade_item::fetch(array('itemtype'     => 'mod',
                                             'itemmodule'   => 'assign',
                                             'iteminstance' => $assign->id,
                                             'courseid'     => $this->course->id));

        // Set the hidden column to be a hidden until timestamp 1 day in the future
        $futuretime = time() + (3600 * 24);
        $gradeitem->set_hidden($futuretime);

        // Clear adhoc tasks first
        $DB->delete_records('task_adhoc');

        // Create scheduled task and run it
        $scheduledtask = $this->get_mock_myucla_task();
        $scheduledtask->execute();

        // Check that the adhoc task to resend was not queued.
        $record = $DB->get_record_sql('SELECT * FROM {task_adhoc} ORDER BY id DESC LIMIT 1');
        $this->assertFalse($record);

        // Now change the hidden until time to be in the past
        $pasttime = time() - (3600 * 24);
        $gradeitem->set_hidden($pasttime);

        // Change last run time to be before pasttime and run the task again
        $scheduledtask->set_last_run_time($pasttime - (3600 * 24));
        $this->expectOutputString(
                sprintf("Sending grade_item with id %d that was hidden until %d to MyUCLA.\n",
                        $gradeitem->id, $pasttime));
        $scheduledtask->execute();

        // Check that the adhoc task to resend is now queued.
        $record = $DB->get_record_sql('SELECT * FROM {task_adhoc} ORDER BY id DESC LIMIT 1');
        $adhoctask = \core\task\manager::adhoc_task_from_record($record);
        $data = $adhoctask->get_custom_data();
        $this->assertEquals($data->id, $gradeitem->id);

    }

}
