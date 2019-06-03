<?php
// This file is part of the SSC WOWZA plugin for Moodle - http://moodle.org/
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
 *  WOWZA streaming media filter plugin key checking.
 *
 * @package    filter_sscwowza
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/config.php');

// Get global user variable.
global $USER;

$isvalid = true;
if (!isset($USER->ssc_video_allowed) || !$USER->ssc_video_allowed) {
    $isvalid = false;
}

// Log to php error log to check if keycheck is being called.
if (debugging()) {
    error_log(sprintf('Called by %s, returning $isvalid = ', getremoteaddr(), $isvalid));
}

if (!$isvalid) {
    header('HTTP/1.0 403 Forbidden');
} else {
    header('Content-Type: binary/octet-stream');
    header('Pragma: no-cache');
    echo hex2bin('7BFA375DEDE2756571EFD3487F6AEB4E');
    exit(); // This is needed to ensure cr/lf is not added to output.
}
