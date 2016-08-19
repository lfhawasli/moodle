<?php
// This file is part of the UCLA Library Reserves block for Moodle - http://moodle.org/
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
 * Upgrades database for bruincast
 *
 * @package    block_ucla_library_reserves
 * @author     Anant Mahajan
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Command line script to parse, verify, and update Library reserves entries in the Moodle database.
 *
 * $CFG->libraryreserves_data is defined in the plugin configuration at
 * Site administration->Plugins->Blocks->Library reserves
 *
 * See CCLE-2312 for details
 **/
require_once('updatelib.php');


/**
 * Sets up array to be used to validate library reserve data source.
 *
 * Expects library reserves data to be in the following format:
 * Course Number: VARCHAR2(10)
 * Course Name: VARCHAR2(40)
 * Department Code: VARCHAR2(10)
 * Department Name: VARCHAR2(40)
 * Instructor Last Name: VARCHAR2(50)
 * Instructor First Name: VARCHAR2(40)
 * Reserves List Title: VARCHAR2(40)
 * List Effective Date: YYYY-MM-DD
 * List Ending Date: YYYY-MM-DD
 * URL: VARCHAR2
 * SRS Number: VARCHAR2(9)
 * Quarter: CHAR(3)
 *
 * @return mixed
 */
function define_data_source() {
    $retval = array();

    /* [index in datasource] => ['name']
     *                          ['type']
     *                          ['min_size']
     *                          ['max_size']
     */
    $retval[] = array('name' => 'course_number',
                        'type' => 'coursenum',
                        'min_size' => '0',
                        'max_size' => '10');
    $retval[] = array('name' => 'course_name',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '40');
    $retval[] = array('name' => 'department_code',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '10');
    $retval[] = array('name' => 'department_name',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '40');
    $retval[] = array('name' => 'instructor_last_name',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '50');
    $retval[] = array('name' => 'instructor_first_name',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '40');
    $retval[] = array('name' => 'reserves_list_title',
                        'type' => 'string',
                        'min_size' => '0',
                        'max_size' => '40');
    $retval[] = array('name' => 'list_effective_date',
                        'type' => 'date_dashed',
                        'min_size' => '6',
                        'max_size' => '10');
    $retval[] = array('name' => 'list_ending_date',
                        'type' => 'date_dashed',
                        'min_size' => '6',
                        'max_size' => '10');
    $retval[] = array('name' => 'url',
                        'type' => 'url',
                        'min_size' => '1',
                        'max_size' => '400');
    $retval[] = array('name' => 'srs',
                        'type' => 'srs',
                        'min_size' => '7',  // In case leading zeroes are removed.
                        'max_size' => '9');
    $retval[] = array('name' => 'quarter',
                        'type' => 'term',
                        'min_size' => '3',
                        'max_size' => '3');

    return $retval;
}

function parse_datasource($datasourceurl) {
    $parseddata = array();

    // Get fields that should be in data source.
    $fields = define_data_source();

    // Read the file into a two-dimensional array.
    $lines = file($datasourceurl);
    if ($lines === false) {
        log_ucla_data('library reserves', 'read', 'Reading data source url',
                get_string('errlrfileopen', 'tool_ucladatasourcesync'));
        die("\n" . get_string('errlrfileopen', 'tool_ucladatasourcesync') . "\n");
    }

    $invalidfieldserrors = array();
    $numentries = 0;
    foreach ($lines as $linenum => $line) {
        // Stop processing data if we hit the end of the file.
        if ($line == "=== EOF ===\n") {
            break;
        }

        // Remove the newline at the end of each line.
        $line = rtrim($line);
        $incomingdata = explode("\t", $line);

        // Check if all entries have the correct number of columns.
        if (count($incomingdata) != count($fields)) {
            // If first line, then don't give error, just skip it.
            if ($linenum != 0) {
                log_ucla_data('library reserves', 'read', 'Reading data source',
                        get_string('errinvalidrowlen', 'tool_ucladatasourcesync', $linenum) );
                echo(get_string('errinvalidrowlen', 'tool_ucladatasourcesync',
                        $linenum) . "\n");
            }
            continue;
        }

        // Bind incoming data field to local database table.
        // Don't fail if fields are invalid, just note it and continue, because
        // data is very messy, but we will try to work with it when trying to
        // match a library reserve entry with a course.
        $invalidfields = array();
        foreach ($fields as $fieldnum => $fielddef) {
            // Validate/clean data.
            $data = validate_field($fielddef['type'],
                    $incomingdata[$fieldnum], $fielddef['min_size'],
                    $fielddef['max_size']);
            if ($data === false) {
                $invalidfields[] = $fielddef['name'];
            }

            $parseddata[$numentries][$fielddef['name']] = $data;
            ++$fieldnum;
        }

        // Give warning about errors.
        if (!empty($invalidfields)) {
            $error = new stdClass();
            $error->fields = implode(', ', $invalidfields);
            $error->line_num = $linenum;
            $error->data = $incomingdata;
            print ($incomingdata);

            // Compile a list of parsing errors and send all errors in one email.
            $invalidfieldserrors[] = get_string('warninvalidfields',
                    'tool_ucladatasourcesync', $error) . "\n";

            echo(get_string('warninvalidfields', 'tool_ucladatasourcesync',
                    $error) . "\n");
        }

        ++$numentries;
    }

    // Email ccle support about invalid field errors.
    log_ucla_data('library reserves', 'read', 'library reserve invalid',
            implode("\r\n", $invalidfieldserrors));

    return $parseddata;
}

function update_libraryreserves_db($datasourceurl) {
    // Get global variables.
    global $CFG, $DB;

    echo get_string('lrstartnoti', 'tool_ucladatasourcesync') . "\n";

    $parseddata = parse_datasource($datasourceurl);
    if (empty($parseddata)) {
        echo get_string('lrnoentries', 'tool_ucladatasourcesync') . "\n";
        exit;
    }

    // Wrap everything in a transaction, because we don't want to have an empty.
    // Table while data is being updated.
    $numinserted = 0;
    try {
        $transaction = $DB->start_delegated_transaction();

        // Drop table and refill with data.
        $DB->delete_records('ucla_library_reserves');

        foreach ($parseddata as $reserveentry) {
            // Through each entry and try to match it to a course.

            // Catnum might have x, indicating a secnum.
            $result = explode('x', $reserveentry['course_number']);
            $sectionnum = null;
            $catnum = $result[0];
            if (isset($result[1])) {
                $sectionnum = $result[1];
            }
            $courseid = match_course($reserveentry['quarter'],
                    $reserveentry['srs'], $reserveentry['department_code'],
                    $catnum, $sectionnum);
            if (!empty($courseid)) {
                $reserveentry['courseid'] = $courseid;
            }

            if ($DB->insert_record('ucla_library_reserves', $reserveentry)) {
                ++$numinserted;
            }
        }

        if ($numinserted == 0) {
            log_ucla_data('library reserves', 'write', 'Inserting library reserve data',
                    get_string('errbcinsert', 'tool_ucladatasourcesync') );

            throw new moodle_exception('errbcinsert', 'tool_ucladatasourcesync');
        }

        // Assuming the both inserts work, we get to the following line.
        $transaction->allow_commit();
    } catch (Exception $e) {
        $transaction->rollback($e);
    }

    log_ucla_data('library reserves', 'update',
            get_string('lrsuccessnoti', 'tool_ucladatasourcesync', $numinserted) );

    echo  get_string('lrsuccessnoti', 'tool_ucladatasourcesync',
            $numinserted) . "\n";
}

