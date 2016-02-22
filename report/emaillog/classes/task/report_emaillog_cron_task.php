<?php
// This file is part of the UCLA report_emaillog for Moodle - http://moodle.org/
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
 * UCLA report_emaillog cron task runner.
 *
 * @package    report_emaillog
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_emaillog\task;

/**
 * Contains the settings for UCLA specific customizations.
 *
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_emaillog_cron_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskname', 'report_emaillog');
    }

    /**
     * Executes the task.
     * 
     * @throws Exception on error
     */
    public function execute() {
        global $DB;

        // Value {daysexpire} is days before current time.
        $loglifetime = time() - DAYSECS * get_config('report_emaillog', 'daysexpire');
        $DB->delete_records_select("report_emaillog", "timestamp < ?", array($loglifetime));

        mtrace(" Deleted old log records from emaillog.");
    }
}

