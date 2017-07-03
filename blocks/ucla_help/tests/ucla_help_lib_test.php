<?php
// This file is part of the UCLA Help plugin for Moodle - http://moodle.org/
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
 * Tests the ucla_help package functionality.
 *
 * @package    block_ucla_help
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');

/**
 * Contains tests for ucla_help package.
 *
 * @package    block_ucla_help
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucla_help_lib_testcase extends advanced_testcase {

    /** @var array  Stores our request data so it can be reused easily */
    private $requestdata;

    /**
     * Ready the variables and configs for testing.
     */
    protected function setUp() {
        $this->resetAfterTest();

        // Set up the properties.
        unset_config('noemailever');
        $this->requestdata = array(
            'subj'  => 'Test support request',
            'body'  => 'How do I log in?',
            'file'  => 'screenshot.png',
            'name'  => 'Screenshot'
        );
    }

    /**
     * Verify that the message_support_contact function generates an adhoc task when it is called, and that this task
     * contains the proper information.
     */
    public function test_support_request_queue() {
        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNull($task);

        // Call function that will send a JIRA request to create an issue.
        message_support_contact('support', null, null, $this->requestdata['subj'], $this->requestdata['body'],
                $this->requestdata['file'], $this->requestdata['name']);
        $task = \core\task\manager::get_next_adhoc_task(time());
        $this->assertNotNull($task);
        $this->assertEquals('block_ucla_help_try_support_request', get_class($task));
        // Important to call this to release the cron lock.
        \core\task\manager::adhoc_task_complete($task);

        // Make sure the task has the proper elements set.
        $taskdata  = $task->get_custom_data();
        $this->assertEquals($this->requestdata['file'], $taskdata->attachmentfile);
        $this->assertEquals($this->requestdata['name'], $taskdata->attachmentname);
        $taskdataparams = $taskdata->params->fields;
        $this->assertEquals($this->requestdata['subj'], $taskdataparams->summary);
        $this->assertEquals($this->requestdata['body'], $taskdataparams->description);
    }

}