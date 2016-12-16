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
 * Script to email admin when certain processing queues are not being processed.
 *
 * @package    local_ucla
 * @copyright  2016 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require($CFG->dirroot . '/local/ucla/lib.php');
require($CFG->libdir . '/clilib.php');

// Support a 'tolerance' param.
// Will allow admin to set a value threshold for retry count.
list($extargv, $unrecog) = cli_get_params(
    array(
        'tolerance' => false,
        'maxcount' => false,
    ),
    array(
        't' => 'tolerance',
        'x' => 'maxcount',
    )
);

// Default values.
$defaulttolerance = 5;  // How big can the faildelay be in minutes?
$defaultdisplay = 20;
$defaultmaxcount = 30;

$tolerance = (!empty($extargv['tolerance']) && !empty($unrecog[0])) ? $unrecog[0] : $defaulttolerance;
$maxcount = (!empty($extargv['maxcount']) && !empty($unrecog[1])) ? $unrecog[1] : $defaultmaxcount;

// Queues to monitor.
$queues = array('assignfeedback_editpdf_queue' => '\assignfeedback_editpdf\task\convert_submissions',
    'forum_queue' => '\mod_forum\task\cron_task',
    'events_queue_handlers' => '\core\task\events_cron_task');

$subject = "Problems with queues: ";
$message = "";
foreach ($queues as $queue => $task) {
    // Find number of records for a given queue.
    $totalrecords = $DB->count_records($queue);
    if ($totalrecords > $maxcount) {
        // Check to see if the task that processes the queue is failing.
        $alert = $DB->record_exists_select('task_scheduled', 'classname=? AND faildelay>?',
                array($task, $tolerance * MINSECS));
        if (!empty($alert)) {
            $subject .= "$queue ";
            $message .= "There are $totalrecords pending events in the "
                . "$queue queue. The task that processes that queue has a "
                . "faildelay greater than the tolerance of $tolerance minutes.\n\n";
        }
    }
}

// If we find any queue delays, notify admins.
if (!empty($message)) {
    $to = get_config('local_ucla', 'admin_email');
    if (empty($to)) {
        // Variable not properly set.
        cli_error("Event queue check: Error -- you're missing the 'to' email field!");
    }
    cli_problem("Event queue check: Event queue has grown too much, email sent");
    return ucla_send_mail($to, $subject, $message);
}
cli_writeln("Event queue check: Successfully ran the script");
