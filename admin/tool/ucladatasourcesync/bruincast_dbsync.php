<?php
/**
 * Command line script to parse, verify, and update Bruincast entries in the 
 * Moodle database.
 *
 * See CCLE-2314 for details.
 *
 */
require_once(dirname(__FILE__) . '/lib.php');

// Check to see if config variables are initialized
$source_url = get_config('block_ucla_bruincast', 'source_url');
if (empty($source_url)) {
    log_ucla_data('bruincast', 'read', 'Initializing cfg variables', 
            get_string('errbcmsglocation','tool_ucladatasourcesync') );
    die("\n".get_string('errbcmsglocation','tool_ucladatasourcesync')."\n");
}

// Begin database update
update_bruincast_db($source_url);

function define_data_source() {
    $ret_val = array();

    $ret_val[] = array('name' => 'term',
        'type' => 'term',
        'min_size' => '3',
        'max_size' => '3');
    $ret_val[] = array('name' => 'srs',
        'type' => 'srs',
        'min_size' => '7',
        'max_size' => '9');
    $ret_val[] = array('name' => 'class',
        'type' => 'string',
        'min_size' => '0',
        'max_size' => '255');
    $ret_val[] = array('name' => 'restricted',
        'type' => 'string',
        'min_size' => '0',
        'max_size' => '12');
    $ret_val[] = array('name' => 'bruincast_url',
        'type' => 'url',
        'min_size' => '1',
        'max_size' => '255');
    return $ret_val;
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
 * @param type $source_url 
 */
function update_bruincast_db($source_url) {
    global $CFG, $DB;
    
    echo get_string('bcstartnoti', 'tool_ucladatasourcesync') . "\n";

    $csv_data = get_csv_data($source_url);
    $fields = define_data_source();

    // We know for the bruincast DATA that the first two rows are a
    // timestamp and the column titles, so get rid of them
    unset($csv_data[0]);
    unset($csv_data[1]);

    $clean_data = array();

    // This is expected bruincast mapping
    $keymap = array('term', 'srs', 'class', 'restricted', 'bruincast_url');

    // create an array of table record objects to insert
    foreach ($csv_data as $data_num => $d) {
        $obj = new stdClass();

        if (sizeof($d) != sizeof($fields)) {
            echo get_string('errbcinvalidrowlen', 'tool_ucladatasourcesync') . "\n";
            continue;
        }
        $invalid_fields = array();
        foreach ($fields as $field_num => $field_def) {
            // validate/clean data
            $data = validate_field($field_def['type'], $d[$field_num],
                    $field_def['min_size'], $field_def['max_size']);
            if ($data === false) {
                $invalid_fields[] = $field_def['name'];
            }
        }

        // give warning about errors
        if (!empty($invalid_fields)) {
            $error = new stdClass();
            $error->fields = implode(', ', $invalid_fields);
            $error->line_num = $data_num;
            $error->data = print_r($d, true);
            echo(get_string('warninvalidfields', 'tool_ucladatasourcesync',
                    $error) . "\n");
            continue;
        }

        foreach ($keymap as $k => $v) {
            if ($k == 2) {   // skip 'class' field
                continue;
            }
            $obj->$v = $d[$k];
        }

        // index by term-srs, so that check_crosslists can see if crosslists exist       
        // there can be multiple entries for a given term/srs
        $clean_data[$obj->term.'-'.$obj->srs][] = $obj;
    }

    // Do not process bruincast data if there are no valid entries.
    if (empty($clean_data)) {
        die(get_string('bcnoentries', 'tool_ucladatasourcesync'). "\n");
    }

    // Drop table if we are processing new entries.
    $DB->delete_records('ucla_bruincast');

    // Insert records
    try {
        $insert_count = 0;
        foreach ($clean_data as &$termsrs_entries) {
            foreach ($termsrs_entries as &$cd) {
                $courseid = match_course($cd->term, $cd->srs);

                if (!empty($courseid)) {
                    $cd->courseid = $courseid;
                }

                $DB->insert_record('ucla_bruincast', $cd);
                ++$insert_count;
            }
        }

        // Give total inserts
        echo get_string('bcsuccessnoti', 'tool_ucladatasourcesync', $insert_count) . "\n";

        // Find errors in the crosslisted courses and notify
        check_crosslists($clean_data);
    } catch (dml_exception $e) {
        // Report a DB insert error
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
    
    $errornotify_email = get_config('block_ucla_bruincast', 'errornotify_email');
    $quiet_mode = get_config('block_ucla_bruincast', 'quiet_mode');
    
    $problem_courses = array();

    // Find crosslisted courses.
    foreach ($data as $entries) {
        foreach ($entries as $d) {
            // Get the courseid for a particular TERM-SRS
            if (isset($d->courseid)) {
                $courseid = $d->courseid;
            } else {
                continue;   // course is not on the system
            }

            // Find out if it's crosslisted
            $courses = ucla_map_courseid_to_termsrses($courseid);

            // Enforce:
            // If for a crosslisted course, any of the bruincast urls are restricted,
            //   then all of the courses need to have access to the bruincast.
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

                            $problem_courses[] = $msg;
                        }
                    }
                }
            }
        }
    }

    $mail_body = implode("\n", $problem_courses);

    // Send problem course details if we have any
    if (empty($errornotify_email) || $quiet_mode) {
        echo $mail_body;
    } else if (trim($mail_body) != '') {
        ucla_send_mail($errornotify_email,
                'BruinCast data issues (' . date('r') . ')', $mail_body);
    }
}
