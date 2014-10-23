<?php



/**
 * Updates Bruinmedia DB from CSV at $CFG->bruinmedia_data URL
 */
function update_bruinmedia_db(){
    global $CFG, $DB;
    
    echo get_string('bcstartnoti','tool_ucladatasourcesync');

    $data = &get_csv_data($CFG->bruinmedia_data);

    // We know for the bruinmedia DATA that the first two rows are a 
    // timestamp and the column titles, so get rid of them
    unset($data[0]);
    unset($data[1]);

    $clean_data = array();

    // This is expected bruinmedia mapping
    $keymap = array('term', 'srs', 'class', 'restricted', 'bruincast_url');
        
    // create an array of table record objects to insert
    foreach($data as $d) {
        $obj = new stdClass();
        
        foreach($keymap as $k => $v) {
            if($k == 2) {   // skip 'class' field
                continue;
            }
            $obj->$v = $d[$k];
        }
        
        // Use SRS as key, might be handy
        $clean_data[$obj->srs] = $obj;
    }
    
    // Drop table if we have new data
    if(!empty($clean_data)) {
        $DB->delete_records('ucla_bruinmedia');
    }
    
    // Insert records
    try {
        foreach($clean_data as $cd) {
            $DB->insert_record('ucla_bruinmedia', $cd);
        }
        
        // Get total inserts
        $insert_count = count($clean_data);
        echo "\n... ".$insert_count." ".get_string('bcsuccessnoti','tool_ucladatasourcesync')."\n" ;
        
        // Find errors in the crosslisted courses and notify
        check_crosslists($clean_data);
        
    } catch (dml_exception $e) {
        // Report a DB insert error
        log_ucla_data('bruinmedia', 'write', 'Inserting bruinmedia data', 
                get_string('errbcinsert','tool_ucladatasourcesync') );
        echo "\n".get_string('errbcinsert','tool_ucladatasourcesync')."\n";
    }

}

/**
 * Checks for crosslist issues and emails $CFG->bruinmedia_errornotify_email to fix.
 * 
 * @param $data_incoming The array of CSV data to check
 * @todo Finish when uclalib registrar query functions are done
 *
 */
function check_crosslists(&$data) {
    global $CFG, $DB;
    
    $problem_courses = array();
    
    // Find crosslisted courses.  
    foreach($data as $d) {
        // Get the courseid for a particular TERM-SRS
        $courseid = ucla_map_termsrs_to_courseid($d->term, $d->srs);
        
        // Find out if it's crosslisted 
        $courses = ucla_map_courseid_to_termsrses($courseid);
        
        // Enforce:
        // If for a crosslisted course, any of the bruinmedia urls are restricted, 
        //   then all of the courses need to have access to the bruinmedia.
        if(count($courses) > 1) {
            if(strtolower($d->restricted) == 'restricted') {
                foreach($courses as $c) {
                    if(empty($data[$c->srs])) {
                        $msg = "There is a restricted bruinmedia URL that is not \n"
                                . "associated with crosslisted coures:"
                                . "url: " . $d->bruincast_url . "\n"
                                . "srs: " . $d->srs . "\n"
                                . "affected course srs: " . $c->srs . "\n";
                        
                        $problem_courses[] = $msg;
                    }
                }
            }
        }
    }
    
    $mail_body = implode("\n", $problem_courses);
    
    // log any crosslist problems
    if (trim($mail_body) != '') {
        log_ucla_data('bruinmedia', 'read', $mail_body);
    }
    
    // Send problem course details if we have any
    if (!isset($CFG->bruinmedia_errornotify_email) || $CFG->quiet_mode) {
        echo $mail_body;
    } else if (trim($mail_body) != '') {
        mail($CFG->bruinmedia_errornotify_email, 'BruinMedia Data Issues (' . date('r') . ')', $mail_body);
    }
}
