<?php
// This file is part of the local UCLA plugin for Moodle - http://moodle.org/
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
 * Event handler class.
 *
 * @package    report_uclastats
 * @copyright  2019 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * Event handler class file.
 *
 * @package    report_uclastats
 * @copyright  2019 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_uclastats_observer {

    /**
     * If enabled, runs eport/uclastats/cli/run_reports.php.
     *
     * @param \block_ucla_weeksdisplay\event\week_changed $event
     */
    public static function run_reports(\block_ucla_weeksdisplay\event\week_changed $event) {
        global $CFG;

        // Don't run during unit tests.
        if (defined('PHPUNIT_TEST') and PHPUNIT_TEST) {
            return true;
        }

        $weeknum = $event->other['week'];
        $enabled = get_config('report_uclastats', 'enable');
        if (empty($enabled)) {
            return true;
        }

        if ($weeknum != 1) {
            return true;
        }

        // Get previous quarter.
        $pastterm = term_get_prev($CFG->currentterm);
        if (!ucla_validator('term', $pastterm)) {
            // Strange, cannot figure out past_term, just exit.
            return true;
        }

        system(sprintf('php %s/report/uclastats/cli/run_reports.php %s', 
                $CFG->dirroot, $pastterm));

        return true;
    }

}
