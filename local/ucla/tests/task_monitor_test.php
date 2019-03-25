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
 * Tests for the \local_ucla\task\monitor class.
 *
 * @package    local_ucla
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
define("TOLERANCE", 5);
define("MAXCOUNT", 30);


/**
 * PHPunit testcase class.
 *
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_monitor_test extends advanced_testcase {
    /**
     * Monitor class.
     *
     * @var \local_ucla\task\monitor
     */
    private $monitor = null;

    /**
     * Where all emails are redirected.
     *
     * @var phpunit_message_sink
     */
    private $sink = null;

    /**
     * Setup method.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        unset_config('noemailever');
        $this->sink = $this->redirectEmails();

        $tolerance = TOLERANCE;
        $maxcount = MAXCOUNT;
        $this->monitor = new \local_ucla\task\monitor($tolerance, $maxcount);

        set_config('admin_email', 'test@ucla.edu', 'local_ucla');
        set_config('divertallemailsto', 'test@ucla.edu');

        // Run monitor and verify that nothing is emailed.
        $this->expectOutputRegex("/Successfully ran the script/");
        $this->monitor->execute();
        $this->assertEquals(0, $this->sink->count());
        $this->sink->clear();
    }

    /**
     * Tests that adhoc_monitoring() is working as expected.
     */
    public function test_adhoc_monitoring() {
        global $DB;

        // Create adhoc tasks.
        for ($i = 0; $i < 5; $i++) {
            $adhocktask = new \tool_monitor\notification_task();
            \core\task\manager::queue_adhoc_task($adhocktask);
        }

        // Set a random scheduled task to have a fail delay below threshold.
        $tasks = $DB->get_records('task_adhoc');
        $task = $tasks[array_rand($tasks)];
        $task->faildelay = (TOLERANCE - 2) * MINSECS;
        $DB->update_record('task_adhoc', $task);

        // Run monitor and verify that nothing is emailed.
        $this->expectOutputRegex("/Successfully ran the script/");
        $this->monitor->execute();
        $this->assertEquals(0, $this->sink->count());
        $this->sink->clear();

        // Set a random scheduled task to have a fail delay above threshold.
        $task = $tasks[array_rand($tasks)];
        $task->faildelay = (TOLERANCE + 1) * MINSECS; // Default threshold is 5 minutes.
        $DB->update_record('task_adhoc', $task);

        // Run monitor and verify that email is sent.
        $this->expectOutputRegex("/Problems detected, email sent/");
        $this->monitor->execute();
        $this->assertEquals(1, $this->sink->count());
        $messages = $this->sink->get_messages();
        $message = array_pop($messages);
        $this->assertRegExp('/There are 5 pending events/', $message->body);
    }

    /**
     * Tests that failing_tasks() is working as expected.
     */
    public function test_failing_tasks() {
        global $DB;
        
        // Set random task from task_scheduled with fail delay before threshold.
        $tasks = $DB->get_records('task_scheduled', array('disabled' => 0));
        $task = $tasks[array_rand($tasks)];
        $task->faildelay = (TOLERANCE - 3) * MINSECS;
        $DB->update_record('task_scheduled', $task);

        // Run monitor and verify that nothing is emailed.
        $this->expectOutputRegex("/Successfully ran the script/");
        $this->monitor->execute();
        $this->assertEquals(0, $this->sink->count());
        $this->sink->clear();

        // Set random task from task_scheduled with fail delay above threshold.
        $task = $tasks[array_rand($tasks)];
        $task->faildelay = (TOLERANCE + 5) * MINSECS;
        $DB->update_record('task_scheduled', $task);

        // Run monitor and verify that email is sent.
        $this->expectOutputRegex("/Problems detected, email sent/");
        $this->monitor->execute();
        $this->assertEquals(1, $this->sink->count());
        $messages = $this->sink->get_messages();
        $message = array_pop($messages);
        $classname = preg_quote($task->classname);
        $this->assertRegExp("/$classname/", $message->body);
        $this->assertRegExp("/tolerance of 5 minutes/", $message->body);
    }

    /**
     * Tests that queue_monitoring() is working as expected.
     */
    public function test_queue_monitoring() {
        global $DB;

        // Set fail delay for \mod_forum\task\cron_task above threshold.
        $task = $DB->get_record('task_scheduled',
                array('classname' => '\mod_forum\task\cron_task'));
        $task->faildelay = (TOLERANCE + 7) * MINSECS;
        $DB->update_record('task_scheduled', $task);

        // Add entries into forum_queue below threshold.
        for ($i = 0; $i < MAXCOUNT; $i++) {
            $record = new stdClass();
            $record->userid = rand(1, 1000);
            $record->discussionid = rand(1, 1000);
            $record->postid = rand(1, 1000);
            $DB->insert_record('forum_queue', $record);
        }

        // Run monitor and verify that nothing is emailed.
        $this->expectOutputRegex("/Successfully ran the script/");
        $this->monitor->execute();
        $this->assertEquals(0, $this->sink->count());
        $this->sink->clear();

        // Add one more entry into forum_queue to be above threshold.
        $record = new stdClass();
        $record->userid = rand(1, 1000);
        $record->discussionid = rand(1, 1000);
        $record->postid = rand(1, 1000);
        $DB->insert_record('forum_queue', $record);

        // Run monitor and verify that email is sent.
        $this->expectOutputRegex("/Problems detected, email sent/");
        $this->monitor->execute();
        $this->assertEquals(1, $this->sink->count());
        $messages = $this->sink->get_messages();
        $message = array_pop($messages);
        $this->assertRegExp("/pending events in the forum_queue queue/", $message->body);
        $this->assertRegExp("/tolerance of 5 minutes/", $message->body);
    }

}
