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
 * Script to get usage stats of the syllabus reminder prompt.
 *
 * @package    local_ucla
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');

/*
 * Results will be binned by term.
 */
$results = array();

// Get all entries in mdl_user_preferences for the syllabus prompt.
$sql = "SELECT  up.*
        FROM    {user_preferences} up
        JOIN    {role_assignments} ra ON (
                ra.userid=up.userid
        )
        JOIN    {role} r ON (
                ra.roleid=r.id
        )
        WHERE   up.name LIKE 'ucla_syllabus_noprompt_%' AND
                r.shortname='editinginstructor'";
$rs = $DB->get_recordset_sql($sql);

if ($rs->valid()) {
    foreach ($rs as $record) {
        // Get courseid from record and find what term course belongs to.
        $parts = explode('_', $record->name);
        $courseid = array_pop($parts);
        $term = $DB->get_field('ucla_request_classes', 'term', array('courseid' => $courseid), IGNORE_MULTIPLE);
        if (!empty($term)) {
            if (!isset($results[$term])) {
                $results[$term] = array('never' => 0, 'later' => 0);
            }
            if ($record->value == 0) {
                ++$results[$term]['never'];
            } else {
                ++$results[$term]['later'];
            }
        }
    }
}

// Using print_r, because it is easy to read display.
// @codingStandardsIgnoreLine
print_r($results);
