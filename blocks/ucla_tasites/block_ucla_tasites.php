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
    /**
     * Used to keep track of naming schemas used in the form.
     *
     * @param stdClass $tainfo
     * @return string
     */
    public static function action_naming($tainfo) {
        return $tainfo->id . '-action';
    }

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
    public static function can_access($courseid, $user=false) {
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
            if ($ta->userid == $user->id) {
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
        // Make sure that TA site doesn't already exist.
        return self::can_have_tasite($user, $courseid)
                && !self::get_tasite($courseid, $user);
    }

    /**
     * Checks if the current user can have TA-sites.
     *
     * @param int $courseid
     *
     * @return boolean
     */
    public static function check_access($courseid) {
        return self::enabled() && self::can_access($courseid);
    }

    /**
     * Used to keep track of naming schemas used in the form.
     *
     * @param stdClass $tainfo
     * @return string
     */
    public static function checkbox_naming($tainfo) {
        return $tainfo->id . '-checkbox';
    }

    /**
     * Creates a new course, assigns enrolments.
     *
     * @param stdClass $tainfo
     * return stdClass          Returns created course.
     */
    public static function create_tasite($tainfo) {
        $course = clone($tainfo->parentcourse);

        $course->shortname = self::new_name($tainfo);

        $fullnamedata = new object();
        $fullnamedata->course_fullname = $course->fullname;
        // This is the fullname of the TA.
        $fullnamedata->fullname = $tainfo->fullname;

        $course->fullname = get_string('tasitefor', 'block_ucla_tasites',
            $fullnamedata);

        // Hacks for public private.
        unset($course->grouppublicprivate);
        unset($course->groupingpublicprivate);

        // Remove course description, because it doesn't make sense for tasites.
        unset($course->summary);

        $newcourse = create_course($course);

        // Tag site as TA site.
        self::set_site_indicator($newcourse);

        $course->id = $newcourse->id;

        // Setup meta enrolment plugin and sync enrolments.
        $meta = new enrol_meta_plugin();
        $meta->add_instance($course, array(
            'customint1' => $tainfo->parentcourse->id,
            'customint2' => self::get_ta_role_id(),
            'customint3' => self::get_ta_admin_role_id(),
            'customint4' => $tainfo->id
        ));
        enrol_meta_sync($course->id);

        // Check if Announcements forum should be deleted for TA site.
        $istasite = self::is_tasite($course->id);
        if ($istasite) {
            $enabletasitenewsforum = !(get_config('format_ucla', 'disable_tasite_news_forum'));
            if (!$enabletasitenewsforum) {
                $newsforum = forum_get_course_forum($course->id, 'news');
                forum_delete_instance($newsforum->id);
            }
        }

        // Sync groups from course site.
        $trace = new null_progress_trace();
        local_metagroups_sync($trace, $course->id);
        $trace->finished();

        return $newcourse;
    }

    /**
     * Checks if TA sites is supported.
     *
     * @return boolean
     */
    public static function enabled() {
        return self::validate_enrol_meta() && self::validate_roles();
    }

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
    public static function get_ta_role_id($promoted=false) {
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
    public static function get_ta_role_shortname($promoted=false) {
        $rolesubstr = $promoted ? '_admin' : '';
        $rolestr = 'ta' . $rolesubstr;

        return $rolestr;
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
            'enrol',
            array(
                'enrol' => 'meta',
                'customint1' => $courseid,
                'customint2' => self::get_ta_role_id(),
                'customint3' => self::get_ta_admin_role_id()
            ),
            '',
            'customint4 as ownerid, '
                . 'courseid, '
                . 'customint1 as parentcourseid, '
                . 'customint2 as ta_roleid, '
                . 'customint3 as ta_admin_roleid'
        );

        return $enrols;
    }

    /**
     * Checks if there are any valid users that can have a TA-site.
     *
     * Cached using static variables.
     *
     * @param int $courseid
     * @return array    Role assignments for users that can have TA-sites.
     */
    public static function get_tasite_users($courseid) {
        static $retrar;

        if (!isset($retrar[$courseid])) {
            // Allow ta and ta-admins to have ta sites.
            $role = new object();
            $context = context_course::instance($courseid);

            $role->id = self::get_ta_role_id();
            $tas = get_users_from_role_on_context($role, $context);

            $role->id = self::get_ta_admin_role_id();
            $taadmins = get_users_from_role_on_context($role, $context);

            // Merge both roles.
            $tausers = $tas + $taadmins;

            // Then remove any duplicated users.
            $userids = array();
            foreach ($tausers as $index => $tauser) {
                if (in_array($tauser->userid, $userids)) {
                    // Exist already, so unset it.
                    unset($tausers[$index]);
                } else {
                    $userids[] = $tauser->userid;
                }
            }
            $retrar[$courseid] = $tausers;
        }

        return $retrar[$courseid];
    }

    /**
     * Gets all the TA-sites that are associated with a course.
     *
     * @param int $courseid
     * @return array    Enrol_meta instances, indexed-by customint4, with course
     *                  field pointing to relevant {course} row.
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
            $course->defaultgroupingname =
                    groups_get_grouping_name($course->defaultgroupingid);

            $tacourses[$enrol->ownerid] = $course;
        }

        return $tacourses;
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
            if (empty($enrol->customint2)
                    || empty($enrol->customint3)
                    || empty($enrol->customint4)
                    || $enrol->customint2 != self::get_ta_role_id()
                    || $enrol->customint3 != self::get_ta_admin_role_id()) {
                $result = false;
            }
            $cacheistasite[$enrol->id] = $result;
        }
        return $cacheistasite[$enrol->id];
    }

    /**
     * Generates a shortname for the TA-site.
     *
     * @param stdClass $tainfo
     * @param boolean $usefirstname Default is false.
     * @param int $cascade          Used if there is a name collision.
     * @return string
     */
    public static function new_name($tainfo, $usefirstname=false, $cascade=0) {
        global $DB;

        // Would use calculate_course_names but that adds "Copy" to shortname.
        $coursename = $tainfo->parentcourse->shortname . '-'
            . $tainfo->lastname;

        if ($usefirstname) {
            $coursename .= '-' . $tainfo->firstname;
        }

        if ($cascade) {
            // Name conflict, so try appending number.
            $coursename .= '-' . $cascade;
        }

        if ($DB->record_exists('course', array('shortname' => $coursename))) {
            if ($usefirstname) {
                $cascade++;
            }

            return self::new_name($tainfo, true, $cascade);
        }

        return strtoupper($coursename);
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
                            '/course/view.php',
                            array('id' => $tasites[$iid]->id)
                        ),
                        get_string('view_tasite', 'block_ucla_tasites')
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

        if (($tasiteenrol = self::get_tasite_enrol_meta_instance($course->id))
                && self::is_tasite_enrol_meta_instance($tasiteenrol)) {

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
            $accessible = self::check_access($courseid)
                && self::get_tasite_users($courseid)
                && !self::is_tasite($courseid);
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
                        '/blocks/ucla_tasites/index.php',
                        array(
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
                require_capability('moodle/site:config',
                    context_system::instance());
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
            throw new block_ucla_tasites_exception('setuprole',
                self::get_ta_role_shortname());
        }

        if (!self::get_ta_admin_role_id()) {
            throw new block_ucla_tasites_exception('setuprole',
                self::get_ta_role_shortname(true));
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
    public function __construct($errorcode, $a=null) {
        parent::__construct($errorcode, 'block_ucla_tasites', '', $a);
    }
}
