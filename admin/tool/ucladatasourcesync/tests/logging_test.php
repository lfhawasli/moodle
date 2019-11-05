<?php
// This file is part of the UCLA data source sychronization plugin for Moodle - http://moodle.org/
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
 * Tests new add_to_log event functions.
 *
 * @package    tool_ucladatasourcesync
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../lib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group tool_ucladatasourcesync
 */
class logging_test extends advanced_testcase {
    /**
     * Test the new logging event.
     */
    public function test_new_logging() {
        global $DB;
        
        // Setup logging.
        $this->resetAfterTest();
        $this->preventResetByRollback();
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        set_config('buffersize', 0, 'logstore_standard');
        set_config('logguests', 1, 'logstore_standard');
        get_log_manager(true);
        
        // Verify that there are no logs in the beginning.
        $this->assertEquals(0, $DB->count_records('logstore_standard_log'));
        
        // Trigger 3 events.
        log_ucla_data('bruincast', 'read', 'Initializing cfg variables', 
                get_string('errbcmsglocation','tool_ucladatasourcesync') );
        log_ucla_data('library reserves', 'read', 'Initializing cfg variables', 
                get_string('errlrmsglocation', 'tool_ucladatasourcesync'));
        log_ucla_data('library reserves', 'write', 'Inserting library reserve data', 
                get_string('errbcinsert', 'tool_ucladatasourcesync') );
        $logs = $DB->get_records('logstore_standard_log', array());

        // Verify that 3 logs were logged.
        $this->assertCount(3, $logs);
        
        // Verify if the logs are correctly logged.
        // Get the 1st log.
        $log1 = array_shift($logs);
        
        // Recreate the log message.
        $notice = 'Initializing cfg variables';
        $error = get_string('errbcmsglocation','tool_ucladatasourcesync');
        $logmessage = $notice . PHP_EOL . $error;
        $logmessage = core_text::substr($logmessage, 0, 252) . '...';
        
        // Verify target, action, event name, and log message.
        $this->assertEquals('bruincast' ,$log1->target);
        $this->assertEquals('read' ,$log1->action);
        $this->assertEquals('\tool_ucladatasourcesync\event\bruincast_read', $log1->eventname);
        $this->assertEquals(1 , preg_match("/".$logmessage."/", $log1->other));
        
        // Get the 3rd log.
        $log3 = array_pop($logs);
        
        // Recreate the log message.
        $notice = 'Inserting library reserve data';
        $error = get_string('errbcinsert', 'tool_ucladatasourcesync');
        $logmessage = $notice . PHP_EOL . $error;
        $logmessage = core_text::substr($logmessage, 0, 252) . '...';
        
        // Verify target, action, event name, and log message.
        $this->assertEquals('libraryreserves' ,$log3->target);
        $this->assertEquals('write' ,$log3->action);
        $this->assertEquals('\tool_ucladatasourcesync\event\libraryreserves_write', $log3->eventname);
        $this->assertEquals(1 , preg_match("/".$logmessage."/", $log3->other));
    }
}