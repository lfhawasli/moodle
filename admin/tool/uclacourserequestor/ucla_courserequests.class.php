<?php

/**
 *  This class represents a set of course requests.
 *  This class is badly organized, I'm sorry.
 **/
class ucla_courserequests {
    // This is the requests indexed by setid
    var $setindex = array();

    // These are abandoned course requests
    var $abandoned = array();

    // These are records to be deleted.
    var $deletes = array();

    var $unpreppedsetid = 1;

    // Success flags should be >= 100
    const savesuccess = 100;
    const deletesuccess = 101;
    const deletecoursesuccess = 104;
    const insertsuccess = 102;

    // Failed flags should be < self::savesuccess, enums would be cool
    const savefailed = 0;
    const deletefailed = 1;

    // Soft fail...
    const deletecoursefailed = 50;

    private $_validated = null;

    /**
     *  Determines if the request was properly saved based on the flag
     *  returned by commit().
     **/
    static function request_successfully_handled($flag) {
        return $flag > self::deletecoursefailed;
    }

    static function commit_flag_string($flag) {
        $s = '';
        // Is there a better way?
        switch ($flag) {
            case self::savesuccess:
                $s = 'savesuccess';
                break;
            case self::insertsuccess:
                $s = 'insertsuccess';
                break;
            case self::deletesuccess:
                $s = 'deletesuccess';
                break;
            case self::deletecoursesuccess:
                $s = 'deletecoursesuccess';
                break;
            case self::savefailed:
                $s = 'savefailed';
                break;
            case self::deletefailed:
                $s = 'deletefailed';
                break;
            case self::deletecoursefailed:
                $s = 'deletecoursefailed';
                break;
            default:
                $s = 'request_error';
        }

        return $s;
    }

    /**
     *  Returns if there are no courses in this index.
     **/
    function is_empty() {
        return empty($this->setindex);
    }

    /**
     *  Adds a set to the bunch of course requests.
     **/
    function add_set($set) {
        $this->_validated = false;
        
        // Go through and figure out the setid provided in the set
        $setid = null;
        $f = 'setid';
        foreach ($set as $rq) {
            if (isset($rq[$f])) {
                if ($setid == null) {
                    // Set the setid to the first one we find
                    $setid = $rq[$f];
                } else if ($setid != $rq[$f]) {
                    debugging('Mismatching setid expected ' . $setid 
                        . ' got ' . $rq[$f]);
                }
            }
        }

        if ($setid == null) {
            $setid = $this->get_unprepped_setid();
        }

        $set = apply_to_set($set, $f, $setid);
        $this->setindex[$setid] = $set;
    }

    /**
     *  This and the following function need to be in sync.
     *  Finds a special index for set id in cases where we need a new index
     *  that is not in the db.
     **/
    function get_unprepped_setid() {
        $upsi = 'new_' . $this->unpreppedsetid;
        $this->unpreppedsetid++;
        return $upsi;
    }

    /**
     *  Checks if a particular used set id is a non-db thing.
     **/
    function is_unprepped_setid($setid) {
        return preg_match('/new_[0-9]*/', $setid);
    }

    /**
     *  Apply changes onto our current set index.
     **/
    function apply_changes($changes, $context) {
        $this->_validated = false;

        // Store this for later
        $this->_changes_copy = $changes;

        $checkers = request_get_editables();

        if ($context == UCLA_REQUESTOR_FETCH) {
            $d = 'build';
            $deletemode = false;
        } else {
            $d = 'delete';
            $deletemode = true;
        }

        $checkers[] = $d;

        $h = 'hostcourse';
        $f = 'crosslists';

        // These will have the changes that were actually perfomed
        $changed = array();

        $changeaccessors = array();
        foreach ($this->setindex as $setid => $courses) {
            if (!isset($changes[$setid])) {
                // This should never happen
                debugging('no changes for ' . $setid);
            }
        }

        foreach ($changes as $setid => $changeset) {
            if (empty($this->setindex[$setid])) {
                unset($changes[$setid]);
                continue;
            }

            // First handle checking to ignore
            if (empty($changeset[$d])) {
                $appval = 0;
            } else {
                $appval = 1;
            }

            if (request_ignored(array($d => $appval))) {
                $this->setindex[$setid] = apply_to_set($this->setindex[$setid],
                    $d, $appval);

                if ($deletemode && $appval == 1) {
                    $this->deletes[$setid] = true;
                }

                // none of these really matter
                continue;
            }

            if ($changeset['action'] == UCLA_COURSE_BUILT) {
                // You cannot change after you've already built a request.
                continue;
            }

            // Figure out what we're going to end up changing for each entry
            $setprops = array();
            foreach ($checkers as $editable) {
                if (!isset($changeset[$editable])) {
                    $v = 0;
                } else {
                    $v = $changeset[$editable];
                }

                // this is the value that has is agreed on by the set
                unset($k);
                foreach ($this->setindex[$setid] as $course) {
                    if (!isset($course[$editable])) {
                        $k = null;
                        break;
                    }

                    $cv = $course[$editable];

                    if (!isset($k)) {
                        $k = $cv;
                    } else if ($k != $cv) {
                        // Disagreeing course objects
                        $k = null;
                        break;
                    }
                }

                // This value must be changed
                if ($k != $v) {
                    $this->setindex[$setid] = apply_to_set(
                        $this->setindex[$setid],
                        $editable, $v
                    );


                    $setprops[$editable] = $v;
                }
            }

            if (!empty($setprops)) {
                $changed[$setid] = $setprops;
            }
        }

        // Array indexed by setid => array indexed by srs
        // designed to be used to see what ideally each set should look like
        $setdests = array();

        // Remove unwanted crosslists
        foreach ($changes as $setid => $changeset) {
            $courses = $this->setindex[$setid];
            $removedcls = array();

            // Destination of srses we want in this set indexed by srs
            $clind = array();

            if (!isset($changeset[$f])) {
                $changeset[$f] = array();
            }

            // Sanitize and index inputs
            foreach ($changeset[$f] as $cls) {
                $cls = trim($cls);
                if (!empty($cls)) {
                    $clind[$cls] = $cls;
                }
            }
            
            if (empty($clind)) {
                throw new moodle_exception('noselfcrosslist');
            }

            // Remove no longer existing crosslists
            foreach ($courses as $key => $c) {
                $srs = $c['srs'];

                if (!isset($clind[$srs])) {
                    $this->abandoned[$key] = $c;
                    $removedcls[] = $c;
                    unset($courses[$key]);
                } else {
                    // Everything is all right
                    unset($clind[$srs]);
                }
            }

            if (!empty($clind)) {
                $setdests[$setid] = $clind;
            }

            if (!empty($removedcls)) {
                if (!request_ignored(reset($courses))) {
                    $changed[$setid][$f]['removed'] = $removedcls;
                }
            }

            $this->setindex[$setid] = $courses;
        }

        // Add crosslists that need to be added
        foreach ($setdests as $setid => $clind) {
            $courses = $this->setindex[$setid];
            $addedcls = array();

            // Figure out the term
            unset($theterm);
            // Figure out the course
            $thecourseid = null;
            foreach ($courses as $reqkey => $course) {
                $cterm = $course['term'];
                if (!isset($theterm)) { 
                    $theterm = $cterm;
                } else if ($cterm != $theterm) {
                    throw new moodle_exception('inconsistent_set');
                }

                $ccourseid = $course['courseid'];
                if ($thecourseid == null) { 
                    $thecourseid = $ccourseid;
                } else if ($thecourseid != $ccourseid) {
                    throw new moodle_exception('inconsistent_set');
                }
            }

            // New hosts...
            foreach ($clind as $ncl) {
                $fakereq = array('term' => $theterm, 'srs' => $ncl);
                $nclkey = make_idnumber($fakereq);

                // We have data, let's save time
                if (isset($this->abandoned[$nclkey])) {
                    $newreq = $this->abandoned[$nclkey];

                    $newreq['setid'] = $setid;
                    $newreq['courseid'] = $thecourseid;

                    $courses[$nclkey] = $newreq;
                    $addedcls[] = $newreq;

                    unset($this->abandoned[$nclkey]);
                    continue;
                }

                // For now just add it.
                $nr = false;
                if (ucla_validator('srs', $ncl)) {
                    $nr = get_request_info($theterm, $ncl);
                }

                $nr_built = $nr['action'] == 'built';
                $e = UCLA_REQUESTOR_ERROR;
                if (!$nr || $nr_built) {
                    $nr = array(
                        'term' => $theterm,
                        'srs' => $ncl,
                        UCLA_REQUESTOR_ERROR => array(
                            ($nr_built ? UCLA_REQUESTOR_BADCL : UCLA_REQUESTOR_NOCOURSE) => true
                        ),
                        'instructor' => array(),
                    );
                }

                // Things fetched from the registrar should NOT
                // have this field set...
                if (isset($nr[$h])) {
                    $newset = get_crosslist_set_for_host($nr);
                    if ($nr[$h] == 0) {
                        // Just add the course only
                        $newset = array(
                            make_idnumber($nr) => $nr
                        );
                    } else {
                        // We are integrating an entire set
                        $hostkey = set_find_host_key($newset);
                        $newsetid = $newset[$hostkey]['setid'];
                        // Remove this set from our index
                        if (isset($this->setindex[$newsetid])) {
                            unset($this->setindex[$newsetid]);
                        }
                    }
                } else {
                    // This was fetched from the Registrar, just add it
                    $newset = array(
                        make_idnumber($nr) => $nr
                    );
                }

                // integrate the set into the new set
                foreach ($newset as $nrkey => $newreq) {
                    $newreq[$h] = 0;
                    $newreq['setid'] = $setid;
                    $newreq['courseid'] = $thecourseid;
                    if (!isset($courses[$nrkey])) {
                        $courses[$nrkey] = $newreq;
                        $addedcls[] = $newreq;
                    }
                }
            }

            if (!empty($addedcls)) {
                $changed[$setid][$f]['added'] = $addedcls;
            }

            $this->setindex[$setid] = $courses;
        }

        return $changed;
    }

    /** 
     *  Validates and injects errors into each request-course.
     **/
    function validate_requests($context) {
        // Special case for enforcing validation before commit
        if ($context == null) {
            if (!empty($this->_validated)) {
                return $this->_validated;
            }

            throw new coding_exception();
        }
    
        // Make sure we don't build courses twice
        $builtcourses = array();
        $requestinfos = $this->setindex;

        // handle cases in which nourlupdate is not set
        $nourlupdate_default = get_config('tool_uclacourserequestor', 'nourlupdate_default');

        $h = 'hostcourse';
        $errs = UCLA_REQUESTOR_ERROR;
        $e = UCLA_REQUESTOR_BADCL;
        foreach ($requestinfos as $setid => $set) {
            $hcthere = false;
            foreach ($set as $key => $course) {
                if (isset($course['enrolstat'])
                        && enrolstat_is_cancelled($course['enrolstat'])) {
                    $course[UCLA_REQUESTOR_ERROR][UCLA_REQUESTOR_CANCELLED]
                        = true;
                }

                // if nourlupdate is not set, then it is most likely because 
                // nourlupdate_hide is set
                if (!isset($course['nourlupdate'])) {
                    $course['nourlupdate'] = $nourlupdate_default;
                }

                // handle case when requestoremail is not set
                if (empty($course['requestoremail'])) {
                    $course['requestoremail'] = '';
                }

                /*
                if (request_ignored($course)) {
                    $requestinfos[$setid][$key] = $course;
                    $hcthere = true;
                    continue;
                }*/
                // Avoid affecting requests already built on another server when
                // fetching requests from the Registrar.
                if ($context == UCLA_REQUESTOR_FETCH) {
                    // $course will only have the index 'existselsewhere' if it has a url
                    // associated to it and the server is PROD.
                    if (array_key_exists('existselsewhere', $course)) {
                        $course[UCLA_REQUESTOR_WARNING][UCLA_REQUESTOR_URLEXISTS] = $course['existselsewhere'];
                    }
                }

                // Avoid affecting existing requests when fetching requests from
                // the Registrar
                if ($context == UCLA_REQUESTOR_FETCH) {
                    if (isset($course['id'])) {
                        $course[$errs][UCLA_REQUESTOR_EXIST] = true;
                    }
                }

                if ($course[$h] > 0) {
                    $hcthere = $key;
                }

                if (isset($builtcourses[$key])) {
                    $course[$errs][$e] = true;

                    $badsetid = $builtcourses[$key];
                    // Mark that other requests are causing problems
                    foreach ($requestinfos[$badsetid] as $crkey => $badreq) {
                        $requestinfos[$badsetid][$crkey][UCLA_REQUESTOR_KEEP] 
                            = true;
                    }
                } else {
                    $builtcourses[$key] = $setid;
                }

                $requestinfos[$setid][$key] = $course;
            }

            // This is a data inconsistency, this error needs to be handled
            // earlier.
            if (!$hcthere) {
                foreach ($set as $key => $course) {
                    $requestinfos[$setid][$key][$errs][UCLA_REQUESTOR_BADHOST] 
                        = true;
                }
            }
        }

        $this->_validated = $requestinfos;

        return $requestinfos;
    }

    /** 
     *  Commits this requests representation.
     **/
    function commit() {
        // Make a giant SQL statement?
        global $DB;
        $urc = 'ucla_request_classes';

        $requests = $this->validate_requests(null);

        $result = $DB->get_record_sql('SELECT MAX(`setid`) FROM ' . '{' . $urc . '}');
        $maxsetid = reset($result);

        // This stores all the statuses of the previously handled
        // results
        $results = array();

        $now = time();

        $i = 'instructor';
        $h = 'hostcourse';

        foreach ($requests as $setid => $set) {
            $failset = false;
            $requestentries = array();
            $thisresult = self::savesuccess;

            // Check for failsafes
            foreach ($set as $k => $r) {
                // Don't submit things with errors
                if (!empty($r[UCLA_REQUESTOR_ERROR])) {
                    $failset = true;
                    break;
                }

                // Don't save requests that were just added but
                // have a warning
                if (!empty($r[UCLA_REQUESTOR_WARNING])
                        && empty(
                            $this->_changes_copy[$setid]
                                [request_warning_checked_key($r)]
                            )) {
                    $failset = true;
                    break;
                }

                if (request_ignored($r)) {
                    $failset = true;
                    break;
                }
            }

            if ($failset) {
                continue;
            }

            // Find a valid set id
            if ($this->is_unprepped_setid($setid)) {
                $maxsetid++;
                $set = apply_to_set($set, 'setid', $maxsetid);
            }

            // Figure out what we're going to save for what course
            // to display when someonse uses the requestor
            // Also what the shortname is supposed to be...
            $properhost = set_find_host_key($set);

            $set = apply_to_set($set, $h, 0);
            $set[$properhost][$h] = 1;

            foreach ($set as $key => $request) {
                if (request_ignored($request)) {
                    continue;
                }

                // Propagate the information from the set host, except for 
                // hostcourse and instructor
                if ($key != $properhost) {
                    foreach ($set[$properhost] as $field => $val) {
                        // Skip hostcourse, instructor and id fields
                        if (in_array($field, array('courseid', 'setid', 'action'))) {
                            $request[$field] = $val;
                        }
                    }
                }

                if (!empty($request[$i])) {
                    $request[$i] = implode(' / ', $request[$i]);
                } else {
                    $request[$i] = '';
                }

                // Don't go over character limit for instructors (CCLE-4189).
                if (core_text::strlen($request[$i]) > 255) {
                    $request[$i] = trim(core_text::substr($request[$i], 0, 254));
                }

                try {
                    if (isset($request['id'])) {
                        $DB->update_record($urc, $request);
                    } else {
                        $request['added_at'] = $now;

                        $insertid = $DB->insert_record($urc, $request);
                        $thisresult = self::insertsuccess;

                        $set[$key]['id'] = $insertid;

                        // Update the internal version
                        $this->setindex[$setid] = $set;
                    }
                } catch (dml_exception $e) {
                    var_dump($e);
                    $thisresult = self::savefailed;
                } catch (coding_exception $e) {
                    var_dump($request);
                    $thisresult = self::savefailed;
                }
            }

            $results[$setid] = $thisresult;
        }

        // Delete crosslists that have been removed and not reattached.
        $coursestodelete = array();
        foreach ($this->deletes as $setid => $true) {
            if (empty($this->setindex[$setid])) {
                continue;
            }

            $coursetodelete = null;
            $requestsdeleted = false;
            $thisresult = self::deletefailed;

            foreach ($this->setindex[$setid] as $key => $course) {
                if ($coursetodelete === null 
                        || $course['courseid'] != $coursetodelete) {
                    $coursetodelete = $course['courseid'];
                } else {
                    // Inconsistent courses
                    $coursetodelete = false;
                }
            }

            // CCLE-3103 - When deleting a course add in a trigger to also 
            // delete the course request and My.UCLA url
            // Preventing requests from being deleted in this UI. It should be
            // deleted by deleting the actual course
            if (!empty($coursetodelete)) {
                $results[$setid] = self::deletecoursefailed;            
                continue;
            }
            
            // Currently, we are NOT allowing any Moodle course data to 
            // be deleted via the requestors
            $coursetodelete = false;
            
            try {
                if ($DB->delete_records($urc, array('setid' => $setid))) {
                    $thisresult = self::deletesuccess;

                    // Update internal data representation
                    unset($this->setindex[$setid]);

                    if ($coursetodelete) {
                        // Attempt to delete the courses
                        if (!delete_course($coursetodelete, false)) {
                            $thisresult = self::deletecoursefailed;
                        }
                    }
                }
            } catch (dml_exception $e) {
                var_dump($e);
                // This may not be the right usage...
            }

            $results[$setid] = $thisresult;
        }

        // mass delete the abandoned ones...
        $ids = array();
        foreach ($this->abandoned as $ab) {
            $ids[] = $ab['id'];
        }

        if (!empty($ids)) {
            list ($sql, $params) = $DB->get_in_or_equal($ids);

            $sqlwhere = '`id` ' . $sql;
            $DB->delete_records_select($urc, $sqlwhere, $params);
        }

        // Since mappings changed, purge all caches.
        $cache = cache::make('local_ucla', 'urcmappings');
        $cache->purge();

        return $results;
    }
    
    /**
     * Convert course section srs to main course srs
     * If the main cours srs is passed in, then it wil return the same value
     * 
     * @param $term
     * @param $srs
     * @return      course srs 
     */
    static function get_main_srs($term, $srs) {
        ucla_require_registrar();
        
        $main_srs = $srs;
        $t1 = registrar_query::run_registrar_query(
                'ccle_get_primary_srs', array($term, $srs), true);
        if (!empty($t1)) {
            $t2 = array_shift($t1);
            if (!empty($t2)) {
                $main_srs = array_pop($t2);
            }
        }
        
        return $main_srs;
    }
}

// EoF
