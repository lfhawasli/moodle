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
require_once($CFG->libdir . '/clilib.php');

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
$defaultmaxcount = 30;

$tolerance = (!empty($extargv['tolerance']) && !empty($unrecog[0])) ? $unrecog[0] : $defaulttolerance;
$maxcount = (!empty($extargv['maxcount']) && !empty($unrecog[1])) ? $unrecog[1] : $defaultmaxcount;

$monitor = new \local_ucla\task\monitor($tolerance, $maxcount);
$monitor->execute();
