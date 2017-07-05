<?php
// This file is part of Moodle - http://moodle.org/
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
 * Download link for a CSV file containing the class roster.
 *
 * @package     block_ucla_control_panel
 * @copyright   UC Regents 2017
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 **/

require_once(dirname(__FILE__) . '/../../config.php');
global $PAGE;

$shortname = required_param('shortname', PARAM_ALPHANUMEXT);

$filename = $shortname . "_roster.csv";
$sql = "SELECT u.id, u.idnumber, u.firstname, u.lastname, u.email, u.alternatename
          FROM mdl_course c
     LEFT JOIN mdl_groups g ON c.id=g.courseid AND g.name='Course members'
     LEFT JOIN mdl_groups_members m ON g.id=m.groupid
          JOIN mdl_user u ON u.id = m.userid
         WHERE c.shortname = :shortname
      ORDER BY lastname, firstname";
$records = $DB->get_records_sql($sql, array('shortname'=>$shortname));
$students = json_decode(json_encode($records), true);

// Create a temporary file to store to csv.
$f = tmpfile();
// Include attributes in the CSV.
fputcsv($f, array('IdNumber', 'FirstName', 'LastName', 'Email','LegalName'), ',');
foreach ($students as $line) {
    // If the alternatename (legal name) field is null, there is no preferred name.
    if (empty($line['alternatename'])) {
        $line['alternatename'] = $line['firstname'];
    }
    unset($line['id']); // We don't care about the Moodle ID.
    // Generate csv lines from the inner arrays.
    fputcsv($f, $line, ',');
}
fseek($f, 0);
// Tell the browser to save the CSV instead of displaying it.
header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="'.$filename.'";');
// Make php send the generated csv lines to the browser.
fpassthru($f);
// Closing the tempfile automatically deletes it.
fclose($f);