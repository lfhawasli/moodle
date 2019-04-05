<?php
// This file is part of the UCLA course creator plugin for Moodle - http://moodle.org/
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
 * A library of functions useful for course requestor and probably course creator.
 *
 * @author     Yangmun Choi
 * @package    tool_uclacourserequestor
 * @copyright  2011 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// These are the requestor flags used.
// This means to build.
define('UCLA_COURSE_TOBUILD', 'build');
// This means that stuff is done.
define('UCLA_COURSE_BUILT', 'built');
// This means that hopefully things are working.
define('UCLA_COURSE_LOCKED', 'running');
// This means request failed tobe built.
define('UCLA_COURSE_FAILED', 'failed');

// This means to skip validation for this course.
define('UCLA_REQUEST_IGNORE', 'ignore');

// Meta Error.
define('UCLA_REQUESTOR_ERROR', 'error');
define('UCLA_REQUESTOR_WARNING', 'warning');
define('UCLA_REQUESTOR_KEEP', 'keepintable');
define('UCLA_REQUESTOR_PASTTERM_ALERT', 'pasttermalert');

define('UCLA_REQUEST_WARNING_CHECKED', 'warning-checked');

// Errors.
define('UCLA_REQUESTOR_EXIST', 'alreadysubmitted');
define('UCLA_REQUESTOR_BADCL', 'illegalcrosslist');
define('UCLA_REQUESTOR_GHOST', 'ghostcoursecreated');
define('UCLA_REQUESTOR_BADHOST', 'inconsistenthost');
define('UCLA_REQUESTOR_CANCELLED', 'cancelledcourse');
define('UCLA_REQUESTOR_NOCOURSE', 'nosrsfound');
define('UCLA_REQUESTOR_URLEXISTS', 'builtbyanothersystem');

define('UCLA_REQUESTOR_FETCH', 'fetch');
define('UCLA_REQUESTOR_VIEW', 'views');

$uclalib = $CFG->dirroot . '/local/ucla/lib.php';
require_once($uclalib);

require_once($CFG->dirroot . '/' . $CFG->admin
    . '/tool/uclacourserequestor/ucla_courserequests.class.php');

require_once($CFG->dirroot . '/admin/tool/myucla_url/myucla_urlupdater.class.php');

/**
 * Fetches a single course from the request table.
 *
 * @param string $term
 * @param string $srs
 * $return array|boolean false
 */
function get_course_request($term, $srs) {
    if (empty($term) || empty($srs)) {
        return false;
    }

    $r = get_course_requests(
        array(array('term' => $term, 'srs' => $srs))
    );

    if (!empty($r)) {
        return reset($r);
    }

    return false;
}

/**
 * Fetches requests either by term or by term-srs,
 * you can currently only fetch by host requests.
 * @param array $inputs
 *      This can either be:
 *          Array of terms
 *              OR
 *          Array of Array('term' => term, 'srs' => srs)
 * @return array
 *      Array(
 *          term-srs => Array(
 *              request-fields
 *          ),
 *          ...
 *      )
 */
function get_course_requests($inputs=array()) {
    global $DB;

    if (empty($inputs)) {
        return array();
    }

    $where = '';
    $params = array();

    // Build parameters and SQL.
    if (!empty($inputs)) {
        $firstone = reset($inputs);
        if (!is_array($firstone)) {
            // This means a set of terms.
            $sql = '';
            list($sql, $params)  = $DB->get_in_or_equal($inputs);
            $where = 'term ' . $sql;

            $clwhere = $where;
            $clparams = $params;
        } else {
            // This means a set of term-srs.
            $wheres = array();
            $clwheres = array();

            foreach ($inputs as $termsrs) {
                $term = $termsrs['term'];
                $srs = $termsrs['srs'];

                $params[] = $term;
                $params[] = $srs;

                $wheres[] = 'term = ? AND srs = ?';
            }

            $where = implode(' OR ', $wheres);
        }
    }

    // Fetch none of them (on the safe side).
    if (empty($params)) {
        debugging('get_course_request() could not figure out params!');
        return array();
    }

    $results = $DB->get_records_select('ucla_request_classes', $where,
        $params);

    $returns = array();
    if ($results) {
        foreach ($results as $k => $r) {
            $returns[make_idnumber($r)] = prep_request_from_db($r);
        }
    }

    return $returns;
}

/**
 * Fetches a set of requests from the db.
 *
 * @param string $setid
 * @return array
 */
function get_set($setid) {
    global $DB;

    $set = $DB->get_records('ucla_request_classes',
        array('setid' => $setid));

    if (!$set) {
        return false;
    }

    $iset = array();
    foreach ($set as $request) {
        $k = make_idnumber($request);
        $iset[$k] = prep_request_from_db($request);
    }

    return $iset;
}

/**
 * Wrapper function for set_field.
 *
 * @param string $setid
 * @param string $courseid
 * @return array
 */
function associate_set_to_course($setid, $courseid) {
    global $DB;

    return $DB->set_field('ucla_request_classes', 'courseid',
        $courseid, array('setid' => $setid));
}


/**
 * Convenience function to apply a change to a set in memory.
 *
 * @param object $set
 * @param string $field
 * @param string $val
 * @return object
 */
function apply_to_set($set, $field, $val) {
    if (empty($set)) {
        return false;
    }

    foreach ($set as $k => $rq) {
        if (is_object($set[$k])) {
            $set[$k]->$field = $val;
        } else {
            $set[$k][$field] = $val;
        }
    }

    return $set;
}

/**
 * Inflates up the instructors.
 * @param  array|object $r
 * @return array
 *     Array(
 *     ... ,
 *     'instructor' => Array('instructors'),
 *     ...
 *     )
 **/
function prep_request_from_db($r) {
    if (is_object($r)) {
        $r = get_object_vars($r);
    }

    $f = 'instructor';
    if (is_string($r[$f])) {
        $v = explode('/', $r[$f]);

        $instarr = array();
        foreach ($v as $inst) {
            $tinst = trim($inst);
            if (!empty($tinst)) {
                $instarr[$tinst] = $tinst;
            }
        }

        $r[$f] = $instarr;
    }

    return $r;
}

/**
 * Fills in instructor information from the Registrar, then preps each entry
 *     to be inserted into the DB.
 *
 * @param  array $courses    None of these courses should be in the request tables,
 *             but they should be direct from Registrar.
 * @return array     Representing ucla_request_classes row.
 */
function registrar_to_requests($courses) {
    $infos = array();

    $returninfos = array();
    $defaults = get_requestor_defaults();

    foreach ($courses as $ak => $course) {
        if (is_object($course)) {
            $course = get_object_vars($course);
        }

        if (empty($course['term']) || empty($course['srs'])) {
            continue;
        }

        $term = $course['term'];
        $srs = $course['srs'];

        $k = make_idnumber($course);

        $instrs = get_instructor_info_from_registrar($term, $srs);
        $returninfos[] = prep_registrar_entry($course, $instrs, $defaults);
    }

    return $returninfos;
}

/**
 * Convenience function returns either the request info from the local DB
 * or automatically queries the Registrar.
 *
 * @param string $term
 * @param string $srs
 * @return array|boolean false
 */
function get_request_info($term, $srs) {
    $exists = get_course_request($term, $srs);

    if ($exists) {
        return $exists;
    }

    // This is very expensive.
    $reted = get_course_info_from_registrar($term, $srs);

    $ret = false;
    if (!empty($reted)) {
        $ret = registrar_to_requests($reted);
        $ret = reset($ret);
    } else if (is_array($reted)) {
        $ret = $reted;
    }

    return $ret;
}

/**
 * Wastes clock cycles and returns the crosslist checking mechanism. Takes about 0.25 seconds.
 *
 * @param string $term
 * @param string $srs
 */
function get_crosslisted_courses($term, $srs) {
    global $CFG;

    $regurl = 'http://webservices.registrar.ucla.edu/SRDB/SRDBWeb.asmx/'
        . 'getConSched?user=' . $CFG->registrar_dbuser . '&pass='
        . $CFG->registrar_dbpass . '&term=' . $term . '&SRS=' . $srs;

    try {
        $r = new SimpleXMLElement($regurl, 0, true);
    } catch (Exception $e) {
        throw new Exception('Could not connect to Registrar Crosslisting '
            . 'Webservice');
    }

    $exts = false;

    // Extract out the array with term and srs keys.
    if (!empty($r->getConSchedData)) {
        $exts = array();

        foreach ($r->getConSchedData as $termsrs) {
            $ext = extract_term_srs_xml($termsrs);

            if (!$ext) {
                continue;
            }
            // Web service can return duplicate info.
            $exts[make_idnumber($ext)] = $ext;
        }
    }

    return $exts;
}

/**
 * Convenience function to extract the term and SRS from the returned
 * XML-parsed-node-object.
 *
 * @param string $xml
 * @return object
 */
function extract_term_srs_xml($xml) {
    $t = array('term', 'srs');
    $r = array();

    foreach ($t as $k) {
        if (!isset($xml->{$k})) {
            return false;
        }

        $value = $xml->{$k}->__toString();
        if (!empty($value)) {
            $r[$k] = $value;
        }
    }

    return $r;
}

/**
 * Customizable ignoring stuff.
 *
 * @param object $data
 * @return boolean
 */
function requestor_ignore_entry($data) {
    if (is_array($data)) {
        $data = (object) $data;
    }

    if (!isset($data->subj_area)) {
        debugging('cannot check to ignore entry: '
            . var_dump($data, true));
        return false;
    }

    $subj = $data->subj_area;

    // Use this to compare exact strings.
    $rawnum = trim($data->coursenum);
    // Use this to compare course numbers.
    $num = get_course_num($rawnum);

    if ($num > 495) {
        // CCLE-8234 - Allow courses between 495-500 for EDUC and INF STD.
        if (!(in_array($subj, ['EDUC', 'INF STD']) && $num < 500)) {
            return true;
        }
    }

    if ($subj == 'PHYSICS' && $num > 295) {
        return true;
    }

    if ($subj == 'ASTR' && in_array($rawnum, array('277B', '296', '375'))) {
        return true;
    }

    // CCLE-4511 - Filter certain course numbers for engineering.
    if (in_array($subj, array('BIOENGR', 'BIOINFR', 'CH ENGR', 'C&EE', 'COM SCI',
            'EC ENGR', 'EL ENGR', 'ENGR', 'MAT SCI', 'MECH&AE')) &&
            in_array($rawnum, array('199', '260', '296', '298', '299', '375',
                '596', '597A', '597B', '597C', '598', '599'))) {
        // But include these class numbers if they are section 80-89.
        if ($data->sectnum < 80 || $data->sectnum >89) {
            return true;
        }
    }

    // CCLE-4513 - Limit course builds for LS to 192 and below.
    if (in_array($subj, array('BMD RES', 'EE BIOL', 'MCD BIO', 'PHYSCI',
            'NEUROSC', 'MIMG', 'LIFESCI', 'SOC GEN')) && $num > 192) {
        return true;
    }

    // CCLE-2894: Custom filtering for courses.
    $customfilter = get_config('tool_uclacourserequestor', 'customfilters');
    if ($customfilter) {
        foreach ($customfilter as $filter) {
            if ($rawnum == trim($filter)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Strips and simplifies data from the registrar to be ready for placement
 * in the request classes tables.
 *
 * @param object $regdata
 * @param array $instinfo
 * @param array $defaults
 * @return object
 */
function prep_registrar_entry($regdata, $instinfo, $defaults=array()) {
    global $OUTPUT;
    $term = $regdata['term'];
    $srs = $regdata['srs'];

    // Generate a request array.
    $req = array();
    $req['term']        = $term;
    $req['srs']         = $srs;
    $req['department']  = $regdata['subj_area'];
    $req['course']      = get_course_from_reginfo($regdata);
    $req['enrolstat']   = $regdata['enrolstat'];

    // Get type of course, ugrad/grad/tut so that we can filter course builds.
    $req['type']        = get_class_type($regdata);

    // Get the url of the course to check if it has already been built
    // on another server.
    if (!empty($regdata['url']) && $OUTPUT->get_environment() == 'prod' && (!array_key_exists('crs_desc', $regdata))) {
        $req['existselsewhere'] = $regdata['url'];
    }

    $instarr = array();
    if (!isset($regdata['instructor'])) {
        // This is some redundant code...
        foreach ($instinfo as $inst) {
            if (is_object($inst)) {
                $inst = get_object_vars($inst);
            }

            $fn = $inst['first_name_person'];
            $ln = $inst['last_name_person'];

            if ($fn && $ln) {
                $u = new stdClass();

                $u->firstname = $fn;
                $u->lastname = $ln;
                // Moodle 2.7 introduced new username fields.
                $u->alternatename = '';
                $u->firstnamephonetic = '';
                $u->lastnamephonetic = '';
                $u->middlename = '';

                $fullname = fullname($u);

                $instarr[$fullname] = $fullname;
            }
        }
    }
    $req['instructor'] = $instarr;

    if (empty($defaults)) {
        $defaults = get_requestor_defaults();
    }

    foreach ($defaults as $field => $defval) {
        if (!isset($req[$field])) {
            $req[$field] = $defval;
        }
    }

    return $req;
}

/**
 * Gets default settings for requests from the database.
 *
 * @return object
 */
function get_requestor_defaults() {
    // Determine some defaults.
    $defaults = array();

    $configs = get_config('tool_uclacourserequestor');

    $editables = request_get_editables();
    $translatetf = array('true' => 1, 'false' => 0);

    // These are options that are soft, defaults changed through UI.
    foreach ($editables as $ed) {
        $varname = $ed . '_default';
        $d = false;

        if (isset($configs->$varname)) {
            $d = $configs->$varname;
        }

        $defaults[$ed] = $d;
    }

    $defaults['action'] = UCLA_COURSE_TOBUILD;
    $defaults['timerequested'] = time();

    $defaults['id'] = null;
    $defaults['courseid'] = null;

    return $defaults;
}

/**
 * Returns the set of related courses to the host course.
 * @param array $host Array(
 *         'term' => term
 *         'srs' => srs
 *         (optional) 'setid' => setid
 *     )
 * @return array Array(
 *         request_key => Array(request)
 *         ...
 *     ) at least one of these will have the property of 'hostcourse' = 1
 */
function get_crosslist_set_for_host($host) {
    if (is_object($host)) {
        $host = get_object_vars($host);
    }

    if (empty($host['srs']) || empty($host['term'])) {
        return false;
    }

    // If it's already existing in our database, just use that.
    if (isset($host['setid'])) {
        return get_set($host['setid']);
    }

    // Non-existing set of courses.
    $h = 'hostcourse';
    $hostkey = make_idnumber($host);
    $set = array($hostkey => $host);

    // These are entries from the registrar, so they need to have their
    // crosslists checked.
    global $DB;
    $clists = get_crosslisted_courses($host['term'], $host['srs']);

    foreach ($clists as $clist) {
        $clkey = make_idnumber($clist);

        if (!empty($set[$clkey])) {
            $setter = $set[$clkey];
        } else {
            // This will get us just the single course we are looking for.
            $setter = get_request_info($clist['term'], $clist['srs']);
        }

        // CCLE-3870 - If the crosslist does not exist in our local DB, then do
        // not include it as a crosslist, even if it is in the registrar.
        // If the course is not even in our local DB, then include the
        // registrar's crosslistings.
        if ( !isset($setter['setid']) ||
                $DB->record_exists('ucla_request_classes', array('srs' => $host['srs'], 'setid' => $setter['setid'])) ) {
            $set[$clkey] = $setter;
        }
    }

    $set = set_host_calculate($hostkey, $set);
    return $set;
}

/**
 * Calculates and sets the proper host numbers.
 *
 * @param string $orighost
 * @param object $set
 * @return object
 */
function set_host_calculate($orighost, $set) {
    $h = 'hostcourse';
    $hostexists = false;

    foreach ($set as $key => $request) {
        if (!isset($request[$h])) {
            $set[$key][$h] = 0;
            continue;
        }

        if (!$hostexists && $request[$h]) {
            $hostexists = $key;
        }
    }

    if (!$hostexists) {
        $set[$orighost][$h] = 1;
    } else if ($hostexists != $orighost) {
        $set[$orighost][$h] = 2;
    }

    return $set;
}

/**
 * Returns the greatest host of the course.
 *
 * @param object $set
 * @return string
 */
function set_find_host_key($set) {
    $hk = false;
    $h = 'hostcourse';

    foreach ($set as $k => $c) {
        if (!isset($c[$h])) {

            debugging('no hostcourse: ' . var_dump($set));
            return false;
        }

        if (!$hk || $c[$h] > $set[$hk][$h]) {
            $hk = $k;
        }
    }

    return $hk;
}

/**
 * Convenience function to write the visual character summary for a
 * particular course request.
 * @param object $regdata with fields coursenum sectnum
 * @return string
 */
function get_course_from_reginfo($regdata) {
    if (is_object($regdata)) {
        $regdata = get_object_vars($regdata);
    }

    return $regdata['coursenum'] . '-' . $regdata['sectnum'];
}

/**
 * Returns class type for course requestor classes.
 *
 * @param array $regdata    Entry from Registrar SP cis_coursegetall.
 * @return string   Undergraduate (ugrad), graduate (grad), or tutorial (tut)
 *                  class type.
 */
function get_class_type($regdata) {
    $type = '';

    // If fields aren't set, entry is a cross-listed course.
    if (!isset($regdata['activitytype']) || !isset($regdata['catlg_no'])) {
        return $type;
    }

    if ($regdata['activitytype'] == 'TUT') {
        $type = 'tut';
    } else {
        $coursenum = get_course_num($regdata['catlg_no']);
        $type = $coursenum < 200 ? 'ugrad' : 'grad';
    }
    return $type;
}

/**
 * Returns course number from course that possibly contains section number
 * and/or leading letter.
 *
 * @param string $coursenum     From ucla_reg_classinfo table.
 * @return int
 */
function get_course_num($coursenum) {
    return (int)preg_replace('/[^0-9]/', '', $coursenum);
}

/**
 * Takes a set of sets, and returns a flat requests list, with each
 * request maintaing its own crosslists.
 * @param object $requestinfos
 * @param object $context
 * @return array
 */
function prepare_requests_for_display($requestinfos, $context) {
    // Here, we finally turn our setid-indexed flat array into
    // the crosslist heirarchy.
    $c = 'crosslists';

    $displayrows = array();
    $errorrows = array();

    $nourlupdatehide = get_config('tool_uclacourserequestor', 'nourlupdate_hide');

    foreach ($requestinfos as $setid => $set) {
        $displaykey = set_find_host_key($set);

        $displayrow = $set[$displaykey];

        // Hide MyUCLA url.
        if (!empty($nourlupdatehide)) {
            unset($displayrow['nourlupdate']);
        }

        // Add crosslists.
        $displayrow[$c] = array();

        foreach ($set as $key => $request) {
            if ($key == $displaykey) {
                continue;
            }

            $displayrow[$c][$key] = $request;
        }

        // Deal with fields that are displayed but not in the request
        // tables themselves.
        if ($context == UCLA_REQUESTOR_FETCH) {
            $k = 'build';
            // Hack, perhaps find a better place for this...
            if (isset($displayrow[UCLA_REQUESTOR_ERROR]
                    [UCLA_REQUESTOR_CANCELLED])) {
                $default = false;
            } else {
                $default = true;
            }
        } else {
            $k = 'delete';
            $default = false;
        }

        if (!isset($displayrow[$k])) {
            $displayrow[$k] = $default;
        }

        // Make fields pretty.
        $prepped = prep_request_entry($displayrow);

        if (!empty($prepped)) {
            $displayrows[$displaykey] = $prepped;
        }
    }

    return remove_empty_fields($displayrows);
}

/**
 * Removes fields with no data for all rows.
 *
 * @param array $table
 * @return array
 */
function remove_empty_fields($table) {
    $removes = array();
    foreach ($table as $k => $r) {
        foreach ($r as $f => $d) {
            if (isset($removes[$f]) && $removes[$f] === false) {
                continue;
            }

            if (empty($d)) {
                $removes[$f] = true;
            } else {
                $removes[$f] = false;
            }
        }
    }

    foreach ($table as $k => $r) {
        $newone = array();
        foreach ($removes as $f => $t) {
            if ($t) {
                unset($r[$f]);
            }
        }

        $table[$k] = $r;
    }

    return $table;
}

/**
 * Quick function that doesn't really need to be a function,
 * but it parses the fields from a previously displayed
 * requestor contents tables.
 *
 * @param string $key
 * @param string $value
 * @return boolean
 */
function request_parse_input($key, $value) {
    $vals = array();
    preg_match('/^([new_0-9]*)-(.*)$/', $key, $vals);

    if (is_array($value)) {
        foreach ($value as $index => $content) {
            $value[$index] = trim($value[$index]);
        }
    } else {
        $value = trim($value);
    }

    $x = 1;
    if ($vals && count($vals) >= 2) {
        $set = $vals[$x++];
        $var = $vals[$x++];

        return array($set, $var, $value);
    }

    return false;
}

/**
 * Checks if a request's changes should be ignored.
 *
 * @param object $request
 * @return boolean
 **/
function request_ignored($request) {
    $b = 'build';
    $d = 'delete';

    $ignored = false;
    if (isset($request[$b])) {
        $ignored = ($request[$b] == 0);
    } else if (isset($request[$d])) {
        $ignored = ($request[$d] != 0);
    }

    return $ignored;
}

/**
 * Convenience function to return an array of editables.
 * @return array of strings
 */
function request_get_editables() {
    if (get_config('tool_uclacourserequestor', 'nourlupdatehide')) {
        return array('mailinst', 'action', 'requestoremail');
    } else {
        return array('mailinst', 'nourlupdate', 'action', 'requestoremail');
    }
}

/**
 * This takes all the data for a request, and prepares it to be displayed
 * as text to a user, including all errors that need to be included. This entire
 * function and its functionality could use some good organizing.
 *
 * @param object $requestinfo
 * @return object
 */
function prep_request_entry($requestinfo) {
    global $PAGE;

    $errs = UCLA_REQUESTOR_ERROR;
    $wars = UCLA_REQUESTOR_WARNING;
    $alert = UCLA_REQUESTOR_PASTTERM_ALERT;
    $worstnote = null;

    // Shortcut/optimization.
    $br = html_writer::empty_tag('br');

    $rucr = 'tool_uclacourserequestor';

    // This is the returned display-ready row.
    $formatted = array();

    // Will be used to identify changes for sets.
    $key = $requestinfo['setid'];

    // Check if course is from a past term.
    $ispastterm = true;
    $activeterms = get_active_terms();
    foreach ($activeterms as $activeterm) {
        if ($requestinfo['term'] == $activeterm) {
            $ispastterm = false;
        }
    }

    // If course is from a past term, set past term alert.
    // This alert will be overwritten by requestor warnings and errors, if any.
    if ($ispastterm) {
        $worstnote = $alert;
    }

    // Add build/delete button.
    $actiondefault = null;
    $addedtext = '';
    $e = UCLA_REQUESTOR_URLEXISTS;
    if (isset($requestinfo[$wars][$e])) {
        $worstnote = $wars;
        $addedtext = $br . get_string($e, $rucr, $requestinfo[$wars][$e]);
        // This hidden field will who us that this request has already
        // been viewed at least once.
        $addedtext .= html_writer::tag('input',
            '', array(
                'value' => 1,
                'name' => "$key-" . request_warning_checked_key($requestinfo),
                'type' => 'hidden'
            ));
    }

    // Handle the action drop down.
    $tr = 'action';
    $actionval = $requestinfo[$tr];
    $inputname = "$key-$tr";

    $fail = UCLA_COURSE_FAILED;
    $buil = UCLA_COURSE_TOBUILD;
    if ($actionval == $fail) {
        $options = array(
            $buil => requestor_statuses_translate($buil),
            $fail => requestor_statuses_translate($fail)
        );

        $trstr = html_writer::select($options, $inputname, $fail);
    } else {
        // No choices if it does not involving changing from
        // 'failed' to 'build'.
        $trstr = html_writer::tag('span', requestor_statuses_translate($actionval),
                array('class' => $actionval))
            . html_writer::empty_tag('input', array(
                'name' => $inputname,
                'type' => 'hidden',
                'value' => $actionval
            ));
    }

    // Finished with 'action'.
    $formatted[$tr] = $trstr;
    unset($requestinfo[$tr]);

    // If there is any relevance to changing the values, we're going
    // to let the user edit the row.
    $editable = false;
    if ($actionval != UCLA_COURSE_BUILT) {
        $editable = true;
    }

    // Request time...
    $timestr = '';
    $f = 'timerequested';
    $dds = 'Y-m-d g:i A';
    if (!empty($requestinfo[$f])) {
        $timestr = date($dds, $requestinfo[$f]);
    } else {
        $timestr = date($dds);
    }

    // Finished with timerequested.
    $formatted[$f] = $timestr;
    unset($requestinfo[$f]);

    // Deal with id field.
    $e = UCLA_REQUESTOR_EXIST;
    // If a class has already been built and is cancelled, display the already
    // sent to be built error message.
    if (isset($requestinfo[$errs][UCLA_REQUESTOR_CANCELLED])) {
        $e = UCLA_REQUESTOR_CANCELLED;
    }
    if (!empty($requestinfo[$errs][UCLA_REQUESTOR_EXIST])) {
        $e = UCLA_REQUESTOR_EXIST;
    }
    $f = 'id';
    $idstr = '';

    // Will disable building of this course
    // when course is already built.
    $buildoptions = array();

    $idstr = '';
    if (!empty($requestinfo[$errs][$e])) {
        $worstnote = $errs;

        // Can't use an MForm due to disability to use nested form,
        // so cannot be clever, unless decide to overwrite some PEAR
        // Libraries... which is not desired.
        if ($e == UCLA_REQUESTOR_EXIST) {
            $gotosinglesrshtml = html_writer::link(new moodle_url(
                $PAGE->url, array('srs' => $requestinfo['srs'],
                    'term' => $requestinfo['term'])
                ), get_string('viewrequest', $rucr));
            $idstr = get_string($e, $rucr) . html_writer::empty_tag('br')
            . $gotosinglesrshtml;
        } else {
            $idstr = get_string($e, $rucr) . html_writer::empty_tag('br');
        }
        $editable = false;

    } else {
        $idstr .= $requestinfo[$f];
    }

    $formatted[$f] = $idstr;
    unset($requestinfo[$f]);

    // Can be 'requestoremail or 'contact'.
    $f = 'requestoremail';
    $reval = '';
    if ($actionval == UCLA_COURSE_BUILT) {
        // Append '' to prevent a checkbox from appearing.
        $reval = $requestinfo[$f] . '';
        unset($requestinfo[$f]);
    } else {
        if (empty($requestinfo[$f])) {
            $requestinfo[$f] = '';
        }

        $reqprops = array(
                'name' => "$key-$f",
                'type' => 'text',
                'value' => $requestinfo[$f]
            );

        if (!$editable) {
            $reqprops['disabled'] = true;
        }

        $reval = html_writer::empty_tag('input', $reqprops);
    }

    $formatted[$f] = $reval;
    unset($requestinfo[$f]);

    // Only 'courseid'.
    $f = 'courseid';
    $fstr = '';
    if (!empty($requestinfo[$f])) {
        $courseid = $requestinfo[$f];
        $fstr = html_writer::link(new moodle_url(
                '/course/view.php',
                array('id' => $courseid
            )),
            $courseid,
            array('target' => '_blank')
        );
    }

    $formatted[$f] = $fstr;
    unset($requestinfo[$f]);
    // Finished with courseid.

    // Handle other checkboxes.
    $editables = request_get_editables();

    $sharedattr = array();
    if (!$editable) {
        $sharedattr['disabled'] = true;
    }

    foreach ($editables as $editme) {
        if (isset($formatted[$editme])) {
            continue;
        }

        // Add class to "Email instructor" column checkbox.
        if ($editme == 'mailinst') {
            $sharedattr['class'] = $editme;
        }

        if ($actionval == UCLA_COURSE_BUILT) {
            $requestinfo[$editme] = null;
            continue;
        }

        // The defaults should've been handled these a long time ago.
        if (!isset($requestinfo[$editme])) {
            $oldval = false;
        } else {
            $oldval = $requestinfo[$editme];
        }

        $formatted[$editme] = html_writer::checkbox("$key-$editme",
            '1', $oldval, '', $sharedattr);

        unset($requestinfo[$editme]);
    }

    // Handle Crosslists.
    $f = 'crosslists';
    $ff = "$key-crosslists[]";
    $clinputattr = array(
        'type' => 'text',
        'name' => "$ff"
    );

    $cleditable = $editable || $actionval == UCLA_COURSE_BUILT;

    if (!$cleditable) {
        $clinputattr['disabled'] = true;
    }

    $ocls = array();

    // Add self to crosslists.
    $riclstr = html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'name' => $ff,
        'value' => $requestinfo['srs']
    ));

    if (!empty($requestinfo[$f])) {
        foreach ($requestinfo[$f] as $clkey => $ocl) {
            $clsrs = $ocl['srs'];
            $moreinfo = requestor_dept_course($ocl);
            if (!empty($moreinfo)) {
                $moreinfo = '(' . $moreinfo . ')';
            } else {
                $moreinfo = '';
            }

            // Perhaps refactor this code later?
            if (!empty($ocl[$errs])) {
                // Save this for later.
                $worstnote = $errs;

                $errstr = '';
                foreach ($ocl[$errs] as $error => $true) {
                    if ($error == UCLA_REQUESTOR_EXIST || $error == UCLA_REQUESTOR_CANCELLED) {
                        continue;
                    }

                    $errstr .= get_string($error, $rucr);
                    unset($ocl[$errs][$error]);
                }

                // There was an error, display editable field and error msg.
                $clinputattr['value'] = $clsrs;

                $clinput = $errstr . $br . html_writer::empty_tag(
                        'input',
                        $clinputattr
                    ) . "$br $moreinfo";

                // Secret to keep crosslists alive.
                if (!$editable) {
                    $clinput .= html_writer::empty_tag(
                        'input',
                        array(
                            'type' => 'hidden',
                            'value' => $clsrs,
                            'name' => $ff
                        )
                    );
                }
            } else {
                $warstr = '';
                // If there is a warning, uncheck box by default.
                $defaultclbuild = true;

                $clinput = '';

                if (!empty($ocl[$wars])) {
                    if ($worstnote == null) {
                        $worstnote = $wars;
                    }

                    $defaultclbuild = false;

                    foreach ($ocl[$wars] as $warning => $true) {
                        $warstr .= $br . get_string($warning, $rucr);
                    }

                    $clinput = html_writer::tag('input',
                        '', array(
                            'name' => "$key-"
                                . request_warning_checked_key($ocl),
                            'value' => 1,
                            'type' => 'hidden'
                        ));
                }

                // Display check box.
                $clinput .= html_writer::checkbox(
                    $ff,
                    $clsrs,
                    $defaultclbuild,
                    "$clkey $moreinfo",
                    $clinputattr
                ) . html_writer::tag(
                        'span',
                        $warstr
                    );
            }

            $riclstr .= $clinput . $br;

            // Instructors merge up into host course.
            foreach ($ocl['instructor'] as $k => $v) {
                $requestinfo['instructor'][$k] = $v;
            }
        }
    }

    // Add a new crosslist dialog.
    if ($cleditable) {
        unset($clinputattr['value']);
        $riclstr .= html_writer::empty_tag('input', $clinputattr);
        $riclstr .= html_writer::empty_tag(
            'input',
            array(
                'type' => 'submit',
                'name' => "$key-add-crosslist",
                'value' => get_string('addmorecrosslist', $rucr)
            )
        );
    }

    $e = UCLA_REQUESTOR_BADCL;
    if (!empty($requestinfo[$errs][$e])) {
        $riclstr .= $br . get_string('hostandchild', $rucr);
        $worstnote = $errs;
        unset($requestinfo[$errs][$e]);
    }

    $formatted['crosslists'] = $riclstr;

    // Instructors.
    $instrstr = '';
    if (empty($requestinfo['instructor'])) {
        $instrstr = get_string('noinst', $rucr);
    } else {
        if (!is_array($requestinfo['instructor'])) {
            debugging('non-arr-inst');
        }
        $instrstr = implode(' / ', $requestinfo['instructor']);
    }

    $formatted['instructor'] = $instrstr;

    unset($requestinfo['instructor']);

    // Add delete/build (action) checkboxes.
    $maybeexists = array('delete', 'build');
    foreach ($maybeexists as $k) {
        // CCLE-3103 - When deleting a course add in a trigger to also
        // delete the course request and My0UCLA url,
        // preventing requests from being deleted in this UI. It should be
        // deleted by deleting the actual course.
        if ($k == 'delete' && !empty($formatted['courseid'])) {
            continue;
        }

        if (isset($requestinfo[$k])) {
            $actval = $requestinfo[$k];

            if (!$editable
                    && $actionval != UCLA_COURSE_BUILT
                    && $actionval != UCLA_COURSE_FAILED) {
                $actval = true;
            } else if (!$editable && $actionval == UCLA_COURSE_BUILT) {
                // If the course is built, uncheck checkbox.
                $actval = false;
            } else if (!$editable && $actionval == UCLA_COURSE_FAILED) {
                // If the course has an error, uncheck checkbox.
                $actval = false;
            }

            // Also disable if we have $addedtext.
            // This is the case when a course is marked as cancelled.
            if ($worstnote == $errs || !empty($addedtext)) {
                $buildoptions['disabled'] = true;
                $actval = false;
            }

            // CCLE-4984.
            // Allow the user to build the course even if the URL exists already and it is not cancelled.
            if (array_key_exists('existselsewhere', $requestinfo) && !isset($requestinfo[$errs][UCLA_REQUESTOR_CANCELLED])) {
                unset($buildoptions['disabled']);
            }

            // Add class to "To be built" column checkbox.
            if (isset($requestinfo['type'])) {
                $buildoptions['class'] = $requestinfo['type'];
            }

            $formatted[$k] = html_writer::checkbox("$key-$k", '1',
                $actval, $addedtext, $buildoptions);
        }
    }

    // Include all the non-changable but displayed data.
    foreach ($requestinfo as $k => $v) {
        if (!isset($formatted[$k])) {
            $formatted[$k] = $v;
        }
    }

    // Just reorder things.
    $ordfor = array();
    $notused = array();
    $ordered = array(
        'id', 'courseid',
        'term', 'srs',
        'department', 'course', 'instructor',
        'crosslists',
        'timerequested',
        'requestoremail', 'action',
        'mailinst', 'hidden', 'nourlupdate',
        'delete', 'build', 'type'
    );

    foreach ($ordered as $field) {
        if (!isset($formatted[$field])) {
            $ordfor[$field] = '';
            $notused[] = $field;
        } else {
            $ordfor[$field] = $formatted[$field];
        }
    }

    // Add error/warn in here...
    if ($worstnote != null) {
        $ordfor['errclass'] = $worstnote;
    }

    return $ordfor;
}

/**
 * Designates a warning-viewed handler for warnings.
 * @param array $request The request should be an array with keys
 *     'term', 'srs'
 * @return string The string to use in the name field of the input
 *     that represents that a particular warning has been viewed.
 *     Currently used by ucla_courserequests->commit()
 */
function request_warning_checked_key($request) {
    return make_idnumber($request) . '-' . UCLA_REQUEST_WARNING_CHECKED;
}

/**
 * Convenience function returns the concatenation of the subject area
 * and the course (course num and sect num).
 * @param object $request
 * @return string
 */
function requestor_dept_course($request) {
    $co = 'course';
    $de = 'department';

    $moreinfo = '';
    if (!empty($request[$co]) && !empty($request[$de])) {
        $moreinfo = $request[$de] . ' ' . $request[$co];
    }

    return $moreinfo;
}

/**
 * Takes a status/action and translates it to a human readable form.
 *
 * @param string $status
 * @return object
 */
function requestor_statuses_translate($status) {
    $rucr = 'tool_uclacourserequestor';

    if (get_string_manager()->string_exists($status, $rucr)) {
        $posstext = get_string($status, $rucr);
    } else {
        $posstext = ucwords($status);
    }

    return $posstext;
}

/**
 * Calculates the available filters for the drop down menu for
 * viewing existing request entries.
 * @return object
 */
function get_requestor_view_fields() {
    global $DB;

    $prefields = array('term', 'department', 'action');
    $prefieldstr = trim(implode(', ', $prefields));
    $rsid = 'CONCAT(' . $prefieldstr . ')';
    if (!$prefieldstr) {
        $prefieldstr = $rsid;
    } else {
        $prefieldstr = $rsid . ', ' . $prefieldstr;
    }
    $builtcategories = $DB->get_records('ucla_request_classes', null,
        'department', 'DISTINCT ' . $prefieldstr);
    $prefieldsdata = array();
    foreach ($builtcategories as $builts) {
        foreach ($prefields as $prefield) {
            $varname = $prefield;

            if (!isset($prefieldsdata[$varname])) {
                $prefieldsdata[$varname] = array();
            }

            $prefieldsdata[$varname][$builts->$prefield] = $builts->$prefield;
        }
    }

    $prefieldsdata['term'] = terms_arr_sort($prefieldsdata['term'], true);
    return $prefieldsdata;
}

/**
 * Fetches courses for the subject area. Takes about 0.015 seconds per entry.
 * @param string $term
 * @param string $subjarea
 */
function get_courses_for_subj_area($term, $subjarea) {
    $result = registrar_query::run_registrar_query('cis_coursegetall',
        array('term' => $term, 'subjarea' => $subjarea));

    return $result;
}

/**
 * Fetches course info using course term and srs. Takes about 0.025 seconds.
 * @param string $term
 * @param string $srs
 * @return array
 */
function get_course_info_from_registrar($term, $srs) {
    $result = registrar_query::run_registrar_query('ccle_getclasses',
        array('term' => $term, 'srs' => $srs));

    return $result;
}

/**
 * Fetches instructor info using course term and srs. Takes about 0.015 seconds.
 * @param string $term
 * @param string $srs
 * @return array
 */
function get_instructor_info_from_registrar($term, $srs) {
    $result = registrar_query::run_registrar_query('ccle_courseinstructorsget',
        array('term' => $term, 'srs' => $srs));
    return $result;
}

/**
 * Queries the registrar for the course and inserts it into ucla_reg_classinfo
 *
 * @param string $term
 * @param string $srs
 * @return boolean true if term/srs pair exists, false otherwise
 */
function crosslist_course_from_registrar($term, $srs) {
    global $DB;

    if ($DB->record_exists('ucla_reg_classinfo', array('term' => $term, 'srs' => $srs))) {
        return true;
    }

    $course = get_course_info_from_registrar($term, $srs);
    if ( !empty($course) && isset($course['term']) && isset($course['srs']) ) {
        $DB->insert_record('ucla_reg_classinfo', $course);
        return true;
    } else if (!empty($course) && isset($course[0])) {
        $DB->insert_record('ucla_reg_classinfo', $course[0]);
        return true;
    }

    return false;
}

/**
 * Update MyUCLA urls that link to ccle course pages.
 *
 * @param string $term  Course term.
 * @param string $srs   Course srs.
 * @param string $url   Course url for myucla to link to. An empty string or
 *                      null will clear the url at MyUCLA.
 */
function update_myucla_urls($term, $srs, $url) {
    $urlupdater = new myucla_urlupdater();
    $course = array('term' => $term, 'srs' => $srs);
    $updatecourse = array(
        make_idnumber($course) => array(
            'term' => $term,
            'srs' => $srs,
            'url' => $url
        )
    );

    $urlupdater->send_MyUCLA_urls($updatecourse, true);
}