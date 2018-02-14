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
 * UCLA local plugin cron task runner.
 *
 * @package    local_ucla
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ucla\task;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/ucla/lib.php');

/**
 * Task class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_cron_task extends \core\task\scheduled_task {

    /**
     * Executes the task.
     *
     * @throws \Exception on error
     */
    public function execute() {
        global $CFG;

        $activeterms = get_config('local_ucla', 'active_terms');
        $terms = explode(',', $activeterms);
        mtrace('Processing terms: ' . $activeterms);

        include_once($CFG->dirroot . '/local/ucla/cronlib.php');
        ucla_require_registrar();

        $works = array('classinfo', 'subjectarea', 'enrolment_plugin');
        foreach ($works as $work) {
            $cn = 'ucla_reg_' . $work . '_cron';
            if (class_exists($cn)) {
                $runner = new $cn();
                if (method_exists($runner, 'run')) {
                    mtrace("Running $cn.");
                    $result = $runner->run($terms);
                } else {
                    mtrace("Could not run() for $cn.");
                }
            } else {
                mtrace("Could not run cron for $cn, class not found.");
            }

            if (!$result) {
                // Retry task with exception.
                throw new \Exception("Error: $cn returned false.  Will retry local_ucla_cron_task.");
            }
        }
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'local_ucla');
    }
}
