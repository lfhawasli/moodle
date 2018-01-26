<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Command line script to update BruinCast for given term.
 *
 * @package    block_ucla_media
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once("$CFG->dirroot/local/ucla/lib.php");

list($extargv, $unrecog) = cli_get_params(
    array(
        'help' => false
    ),
    array(
        'h' => 'help'
    )
);

// Display help.
if ($extargv['help']) {
    die(
    "Usage: php blocks/ucla_media/cli/update_bcast.php TERM [ TERM ... ]

Options:
    -h, --help      Display this message.");
}

// Figure out which terms to run for.
$termlist = array();
foreach ($unrecog as $arg) {
    if (ucla_validator('term', $arg)) {
        $termlist[] = $arg;
    }
}
if (empty($termlist)) {
    cli_error('No terms passed');
}

$task = new block_ucla_media\task\update_bcast($termlist);
$task->execute($termlist);
