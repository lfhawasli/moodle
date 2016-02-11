<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Control logic for TA-site functionality.
 *
 * @package    block_ucla_tasites
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/enrol/meta/locallib.php');
require_once($CFG->dirroot . '/local/metagroups/locallib.php');

/**
 * TA sites block.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_tasites extends block_base {

    const GROUPINGID = 'tasitegrouping';

    /**
     * Do not make this block available to add via "Add a block" dropdown.
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
    }

    /**
     * Checks if a user can create a specific or any TA-site.
     *
     * @param int $courseid
     * @param stdClass $user    Optional.
     *
     * @return boolean
     */
    public static function can_access($courseid, $user = false) {
        global $USER;
        $user = $user ? $user : $USER;

        return self::can_have_tasite($user, $courseid)
                || has_capability('moodle/course:update',
                        context_course::instance($courseid), $user)
                || require_capability('moodle/site:config',
                        context_system::instance(), $user);
    }

    /**
     * Checks if a particular user can have a TA-site.
     *
     * @param stdClass $user
     * @param int $courseid
     *
     * @return boolean
     */
    public static function can_have_tasite($user, $courseid) {
        $tas = self::get_tasite_users($courseid);
        foreach ($tas as $ta) {
            // Check if user is one of the TAs.
            if ($ta->idnumber == $user->idnumber) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the TA-site can be made (does not exist).
     *
     * @param stdClass $user
     * @param int $courseid
     *
     * @return boolean
     */
    public static function can_make_tasite($user, $courseid) {
        if (self::can_have_tasite($user, $courseid)) {
            // Make sure that TA site doesn't already exist.
            $tasites = self::get_tasites($courseid);
            foreach ($tasites as $tasite) {
                if (strpos($tasite->enrol->ta_uclaids, $user->idnumber) !== false) {
                    return false;
                }
            }
            return true;
        } else {
            $context = context_course::instance($courseid);
            if (has_capability('moodle/course:update', $context)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the current user can have TA-sites.
     *
     * @param int $courseid
     *
     * @return boolean
     */
    public static function check_access($courseid) {
        return self::enabled($courseid) && self::can_access($courseid);
    }

//    /**
//     * Used to keep track of naming schemas used in the form.
//     *
//     * @param stdClass $tainfo
//     * @return string
//     */
//    public static function checkbox_naming($tainfo) {
//        return $tainfo->id . '-checkbox';
//    }

    /**
     * Creates a new course and assigns enrolments.
     *
     * @param stdClass $parentcourse
     * @param array $typeinfo   This is a subset of what we get from
     *                          get_tasection_mapping:
     *                  [bysection] => [secnum] => [secsrs] => [array of srs numbers]
     *                                          => [tas] => [array of uid => fullname]
     *                  [byta] => [fullname] => [ucla_id] => [uid]
     *                                       => [secsrs] => [secnum] => [srs numbers]
     * @return stdClass          Returns created course.
     */
    public static function create_tasite($parentcourse, $typeinfo) {
        global $DB, $USER;
        $course = clone($parentcourse);

        // Get default name for TA site.
        $course->fullname = self::new_fullname($parentcourse->fullname, $typeinfo);
        $course->shortname = self::new_shortname($parentcourse->shortname, $typeinfo);

        // Hacks for public private.
        unset($course->grouppublicprivate);
        unset($course->groupingpublicprivate);

        // Remove course description, because it doesn't make sense for tasites.
        unset($course->summary);

        $newcourse = create_course($course);

        // Tag site as TA site.
        self::set_site_indicator($newcourse);

        // Map section numbers to SRS numbers.
        $uidarray = array();
        $srsarray = array();
        if (isset($typeinfo['bysection'])) {
            foreach ($typeinfo['bysection'] as $secinfo) {
                $srsarray = array_merge($srsarray, $secinfo['secsrs']);
                $uidarray = array_merge($uidarray, array_keys($secinfo['tas']));               
            }
        } else if (isset($typeinfo['byta'])) {
            // Getting the TA's UID and section SRS numbers.
            foreach ($typeinfo['byta'] as $tainfo) {
                $uidarray[] = $tainfo['ucla_id'];
                if (!empty($tainfo['secsrs'])) {
                    // If course has sections, then handle multiple srs numbers.
                    foreach ($tainfo['secsrs'] as $secnum => $secinfo) {
                        foreach ($secinfo as $srs) {
                            $srsarray[] = $srs;
                        }
                    }
                }
            }
        }

        // Setup meta enrolment plugin and sync enrolments.
        $meta = new enrol_meta_plugin();
        $meta->add_instance($newcourse, array(
            'customint1' => $parentcourse->id,
            'customint2' => self::get_ta_role_id(),
            'customint3' => self::get_ta_admin_role_id(),
            'customint4' => $USER->id,
            'customtext1' => implode(',', $uidarray),
            'customtext2' => implode(',', $srsarray),
        ));
        enrol_meta_sync($newcourse->id);

        // Sync groups from course site.
        $trace = new null_progress_trace();
        local_metagroups_sync($trace, $newcourse->id);
        $trace->finished();

        if (!empty($srsarray)) {
            // For the new TA site, create a special grouping based on sections.
            // Create grouping.
            $tasitegrouping = new stdClass();
            $tasitegrouping->name = 'TA site grouping';
            $tasitegrouping->idnumber = self::GROUPINGID;
            $tasitegrouping->courseid = $newcourse->id;
            $tasitegrouping->id = groups_create_grouping($tasitegrouping);

            // Find matching groups in TA site matching section groups.
            // The local_metagroups_sync function sets the idnumber for
            // groups in the TA site to match the group ids in the parent.
            list($sqlidnumber, $params) = $DB->get_in_or_equal($srsarray);
            $params[] = $newcourse->id;
            $sql = "SELECT child.id
                       FROM {groups} parent
                       JOIN {groups} child
                      WHERE parent.idnumber $sqlidnumber
                            AND child.idnumber=parent.id
                            AND child.courseid=?";
            $tasitegroups = $DB->get_fieldset_sql($sql, $params);

            // Add these groups to the $tasitegrouping.
            foreach ($tasitegroups as $groupid) {
                groups_assign_grouping($tasitegrouping->id, $groupid);
            }

            // Set default grouping to be this grouping.
            $newcourse->defaultgroupingid = $tasitegrouping->id;
            $DB->update_record('course', $newcourse);
        }

        // Check if Announcements forum should be deleted for TA site. We only
        // want Annoucement forums if the TA site was created for a specific
        // section and not all sections.
        /*
          if (isset($typeinfo['bysection'])) {
          if ($typeinfo['bysection'] == 'all') {
          $enabletasitenewsforum = !(get_config('format_ucla', 'disable_tasite_news_forum'));
          if (!$enabletasitenewsforum) {
          $newsforum = forum_get_course_forum($newcourse->id, 'news');
          forum_delete_instance($newsforum->id);
          }
         */

        return $newcourse;
    }

    /**
     * Checks if TA sites is supported.
     *
     * @return boolean
     */
    public static function enabled($courseid) {
        // See if the instructor has enabled TA site creation for this specific user.
        $formatoptions = course_get_format($courseid)->get_format_options();
        if ($formatoptions['createtasite'] == 1) {
            return self::validate_enrol_meta() && self::validate_roles();
        }
    }

    /**
     * Section number is in this format:
     *    P000SS -> P+CSTR(CINT(000))+SS    // Trim accordingly.
     *
     * @param string $secnum
     * @param string            Returns formatted string
     */
    public static function format_sec_num($secnum) {
        $retval = '';
        if (strlen($secnum) <= 3) {
            // If the string length is ~3, then just convert it to int.
            $retval = intval($secnum);
        } elseif (strlen($secnum) <= 5) {
            // Then maybe the last and/or first character doesn't exist; truncated.
            $len    = strlen($secnum);
            $num    = intval(substr($secnum, 1, 3));
            $SS     = trim(substr($secnum, $len - 1, 1));
            $retval = $num . $SS;
        } else {
            // All characters should be present.
            $P      = trim(substr($secnum, 0, 1));
            $num    = intval(substr($secnum, 1, 3));
            $SS     = trim(substr($secnum, 4, 2));
            $retval = $P . $num . $SS;
        }
        return $retval;
    }

//    /**
//     * Get all potentional TA users and their according TA sites from
//     * {role_assignments}.
//     *
//     * @param int $courseid
//     * @return array
//     */
//    public static function get_potential_tasites($courseid) {
//        $tasiteinfo = array();
//        $tasra = self::get_tasite_users($courseid);
//        if (!empty($tasra)) {
//            $course = get_course($courseid);
//
//            // Used for user_get_users_by_id.
//            $userids = array();
//
//            // Index $tasra by userid.
//            $tas = array();
//            foreach ($tasra as $tara) {
//                $userid = $tara->userid;
//                $userids[] = $userid;
//                $tas[$userid] = $tara;
//            }
//
//            // From user table.
//            $users = user_get_users_by_id($userids);
//
//            // From enrol table indexed by customint4.
//            $existingtasites = block_ucla_tasites::get_tasites($courseid);
//
//            // Populate $tasiteinfo array.
//            foreach ($users as $userid => $user) {
//                if (!empty($existingtasites[$userid])) {
//                    // Associate ta to TA-site.
//                    $tasite = $existingtasites[$userid];
//                    $user->tasite = $tasite;
//
//                    // These are all for display sake.
//                    $courseurl = new moodle_url('/course/view.php', array('id' => $tasite->id));
//                    $user->courseurl = $courseurl->out();
//
//                    $user->courseshortname = $tasite->shortname;
//                }
//
//                // Some more shortcuts.
//                $user->fullname = fullname($user);
//                $user->parentcourse = $course;
//
//                $tasiteinfo[$userid] = $user;
//            }
//        }
//        return $tasiteinfo;
//    }

    /**
     * Returns the TA admin role id.
     *
     * @return int
     */
    public static function get_ta_admin_role_id() {
        return self::get_ta_role_id(true);
    }

    /**
     * Gets the role id of one of the roles that are relevant.
     *
     * Cached using static variables.
     *
     * @param boolean $promoted Return the promoted role?
     * @return int
     */
    public static function get_ta_role_id($promoted = false) {
        global $DB;
        static $roleids;

        $tarsn = self::get_ta_role_shortname($promoted);

        if (!isset($roleids[$tarsn])) {
            $roleids[$tarsn] = $DB->get_field('role', 'id',
                    array('shortname' => $tarsn));
        }

        return $roleids[$tarsn];
    }

    /**
     * Gets the shortnames expected of the TA roles.
     *
     * @param boolean $promoted Return the promoted role?
     * @return string
     */
    public static function get_ta_role_shortname($promoted = false) {
        $rolesubstr = $promoted ? '_admin' : '';
        $rolestr = 'ta' . $rolesubstr;

        return $rolestr;
    }

    /**
     * Returns TA full name from UID.
     *
     * @param int $uid
     * @return string
     */
    public static function get_tafullname($uid) {
        static $tanamecache = array();
        global $DB;

        if (!isset($tanamecache[$uid])) {
            $user = $DB->get_record('user', array('idnumber' => $uid));
            $tanamecache[$uid] = fullname($user);
        }

        $fullname = $tanamecache[$uid];

        return $fullname;
    }

    /**
     * Returns a mapping of discussion srs to TAs, if available.
     *
     * Consolidates cross-listed sections as well.
     *
     * @param int $courseid
     * @return array    Returns array in following format:
     *                  [term]
     *                  [bysection] => [secnum] => [secsrs] => [array of srs numbers]
     *                                          => [tas] => [array of uid => fullname]
     *                  [byta] => [fullname] => [ucla_id] => [uid]
     *                                       => [secsrs] => [secnum] => [srs numbers]
     */
    public static function get_tasection_mapping($courseid) {
        $cache = cache::make('block_ucla_tasites', 'tasitemapping');
        $tasitemapping = $cache->get($courseid);
        if (empty($tasitemapping)) {
            ucla_require_registrar();

            $termsrses = ucla_map_courseid_to_termsrses($courseid);
            if (empty($termsrses)) {
                return null;
            }

            // Get term, we need it later.
            $termsrs = reset($termsrses);
            $term = $termsrs->term;

            $tasitemapping = array();
            $tasitemapping['term'] = $term;
            foreach ($termsrses as $termsrs) {
                $sections = \registrar_query::run_registrar_query(
                                'ccle_ta_sections', array(
                            'term' => $termsrs->term,
                            'srs' => $termsrs->srs
                ));
                if (!empty($sections)) {
                    foreach ($sections as $section) {
                        $fullname = self::get_tafullname($section['ucla_id']);

                        // There might be multiple srs numbers for cross-listed sections.
                        $tasitemapping['bysection'][$section['sect_no']]['secsrs'][] = $section['srs_crs_no'];
                        $tasitemapping['bysection'][$section['sect_no']]['tas'][$section['ucla_id']] = $fullname;

                        $tasitemapping['byta'][$fullname]['secsrs'][$section['sect_no']][] = $section['srs_crs_no'];
                        $tasitemapping['byta'][$fullname]['ucla_id'] = $section['ucla_id'];
                    }
                } else {
                    // No course sections found, so just use main lecture.
                    $tasitemapping['bysection']['all']['secsrs'][] = $termsrs->srs;

                    // Get users with the role of TA and TA admin.
                    $tausers = self::get_tasite_users($courseid);
                    foreach ($tausers as $tauser) {
                        $fullname = self::get_tafullname($tauser->idnumber);
                        $tasitemapping['byta'][$fullname]['ucla_id'] = $tauser->idnumber;
                    }
                }
            }

            // Sort the TA names.
            foreach ($tasitemapping['bysection'] as &$tasection) {
                if (!empty($tasection['tas'])) {
                    asort($tasection['tas']);
                }
            }

            // Sort the byta mapping by TAs.
            ksort($tasitemapping['byta']);

            $cache->set($courseid, $tasitemapping);
        }

        return $tasitemapping;
    }

    /**
     * Returns the relevant enrollment entry that is related to the particular
     * ta_site.
     *
     * @param int $courseid
     * @return stdClass
     */
    public static function get_tasite_enrol_meta_instance($courseid) {
        $tasiteenrol = false;
        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $instance) {
            if ($instance->enrol == 'meta') {
                // Check to see if instance is a tasite enrol meta instance.
                if (self::is_tasite_enrol_meta_instance($instance)) {
                    $tasiteenrol = $instance;
                    // Small convenience naming.
                    $tasiteenrol->ownerid = $tasiteenrol->customint4;
                }
            }
        }

        return $tasiteenrol;
    }

    /**
     * Gets all the enrol_meta instances associated that signifies that a course
     * is a TA-site.
     *
     * @param int $courseid
     * return array
     */
    public static function get_tasite_enrolments($courseid) {
        global $DB;

        // Find all TA site meta enrolment instances.
        $enrols = $DB->get_records(
                'enrol', array(
            'enrol' => 'meta',
            'customint1' => $courseid,
            'customint2' => self::get_ta_role_id(),
            'customint3' => self::get_ta_admin_role_id()
                ), '', 'customtext1 as ta_uclaids, '
                . 'courseid, '
                . 'customint1 as parentcourseid, '
                . 'customint2 as ta_roleid, '
                . 'customint4 as createrid, '
                . 'customint3 as ta_admin_roleid, '
                . 'customtext2 as ta_secsrs'
        );

        return $enrols;
    }

    /**
     * Checks if there are any valid users that can have a TA-site.
     *
     * @param int $courseid
     * @return array    Role assignments for users that can have TA-sites.
     */
    public static function get_tasite_users($courseid) {
        global $DB;

        // Allow ta and ta-admins to have ta sites.
        $role = new object();
        $context = context_course::instance($courseid);

        $taroleid = self::get_ta_role_id();
        $taadminroleid = self::get_ta_admin_role_id();

        return $DB->get_records_sql("SELECT u.id, u.idnumber
            FROM {role_assignments} ra
            JOIN {user} u ON ra.userid = u.id
            WHERE ra.contextid = ? AND (ra.roleid = ? OR ra.roleid = ?)",
                array($context->id, $taroleid, $taadminroleid));
    }

    /**
     * Gets all the TA-sites that are associated with a course.
     *
     * @param int $courseid
     * @return array    Enrol_meta instances.
     */
    public static function get_tasites($courseid) {
        global $DB;

        $enrols = self::get_tasite_enrolments($courseid);

        // Find related courses.
        $courseids = array();
        foreach ($enrols as $enrolkey => $enrol) {
            $courseids[$enrolkey] = $enrol->courseid;
        }

        $courses = $DB->get_records_list('course', 'id', $courseids);

        // Match users to courses.
        $tacourses = array();
        foreach ($enrols as $key => $enrol) {
            $course = $courses[$courseids[$key]];
            $course->enrol = $enrol;

            // Get default grouping for each course.
            $course->defaultgroupingname = groups_get_grouping_name($course->defaultgroupingid);

            $tacourses[$enrol->ta_uclaids] = $course;
        }

        return $tacourses;
    }

    /**
     * Checks if a given user has a TA site for given course.
     *
     * @param int $courseid
     * @param string $uclaid
     * @return boolean
     */
    public static function has_tasite($courseid, $uclaid) {
        global $DB;
        
        $where = "customint1=:courseid AND enrol='meta' AND " .
                $DB->sql_like('customtext1', ':uclaid');
        return $DB->record_exists_select('enrol', $where,
                array('courseid' => $courseid, 'uclaid' => '%'.$uclaid.'%'));
    }

    /**
     * Block initializer.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_tasites');
    }

    /**
     * Checks if a site is a TA-site.
     *
     * @param int $courseid
     * @return boolean
     */
    public static function is_tasite($courseid) {
        return is_object(self::get_tasite_enrol_meta_instance($courseid));
    }

    /**
     * Checks if the instance of enrol_meta is for a TA-site.
     *
     * Cached using static variables.
     *
     * @param stdClass $enrol   Enrol instance.
     */
    public static function is_tasite_enrol_meta_instance($enrol) {
        // This can get called a lot from the meta sync.
        static $cacheistasite;
        if (!empty($cacheistasite) || !isset($cacheistasite[$enrol->id])) {
            $result = true;
            if (empty($enrol->customint2) || empty($enrol->customint3) || empty($enrol->customint4) || $enrol->customint2 != self::get_ta_role_id() || $enrol->customint3 != self::get_ta_admin_role_id()) {
                $result = false;
            }
            $cacheistasite[$enrol->id] = $result;
        }
        return $cacheistasite[$enrol->id];
    }

    /**
     * Generates a fullname for the TA-site.
     *
     * @param string $parentshortname
     * @param array $typeinfo           Indexed by the site type.
     * @return string
     */
    public static function new_fullname($parentfullname, $typeinfo) {
        $fullname = '';
        $a = new stdClass();
        $a->fullname = $parentfullname;

        if (isset($typeinfo['bysection'])) {
            $secnums = array_keys($typeinfo['bysection']);
            // Format secnums.
            $secnums = array_map(array('block_ucla_tasites', 'format_sec_num'), $secnums);
            $a->text = implode(', ', $secnums);
        } else if (isset($typeinfo['byta'])) {
            $tanames = array_keys($typeinfo['byta']);
            $a->text = implode(', ', $tanames);
        }
        $fullname = get_string('tasitefullname', 'block_ucla_tasites', $a);
        return $fullname;
    }

    /**
     * Generates a shortname for the TA-site.
     *
     * @param string $parentshortname
     * @param array $typeinfo           Indexed by the site type.
     * @param int $cascade              Used if there is a name collision.
     * @return string
     */
    public static function new_shortname($parentshortname, $typeinfo, $cascade = 0) {
        global $DB;

        // Would use calculate_course_names but that adds "Copy" to shortname.
        $shortname = $parentshortname . '-';
        if (isset($typeinfo['bysection'])) {
            $secnums = array_keys($typeinfo['bysection']);
            // Format secnums.
            $secnums = array_map(array('block_ucla_tasites', 'format_sec_num'), $secnums);
            $shortname .= implode('-', $secnums);
        } else if (isset($typeinfo['byta'])) {
            $tanames = array_keys($typeinfo['byta']);

            $shortname .= implode('-', $tanames);
            $escchars = array(" ", ",", "'");
            $shortname = str_replace($escchars, "", $shortname);
        }

        if ($cascade) {
            // Name conflict, so try appending number.
            $shortname .= '-' . $cascade;
        }

        if ($DB->record_exists('course', array('shortname' => $shortname))) {
            $cascade++;
            return self::new_shortname($parentshortname, $typeinfo, $cascade);
        }

        return $shortname;
    }

    /**
     * Appends Office hours block results with links to TA sites.
     *
     * @param array $params
     * @return array
     */
    public function office_hours_append($params) {
        $instructors = $params['instructors'];
        $course = $params['course'];
        $tasites = self::get_tasites($course->id);

        $appendedinstdata = array();

        if ($tasites) {
            $fieldname = block_ucla_office_hours::blocks_process_displaykey(
                            'tasite', 'block_ucla_tasites'
            );

            foreach ($instructors as $ik => $instructor) {
                $iid = $instructor->id;
                if (isset($tasites[$iid])) {
                    $appendedinstdata[$ik]['tasite'] = html_writer::link(
                                    new moodle_url(
                                    '/course/view.php', array('id' => $tasites[$iid]->id)
                                    ), get_string('view_tasite', 'block_ucla_tasites')
                    );
                } else {
                    $appendedinstdata[$ik]['tasite'] = '';
                }
            }
        }
        return $appendedinstdata;
    }

    /**
     * Filters Office hours block results so that if the course is a TA site, we
     * only want to display the valid TA.
     *
     * @param array $params
     * @return array            The filtered input array.
     */
    public function office_hours_filter_instructors($params) {
        $filtered = array();
        $course = $params['course'];
        $instructors = $params['instructors'];

        if (($tasiteenrol = self::get_tasite_enrol_meta_instance($course->id)) && self::is_tasite_enrol_meta_instance($tasiteenrol)) {

            // Filter out all the people displayed in the office hours block
            // that is not the TA.
            foreach ($instructors as $key => $instructor) {
                if ($tasiteenrol->ownerid != $instructor->id) {
                    $filtered[] = $key;
                }
            }
        }

        return $filtered;
    }

    /**
     * Sets site type for given course to be a TA site.
     *
     * @param stdClass $newcourse
     */
    public static function set_site_indicator($newcourse) {
        global $CFG;
        require_once($CFG->dirroot . '/' . $CFG->admin .
                '/tool/uclasiteindicator/lib.php');
        $sitetype = siteindicator_site::create($newcourse->id);
        $sitetype->set_type('tasite');
    }

    /**
     * Either shows or hides TA site, depending on current status.
     *
     * @param int $courseid
     * @return int              Current visiblity of site.
     */
    public static function toggle_visiblity($courseid) {
        // Make sure course is TA site.
        if (!self::is_tasite($courseid)) {
            throw new block_ucla_tasites_exception('errisnottasite');
        }

        $oldcourse = get_course($courseid);
        course_change_visibility($courseid, !$oldcourse->visible);

        return !$oldcourse->visible;
    }

    /**
     * Adds link to block in Control Panel.
     *
     * @param stdClass $course
     * @param context_course $context
     *
     * @return mixed    Returns array that can be parsed into Control Panel link
     *                  or returns false if user cannot access TA sites.
     */
    public static function ucla_cp_hook($course, $context) {
        $courseid = $course->id;
        $cpmodule = false;

        $accessible = false;
        try {
            $accessible = self::check_access($courseid) && self::get_tasite_users($courseid) && !self::is_tasite($courseid);
        } catch (moodle_exception $e) {
            // Do nothing.
            $accessible = false;
        }

        // User can access TA sites, so provide link.
        if ($accessible) {
            $cpmodule = array(
                array(
                    'item_name' => 'ucla_make_tasites',
                    'action' => new moodle_url(
                            '/blocks/ucla_tasites/index.php', array(
                        'courseid' => $course->id
                            )
                    ),
                    'tags' => array('ucla_cp_mod_other')
                )
            );
        }

        return $cpmodule;
    }

    /**
     * Checks that enrol_meta is enabled, and then enables the plugin
     * if possible.
     *
     * @throws block_ucla_tasites_exception
     * @return boolean
     */
    public static function validate_enrol_meta() {
        if (!enrol_is_enabled('meta')) {
            // Reference admin/enrol.php.
            try {
                require_capability('moodle/site:config', context_system::instance());
            } catch (moodle_exception $e) {
                throw new block_ucla_tasites_exception('errsetupenrol');
            }

            $enabled = array_keys(enrol_get_plugins(true));
            $enabled[] = 'meta';
            set_config('enrol_plugins_enabled', implode(',', $enabled));

            $syscontext = context_system::instance();
            $syscontext->mark_dirty();
        }

        return true;
    }

    /**
     * Checks that the roles have been correctly detected.
     *
     * @throws block_ucla_tasites_exception
     * @return boolean
     */
    public static function validate_roles() {
        if (!self::get_ta_role_id()) {
            throw new block_ucla_tasites_exception('setuprole', self::get_ta_role_shortname());
        }

        if (!self::get_ta_admin_role_id()) {
            throw new block_ucla_tasites_exception('setuprole', self::get_ta_role_shortname(true));
        }

        return true;
    }

}

/**
 * TA sites exception.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_tasites_exception extends moodle_exception {

    /**
     * Constructor.
     *
     * @param string $errorcode
     * @param string $a
     */
    public function __construct($errorcode, $a = null) {
        parent::__construct($errorcode, 'block_ucla_tasites', '', $a);
    }

}
