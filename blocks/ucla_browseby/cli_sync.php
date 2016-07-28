<?php
// This file is part of the UCLA browseby block for Moodle - http://moodle.org/
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
 * CLI script to run BrowseBy update.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require($CFG->libdir . '/clilib.php');

list($ext_argv, $unrecog) = cli_get_params(
    array(
        'all' => false,
        'current-term' => false,
        'subjarea' => false,
        'quiet' => false,
        'help' => false
    ),
    array(
        'h' => 'help',
        'q' => 'quiet'
    )
);

$reg_argv = array();
foreach ($argv as $arg) {
    if (strpos($arg, '-') !== false) {
        continue;
    }

    if (strlen($arg) == 3) {
        // If we have processed up to another TERM argument,
        // and we have no SRS requested within that TERM
        $reg_argv[] = $arg;
    }
}

// This may screw up...
ini_set('display_errors', '1');

// Figure out which terms to run for
$termlist = NULL;
if (!empty($reg_argv)) {
    $termlist = $reg_argv;
} 

if ($ext_argv['current-term']) {
    if (!empty($CFG->currentterm)) {
        $termlist = array($CFG->currentterm);
    } else {
        echo "No currentterm.\n";
    }
}

// Figure out which subject areas to run for.
$subjareas = null;
if ($ext_argv['subjarea']) {
    $subjareas = explode(',', $ext_argv['subjarea']);
}

$q = $ext_argv['quiet'];
if ($q) {
    ob_start();
}

if ($ext_argv['all']) {
    run_browseby_sync(null, $subjareas, true);
} else if (empty($termlist)) {
    echo "No terms specified!\n";
    $ext_argv['help'] = true;
}

if ($ext_argv['help']) {
    die (
"Usage: " . exec("which php") . ' ' . $argv[0] . " TERM [ TERM ... ]

Options:
    --all           Run BrowseBy for all terms.
    --current-term  Automatically use current term.
    --subjarea      Pass in a comma delinated list of subject areas to sync.
    -h, --help      Display this message.
    -q, --quiet     Make script say nothing. All output will be suppressed. 

");
}

block_ucla_browseby_observer::run_browseby_sync($termlist, $subjareas);

if ($q) {
    ob_end_clean();
}

