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
 * @param $datasourceurl The URL of the CSV data to attempt to retrieve.
 * @return mixed    Returns array of lines from data. Else returns false.
 */
function get_csv_data($datasourceurl) {

    $lines = array();
    $fp = fopen($datasourceurl, 'r');

    if ($fp) {
        while (!feof($fp)) {
            $lines[] = fgetcsv($fp);
        }
    }

    if (empty($lines)) {
        $csverror = "... ERROR: Could not open $datasourceurl!";
        log_ucla_data('bruincast', 'parsing data', 'CSV data retrieval', $csverror);

        echo "\n$csverror\n";
        return false;
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
    // Handle empty fields.
    if (empty($field) && $minsize == 0) {
        return $field;
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
 * Comparison function for the terms and class titles.
 *  
 * @param object $a Contains term and class or srs 
 * @param object $b Contains term and class or srs
 * @return int      More recent term is larger. If terms match, then alphabetic
 *                  order by class, if exists. Else order by srs, if exists.
 */
function term_title_cmp_fn($a, $b) {
    // Library reserves calls term => quarter.
    $termparam = 'term';
    if (isset($a->quarter)) {
        $termparam = 'quarter';
    }    
    // Variables $a and $b are switched such that the sort order is recent terms 
    // first and older ones last.
    $termcmp = term_cmp_fn($b->$termparam, $a->$termparam);
    
    if ($termcmp == 0) {        
        $classparam = 'class';
        if (isset($a->course_title)) {
            // Library reserves calls class => course_title.
            $classparam = 'course_title';
        }
        // Bruincast does not have course title, only srs.
        if (!isset($a->$classparam)) {
            $classparam = 'srs';
        }        
        return strnatcmp($a->$classparam, $b->$classparam);              
    }
    return $termcmp;
}

/**
 * Comparison function for the terms, courseids, and dates for Bruincasts.
 *  
 * @param object $a Contains term, courseid, srs, date, and shortname is optional
 * @param object $b Contains term, courseid, srs, date, and shortname is optional
 * @return int      More recent term is smaller. Sorts the array in opposite order of
 *                  what we want. 
 *                  If terms match a shortname is not set, put the unset Bruincast second.
 *                  If terms match and both shortnames are not set, check by srs and then week.
 *                  If terms match and both shortnames are the same, then check week.
 *                  If terms match and not other condition is met, check shortnames.
 *                  Otherwise, check term.
 */
function term_courseid_srs_cmp_fn($a, $b) {
    // Variables $a and $b are switched such that the sort order is recent terms 
    // first and older ones last.
    $termcmp = term_cmp_fn($b->term, $a->term);
    if ($termcmp === 0) {
        if (!isset($a->shortname) && !isset($b->shortname)) {
            if ($a->srs === $b->srs) {
                return $a->date - $b->date;
            }
            return strnatcmp($a->srs, $b->srs);
        } else if ($a->shortname === $b->shortname) {
            return $a->date - $b->date;
        }
        return strnatcmp($a->shortname, $b->shortname);              
    }
    return $termcmp;
}

/**
 * Retrieves all necessary Bruincast data and arranges it into an array.
 * The array is formatted to become an accordion table.
 * 
 * @param array $options    Optional options to filter the data by.
 * @return array
 */
function get_bruincast_data($options) {
    global $DB;

    // Get the header rows.
    $headersql = "SELECT bc.id, bc.courseid, bc.term, shortname
                    FROM {ucla_bruincast} bc LEFT JOIN {course} c
                         ON c.id = bc.courseid,
                         {ucla_request_classes} rc
                   WHERE bc.courseid != 0
                        AND rc.courseid = bc.courseid";

    // Get crosslisted header rows.
    // The id field for the crosslisted data must be manually set
    // so that id's do not conflict with the non-crosslisted data.
    $idinc = $DB->get_field('ucla_bruincast', 'MAX(id)', array()) + 1;
    $xlistheadersql = "SELECT (@num := @num + 1) id, xlist.courseid, rc.term, c.shortname
                         FROM {ucla_bruincast} bc RIGHT JOIN {ucla_bruincast_crosslist} xlist
                              ON xlist.contentid = bc.id,
                              {course} c, {ucla_request_classes} rc, (SELECT @num := $idinc) a
                        WHERE rc.courseid = xlist.courseid
                              AND c.id = xlist.courseid";

    // Display results according to filters.
    if (!empty($options['term'])) {
        $headersql .= " AND bc.term = " . "'" . $options['term'] . "'";
        $xlistheadersql .= " AND rc.term = " . "'" . $options['term'] . "'";
    }
    if (!empty($options['subjarea'])) {
        $headersql .= " AND rc.department = " . "'" . $options['subjarea'] . "'";
        $xlistheadersql .= " AND rc.department = " . "'" . $options['subjarea'] . "'";
    }
    $headersql .= " GROUP BY bc.courseid";
    $xlistheadersql .= " GROUP BY xlist.courseid";
    $result = array_merge($DB->get_records_sql($headersql), 
            $DB->get_records_sql($xlistheadersql));

    // Get the last id in the array to insert subject specific header rows for SSC content.
    end($result);
    $idinc = key($result) + 1;
    reset($result);
    $unassociatedsql = "SELECT (@num := @num + 1) id, bc.courseid, 
                               bc.term, bac.subjarea shortname
                          FROM {ucla_bruincast} bc, {ucla_browseall_classinfo} bac, 
                               (SELECT @num := $idinc) a
                         WHERE bac.term = bc.term 
                               AND bac.srs = bc.srs
                               AND bc.courseid = 0";
    if (!empty($options['term'])) {
        $unassociatedsql .= " AND bc.term = " . "'" . $options['term'] . "'";
    }
    if (!empty($options['subjarea'])) {
        $unassociatedsql .= " AND bac.subjarea = " . "'" . $options['subjarea'] . "'";
    }
    $unassociatedsql .= " GROUP BY bac.term, shortname";
    $result = array_merge($result, $DB->get_records_sql($unassociatedsql));

    // There are some Bruincast content with srses that are not in 
    // the table containing all courses on CCLE and SSC, so we must
    // add an extra header for those.
    $activeterms = empty($options['term']) ? get_active_terms() : array($options['term']);
    $ustring = get_string('bcunassociated', 'tool_ucladatasourcesync');
    if (empty($options['subjarea']) || $options['subjarea'] === 'Unassociated') {
        foreach ($activeterms as $term) {
            $existssql = "SELECT srs 
                            FROM {ucla_bruincast} bc
                           WHERE courseid = :courseid
                                 AND term = :term
                                 AND NOT EXISTS (
                                     SELECT srs 
                                       FROM {ucla_browseall_classinfo} bac
                                      WHERE bc.term = bac.term
                                            AND bc.srs = bac.srs)";
            if ($DB->record_exists_sql($existssql, array('courseid' => 0, 'term' => $term))) {
                $unassociated = new stdClass();
                $unassociated->courseid = $unassociated->shortname = $ustring;
                $unassociated->term = $term;
                $result[] = $unassociated;
            }
        }
    }

    // A course may have crosslisted content and regular content.
    // We need to merge these rows into one.
    $result = array_unique(array_map(function($a) { 
            $a->id = $a->courseid . $a->term . $a->shortname; 
            return $a; 
        }, $result), SORT_REGULAR);
    
    usort($result, 'term_courseid_srs_cmp_fn');

    $headerint = 1;
    foreach ($result as $header) {
        // Get content from the Bruincast table.
        $params = array('term' => $header->term, 'courseid' => $header->courseid, 
                'shortname' => $header->shortname);
        $datasql = "SELECT bc.id, bc.courseid, bc.term, bc.week, bc.date, 
                           CONCAT_WS(', ',
                                     IF(LENGTH(bc.video_files), bc.video_files, NULL),
                                     IF(LENGTH(bc.audio_files), bc.audio_files, NULL)
                           ) AS filename,
                           CONCAT_WS(', ',
                                     IF(LENGTH(bc.video_files), 'Video', NULL),
                                     IF(LENGTH(bc.audio_files), 'Audio', NULL)
                           ) AS type,
                           bc.title, bc.comments, bc.srs
                      FROM {ucla_bruincast} bc";
            
        // Get crosslisted content.
        $xlistdatasql = $datasql . ", {ucla_bruincast_crosslist} xlist
                WHERE xlist.contentid = bc.id
                      AND xlist.courseid = :courseid";

        // Bruincast content not in CCLE will be handled under subject
        // headers or the unassociated header.
        if ($header->courseid === '0') {
            // Gets all content for a specific subject area whose courses 
            // are not on CCLE but in the table with all course info.
            $datasql .= " JOIN {ucla_browseall_classinfo} bac 
                            ON bac.srs = bc.srs AND bac.term = bc.term
                    WHERE bac.subjarea = :shortname AND";
        } else if ($header->courseid === $ustring) {
            // Gets all content in the Bruincast table but not in the table 
            // with all course info.
            $params['courseid'] = 0;
            $datasql .= " WHERE NOT EXISTS (
                                SELECT srs 
                                  FROM {ucla_browseall_classinfo} bac
                                 WHERE bac.term = bc.term
                                       AND bac.srs = bc.srs) 
                               AND";
        } else {
            $datasql .= " WHERE";
        }
        $datasql .= " bc.term = :term AND bc.courseid = :courseid";
        $bcdata = $DB->get_records_sql($datasql, $params);
        $bcxlistdata = $header->courseid === '0' || $header->courseid === $ustring ? 
                [] : $DB->get_records_sql($xlistdatasql, $params);

        $subcontent = array_merge($bcdata, $bcxlistdata);
        $header->num = count($subcontent);
        usort($subcontent, 'term_courseid_srs_cmp_fn');
        array_splice($result, $headerint, 0, $subcontent);

        $headerint += $header->num + 1;
    }

    return $result;
}

/**
 * Gets table information from database for: bruincast, library reserves, and
 * video reserves.
 *
 * @param string $table     The type of table you want to get information for.
 *                          Options: "bruincast", "library_reserves", 
 *                          "video_reserves", "library_music_reserves"
 * @param array $options    Optional options to filter the data by.
 *
 * @return array
 */
function get_reserve_data($table, $options = array()) {
    global $DB;

    // Make sure courseid is the second column.
    $columns = $DB->get_columns('ucla_' . $table);
    $columnsreturned = array('id', 'courseid');
    foreach ($columns as $columnname => $columninfo) {
        if ($columnname == 'id' || $columnname == 'courseid')   continue;
        $columnsreturned[] = $columnname;
    }

    if ($table === 'bruincast') {
        $result = get_bruincast_data($options);
    } else {
        $result = $DB->get_records('ucla_' . $table, null, null,
                implode(',', $columnsreturned));
    }

    $ustring = get_string('bcunassociated', 'tool_ucladatasourcesync');
    $timeformat = get_string('strftimedatefullshort', 'tool_ucladatasourcesync');
    foreach ($result as $index => $item) {
        // Give link to course as first column of table.
        if ($item->courseid != null) {
            $shortname = $DB->get_field('course', 'shortname',
                    array('id' => ($item->courseid)));
            $courseurl = new moodle_url('/course/view.php',
                    array('id' => ($item->courseid)));
            $shortnamewithlink = html_writer::link ($courseurl, $shortname);

            if ($table === 'bruincast') {
                $icon = html_writer::tag('i','',array('class' => 'fa fa-chevron-down'));
                if (isset($item->date)) {
                    $item->date = userdate($item->date, $timeformat);
                    $item->filename = implode('<br>', explode(', ', $item->filename));
                    $item->type = implode('<br>', explode(', ', $item->type));
                } else if ($item->courseid === '0') {
                    $shortnamewithlink = $icon . $item->shortname;
                } else if ($item->courseid === $ustring) {
                    $shortnamewithlink = $icon . $item->courseid;
                } else {
                    $shortnamewithlink = $icon . $shortnamewithlink;
                }
            }

            $item->courseid = $shortnamewithlink;
        }

        // Special formatting for Video reserves.
        if ($table == 'video_reserves') {
            // Convert start/end date to standard format.
            $item->start_date = userdate($item->start_date, $timeformat);
            $item->stop_date = userdate($item->stop_date, $timeformat);

            // Create source column.
            $item->source = 'Wowza';

            // Reorder $item properties to render source before video_url.
            $temp = (object) array('courseid' => $item->courseid,
                                   'term' => $item->term,
                                   'srs' => $item->srs,
                                   'start_date' => $item->start_date,
                                   'stop_date' => $item->stop_date,
                                   'class' => $item->class,
                                   'instructor' => $item->instructor,
                                   'video_title' => $item->video_title,
                                   'source' => $item->source,
                                   'video_url' => $item->video_url);
            $result[$index] = $temp;
        }
    }
    
    if ($table !== 'bruincast') {
        usort($result, 'term_title_cmp_fn');
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
    $log_message = core_text::substr($log_message, 0, 252) . '...';
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
