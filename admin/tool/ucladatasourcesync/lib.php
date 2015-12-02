<?php
/**
 * Library for use by the Datasource Syncronization Scripts of the Bruincast
 * (CCLE-2314), Library reserves (CCLE-2312), and Video reserves (CCLE-5185)
 *
 * See CCLE-2790 for details.
 **/

// Satisfy Moodle's requirement for running CLI scripts
if (!defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(__FILE__) . '/../../../config.php');
global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');

ucla_require_registrar();

require_once($CFG->dirroot . '/lib/moodlelib.php');

/**
 * Returns an array of raw CSV data from the CSV file at datasource_url.
 *
 * @param $datasource_url The URL of the CSV data to attempt to retrieve.
 */
function get_csv_data($datasource_url) {

    $lines = array();
    $fp = fopen($datasource_url, 'r');

    if ($fp) {

        while (!feof($fp)) {
            $lines[] = fgetcsv($fp);
        }
    }

    if (empty($lines)) {
        $csverror = "... ERROR: Could not open $datasource_url!";
        log_ucla_data('bruincast', 'parsing data', 'CSV data retrieval', $csverror);

        echo "\n$csverror\n";
        exit(5);
    }

    return $lines;
}

/**
 * Returns an array of cleaned and parsed CSV data from the unsafe and/or unclean input array of data.
 * @param $data The array of unsafe CSV data.
 * @param $table_name The moodle DB table name against which to validate the field labels of the CSV data.
 * @note Currently only works with the Bruincast update script at ./bruincast_dbsync.php  May cause undefined behaviour if used with other datasets.
 *
 */
function cleanup_csv_data(&$data_array, $table_name) {

    // get global variables
    global $CFG;
    global $DB;

    $incoming_data = $data_array; // mmm... memory savings.....
    $posfields = array();

    // Automatically ignore fields
    $curfields = $DB->get_records_sql("DESCRIBE {$CFG->prefix}" . $table_name);

    foreach ($curfields as $fieldname => $fielddata) {

        // Skip the field `id`
        if ($fieldname == 'id') {
            continue;
        }

        $posfields[$fieldname] = $fieldname;
    }



    // Assuming the field descriptor line is going to come before all the other lines
    $field_descriptors_obtained = FALSE;
    $fields_desc_line = -1;
    $total_lines = count($incoming_data);

    while (!$field_descriptors_obtained) {

        $fields_desc_line++;

        if ($fields_desc_line == $total_lines) {
            die("\nCould not find any lines that match any field in the DB!\n");
        }

        $file_fields = array();

        foreach ($incoming_data[$fields_desc_line] as $tab_num => $field_name) {

            if (!isset($posfields[$field_name])) {
                $ignored_fields[] = $field_name;
            } else {
                $finfields[] = $field_name;
            }

            $file_fields[$field_name] = $field_name;
        }

        // Assume that this is the line that we want
        $field_descriptors_obtained = TRUE;

        foreach ($posfields as $fieldname => $field) {

            if (!isset($file_fields[$field])) {
                // This line is not the field descriptor line!
                $field_descriptors_obtained = FALSE;
            }
        }
    }

    // Reindex the data for nicer formatting
    $data_incoming = array();
    $invalid_restrictions = array();

    for ($line = $fields_desc_line + 1; $line < count($incoming_data); $line++) {

        // Make sure this line has data as we have fields
        if (count($incoming_data[$line]) != count($file_fields)) {
            echo "Line $line is badly formed!\n";
            continue;
        }

        foreach ($incoming_data[$fields_desc_line] as $tab_num => $field_name) {

            $data = $incoming_data[$line][$tab_num];
            $field = trim($field_name);

            if (in_array($field, $ignored_fields)) {
                continue;
            }

            // Pad the beginning with 0s
            if ($field_name == 'srs') {
                $data = sprintf('%09s', $data);
            }

            if ('restricted' == $field) {
                // make sure that restricted is a known value
                if (!in_array($data, array('Open', 'Restricted', 'See Instructor', 'Online'))) {
                    $invalid_restrictions[] = "Line $line has unknown restriction of $data. Marking it as 'Undefined'. Please add it.\n";
                    // restriction is not in lang file (for now). just set
                    // restriction as undefined
                    $data = 'Undefined';
                }
            }

            $data_incoming[$line][$field] = $data;
        }
    }

    if (!empty($invalid_restrictions)) {
        $invalid_restrictions = implode("\n", $invalid_restrictions);

        $bruincast_source_url = get_config('block_ucla_bruincast', 'source_url');
        $bruincast_quiet_mode = get_config('block_ucla_bruincast', 'quiet_mode');

        if (empty($bruincast_source_url) || $bruincast_quiet_mode) {
            echo $invalid_restrictions;
        } else {
            ucla_send_mail($bruincast_source_url,
                    'BruinCast data issues (' . date('r') . ')', $invalid_restrictions);
        }
    }


    return $data_incoming;
}

/**
 * For the different data sources, try to match the entry to a courseid.
 *
 * @param string $term
 * @param int $srs                  May be course srs or section srs
 * @param string $subject_area      Default to null.
 * @param string $cat_num           Default to null.
 * @param string $sec_num           Default to null.
 *
 * @return int      Returns courseid if found, else returns null
 */
function match_course($term, $srs, $subject_area=null, $cat_num=null, $sec_num=null) {
    global $DB;
    $ret_val = null;   // trying to find courseid that matches params
    $found_bad_srs = false; // used to track bad data source

    // prepare error message object (not going to be used most of the time,
    // but useful to do it here than whenever and error is called)
    $a = new stdClass();
    $a->term = $term;
    $a->srs = $srs;
    $a->subject_area = $subject_area;
    $a->cat_num = $cat_num;
    $a->sec_num = $sec_num;

    if (!ucla_validator('srs', $srs)) {
        $found_bad_srs = true;
    }

    // If srs is valid, then try to look up courseid by term/srs
    if (!$found_bad_srs) {
        $ret_val = ucla_map_termsrs_to_courseid($term, $srs);
        if (empty($ret_val)) {
            // maybe given srs is a discussion, not course
            $primary_srs = null;
            $result = registrar_query::run_registrar_query(
                    'ccle_get_primary_srs', array('term' => $term, 'srs' => $srs));
            if (!empty($result)) {
                $primary_srs = $result[0]['srs_crs_no'];
            }

            if (!empty($primary_srs) && $srs != $primary_srs) {
                $a->primary_srs = $primary_srs;
                echo get_string('noticediscussionsrs', 'tool_ucladatasourcesync', $a) . "\n";
                $ret_val = ucla_map_termsrs_to_courseid($term, $primary_srs);
            }

            if (empty($ret_val)) {
                // if still couldn't find srs as either discussion or real srs on
                // system, maybe it doesn't exist?

                // see if course record exist at Registrar
                $results = registrar_query::run_registrar_query(
                        'ccle_getclasses', array('term' => $term, 'srs' => $srs));

                if (empty($results)) {
                    // bad srs number, so try to match using $subject_area,
                    // $cat_num, (and $sec_num)
                    echo get_string('warnnonexistentsrs', 'tool_ucladatasourcesync', $a) . "\n";
                    $found_bad_srs = true;
                }
            }
        }
    }

    // Else, try to find course using subject area, catalog number, and section
    // number (if any).
    if (empty($ret_val) && !empty($subject_area) && !empty($cat_num)) {
        $sql = 'SELECT  courseid
                FROM    {ucla_request_classes} rc,
                        {ucla_reg_classinfo} rci,
                        {course} co
                WHERE   co.id=rc.courseid AND
                        rc.term = rci.term AND
                        rc.srs = rci.srs AND
                        rci.term = :term AND
                        rci.subj_area = :subj_area AND
                        rci.coursenum = :coursenum';

        $params['term'] = $term;
        $params['subj_area'] = $subject_area;
        $params['coursenum'] = $cat_num;

        if (!empty($sec_num)) {
            $sql .= ' AND rci.sectnum = :sectnum';
            $params['sectnum'] = $sec_num;
        }
        $records = $DB->get_records_sql($sql, $params);

        // only use record if there was one match, because might match courses
        // with many sections (see bug in CCLE-2938)
        if (count($records) == 1) {
            $record = array_pop($records);
            $ret_val = $record->courseid;
        }

        if (!empty($ret_val) && $found_bad_srs) {
            // found courseid through alternative means! log it
            $a->courseid = $ret_val;
            echo get_string('noticefoundaltcourseid', 'tool_ucladatasourcesync', $a) . "\n";
        }
    }

    return $ret_val;
}

/**
 * Validates a given field.
 *
 * @param string $type     Type can be: string, term, srs, date_slashed, url,
 *                         coursenum, int
 * @param mixed $field
 * @param int $minsize     Min given field can be. Default to 0
 * @param int $maxsize     Max given field can be. Default to 100
 *
 * @return mixed            Returns false if error, else returns given field
 *                          cleaned up and ready to use (e.g. srs is padded,
 *                          field trimed)
 */
function validate_field($type, $field, $minsize=0, $maxsize=100) {
    $field = trim($field);
    $fieldsize = strlen($field);
    if ($fieldsize > $maxsize || $fieldsize < $minsize) {
        return false;
    }

    switch ($type) {
        case 'term':
            if (!ucla_validator('term', $field)) {
                return false;
            }
            break;
        case 'srs':
            $field = sprintf('%09s', $field); // Pad it since data is unreliable.
            if (!ucla_validator('srs', $field)) {
                return false;
            }
            break;
        case 'coursenum':
            $field = ltrim($field, '0');
            break;
        case 'date_slashed':
            if (substr_count($field, '/') == 2) {
                list($m, $d, $y) = explode('/', $field);
                if (!checkdate($m, $d, sprintf('%04u', $y))) {
                    return false;
                }
            }
            break;
        case 'date_dashed':
            if (substr_count($field, '-') == 2) {
                list($y, $m, $d) = explode('-', $field);
                if (!checkdate($m, $d, sprintf('%04u', $y))) {
                    return false;
                }
            }
            break;
        case 'url':
            $field = clean_param($field, PARAM_URL);
            if (empty($field)) {
                // Function clean_param returns blank on invalid url.
                return false;
            }
            break;
        case 'string':
            $field = clean_param($field, PARAM_TEXT);
            break;
        case 'int':
            $field = clean_param($field, PARAM_INT);
            break;
        default:
            return false;   // Invalid type.
    }

    return $field;
}

/**
 * Gets table information from database for: bruincast, library reserves, and
 * video reserves.
 *
 * @param string $table The type of table you want to get information for.
 *                      Options: "bruincast", "library_reserves", "video_reserves"
 *
 * @return array
 */
function get_reserve_data($table) {
    global $DB;

    // Make sure courseid is the second column.
    $columns = $DB->get_columns('ucla_' . $table);
    $columnsreturned = array('id', 'courseid');
    foreach ($columns as $columnname => $columninfo) {
        if ($columnname == 'id' || $columnname == 'courseid')   continue;
        $columnsreturned[] = $columnname;
    }

    $result = $DB->get_records('ucla_' . $table, null, null,
            implode(',', $columnsreturned));

    $timeformat = get_string('strftimedatefullshort');
    foreach ($result as $index => $item) {
        // Give link to course as first column of table.
        if ($item->courseid != null) {
            $shortname = $DB->get_field('course', 'shortname',
                    array('id' => ($item->courseid)));
            $courseurl = new moodle_url('/course/view.php',
                    array('id' => ($item->courseid)));
            $shortnamewithlink = html_writer::link ($courseurl, $shortname);

            $item->courseid = $shortnamewithlink;
        }
        // Convert start/end date to standard format.
        $item->start_date = userdate($item->start_date, $timeformat);
        $item->stop_date = userdate($item->stop_date, $timeformat);
    }

    return $result;
}

/**
 * Generic logging of library reserves and video reserves data processing scripts
 * Sends email to ccle support if an error occured
 *
 * @param string $func     Activity to be logged
 *                         (video reserves, library reserve, bruincast)
 * @param string $action   Action taken
 * @param string $notice   Description of what is to be logged
 * @param string $error    Possible errors that occured when running the script
 */

function log_ucla_data($func, $action, $notice, $error = '') {
    global $SITE;

    $log_message = empty($error) ? $notice : $notice . PHP_EOL . $error;
    $log_message = textlib::substr($log_message, 0, 252) . '...';
    $func = str_replace(' ', '', $func);
    $action = str_replace(' ', '', $action);
    $datasourceevent = \tool_ucladatasourcesync\event\ucladatasourcesync_event::datasource($func, $action);
    $event = $datasourceevent::create(array(
        'context' => context_system::instance(),
        'other'    => array(
            'func' => $func,
            'message' => $log_message,
            'action' => $action,
            'siteid' => $SITE->id
        )
    ));
    $event->trigger();

    // If an error was reported, then send an email to ccle support.
    if (!empty($error)) {
        $contact_email = get_config('contact_email', 'tool_ucladatasourcesync');
        if (!empty($contact_email)) {
            $body = sprintf("Action: %s\nNotice: %s\nError: %s", $action,
                    $notice, $error);
            ucla_send_mail($contact_email, 'Error from ' . $func, $body);
        }
    }
}
