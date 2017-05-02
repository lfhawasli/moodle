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
 * CLI script to disable TA site invitation plugin for existing TA sites
 *
 * @package    local_ucla
 * @copyright  2017 UCLA regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false),
        array('h' => 'help'));

if ($options['help'] || !empty($unrecognized)) {
    $help = "CLI script to disable TA site invitation plugin for existing TA sites.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/cli/disable_tasite_invites.php
";
    cli_error($help);
    die;
}

$trace = new text_progress_trace();

// Find TA sites.
$records = $DB->get_recordset('ucla_siteindicator', array('type' => 'tasite'));

$numdisabled = $numsites = 0;
$plugins = enrol_get_plugins(false);
$invitationplugin = $plugins['invitation'];
if ($records->valid()) {
    foreach ($records as $record) {
        ++$numsites;
        $instances = enrol_get_instances($record->courseid, true);
        foreach ($instances as $instance) {
            if ($instance->enrol == "invitation") {
                $invitationplugin->update_status($instance, ENROL_INSTANCE_DISABLED);
                $trace->output('Disabled invitation for TA site ' . $record->courseid);
                ++$numdisabled;
            }
        }
    }
}

$trace->output("Found $numsites TA sites. Disabled invitation for $numdisabled TA sites.");
