<?php
// This file is part of the UCLA local_ucla plugin for Moodle - http://moodle.org/
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
 * Monitors adhoc and scheduled tasks.
 *
 * @package    local_ucla
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\task;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * Monitor class.
 *
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class monitor {
    /**
     * Message of alert, if any.
     * @var string
     */
    private $message = '';

    /**
     * Queues to monitor.
     * @var array
     */
    private $queues = array(
        'assignfeedback_editpdf_queue' => '\assignfeedback_editpdf\task\convert_submissions',
        'forum_queue' => '\mod_forum\task\cron_task',
        'events_queue_handlers' => '\core\task\events_cron_task'
    );

    /**
     * Subject line of alert message, if any.
     * @var string
     */
    private $subject = 'Problems with queues: ';

    /**
     * Constructor.
     *
     * @param int $tolerance    Number of minutes before alerting about a fail delay.
     * @param int $maxcount     Number of backed up events in queue before sending alert.
     */
    public function __construct($tolerance, $maxcount) {
        $this->maxcount = $maxcount;
        $this->tolerance = $tolerance;
    }

    /**
     * Checks if there are any adhoc task with long fail delay and over a
     * number of certain events.
     */
    protected function adhoc_monitoring() {
        global $DB;
        // See if there is an adhoc task with long fail delay.
        $tasks = $DB->get_records_select('task_adhoc', 'faildelay>?',
                array($this->tolerance * MINSECS, '', 'DISTINCT classname'));
        foreach ($tasks as $task) {
            $totalrecords = $DB->count_records('task_adhoc',
                    array('classname' => $task->classname));
            $this->subject .= $task->classname . " ";
            $this->message .= "There are $totalrecords pending events in from "
                    . "$task->classname in the task_adhoc queue. The task that "
                    . "processes that queue has a faildelay greater than the "
                    . "tolerance of $this->tolerance minutes.\n\n";
        }
    }

    /**
     * Runs task monitoring tasks and sends out alert if there is a problem.
     */
    public function execute() {
        $this->adhoc_monitoring();
        $this->failing_tasks();
        $this->queue_monitoring();

        // If we find any queue delays, notify admins.
        if (!empty($this->message)) {
            $to = get_config('local_ucla', 'admin_email');
            if (empty($to)) {
                // Variable not properly set.
                cli_error("Event queue check: Error -- you're missing the 'to' email field!");
            }
            echo "Event queue check: Problems detected, email sent";
            return ucla_send_mail($to, $this->subject, $this->message);
        }
        echo "Event queue check: Successfully ran the script\n";
    }

    /**
     * Check if there are any scheduled tasks that are failing.
     */
    protected function failing_tasks() {
        global $DB;
        $failingtasks = $DB->get_records_select('task_scheduled',
                'disabled=0 AND faildelay>?', array($this->tolerance * MINSECS));
        foreach ($failingtasks as $failingtask) {
            // Ignore task that have already been checked.
            if (in_array($failingtask->classname, $this->queues)) {
                continue;
            }
            $this->subject .= $failingtask->classname . " ";
            $this->message .= "Scheduled task $failingtask->classname has a "
                    . "faildelay greater than the tolerance of "
                    . "$this->tolerance minutes.\n\n";
        }
    }

    /**
     * Check if tasks with queues is beyond the maxcount.
     */
    protected function queue_monitoring() {
        global $DB;
        foreach ($this->queues as $queue => $task) {
            // Find number of records for a given queue.
            $totalrecords = $DB->count_records($queue);
            if ($totalrecords > $this->maxcount) {
                // Check to see if the task that processes the queue is failing.
                $alert = $DB->record_exists_select('task_scheduled', 'classname=? AND faildelay>?',
                        array($task, $this->tolerance * MINSECS));
                if (!empty($alert)) {
                    $this->subject .= "$queue ";
                    $this->message .= "There are $totalrecords pending events "
                            . "in the $queue queue. The task that processes "
                            . "that queue has a faildelay greater than the "
                            . "tolerance of $this->tolerance minutes.\n\n";
                }
            }
        }
    }
}
