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
 * Block definition file for UCLA Media
 *
 * @package    block_ucla_media
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Command line script to parse, verify, and update Bruincast entries in the
 * Moodle database.
 *
 * See CCLE-2314 for details.
 *
 */
require_once(dirname(__FILE__) . '/updatelib.php');


function define_data_source() {
    $retval = array();

    $retval[] = array('name' => 'term',
        'type' => 'term',
        'min_size' => '3',
        'max_size' => '3');
    $retval[] = array('name' => 'srs',
        'type' => 'srs',
        'min_size' => '7',
        'max_size' => '9');
    $retval[] = array('name' => 'class',
        'type' => 'string',
        'min_size' => '0',
        'max_size' => '255');
    $retval[] = array('name' => 'restricted',
        'type' => 'string',
        'min_size' => '0',
        'max_size' => '12');
    $retval[] = array('name' => 'bruincast_url',
        'type' => 'url',
        'min_size' => '1',
        'max_size' => '255');
    $retval[] = array('name' => 'audio_url',
        'type' => 'url',
        'min_size' => '1',
        'max_size' => '255');
    $retval[] = array('name' => 'podcast_url',
        'type' => 'url',
        'min_size' => '1',
        'max_size' => '255');
    $retval[] = array('name' => 'week',
        'type' => 'Integer',
        'min_size' => '1',
        'max_size' => '10');
    $retval[] = array('name' => 'name',
        'type' => 'string',
        'min_size' => '0',
        'max_size' => '50');
    return $retval;
}

/**
 * Updates Bruincast DB from CSV at $CFG->bruincast_data URL
 */

/**
 * Parses and updates bruincast data from given source url.
 *
 * @global type $CFG
 * @global type $DB
 *
 * @param type $sourceurl
 */
function update_bruincast_db($sourceurl) {
    global $CFG, $DB;

    echo get_string('bcstartnoti', 'tool_ucladatasourcesync') . "\n";

    $csvdata = get_csv_data($sourceurl);
    $fields = define_data_source();

    // We know for the bruincast DATA that the first two rows are a
    // timestamp and the column titles, so get rid of them.
    unset($csvdata[0]);
    unset($csvdata[1]);

    $cleandata = array();

    // This is expected bruincast mapping.
    $keymap = array('term', 'srs', 'class', 'restricted', 'bruincast_url');

    // Create an array of table record objects to insert.
    foreach ($csvdata as $datanum => $d) {
        $obj = new stdClass();

        if (count($d) != count($fields)) {
            echo get_string('errbcinvalidrowlen', 'tool_ucladatasourcesync') . "\n";
            continue;
        }
        $invalidfields = array();
        foreach ($fields as $fieldnum => $fielddef) {
            // Validate/clean data.
            $data = validate_field($fielddef['type'], $d[$fieldnum],
                    $fielddef['min_size'], $fielddef['max_size']);
            if ($data === false) {
                $invalidfields[] = $fielddef['name'];
            }
        }

        // Give warning about errors.
        if (!empty($invalidfields)) {
            $error = new stdClass();
            $error->fields = implode(', ', $invalidfields);
            $error->line_num = $datanum;
            $error->data = $d;
            print($d);
            echo(get_string('warninvalidfields', 'tool_ucladatasourcesync',
                    $error) . "\n");
            continue;
        }

        foreach ($keymap as $k => $v) {
            if ($k == 2) {   // Skip 'class' field.
                continue;
            }
            $obj->$v = $d[$k];
        }

        // Index by term-srs, so that check_crosslists can see if crosslists exist.
        // There can be multiple entries for a given term/srs.
        $cleandata[$obj->term.'-'.$obj->srs][] = $obj;
    }

    // Do not process bruincast data if there are no valid entries.
    if (empty($cleandata)) {
        die(get_string('bcnoentries', 'tool_ucladatasourcesync'). "\n");
    }

    // Drop table if we are processing new entries.
    $DB->delete_records('ucla_bruincast');

    // Insert records.
    try {
        $insertcount = 0;
        foreach ($cleandata as &$termsrsentries) {
            foreach ($termsrsentries as &$cd) {
                $courseid = match_course($cd->term, $cd->srs);

                if (!empty($courseid)) {
                    $cd->courseid = $courseid;
                }

                $DB->insert_record('ucla_bruincast', $cd);
                ++$insertcount;
            }
        }

        // Give total inserts.
        echo get_string('bcsuccessnoti', 'tool_ucladatasourcesync', $insertcount) . "\n";

        // Find errors in the crosslisted courses and notify.
        check_crosslists($cleandata);
    } catch (dml_exception $e) {
        // Report a DB insert error.
        echo "\n" . get_string('errbcinsert', 'tool_ucladatasourcesync') . "\n";
    }
}

/**
 * Checks for crosslist issues and emails $bruincast_errornotify_email to fix.
 *
 * @global object $CFG
 * @global object $DB
 *
 * @param array $data   Should be processed data that was validated and matched
 */
function check_crosslists(&$data) {
    global $CFG, $DB;

    $errornotifyemail = get_config('block_ucla_bruincast', 'errornotify_email');
    $quietmode = get_config('block_ucla_bruincast', 'quiet_mode');

    $problemcourses = array();

    // Find crosslisted courses.
    foreach ($data as $entries) {
        foreach ($entries as $d) {
            // Get the courseid for a particular TERM-SRS.
            if (isset($d->courseid)) {
                $courseid = $d->courseid;
            } else {
                continue;   // Course is not on the system.
            }

            // Find out if it's crosslisted.
            $courses = ucla_map_courseid_to_termsrses($courseid);

            // Enforce:
            // If for a crosslisted course, any of the bruincast urls are restricted,
            // then all of the courses need to have access to the bruincast.
            if (count($courses) > 1) {
                if (strtolower($d->restricted) == 'restricted') {
                    foreach ($courses as $c) {
                        if (empty($data[$c->term.'-'.$c->srs])) {
                            $msg = "Restricted BruinCast URL is not "
                                    . "associated with crosslisted coures:\n"
                                    . "url: " . $d->bruincast_url . "\n"
                                    . "term: " . $d->term . "\n"
                                    . "srs: " . $d->srs . "\n"
                                    . "affected course srs: " . $c->srs . "\n";

                            $problemcourses[] = $msg;
                        }
                    }
                }
            }
        }
    }

    $mailbody = implode("\n", $problemcourses);

    // Send problem course details if we have any.
    if (empty($errornotifyemail) || $quietmode) {
        echo $mailbody;
    } else if (trim($mailbody) != '') {
        ucla_send_mail($errornotifyemail,
                'BruinCast data issues (' . date('r') . ')', $mailbody);
    }
}
