<?php
/**
 *  Shared UCLA-written for cron-synching functions.
 **/

/**
 * Updates the ucla_reg_classinfo table.
 */
class ucla_reg_classinfo_cron {

    static function enrolstat_translate($char) {
        $codes = array(
            'X' => 'Cancelled',
            'O' => 'Opened',
            'C' => 'Closed',
            'W' => 'Waitlisted',
            'H' => 'Hold'
        );

        if (!isset($codes[$char])) {
            return 'Unknown Enrollment Code';
        }

        return $codes[$char];
    }

    /**
     * Compares old and new entries from Registrar. An update is really needed
     * only if the following fields are different:
     *  - acttype
     *  - coursetitle
     *  - sectiontitle
     *  - enrolstat
     *  - url
     *  - crs_desc (course description)
     *  - crs_summary (class description)
     *
     * @param stdClass $old Database object.
     * @param array $new    Note, $new comes in as an array, not object.
     * @return boolean      Returns true if records needs to be updated,
     *                      otherwise returns false.
     */
    function needs_updating(stdClass $old, array $new) {
        $checkfields = array('acttype', 'coursetitle', 'sectiontitle',
            'enrolstat', 'url', 'crs_desc', 'crs_summary');

        foreach ($checkfields as $field) {
            if ($old->$field != $new[$field]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Queries given stored procedure for given data.
     *
     * @param string $sp
     * @param array $data
     *
     * @return array
     */
    function query_registrar($sp, $data) {
        return registrar_query::run_registrar_query($sp, $data);
    }

    /**
     * Get courses from the ucla_request_classes table for a given term. Query 
     * the registrar for those courses. 
     * 
     * From the records that are returned from the registrar then insert or 
     * update records in the ucla_reg_classinfo table. 
     * 
     * Then for the courses that didn't have any data returned to them from
     * the registrar, then mark those courses as cancelled in the 
     * ucla_reg_classinfo table.
     *
     * @param array $terms
     * @return boolean 
     */
    function run($terms) {
        global $DB;

        if (empty($terms)) {
            return true;
        }

        // Get courses from our request table.
        list($sqlin, $params) = $DB->get_in_or_equal($terms);
        $where = 'term ' . $sqlin;
        $urcrecords = $DB->get_recordset_select('ucla_request_classes',
            $where, $params);
        if (!$urcrecords->valid()) {
            return true;
        }

        // Updated.
        $uc = 0;
        // No change needed.
        $nc = 0;
        // Inserted.
        $ic = 0;
        // For cancelled courses later.
        $notfoundatregistrar = array();

        // Get the data from the Registrar and process it.
        foreach ($urcrecords as $request) {
            $reginfo = $this->query_registrar('ccle_getclasses',
                    array(
                        'term' => $request->term,
                        'srs' => $request->srs
                    )
                );
            if (!$reginfo) {
                echo "No data for {$request->term} {$request->srs}\n";
                $notfoundatregistrar[] =
                        array('term' => $request->term, 'srs' => $request->srs);
            } else {
                $newclassinfo = reset($reginfo);  // Result is in an array.

                // See if we need to insert/update ucla_reg_classinfo.
                $existingclassinfo = $DB->get_record('ucla_reg_classinfo',
                        array('term' => $request->term, 'srs' => $request->srs));
                if (!empty($existingclassinfo)) {
                    // Exists, so see if we need to update.
                    $newclassinfo['id'] = $existingclassinfo->id;
                    if (self::needs_updating($existingclassinfo, $newclassinfo)) {
                        $DB->update_record('ucla_reg_classinfo', $newclassinfo);
                        $uc++;
                    } else {
                        $nc++;
                    }
                } else {
                    $DB->insert_record('ucla_reg_classinfo', $newclassinfo);
                    $ic++;
                }

                
            }
        }
        $urcrecords->close();
        
        // Mark courses that the registrar didn't have data for as "cancelled".
        $numnotfound = 0;
        if (!empty($notfoundatregistrar)) {
            foreach ($notfoundatregistrar as $termsrs) {
                // Try to update entry in ucla_reg_classinfo (if exists) and
                // mark it as cancelled.
                $DB->set_field('ucla_reg_classinfo', 'enrolstat', 'X', $termsrs);
                ++$numnotfound;
            }
        }
        
        echo "Updated: $uc . Inserted: $ic . Not found at registrar: "
            . "$numnotfound . No update needed: $nc\n";

        return true;
    }
}

/**
 *  Fills the subject area cron table.
 **/
class ucla_reg_subjectarea_cron {
    function run($terms) {
        global $DB;

        $ttte = 'ucla_reg_subjectarea';

        if (empty($terms)) {
            debugging('NOTICE: empty $terms for ucla_reg_subjectarea_cron');
            return true;    // maybe not an error
        }

        $reg = registrar_query::get_registrar_query('cis_subjectareagetall');
        if (!$reg) {
            echo "No registrar module found.";
        }
  
        $subjareas = array();
        foreach ($terms as $term) {
            try {            
                $regdata = 
                    $reg->retrieve_registrar_info(
                            array('term' => $term)
                        );
            } catch(Exception $e) {
                // mostly likely couldn't connect to registrar
                mtrace($e->getMessage());
                return false;
            }                      

            if ($regdata) {
                $subjareas = array_merge($subjareas, $regdata);
            }
        }

        if (empty($subjareas)) {
            debugging('ERROR: empty $subjareas for ucla_reg_subjectarea_cron');
            return false;   // most likely an error
        }        
        
        $checkers = array();
        foreach ($subjareas as $k => $subjarea) {
            $newrec = new stdClass();

            $t =& $subjareas[$k];
            $t = array_change_key_case($subjarea, CASE_LOWER);
            $t['modified'] = time();

            $sa_text = $t['subjarea'];
            $checkers[] = $sa_text;
        }

        list($sql_in, $params) = $DB->get_in_or_equal($checkers);
        $sql_where = 'subjarea ' . $sql_in;

        $selected = $DB->get_records_select($ttte, $sql_where, $params,
            '', 'TRIM(subjarea), id');

        $newsa = 0;
        $updsa = 0;

        $amdebugging = debugging();

        foreach ($subjareas as $sa) {
            $sa_text = $sa['subjarea'];
            if (empty($selected[$sa_text])) {
                $DB->insert_record($ttte, $sa);

                $newsa ++;
            } else {
                $sa['id'] = $selected[$sa_text]->id;
                $DB->update_record($ttte, $sa);
                $updsa ++;
            }

        }

        echo "New: $newsa. Updated: $updsa.\n";

        return true;
    }
}

// CCLE-3739 - Do not allow "UCLA registrar" enrollment plugin to be hidden 
class ucla_reg_enrolment_plugin_cron {
    public function run($terms = null) {
        global $DB;
        
        // Find courses whose registrar (database) enrolment has been disabled
        // @note 'enrol.status' is not indexed, but it's a boolean value
        $records = $DB->get_records('enrol', array('enrol' => 'database', 
            'status' => ENROL_INSTANCE_DISABLED));
        
        // Now enable them the Moodle way
        foreach($records as $r) {
            self::update_plugin($r->courseid, $r->id);
        }
    }
    
    static public function update_plugin($courseid, $instanceid) {
        $instances = enrol_get_instances($courseid, false);
        $plugins   = enrol_get_plugins(false);

        // Straight out of enrol/instances.php
        $instance = $instances[$instanceid];
        $plugin = $plugins[$instance->enrol];
        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            $plugin->update_status($instance, ENROL_INSTANCE_ENABLED);
        }
    }
}

// EoF
