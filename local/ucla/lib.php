<?php
/**
 *  UCLA Global functions.
 **/

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot.'/lib/accesslib.php');

// Contains our external-link generator thing
require_once($CFG->dirroot.'/local/ucla/outputcomponents.php');

// Contains public/private lib
require_once($CFG->dirroot.'/local/publicprivate/lib/course.class.php');

/**
 *  @deprecated
 *  This will attempt to access this file from the web.
 *  If that is properly set up, then all directories below this directory
 *  will be web-forbidden.
 **/
function ucla_verify_configuration_setup() {
    global $CFG;

    if (!function_exists('curl_init')) {
        throw new moodle_exception('curl_failure', 'local_ucla');
    }

    ini_set('display_errors', '1');
    ini_set('error_reporting', E_ALL);

    $ch = curl_init();

    $self = $CFG->wwwroot . '/local/ucla/version.php';
    $address = $self;

    // Attempt to get at a file that should not be web-visible
    curl_setopt($ch, CURLOPT_URL, $address);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $res = curl_exec($ch);

    $returner = false;
    if (!$res) {
        throw new moodle_exception(curl_error($ch));
    } else {
        if (preg_match('/HTTP\/[0-9]*\.[0-9]*\s*403/', $res)) {
            $returner = true;
        }
    }

    curl_close($ch);

    return $returner;
}

/**
 *  Function to sort an array of strings in alphabetical order
 **/
function array_alphasort(&$array, $sortkey) {
    usort($array, function($a, $b) use ($sortkey) {
        if (!empty($a) && !empty($b)) {
            return strcmp(strtolower($a->$sortkey), strtolower($b->$sortkey));
        }
    });
}

/**
 *  Convenience function to include db-helpers.
 **/
function ucla_require_db_helper() {
    global $CFG;

    require_once($CFG->dirroot
        . '/local/ucla/dbhelpers.php');
}

/** 
 *  Function to include all the Registrar connection functionality.
 *  This function MUST NOT do anything other than load libraries.
 *  
 **/
function ucla_require_registrar() {
    global $CFG;

    require_once($CFG->dirroot
        . '/local/ucla/registrar/registrar_stored_procedure.base.php');
    require_once($CFG->dirroot
        . '/local/ucla/registrar/registrar_tester.php');
}

/**
 *  Responder for results from ccle_courseinstructorsget to see if
 *  the user is a dummy user.
 *  @param $ucla_id String of the UID number
 *  @return boolean 
 **/
function is_dummy_ucla_user($ucla_id) {
    // dummy THE STAFF
    if ($ucla_id == '100399990') {
        return true;
    }

    // dummy TA
    if ($ucla_id == '200399999') {
        return true;
    }

    return false;
}

/**
 *  Checks if an enrol-stat code means a course is cancelled.
 **/
function enrolstat_is_cancelled($enrolstat) {
    return strtolower($enrolstat) == 'x';
}

/** 
 *  Checks if a course should be considered cancelled.
 *  Note that this does require an enrolstat, which means that
 *      the data needs to come from ucla_reg_classinfo.
 *  Note that misformed data will throw an exception.
 *  @param  $courseset  Array( Object->enrolstat, ... )
 *  @return boolean     true = cancelled
 **/
function is_course_cancelled($courseset) {
    // No information, assume not-cancellable
    if (empty($courseset)) {
        return false;
    }

    $cancelled = true;
    foreach ($courseset as $course) {
        if (empty($course->enrolstat)) {
            throw new coding_exception('missing enrolstat');
        } else if (!enrolstat_is_cancelled($course->enrolstat)) {
            $cancelled = false;
        }
    }

    return $cancelled;
}

/**
 *  Builds the URL for the Registrar's finals information page.
 **/
function build_registrar_finals_url($courseinfo) {
    if (!empty($courseinfo->term)
            && ucla_validator('term', $courseinfo->term)) {
        $term = $courseinfo->term;
    } else {
        return false;
    }
    if (!empty($courseinfo->srs)
            && ucla_validator('srs', $courseinfo->srs)) {
        $srs = $courseinfo->srs;
    } else {
        return false;
    }
    if (!empty($courseinfo->subj_area)) {
        $subjarea = urlencode($courseinfo->subj_area);
    } else {
        return false;
    }
    if (!empty($courseinfo->crsidx)) {
        $crsidx = urlencode($courseinfo->crsidx);
    } else {
        return false;
    }
    if (!empty($courseinfo->classidx)) {
        $classidx = urlencode($courseinfo->classidx);
    } else {
        return false;
    }

    $regurl = get_config('local_ucla', 'registrarurl');
    $params = array(
        'term_cd' => $term,
        'subj_area_cd' => $subjarea,
        'crs_catlg_no' => $crsidx,
        'class_id' => $srs,
        'class_no' => $classidx
    );
    foreach ($params as $param => $value) {
        $paramstrs[] = $param . '=' . $value;
    }

    return $regurl . '/ro/public/soc/Results/ClassDetail?' . implode('&', $paramstrs);
}

/**
 *  Translate the single-character enrollment code to a word.
 *  There is an assumption here that case does not matter for these
 *  enrollment codes.
 **/
function enrolstat_string($enrolstat) {
    $sm = get_string_manager();
    $ess = 'enrolstat_' . strtolower($enrolstat);
    $rs = '';
    if ($sm->string_exists($ess, 'local_ucla')) {
        $rs = get_string($ess, 'local_ucla');
    } else {
        $rs = get_string('enrolstat_unknown', 'local_ucla');
    }

    return $rs;
}

/**
 *  Creates a display-ready string for a course.
 *  Slightly similar to shortname...
 *  @param $courseinfo Array with fields
 *      subj_area - the subject area
 *      coursenum - the course number
 *      sectnum   - the number of the section
 *      Or object from ucla_get_reg_classinfo
 *  @param $displayone boolean True to display the sectnum of 1
 **/
function ucla_make_course_title($courseinfo, $displayone=true) {
    if (is_object($courseinfo)) {
        $courseinfo = get_object_vars($courseinfo);
    }
 
    $sectnum = '-' . $courseinfo['sectnum'];
    if (!$displayone && $courseinfo['sectnum'] == 1) {
        $sectnum = '';
    }

    return $courseinfo['subj_area'] . ' ' . trim($courseinfo['coursenum'])
        . $sectnum;
}

/** 
 *  Serialize/hashes courses.
 **/
function make_idnumber($courseinfo) {
    if (is_object($courseinfo)) {
        $courseinfo = get_object_vars($courseinfo);
    }

    if (empty($courseinfo['term']) || empty($courseinfo['srs'])) {
        debugging('No key from object: ' . print_r($courseinfo, true));
        return false;
    }

    return $courseinfo['term'] . '-' . $courseinfo['srs'];
}

/**
 *  Fetch each corresponding term and srs for a particular courseid.
 * 
 *  @param int $courseid
 */
function ucla_map_courseid_to_termsrses($courseid) {
    global $DB;

    // Check to see if we queried for this particular mapping before.
    $cache = cache::make('local_ucla', 'urcmappings');
    $cachekey = 'ucla_map_courseid_to_termsrses:'.$courseid;
    if ($termsrses = $cache->get($cachekey)) {
        return $termsrses;
    }
 
    $termsrses = $DB->get_records('ucla_request_classes',
            array('courseid' => $courseid, 'action' => 'built'));

    $cache->set($cachekey, $termsrses);
    return $termsrses;
}

/**
 *  Fetch the corresponding courseid for a particular term and srs.
 **/
function ucla_map_termsrs_to_courseid($term, $srs) {
    global $DB;

    $dbo = $DB->get_record_sql('
        SELECT courseid
        FROM {ucla_request_classes} rc
        INNER JOIN {course} co ON rc.courseid = co.id
        WHERE rc.term = :term AND rc.srs = :srs
    ', array('term' => $term, 'srs' => $srs), '', 'courseid');

    if (isset($dbo->courseid)) {
        return $dbo->courseid;
    }

    return false;
}

/**
 *  Fetch the corresponding information from the registrar for a 
 *  particular term and srs.
 **/
function ucla_get_reg_classinfo($term, $srs) {
    global $DB;

    $records = $DB->get_record('ucla_reg_classinfo',
            array('term' => $term, 'srs' => $srs));

    // CCLE-5854 - Override registrar url with new website.
    $page = get_config('local_ucla', 'registrarurl');
    $page .= '/ro/public/soc/Results?t=' . $records->term;
    $page .= '&sBy=classidnumber&id=' . $records->srs;
    $page .= '&btnIsInIndex=btn_inIndex';
    $records->url = $page;

    return $records;
}

/**
 * Convenience function to get registrar information for classes.
 *
 * @param int $courseid
 * @return array            Array of entries from ucla_reg_classinfo table with
 *                          hostcourse value added.
 */
function ucla_get_course_info($courseid) {
    $reginfos = array();
    $termsrses = ucla_map_courseid_to_termsrses($courseid);
    foreach ($termsrses as $termsrs) {
        $reginfoobj = ucla_get_reg_classinfo($termsrs->term, $termsrs->srs);
        if (empty($reginfoobj)) {
            continue;
        }
        $reginfoobj->hostcourse = $termsrs->hostcourse;
        $reginfos[] = $reginfoobj;
    }

    return $reginfos;
}

/**
 *  Convenience function. 
 *  @param  $requests   
 *      Array of Objects with properties term, srs, and $indexby
 *      if the requests do not have $indexby, then it will not be indexed
 *  @param  $indexby    What you want as the primary index
 **/
function index_ucla_course_requests($requests, $indexby='setid') {
    $reindexed = array();

    if (!empty($requests)) {
        foreach ($requests as $record) {
            if (isset($record->$indexby)) {
                $reindexed[$record->$indexby][make_idnumber($record)] = $record;
            }
        }
    }

    return $reindexed;
}

/**
 *  Gets the course sets for a particular term.
 *  @param  $terms  Array of terms that we want to filter by.
 *  @param  $filterwithoutcourses   boolean do not display requests without
 *      courses associated with them.
 *  @return
 *      Array (
 *          {course}.`id` => ucla_get_course_info()
 *      )
 **/
function ucla_get_courses_by_terms($terms) {
    global $DB;
 
    list($sqlin, $params) = $DB->get_in_or_equal($terms);
    $where = 'term ' . $sqlin;

    $records = $DB->get_records_select('ucla_request_classes',
        $where, $params);

    return index_ucla_course_requests($records, 'courseid');
}
 
/**
 *  Based on what is set by the enrolment plugin, match user info
 *  provided by the Registrar to the local database.
 **/
function ucla_registrar_user_to_moodle_user($reginfo,
                                            $cachedconfigs=null) {
    global $CFG, $DB;

    if ($cachedconfigs) {
        $configs = $cachedconfigs;
    } else {
        $configs = get_config('enrol_database');
    }

    $userfield         = strtolower($configs->remoteuserfield);

    $localuserfield   = $configs->localuserfield;

    $sqlparams = array();
    $sqlbuilder = array();

    $usersearch = array();

    if ($localuserfield === 'username') {
        $usersearch['mnethostid'] = $CFG->mnet_localhost_id;
        $usersearch['deleted'] = 0;
    }

    foreach ($usersearch as $f => $v) {
        $sqlbuilder[] = "$f = ?";
        $sqlparams[] = $v;
    }

    $mapping = false;

    if (!empty($reginfo[$userfield])) {
        $mapping = $reginfo[$userfield];
    }
 
    $searchstr = "$localuserfield = ?";
    $sqlparams[] = $mapping;

    $sqlbuilder[] = $searchstr;
    $usersql = implode(' AND ', $sqlbuilder);

    return $DB->get_record_select('user', $usersql, $sqlparams,
        "*", IGNORE_MULTIPLE);
}

/**
 *  Returns a pretty looking term in the format of 12S => Spring 2012.
 * 
 * @param string term
 * @param char session      If session is passed, then, assuming the term is 
 *                          summer, will return 121, A => Summer 2012 - Session A
 **/
function ucla_term_to_text($term, $session=null) {
    $term_letter = strtolower(substr($term, -1, 1));
    $termtext = '';
    if ($term_letter == "f") {
        $termtext = "Fall";
    } else if ($term_letter == "w") {
        // W -> Winter
        $termtext = "Winter";
    } else if ($term_letter == "s") {
        // S -> Spring
        $termtext = "Spring";
    } else if ($term_letter == "1") {
        // 1 -> Summer
        $termtext = "Summer";
    } else {
        debugging("Invalid term letter: ".$term_letter);
        return null;
    }

    $years = substr($term, 0, 2);
    $termtext .= " 20$years";
 
    if ($term_letter == "1" && !empty($session)) {
        $termtext .= " - Session " . strtoupper($session);
    }
 
    return $termtext;
}

function is_summer_term($term) {
    return ucla_validator('term', $term) && substr($term, -1, 1) == '1';
}

/**
 * Properly format a given string so it is suitable to be used as a name. Name 
 * might include the following characters ' or - or a space. Need to properly 
 * uppercase the first letter and lowercase the rest.
 *
 * NOTE: 
 *  - Special case added if the last name starts with "MC". Assuming that
 * next character should be uppercase.
 *  - Special case: If a name as 's, like Women's studies, then the S shouldn't
 * be capitalized. 
 *
 * @param string $name
 * @param boolean $handleconjunctions   If true, will lower case any
 *                                      conjunctions, like "and" and "a". Used
 *                                      when formatting departments or subject
 *                                      areas.
 * @return string                       Name in proper format.
 */
function ucla_format_name($name=null, $handleconjunctions=false) {
    $name = ucfirst(core_text::strtolower(trim($name)));

    if (empty($name)) {
        return '';
    }

    /* The way to handle special cases in a person's name is to recurse on
     * the following cases:
     *  - If name has a space.
     *  - If name is multipart.
     *  - If name has a hypen.
     *  - If name has an aprostrophe.
     *  - If name starts with "MC".
     *  - If name has conjunctions, e.g. "and", "of", "the", "as", "a".
     *  - If name has initials.
     */

    // Has space?
    $namearray = explode(' ', $name);
    if (count($namearray) > 1) {
        foreach ($namearray as $key => $element) {
            $result = ucla_format_name($element, $handleconjunctions);
            if (!empty($result)) {
                $namearray[$key] = $result;
            } else {
                unset($namearray[$key]);   // Don't use element if it is blank.
            }
        }
        $name = implode(' ', $namearray);  // Combine elements back.

        // Handle European multipart names.
        // See https://public.wsu.edu/~brians/errors/multipart.html.
        $multipartnames = array('Della', 'Le', 'La', 'Du', 'Des', 'Del', 'De La',
                                'Van Der', 'De', 'Da', 'Di', 'Von', 'Van');
        $replacementcount = 0;
        foreach ($multipartnames as $multipartname) {
            $name = preg_replace("/^$multipartname /",
                    core_text::strtolower($multipartname) . ' ', $name, 1,
                    $replacementcount);
            if ($replacementcount > 0) {
                // Should only have 1 type of multipart name, exit early.
                break;
            }
        }
    }

    // Has hypen?
    $namearray = explode('-', $name);
    if (count($namearray) > 1) {
        foreach ($namearray as $key => $element) {
            $namearray[$key] = ucla_format_name($element, $handleconjunctions);
        }
        $name = implode('-', $namearray);  // Combine elements back.
    }

    // Has aprostrophe?
    $namearray = explode("'", $name);
    if (count($namearray) > 1) {
        foreach ($namearray as $key => $element) {
            /*
            * Special case: If a name as 's, like Women's studies, then the S
            * shouldn't be capitalized.
            */
            if (preg_match('/^[s]{1}\\s+.*/i', $element)) {
                // Found a single lowercase s with a space and maybe something
                // following, that means you found a possessive s, so make sure
                // it is lowercase and do not recuse.
                $element[0] = 's';
                $namearray[$key] = $element;
            } else {
                // Found a ' that is part of a name.
                $namearray[$key] = ucla_format_name($element, $handleconjunctions);
            }
        }
        $name = implode("'", $namearray);  // Combine elements back.
    }

    // Starts with MC (and is more than 2 characters)?
    if (core_text::strlen($name)>2 && (0 == strncasecmp($name, 'mc', 2))) {
        $name[2] = core_text::strtoupper($name[2]);    // Make 3rd character uppercase.
    }

    // If name has conjunctions, e.g. "and", "of", "the", "as", "a".
    if ($handleconjunctions &&
            in_array(core_text::strtolower($name), array('and', 'of', 'the', 'as', 'a'))) {
        $name = core_text::strtolower($name);
    }

    // If name has initials. Should be capital if exactly 1 letter is followed
    // by a period.
    $namearray = explode(".", $name);
    if (count($namearray) > 1) {
        foreach ($namearray as $key => $element) {
            if (core_text::strlen($element) == 1) {
                $namearray[$key] = core_text::strtoupper($element);
            }
        }
        $name = implode(".", $namearray);  // Combine elements back.
    }

    return $name;

}

/**
 * Given a registrar profcode and list of other roles a user has, returns what
 * Moodle role a user should have.
 * 
 * @param string|array $profcode    If array, then expects prof code(s) for
 *                                  given user in an array indexed by 'primary'
 *                                  and 'secondary'. If string then will assume
 *                                  profcode is for primary for backwards
 *                                  compatibility.
 * @param array $otherroles         Expects all prof code(s) for all roles for
 *                                  all course sections indexed by 'primary' and
 *                                  'secondary'. However, if those keys are not
 *                                  found, then will assume given array is for
 *                                  primary section for backwards compability.
 * @param string $subjectarea       Default "*SYSTEM*". What subject area we
 *                                  are assigning roles for.
 * @return int                      Role id for local Moodle system.
 */
function role_mapping($profcode, array $otherroles, $subjectarea="*SYSTEM*") {

    // Do backwards compability for those sections in the code that haven't
    // been modified to use the new role mapping parameters of primary/secondary
    // indexes either because it is too hard or dificult to convert.
    if (!is_array($profcode)) {
        $profcode = array('primary' => array($profcode));
    }
    if (!isset($otherroles['primary']) && !isset($otherroles['secondary'])) {
        $otherroles['primary'] = $otherroles;
    }

    // Logic to parse profcodes, and return pseudorole.
    $pseudorole = get_pseudorole($profcode, $otherroles);
 
    // Convert pseudorole to the appropiate role for given subject area.
    $moodleroleid = get_moodlerole($pseudorole, $subjectarea);

    return $moodleroleid;
}

/**
 * Given 
 * Refer to Jira: CCLE-2320 and https://ccle.ucla.edu/mod/page/view.php?id=82418
 * 
 * role InstSet     Pseudo Role
 * 01   any         editingteacher
 * 03	any	    supervising_instructor
 * 04   any         grader
 * 22	any	    student_instructor
 * 02 (in any section)	01 (in any section)       ta
 * 02 (primary)	03 (in any section)       ta_instructor
 * 02 (secondary) 02 (primary) or 03 (in any section) ta
 * 
 * @param array $profcode       Prof code(s) for given user in an array indexed
 *                              by 'primary' and 'secondary'.
 * @param array $other_roles    All prof code(s) for all roles for all course
 *                              sections indexed by 'primary' and 'secondary'.
 * 
 * @return string               Returns either: editingteacher, ta,
 *                              ta_instructor, supervising_instructor, or
 *                              student_instructor
 *                              Returns null if no pseudo role can be found
 */
function get_pseudorole(array $profcode, array $otherprofcodes) {
 
    // Shortcuts for 01 and 03.
    if (isset($profcode['primary']) && in_array('01', $profcode['primary']) ||
            isset($profcode['secondary']) && in_array('01', $profcode['secondary'])) {
        return 'editingteacher';
    }
    if (isset($profcode['primary']) && in_array('03', $profcode['primary']) ||
            isset($profcode['secondary']) && in_array('03', $profcode['secondary'])) {
        return 'supervising_instructor';
    }
 
    // Handling the complex 02 roles.
    if (isset($profcode['primary']) && in_array('02', $profcode['primary']) ||
            isset($profcode['secondary']) && in_array('02', $profcode['secondary'])) {

        // Anyone with 02 on a course with an 01 is a ta.
        // 02 (in any section)	01 (in any section)       ta
        if (isset($otherprofcodes['primary']) && in_array('01', $otherprofcodes['primary']) ||
                isset($otherprofcodes['secondary']) && in_array('01', $otherprofcodes['secondary'])) {
            return 'ta';
        }

        // If someone is an 02 in the primary section, and there is an 03, they
        // are a ta_instructor (assumes no 01, because of first condition).
        // 02 (primary)	03 (in any section)       ta_instructor
        if (isset($profcode['primary']) && in_array('02', $profcode['primary'])) {
            if (isset($otherprofcodes['primary']) && in_array('03', $otherprofcodes['primary']) ||
                    isset($otherprofcodes['secondary']) && in_array('03', $otherprofcodes['secondary'])) {
                return 'ta_instructor';
            }
        }
 
        // For all other 02 cases, default to ta.
        return 'ta';
    }

    // Handle role mapping of prof code 04.
    if (isset($profcode['primary']) && in_array('04', $profcode['primary']) ||
            isset($profcode['secondary']) && in_array('04', $profcode['secondary'])) {
        return 'grader';
    }

    // Handle role mapping of prof code 22.
    if (isset($profcode['primary']) && in_array('22', $profcode['primary']) ||
            isset($profcode['secondary']) && in_array('22', $profcode['secondary'])) {
        return 'student_instructor';
    }

    // No role to return.
    return null;
}

/**
 *  This is a function to return the pseudoroles for student enrolment
 *  code values.
 *  @return string - pseudorole, false - not enrolled
 **/
function get_student_pseudorole($studentcode) {
    $code = strtolower(trim($studentcode));
    $psrole = false;

    switch($code) {
        case 'w':   // waitlist
        case 'h':   // held (unex)
        case 'p':   // pending
            $psrole = 'waitlisted';
            break;
        case 'e':   // enrolled
        case 'a':   // approved (unex)
            $psrole = 'student';
            break;
        default:
            // This includes codes:
            // d = dropped
            // c = cancelled
            // If they do not have an explicitly declared role code,
            // then they are considered unenrolled
            $psrole = false;
    }

    return $psrole;
}

/**
 * @param string $type   
 *      Type can be 'term', 'srs', 'uid'
 * @param mixed $value   
 *      term: DDC (two digit number with C being either F, W, S, 1)
 *      SRS/UID: (9 digit number, can have leading zeroes)
 * @return boolean      true if the value matches the type, false otherwise.
 * @throws moodle_exception When the input type is invalid.
 */
function ucla_validator($type, $value) {
    $result = 0;
 
    switch($type) {
        case 'term':
            $result = preg_match('/^[0-9]{2}[FWS1]$/', $value);
            break;
        case 'srs':
        case 'uid':
            $result = preg_match('/^[0-9]{9}$/', $value);
            break;
        case 'academicyear':
            if (preg_match('/^20[0-9]{2}-20[0-9]{2}$/', $value) == 1 &&
                    substr($value, 2, 2) + 1 == substr($value, 7, 2)) {
                $result = 1;
            }
            break;
        default:
            throw new moodle_exception('invalid type', 'ucla_validator');
            break;
    }
 
    return $result == 1;
}

/**
 * Given a pseudorole (from get_pseudorole), returns what moodle role a user
 * should be assigned for a given department. First a look-up is done in the 
 * database for a given pseudorole and subject area. Then the function looks
 * at the role mapping config file. If the role mapping is present in that file
 * it will override any values from the database.
 * 
 * @throws moodle_exception         Throws moodle exception if no role mapping 
 *                                  is found
 * 
 * @param string $pseudorole
 * @param string $subjectarea       Default "*SYSTEM*".
 * 
 * @return int                      Moodle role id. 
 */
function get_moodlerole($pseudorole, $subjectarea='*SYSTEM*') {
    global $CFG, $DB;

    $subjectarea = trim($subjectarea);

    // Check to see if we queried for this particular mapping before.
    $cache = cache::make('local_ucla', 'rolemappings');
    $cachekey = $pseudorole.':'.$subjectarea;
    if ($moodleroleid = $cache->get($cachekey)) {
        return $moodleroleid;
    }

    require($CFG->dirroot . '/local/ucla/rolemappings.php');

    // if mapping exists in file, then don't care what values are in the db
    if (!empty($role[$pseudorole][$subjectarea])) {
        if ($moodlerole = $DB->get_record('role',
                array('shortname' => $role[$pseudorole][$subjectarea]))) {
            $cache->set($cachekey, $moodlerole->id);
            return $moodlerole->id;
        }
    }
 
    // didn't find role mapping in config file, check database
    if ($moodlerole = $DB->get_record('ucla_rolemapping',
            array(
                'pseudo_role' => $pseudorole,
                'subject_area' => $subjectarea
            ))) {
        $cache->set($cachekey, $moodlerole->moodle_roleid);
        return $moodlerole->moodle_roleid;
    }
 
    // if no role was found, then use *SYSTEM* default
    // (should be set in config)
    if (!empty($role[$pseudorole]['*SYSTEM*'])) {
        if ($moodlerole = $DB->get_record('role',
                array('shortname' => $role[$pseudorole]['*SYSTEM*']))) {
            $cache->set($cachekey, $moodlerole->id);
            return $moodlerole->id;
        } else {
            debugging('pseudorole mapping found, but local role not found');
        }
    }
 
    // oh no... didn't find proper role mapping, stop the presses
    throw new moodle_exception('invalidrolemapping', 'local_ucla', null,
            sprintf('Params: $pseudorole - %s, $subjectarea - %s',
                    $pseudorole, $subjectarea));
}

/**
 *  Wrapper with debugging and diverting controls for PHP's mail.
 **/
function ucla_send_mail($to, $subj, $body='', $header='') {
    global $CFG;

    if (!empty($CFG->noemailever)) {
        // We don't want any email sent.
        return true;
    }

    if (!empty($CFG->divertallemailsto)) {
        // change subject to have divert message
        $subj = "[DIVERTED $to] $subj";
        // clear out old to
        $to = $CFG->divertallemailsto;
        // clear header variable, because it might contain an email address
        $header = '';
    }

    if (debugging() && empty($CFG->divertallemailsto)) {
        // if divertallemailsto is set, then send out email even if debugging is
        // enabled
        debugging("TO: $to\nSUBJ: $subj\nBODY: $body\nHEADER: $header");
    } else {
        debugging("Sending real email to " . $to);
        return @mail($to, $subj, $body, $header);
    }

    return true;
}

/**
 *  Sorts a set of terms.
 *  @param  $terms          Array( term, ... )
 *  @param  $descending     Optional parameter to sort with most recent term 
 *                          first.
 *  @return Array( term_in_order, ... )
 **/
function terms_arr_sort($terms, $descending = false) {
    $ksorter = array();

    // enumerate terms
    foreach ($terms as $k => $term) {
        $ksorter[$k] = term_enum($term);
    }

    // sort
    asort($ksorter);
 
    // denumerate terms
    $sorted = array();
    foreach ($ksorter as $k => $v) {
        $term = $terms[$k];
        $sorted[$term] = $term;
    }
 
    // sort in descending order
    if ($descending == true) {
        $sorted = array_reverse($sorted, true);
    }
    return $sorted;
}

/**
 *  Checks if a particular shortname given is allowed to view the 
 *  the particular term.
 *  @param  $term           Term to check
 *  @param  $roleshortname  Shortname of role
 *  @param  $currterm       Term to use as current term
 *  @param  $currweek       Week to use as current week
 *  @param  $limitweek      Week to use as cut-off week
 **/
function term_role_can_view($term, $roleshortname, $currterm=null,
                            $currweek=null, $limitweek=null, $leastterm=null) {
    // Nobody can see courses from non-terms
    if (!ucla_validator('term', $term)) {
        return false;
    }

    if ($leastterm === null) {
        $leastterm = get_config('local_ucla', 'oldest_available_term');
    }

    if (ucla_validator('term', $leastterm)) {
        if (term_cmp_fn($term, $leastterm) < 0) {
            return false;
        }
    }

    if ($limitweek === null) {
        $limitweek = get_config('local_ucla', 'student_access_ends_week');
    }
 
    if ($currweek === null) {
        $currweek = get_config('local_ucla', 'current_week');
    }

    if ($currterm === null) {
        $currterm = get_config(null, 'currentterm');
    }
 
    // find the maximum-access-role
    // Check out CCLE-2834 for documentation and reasoning
    $canviewprev = false;
    if (in_array($roleshortname, array(
                // Role-mapped course editors
                'ta_admin', 'ta_instructor', 'editinginstructor',
                    'supervising_instructor', 'studentfacilitator',
                // Site adjuncts
                'manager', 'instructional_assistant', 'editor'
            ))) {
        $canviewprev = true;
    }

    // Either can see all terms or can see until week 2, the previous term
    if ($canviewprev || term_cmp_fn($term, $currterm) >= 0
        || ($currweek < $limitweek
            && term_cmp_fn($term, term_get_prev($currterm)) == 0)) {
        // This should evaluate to true
        return $term;
    }

    return false;
}

function terms_arr_fill($terms) {
    $startterm = reset($terms);
    $endterm = end($terms);

    try {
        $terms = terms_range($startterm, $endterm);
    } catch (moodle_exception $e) {
        debugging("Improper term(s): $startterm $endterm");
    }

    return $terms;
}

function terms_range($startterm, $endterm) {
    if (!ucla_validator('term', $startterm)
            || !ucla_validator('term', $endterm)) {
        throw new moodle_exception('invalidterm', 'local_ucla');
    }

    $terms = array($startterm);

    if (term_cmp_fn($startterm, $endterm) == 0) {
        return $terms;
    }

    // We can get a reverse range, so handle that
    $reverse = false;
    if (term_cmp_fn($startterm, $endterm) > 0) {
        $reverse = $startterm;
        $startterm = $endterm;
        $endterm = $reverse;
    }

    $nextterm = term_get_next($startterm);
    $terms[] = $nextterm;

    while (term_cmp_fn($nextterm, $endterm) < 0) {
        $nextterm = term_get_next($nextterm);
        $terms[] = $nextterm;
    }

    if ($reverse !== false) {
        $terms = array_reverse($terms);
    }

    return $terms;
}

/**
 *  Takes in a UCLA term (Ex: 11F) and returns the term after it.
 * 
 *  @param current_term a valid term string (Ex: '11F')
 *  @return the term after the current term.
 */
function term_get_next($term) {
    if (empty($term)) {
        return null;
    }
    $year = intval(substr($term, 0, 2));
    $quarter = substr($term, -1);

    switch($quarter) {
        case 'F':
            $next_year = ($year == 99) ? '00' : sprintf('%02d', intval($year)+1);
            return $next_year.'W';
        case 'W':
            return $year.'S';
        case 'S':
            return $year.'1';
        case '1':
            return $year.'F';
        default:
            return null;
    }
}

/**
 *  Takes in a UCLA term (Ex: 11F) and returns the term before it.
 * 
 *  @param current_term a valid term string (Ex: '11F')
 *  @return the term after the current term.
 */
function term_get_prev($term) {
    if (empty($term)) {
        return null;
    }
    $year = intval(substr($term, 0 , 2));
    $quarter = substr($term, -1);

    switch ($quarter) {
        case 'F':
            return $year.'1';
        case 'W':
            $prev_year = ($year == 0) ? '99' : sprintf('%02d', intval($year)-1);
            return $prev_year.'F';
        case 'S':
            return $year.'W';
        case '1':
            return $year.'S';
        default:
            return null;
    }
}

/**
 *  PHP side function to order terms.
 *  @param  $term   term
 *  @return string sortable term        Returns false on error
 **/
function term_enum($term) {
    if (!ucla_validator('term', $term)) {
        return false;
    }
 
    // assumption: 65F is the oldest term that registrar has
    // so treat years 65 and older as 19XX and years before as 20XX
    $year = (int) substr($term, 0, -1);
    if ($year < 65) {
        $year = str_pad($year, 2, '0', STR_PAD_LEFT); // fixes year 00-09 problems
        $year = '20' . $year;
    } else {
        $year = '19' . $year;
    }
 
    $r = array(
        'W' => 0,
        'S' => 1,
        '1' => 2,
        'F' => 3
    );

    return $year . $r[substr($term, 2, 1)];
}

/**
 *  Compare-to function.
 *  @param  $term   The first
 *  @param  $term   The second
 *  @return 
 *      first > second return 1
 *      first == second return 0
 *      first < second return -1
 **/
function term_cmp_fn($term, $other) {
    $et = term_enum($term);
    $eo = term_enum($other);
    if ($et > $eo) {
        return 1;
    } else if ($et < $eo) {
        return -1;
    } else {
        return 0;
    }
}

/**
 * Returns true if given course is a collabration site (aka non-srs course), 
 * otherwise false.
 * 
 * @param int|object $course
 * @return boolean 
 */
function is_collab_site($course) {
    global $DB;
    if (is_object($course)) {
        $course = $course->id;
    }
    return !$DB->record_exists('ucla_request_classes', array('courseid' => $course));
}

/**
 * Returns true if given course is an Engineering course, otherwise false.
 *
 * Only queries for the division of the hostcourse.
 *
 * @param int $courseid
 * @return boolean 
 */
function is_engineering($courseid) {
    global $DB;

    // We only care about the hostcourse.
    $sql = "SELECT urci.id
              FROM {ucla_request_classes} urc
              JOIN {ucla_reg_classinfo} urci ON (urc.term=urci.term AND urc.srs=urci.srs)
             WHERE urc.courseid=:courseid AND
                   urc.hostcourse=1 AND
                   urci.division='EN'";
    return $DB->record_exists_sql($sql, array('courseid' => $courseid));
}

/**
 * Returns whether or not the user has the given role for a given context.
 *
 * @param string|array $shortname   If string, then the role's shortname. Else
 *                                  if array, then an array of role shortnames.
 * @param context $context          The context in which to check the roles.
 *
 * @return boolean True if user has the role in the context, false otherwise.
 */
function has_role_in_context($shortname, context $context) {
    // Cast $role_shortname as array, if not already.
    if (!is_array($shortname)) {
        $shortname = array($shortname);
    }
    $roles = get_user_roles($context);
    foreach ($roles as $role) {
        if (in_array($role->shortname, $shortname)) {
            return true;
        }
    }
    return false;
}

/**
 * Sets the editing button in the $PAGE element to be the url passed in.
 * 
 * Code copied from fragments of code in course/view.php to set the "Turn 
 * editing on/off" button.
 * 
 * @global object $OUTPUT
 * @global object $PAGE
 * @global object $USER
 * 
 * @param moodle_url $url   Expecting moodle_url object. If null, then defaults
 *                          redirecting user to $PAGE->url
 */
function set_editing_mode_button($url=null) {
    global $OUTPUT, $PAGE, $USER;
 
    if (empty($url)) {
        $url = $PAGE->url;
    }
 
    // see if user is trying to turn editing on/off
    // copied from course/view.php:line 12, 104-128, 153-155, 205-206
    // (at the time of Moodle 2.2.2)
    $edit = optional_param('edit', -1, PARAM_BOOL);
    if (!isset($USER->editing)) {
        $USER->editing = 0;
    }
    if ($PAGE->user_allowed_editing()) {
        if (($edit == 1) and confirm_sesskey()) {
            $USER->editing = 1;
            // edited to use url specified in function
            redirect($url);
        } else if (($edit == 0) and confirm_sesskey()) {
            $USER->editing = 0;
            if (!empty($USER->activitycopy) && $USER->activitycopycourse == $course->id) {
                $USER->activitycopy       = false;
                $USER->activitycopycourse = null;
            }
            // edited to use url specified in function
            redirect($url);
        }
        // edited to use url specified in function
        $buttons = $OUTPUT->edit_button($url);
        $PAGE->set_button($buttons);
    } else {
        $USER->editing = 0;
    }
}

/**
 *  Gets the FriendlyURL version of a course link.
 *  @param $course  course object
 *  @return string  URL to use, relative to $CFG->wwwroot
 **/
function make_friendly_url($course) {
    return '/course/view/' . rawurlencode($course->shortname);
}

/*
 * Checks the role_assignments table and sees if the viewer shares a context
 * with the target.
 *
 * @param int $targetid     Id of user to check if viewer shares a context with
 * @param int $userid       Defaults to null. If null, then will use currently
 *                          logged in user.
 *
 * @return boolean          True if viewer does share a context with target,
 *                          otherwise false.
 */
function has_shared_context($targetid, $viewerid=null) {
    global $DB, $USER;
 
    if (empty($viewerid)) {
        $viewerid = $USER->id;
    }
 
    // use raw SQL, because there is no built in moodle database api to join a
    // table on itself
    $sql = "SELECT  COUNT(*)
            FROM    {role_assignments} AS ra_target,
                    {role_assignments} AS ra_viewer
            WHERE   ra_target.userid=:targetid AND
                    ra_viewer.userid=:viewerid AND
                    ra_target.contextid=ra_viewer.contextid";
    $result = $DB->get_field_sql($sql, array('targetid' => $targetid,
                                             'viewerid' => $viewerid));

    // if there is a result, return true, otherwise false
    return !empty($result);
}

/**
 * Returns active terms. Used by course requestor, course creator, and pre-pop 
 * enrollment to see what terms should be processed.
 * 
 * @param  $descending     Optional parameter to sort active terms with most 
 *      recent first.
 * @return array           Returns an array of terms
 */
function get_active_terms($descending = 'false') {
    $ret_val = array();
 
    $terms = get_config('local_ucla', 'active_terms');
    if (is_string($terms)) {
        // terms should a comma deliminated list (but might be array already if
        // if defined in config file)
        $terms = explode(',', $terms);
    }
 
    if (!empty($terms)) {
        foreach ($terms as $term) {
            $term = trim($term);
            if (ucla_validator('term', $term)) {
                $ret_val[$term] = $term;
            }
        }
    }
 
    // The weeksdisplay block generates all the terms in correct order
    // But in case this is from a Config file instead
    return terms_arr_sort($ret_val, $descending);
}

/**
 * Sets up the JQuery plugin to sort a given table.
 *
 * @global object $PAGE
 *
 * @param string $tableid   Optional. If entered, will be used to associate
 *                          which table to enable sorting. If not passed will
 *                          generate a unique id number.
 * @param array  $options   Optional. Specify additional options to be passed
 *                          to initialize the tablesorter. Each element in the
 *                          array represents an option/value pair, e.g.
 *                          'debug: true'
 * @param string $idorclass Default to id. If set to 'class', will apply table
 *                          sorting to all tables with a given class.
 * @return string           Returns table id, either the one passed in or the
 *                          one auto-generated.
 */
function setup_js_tablesorter($tableid = null, $options = array(), $idorclass = 'id') {
    global $PAGE;

    $PAGE->requires->jquery();
    $PAGE->requires->js('/local/ucla/tablesorter/jquery.tablesorter.js');
    $PAGE->requires->css('/local/ucla/tablesorter/themes/blue/style.css');

    if (!$tableid) {
        $tableid = uniqid();
    }

    $options[] = 'widgets:["zebra"]';
    $optionsstring = '{' . implode(',', $options) . '}';

    $selectorstring = "$(\"#$tableid\")";
    if ($idorclass == 'class') {
        $selectorstring = "$(\".$tableid\")";
    }

    $PAGE->requires->js_init_code('$(document).ready(function() { ' .
        $selectorstring . '.addClass("tablesorter").tablesorter('
        . $optionsstring . '); });');

    return $tableid;
}

function prompt_login($PAGE, $OUTPUT, $CFG, $course) {
    $PAGE->set_url('/');
    $PAGE->set_course($course);
    $PAGE->set_title($course->shortname);
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add(get_string('loginredirect', 'local_ucla'));
 
    notice(get_string('notloggedin', 'local_ucla'), get_login_url());
}

/**
 * Displays flash successful messages from session.
 * 
 * @global object $OUTPUT
 */
function flash_display() {
    global $OUTPUT;
    if (isset($_SESSION['flash_success_msg'])) {
        echo $OUTPUT->notification($_SESSION['flash_success_msg'], 'notifysuccess');
        unset($_SESSION['flash_success_msg']);
    }
}

/**
 * Copies the $success_msg in a session variable to be used on redirected page
 * via flash_display()
 *
 * @param moodle_url|string $url A moodle_url to redirect to. Strings are not to be trusted!
 * @param string $success_msg The message to display to the user
 */
function flash_redirect($url, $success_msg) {
    // message to indicate to user that content was edited
    $_SESSION['flash_success_msg']  = $success_msg;
    redirect($url);
}

/**
 * Notify students and instructors the status of a course.
 *
 * @global object $OUTPUT
 * @param object $course
 * @return string           Returns notice if any is needed.
 */
function notice_course_status($course) {
    global $CFG, $OUTPUT, $USER;

    // Will display a different message depending on the combination of the
    // following statuses.
    $ispastcourse   = false;
    $ishidden       = false;
    $istemprole     = false;

    $noticestring = '';
    $noticeparam = null;
 
    if (is_past_course($course)) {
        $currentweek = get_config('local_ucla', 'current_week');
        if ($currentweek === \block_ucla_weeksdisplay_session::WEEK_BETWEEN_SESSION) {
            // We are between terms, so make sure if this is a course for
            // exactly term before current term, that we don't display notice.
            $courseinfos = ucla_get_course_info($course->id);
            $courseinfo = current($courseinfos);
            $previousterm = term_get_prev($CFG->currentterm);
            if (term_cmp_fn($courseinfo->term, $previousterm) != 0) {
                $ispastcourse = true;
            }
        } else {
            $ispastcourse = true;
        }
    }

    // Let user know if the course is currently hidden.
    if (empty($course->visible)) {
        $ishidden = true;
    }

    // Display helpful text if user's enrollment is expiring.
    if (!isguestuser($USER)) {
        $timeend = enrol_get_enrolment_end($course->id, $USER->id);
        if ($timeend > 0) {
            $noticeparam = date('M j, Y', $timeend);
            $istemprole = true;
        }
    }

//    debugging(sprintf('$ispastcourse = %d, $ishidden = %d, $istemprole = %d',
//            $ispastcourse, $ishidden, $istemprole));

    // For matrix of status/message, please see
    // CCLE-3787 - Temporary participant role.
    // CCLE-5741 - Only show out of term message if it is enabled in course settings
    if ((!$ispastcourse && !$ishidden && !$istemprole) ||
            ($ispastcourse && !$course->enableoutoftermmessage)) {
        return;
    } else if ($ispastcourse && !$ishidden && !$istemprole) {
        $coursecontext = context_course::instance($course->id);
        // Do not indicate that access expires if we do not it enabled.
        if (has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
            if (get_config('local_ucla', 'student_access_ends_week') && !is_siteadmin()) {
                $noticestring = 'notice_course_status_pastinstructor';
            } else {
                $noticestring = 'notice_course_status_pasthidden';
            }
        } else if (!isloggedin() || isguestuser()) {
            $noticestring = get_string('notice_course_status_pasthidden_login', 'local_ucla');
            $loginbutton = new single_button(new moodle_url('/login/index.php'), 
                    get_string('login', 'local_ucla'));
            $loginbutton->class = 'continuebutton';
            $noticestring .= $OUTPUT->render($loginbutton);
            return $OUTPUT->box($noticestring, 'alert alert-warning alert-login');
        } else if (!is_enrolled($coursecontext, $USER)) {
            $noticestring = 'notice_course_status_pasthidden_nonenrolled';
        } else {
            if (get_config('local_ucla', 'student_access_ends_week')) {
                $noticestring = 'notice_course_status_paststudent';
            } else {
                $noticestring = 'notice_course_status_pasthidden';
            }
        }
    } else if (!$ispastcourse && $ishidden && !$istemprole) {
        $noticestring = 'notice_course_status_hidden';
    } else if (!$ispastcourse && !$ishidden && $istemprole) {
        $noticestring = 'notice_course_status_temp';
    } else if ($ispastcourse && $ishidden && !$istemprole) {
        $coursecontext = context_course::instance($course->id);
        // Give different message if Temporary Participant is not enabled.
        if (get_config('enrol_invitation', 'enabletempparticipant')) {
            // Give message if user can use invitation.
            if (has_capability('enrol/invitation:enrol', $coursecontext)) {
                // Create link to invite.
                $inviteurl = new moodle_url('/enrol/invitation/invitation.php',
                        array('courseid' => $course->id));
                $noticeparam = $inviteurl->out();
                $noticestring = 'notice_course_status_pasthidden_tempparticipant';
            } else {
                $noticestring = 'coursehidden';
            }
        } else if (!isloggedin() || isguestuser()) {
            $noticestring = get_string('notice_course_status_pasthidden_login', 'local_ucla');
            $loginbutton = new single_button(new moodle_url('/login/index.php'), 
                    get_string('login', 'local_ucla'));
            $loginbutton->class = 'continuebutton';
            $noticestring .= $OUTPUT->render($loginbutton);
            return $OUTPUT->box($noticestring, 'alert alert-warning alert-login');
        } else if (!is_enrolled($coursecontext, $USER) && !is_siteadmin()) {
            $noticestring = 'notice_course_status_pasthidden_nonenrolled';
        } else {
            $noticestring = 'notice_course_status_pasthidden';
        }
    } else if ($ispastcourse && !$ishidden && $istemprole) {
        $noticestring = 'notice_course_status_pasttemp';
    } else if (!$ispastcourse && $ishidden && $istemprole) {
        $noticestring = 'notice_course_status_hiddentemp';
    } else if ($ispastcourse && $ishidden && $istemprole) {
        $noticestring = 'notice_course_status_pasthiddentemp';
    }

    if (!empty($noticestring)) {
        return $OUTPUT->notification(get_string($noticestring, 'local_ucla',
                $noticeparam), 'notifywarning');
    }
}

/**
 * Checks if given course belongs to a past term.
 * 
 * @global type $CFG
 * @param object $course
 * @return boolean          Returns false if course is not a reg course or is
 *                          not in past term. Otherwise true.
 */
function is_past_course($course) {
    global $CFG;
    $courseinfos = ucla_get_course_info($course->id);
    if (empty($courseinfos)) {
        return false;
    }
    // only notify for old courses
    $courseinfo = current($courseinfos);
    if (term_cmp_fn($courseinfo->term, $CFG->currentterm) == -1) {
        return true;
    }
    return false;
}

/**
 * Handles the hiding of courses and related TA sites, and reports  any 
 * successes or failures. 
 *
 * When hiding a course we will also disable the guest enrollment plugin,
 * because, due to a Moodle bug, users with a disabled enrollment can still
 * access a hidden site if they have the the "Temporary Participant" role.
 *
 * Please do not call this method directly. It should only be called from
 * local/ucla/eventslib.php:hide_past_courses or the command line script
 * local/ucla/scripts/hide_courses.php.
 * 
 * @global object $DB
 * @param string $term
 * @return mixed            Returns false on invalid term. Otherwise returns an
 *                          array of $num_hidden_courses, $num_hidden_tasites,
 *                          $num_problem_courses, $error_messages.
 */
function hide_courses($term) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
    require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');

    if (!ucla_validator('term', $term)) {
        return false;
    }

    // Track some stats.
    $num_hidden_courses = 0;
    $num_hidden_tasites = 0;
    $num_problem_courses = 0;
    $error_messages = '';

    // Get list of courses to hide.
    $courses = ucla_get_courses_by_terms(array($term));

    if (empty($courses)) {
        // No courses to hide.
        return array($num_hidden_courses, $num_hidden_tasites,
                     $num_problem_courses, $error_messages);
    }

    $enrol_guest_plugin = enrol_get_plugin('guest');

    // Now run command to hide all courses for given term. Don't worry about
    // updating visibleold (don't care) and we aren't using update_course,
    // because if might be slow and trigger unnecessary events.
    $courseobj = new stdClass();
    $courseobj->visible = 0;
    foreach ($courses as $courseid => $courseinfo) {
        $courses_processed = array($courseid);
        $courseobj->id = $courseid;
        try {
            ++$num_hidden_courses;

            // Try to see if course had any TA sites.
            $existing_tasites = block_ucla_tasites::get_tasites($courseid);
            if (!empty($existing_tasites)) {
                foreach ($existing_tasites as $tasite) {
                    ++$num_hidden_tasites;
                    $courses_processed[] = $tasite->id;
                }
            }

            // Hide courses and guest plugins.
            foreach ($courses_processed as $courseid) {
                $courseobj->id = $courseid;
                $DB->update_record('course', $courseobj, true);

                $course = $DB->get_record('course', array('id' => $courseid),
                        '*', MUST_EXIST);
                PublicPrivate_Course::set_guest_plugin($course, ENROL_INSTANCE_DISABLED);
            }

        } catch (dml_exception $e) {
            $error_messages .= sprintf("Could not hide courseid %d\n%s\n",
                    $courseobj->id, $e->getMessage());
            ++$num_problem_courses;
        }
    }

    return array($num_hidden_courses, $num_hidden_tasites,
                 $num_problem_courses, $error_messages);
}

/**
 * Given a displayname in the following format:
 *  LAST, FIRST MIDDLE, SUFFIX
 * Will return an array for a user's lastname and firstname.
 *
 * Consolidates name formating used in shib_transform.php and prepop.
 *
 * @param string $displayname
 *
 * @return array
 */
function format_displayname($displayname) {
    $retval = array('firstname' => '', 'lastname' => '');

    // Make sure the name is all capitalized.
    $displayname = core_text::strtoupper($displayname);

    // Expecting name in following format: LAST, FIRST MIDDLE, SUFFIX.
    $names = explode(',',  $displayname);
    // Trim every element.
    $names = array_map('trim', $names);

    if (empty($names)) {
        // No name found.
    } else if (empty($names[1])) {
        // No first name.
        $retval['lastname'] = $names[0];
    } else {
        // First name might have middle name data.
        $retval['firstname'] = $names[1];
        $retval['lastname'] = $names[0];
    }

    // Might have a suffix.
    if (isset($names[2])) {
        $retval['lastname'] .= ' ' . $names[2];
    }

    return $retval;
}

/**
 * Print a grouping menu for filtering by grouping in grader report.
 * 
 * @category group
 * @param stdClass $course
 * @param mixed $urlroot
 * @param int $activegrouping groupingid that is active (NULL for none)
 * @return string
 */
function groupings_print_filter_menu($course, $urlroot, $activegrouping) {
    global $OUTPUT, $DB;

    // Check that user has the capability to view all groups.
    $context = context_course::instance($course->id);
    if (!has_capability('moodle/site:accessallgroups', $context)) {
        return '';
    }

    // Fetch groupings and for select menu.
    $select = 'courseid = ?';
    $params = array($course->id);
    $sort = 'id';
    $fields = 'id, name';
    $groupingsmenu = $DB->get_records_select_menu('groupings', $select, $params, $sort, $fields);

    if ($groupingsmenu) {

        // Change the "Private Course Material" grouping to show as "All" if it exists.
        $publicprivatecourse = new PublicPrivate_Course($course);
        $pubprivgroupingid = $publicprivatecourse->get_grouping();
        if ($pubprivgroupingid && isset($groupingsmenu[$pubprivgroupingid])) {
            $groupingsmenu[$pubprivgroupingid] = get_string('all_groupings', 'local_ucla');
        }

        // Set the active grouping to be the course's default grouping if no
        // active grouping was supplied.
        if (!isset($activegrouping) && isset($course->defaultgroupingid)
            && isset($groupingsmenu[$course->defaultgroupingid])) {
 
            $activegrouping = $course->defaultgroupingid;
        }
 
        // Build a string with the grouping being viewed,
        // or a select with all available groupings.
        $groupinglabel = get_string('view_grouping', 'local_ucla');

        if (count($groupingsmenu) == 1) {
            $groupingname = reset($groupingsmenu);
            $output = $groupinglabel.': '.$groupingname;
        } else {
            $select = new single_select(new moodle_url($urlroot), 'grouping', $groupingsmenu, $activegrouping, null, 'selectgroup');
            $select->label = $groupinglabel.': ';
            $output = $OUTPUT->render($select);
        }

        $output = '<div class="groupselector">'.$output.'</div>';

        return $output;
    } else {
        // Return an empty string if there are no groupings.
        return '';
    }
}

/**
 * For a crosslisted $courseid, find what SRS $userid is enrolled in.
 * This info is in the ccle_roster_class_cache table.  There should
 * only be one record, but it's possible to receive more.
 *
 * @param int $courseid
 * @param int $userid
 * @return array of courses
 */
function ucla_get_user_enrolled_course($courseid, $userid) {
    global $DB;

    // Get crosslisted SRS list.
    $courses = ucla_get_course_info($courseid);

    if (empty($courses)) {
        // Course was not a srs course.
        return array();
    }

    $srslist = implode(',',
        array_map(function($o) {
            return $o->srs;
        }, $courses));
    $term = $courses[0]->term;

    if (empty($srslist) || empty($term)) {
        // Course somehow got here and did not have term/srs.
        return array();
    }

    $sql = "SELECT urc.id, urc.term, urc.srs,
                   urc.subj_area, urc.crsidx, urc.secidx,
                   u.idnumber as uidstudent
              FROM {ccle_roster_class_cache} crcc,
                   {ucla_reg_classinfo} AS urc,
                   {user} AS u
             WHERE u.id = :userid AND
                   u.idnumber = crcc.stu_id AND
                   urc.term = crcc.param_term AND
                   urc.srs = crcc.param_srs AND
                   crcc.param_term = :term AND
                   crcc.param_srs IN ($srslist)";
    $enrolledcourses = $DB->get_records_sql($sql,
            array('userid' => $userid, 'term' => $term));

    return $enrolledcourses;
}
