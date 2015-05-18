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
 * Scheduled Task for sending previously hidden grades to MyUCLA.
 *
 * @package    local_gradebook
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradebook\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/grade/constants.php');
require_once($CFG->libdir . '/grade/grade_item.php');

/**
 * Task that will resend now visible grades to MyUCLA.
 */
class resend_myucla_grade_item extends \core\task\scheduled_task {

    /**
     * Task 'friendly' name
     * 
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('resendtask', 'local_gradebook');
    }

    /**
     * Action to execute.
     */
    public function execute() {
        // Get grade items that were hidden since the last time this task ran.
        global $DB;
        $lastrun = $this->get_last_run_time();
        $select = 'hidden > ?';
        $rs = $DB->get_records_select('grade_items', $select, array($lastrun));
        $gradeitems = array();
        foreach($rs as $data) {
            $instance = new \grade_item();
            \grade_object::set_properties($instance, $data);
            $gradeitems[$instance->id] = $instance;
        }
        
        foreach ($gradeitems as $gradeitem) {
            // Send grade item to MyUCLA if item is no longer hidden
            // (passed Hidden Until time).
            if (!$gradeitem->is_hidden()) {
                $sendmyuclaitem = new \local_gradebook\task\send_myucla_grade_item();
                $valid = $sendmyuclaitem->set_gradeinfo($gradeitem);
                if ($valid) {
                    \core\task\manager::queue_adhoc_task($sendmyuclaitem);

                    mtrace('Sending grade_item with id ' . $gradeitem->id .
                           ' that was hidden until ' . $gradeitem->hidden .
                           ' to MyUCLA.');
                }
            }
        }
    }

}
