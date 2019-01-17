<?php
// This file is part of the UCLA stats report for Moodle - http://moodle.org/
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
 * Command line test script to test that the event trigger for automating
 * UCLA report is working.
 *
 * @package    report_uclastats
 * @copyright  2019 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once("$CFG->libdir/clilib.php");
require_once("$CFG->dirroot/local/ucla/lib.php");
require_once("$CFG->dirroot/report/uclastats/locallib.php");

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help']) {
    $help = "Command line test script to test that the event trigger for automating UCLA report is working.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php report/uclastats/cli/trigger_report.php
";

    echo $help;
    die;
}

// Make it appear it is Week 1.
$event = block_ucla_weeksdisplay\event\week_changed::create(['other' => ['week' => 1]]);
$event->trigger();

cli_writeln('Event triggered. Reports should have ran and results emailed to ' .
        get_config('report_uclastats', 'notifylist'));
