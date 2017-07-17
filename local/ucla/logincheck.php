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
 * Checks if user's session is still active
 *
 * @package     local_ucla
 * @copyright   2013 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

// Checking if user is logged in or not.
// @codingStandardsIgnoreLine
require_once(dirname(__FILE__) . '/../../config.php');

$obj = new stdClass();
$obj->status = false;

if (isloggedin() && !isguestuser()) {
    global $USER;

    $obj->status = true;
    $obj->sesskey = $USER->sesskey;
    $obj->userid = $USER->id;
}

// Return JSON response.
header('Content-Type: application/json');
echo json_encode($obj);
