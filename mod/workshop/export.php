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
 * Exports workshop details as zip file.
 *
 * @package    mod_workshop
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

// Get workshop and course id from url.
$workshopid = required_param('workshopid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);

$cm = get_course_and_cm_from_instance($workshopid, 'workshop')[1];
require_capability('mod/workshop:viewallassessments', $cm->context);

$zipname = 'ws_'. $courseid . '_' . $workshopid . '.zip';
$dirpath = $CFG->tempdir;
// Array of filenames.
$filenames['workshop'] = generate_filename('workshop');
$filenames['workshop_aggregations'] = generate_filename('workshop_aggregations');
$filenames['workshopform_accumulative'] = generate_filename('workshopform_accumulative');
$filenames['workshop_users'] = generate_filename('workshop_users');
$filenames['workshop_submissions'] = generate_filename('workshop_submissions');
$filenames['workshop_assessments'] = generate_filename('workshop_assessments');
$filenames['workshop_grades'] = generate_filename('workshop_grades');

// Set headers.
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private, max-age=0, no-cache");
header("Content-Description: File Transfer");
header("Content-type: application/zip");
header("Content-Disposition: attachment; filename=\"". $zipname ."\"");
header("Content-Transfer-Encoding: binary");

foreach ($filenames as $key => $filename) {
    // Get data from database.
    $content = query_workshop($key);
    $fields = array_keys(json_decode(json_encode(reset($content)), true));
    // Create file using the acquired data.
    $fp = fopen($dirpath . $filename, 'w');
    fputcsv($fp, $fields);
    foreach ($content as $row) {
        $row = json_decode(json_encode($row), true);
        fputcsv($fp, $row);
    }
    fclose($fp);
}

$zip = new ZipArchive;
if ($zip->open($dirpath . $zipname, ZipArchive::CREATE)) {
    // Add created files to zip file.
    foreach ($filenames as $filename) {
        $zip->addFile($dirpath . $filename, 'data/' . $filename);
    }
    $zip->close();
}

// Force download.
flush();
@readfile($dirpath . $zipname);

// Delete all files created.
foreach ($filenames as $filename) {
    unlink($dirpath . $filename);
}
unlink($dirpath . $zipname);

/**
 * Generates a filename by prefix and postfixing the name.
 *
 * @param string $name
 * @param string $format (default = 'csv')
 * @return string
 */
function generate_filename($name, $format = 'csv') {
    global $CFG;
    return  $CFG->prefix . $name . '.' . $format;
}

/**
 * Queries the database based on $key.
 *
 * @param string $key
 * @return array
 */
function query_workshop($key) {
    global $workshopid, $courseid, $DB;
    switch ($key) {
        case 'workshop':
            return $DB->get_records('workshop', array('id' => $workshopid, 'course' => $courseid));
        case 'workshop_aggregations':
            return $DB->get_records('workshop_aggregations', array('workshopid' => $workshopid));
        case 'workshopform_accumulative':
            return $DB->get_records('workshopform_accumulative', array('workshopid' => $workshopid));
        case 'workshop_users':
            return $DB->get_records_sql('
            SELECT DISTINCT wu.id, wu.firstname, wu.lastname, wu.idnumber, wu.username
              FROM {user} wu
              JOIN {user_enrolments} ue ON wu.id = ue.userid
              JOIN {enrol} me ON ue.enrolid = me.id
              JOIN {workshop} mw ON mw.course = me.courseid
             WHERE mw.id = ? 
               AND me.courseid = ?
               AND ue.status=?',
            [$workshopid, $courseid, ENROL_USER_ACTIVE]);
        case 'workshop_submissions':
            return $DB->get_records_sql('
            SELECT DISTINCT ws.*
              FROM {user} wu
              JOIN {user_enrolments} ue ON wu.id = ue.userid
              JOIN {enrol} me ON ue.enrolid = me.id
              JOIN {workshop} mw ON mw.course = me.courseid
         LEFT JOIN {workshop_submissions} ws ON ws.workshopid = mw.id AND ws.authorid = wu.id
             WHERE mw.id = ?
               AND me.courseid = ?
               AND ws.id IS NOT NULL
               AND ue.status=?',
            [$workshopid, $courseid, ENROL_USER_ACTIVE]);
        case 'workshop_assessments':
            return $DB->get_records_sql('
            SELECT DISTINCT wa.*
              FROM {user} wu
              JOIN {user_enrolments} ue ON wu.id = ue.userid
              JOIN {enrol} me ON ue.enrolid = me.id
              JOIN {workshop} mw ON mw.course = me.courseid
              JOIN {workshop_assessments} wa ON wa.reviewerid = wu.id
             WHERE mw.id = ? 
               AND me.courseid = ?
               AND ue.status=?',
            [$workshopid, $courseid, ENROL_USER_ACTIVE]);
        case 'workshop_grades':
            return $DB->get_records_sql('
            SELECT DISTINCT wg.*
              FROM {user} wu
              JOIN {user_enrolments} ue ON wu.id = ue.userid
              JOIN {enrol} me ON ue.enrolid = me.id
              JOIN {workshop} mw ON mw.course = me.courseid
              JOIN {workshop_assessments} wa ON wa.reviewerid = wu.id
              JOIN {workshop_grades} wg ON wa.id = wg.assessmentid
             WHERE mw.id = ? 
               AND me.courseid = ?
               AND ue.status=?',
            [$workshopid, $courseid, ENROL_USER_ACTIVE]);
    }
}