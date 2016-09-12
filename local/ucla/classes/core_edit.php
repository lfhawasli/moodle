<?php
// This file is part of Moodle - http://moodle.org/
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
 * Class to contain miscellaneous methods used in Moodle core edits.
 *
 * @package local_ucla
 * @copyright 2014 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class file.
 *
 * @package local_ucla
 * @copyright 2014 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_core_edit {
    /**
     * Caches setting for local_ucla|handlepreferredname.
     * @var boolean
     */
    public static $handlepreferredname = null;

    /**
     * Caches subject area lookups.
     * @var array
     */
    public static $profilecategorycached = array();

    /**
     * Stores collaboration site listing.
     * @var array
     */
    public static $profilecollabsites = array();

    /**
     * Stores Registrar site listing.
     * @var array
     */
    public static $profilesrscourses = array();

    /**
     * If set, will display a link to view all courses.
     * @var string
     */
    public static $profileviewmore = '';

    /**
     * Returns an array of users who only the ability to grade only at the course
     * context. Moodle normally displays all users who have the ability to grade,
     * even those inherited from the category or site context.
     *
     * @param object $course
     * @param string $sort          Optional.
     * @param string $capability    Defaults to 'mod/assign:grade'.
     * @return array
     */
    public static function get_course_graders($course, $sort = '', $capability = 'mod/assign:grade') {
        global $CFG;
        require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
        $ppcourse = PublicPrivate_Course::build($course);
        $groupid = 0;
        if ($ppcourse->is_activated()) {
            $groupid = $ppcourse->get_group();
        }
        return get_users_by_capability(context_course::instance($course->id),
                $capability, '', $sort, '', '', $groupid, '', false);
    }

    /**
     * Handles logic of how to display 'alternativename' as preferred name.
     *
     * See CCLE-4521 - Handle "preferred name"
     *
     * @param object $user
     * @param boolean $override  If true, will return name formatting for hybrid
     *                           display. By reference, because if we are
     *                           handling preferred name, then need to turn off
     *                           override flag from fullname function.
     * @return string            Returns name string to use in template.
     */
    public static function get_fullnamedisplay($user, &$override) {

        // Be quick to exit, so that Behat tests aren't slowed down.
        if (!isset(self::$handlepreferredname)) {
            self::$handlepreferredname = get_config('local_ucla', 'handlepreferredname');
        }
        if (empty(self::$handlepreferredname)) {
            return false;
        }

        $forcehybridmode = $override;
        $override = false;   // Setting is on, so disable override handling.

        // See if we even need to handle the logic of figuring out preferred name.
        if (empty($user->alternatename) && !empty($user->middlename) && !empty($forcehybridmode)) {
            return 'lastname, firstname middlename';
        } else if (empty($user->alternatename)) {
            return false;
        }

        // User has alternative name, so see if viewer should see it.
        $fullnamedisplay = 'lastname, alternatename';
        // Do we need to display legal first name?
        if (!empty($forcehybridmode)) {
            // Display middle name, if needed.
            $firstname = empty($user->middlename) ? 'firstname' : 'firstname middlename';
            $fullnamedisplay .= " ($firstname)";
        }

        return $fullnamedisplay;
    }

    /**
     * Add the course that we're currently on to the list.
     *
     * @param mixed $courseinfo If false, then course is collab, else has term,
     *                          subject area, catalog number, and section.
     * @param string $cfullname
     */
    public static function profile_add_current_course_to_list($courseinfo, $cfullname) {
        if (!empty($courseinfo)) {
            list($term, $subjarearea, $catalognum, $section) = $courseinfo;
            self::$profilesrscourses[$term][$subjarearea][$catalognum][$section] = $cfullname;
        } else {
            self::$profilecollabsites[$cfullname] = $cfullname;
        }
    }

    /**
     * Add a different course than the one we're currently on to the list.
     *
     * @param mixed $courseinfo If false, then course is collab, else has term,
     *                          subject area, catalog number, and section.
     * @param moodle_url $url
     * @param string $cfullname     Full name for course context.
     * @param array $linkattributes
     */
    public static function profile_add_other_course_to_list($courseinfo, $url, $cfullname, $linkattributes) {
        if (!empty($courseinfo)) {
            list($term, $subjarearea, $catalognum, $section) = $courseinfo;
            self::$profilesrscourses[$term][$subjarearea][$catalognum][$section]
                    = html_writer::link($url, $cfullname, $linkattributes);
        } else {
            self::$profilecollabsites[$cfullname] = html_writer::link($url, $cfullname, $linkattributes);
        }
    }

    /**
     * Displays Registrar courses ordered by term, subject area, and course name.
     * Displays collaboration sites into alphabetical list.
     */
    public static function profile_display_formatted_courses() {
        // Sort the courses by term, department, and course name.
        self::profile_sort_courses();

        // Display SRS courses.
        foreach (self::$profilesrscourses as $term => $department) {
            echo html_writer::tag('dd', $term);
            foreach ($department as $departmentname => $courses) {
                echo html_writer::start_tag('dd');
                echo html_writer::start_tag('ul');
                echo html_writer::tag('span', $departmentname);
                echo html_writer::alist($courses, array(), 'ul');
                echo html_writer::end_tag('ul');
                echo html_writer::end_tag('dd');
            }
        }

        // Display collab sites.
        if (!empty(self::$profilecollabsites)) {
            echo html_writer::tag('dd', get_string('collab_viewall', 'block_ucla_browseby'));
            echo html_writer::start_tag('dd');
            echo html_writer::alist(self::$profilecollabsites, array(), 'ul');
            echo html_writer::end_tag('dd');
        }

        // Display a link to view more courses if all courses are not shown.
        if (!empty(self::$profileviewmore)) {
            echo html_writer::tag('dd', self::$profileviewmore);
        }
    }

    /**
     * Given a user, return true if they have an instructing role in the system.
     *
     * See CCLE-5181
     *
     * @param object $user
     * Returns if the user has instructor roles or not.
     * @return boolean
     */
    public static function is_instructor($user) {
        global $DB, $CFG;
        $roles = array();
        $keys = array_keys($CFG->instructor_levels_roles);
        foreach ($keys as $key) {
            $roles = array_merge($roles, $CFG->instructor_levels_roles[$key]);
        }
        list($shortnamesql, $params) = $DB->get_in_or_equal($roles);
        $sql = "SELECT DISTINCT u.id
                           FROM {user} u
                           JOIN {role_assignments} ra ON (ra.userid=u.id)
                           JOIN {role} r ON (r.id=ra.roleid)
                          WHERE r.shortname $shortnamesql
                                AND u.id = $user->id";
        return $DB->record_exists_sql($sql, $params);
    }
    /**
     * Display complete use details on profile pages.
     *
     * See CCLE-5147.
     *
     * @param object $user
     */
    public static function profile_display_help_request($user) {
        $emailstop = array(0 => get_string('emailenable'), 1 => get_string('emaildisable'));
        $maildigest = array(0 => get_string('emaildigestoff'), 1 => get_string('emaildigestcomplete'), 2 =>get_string('emaildigestsubjects'));
        $maildisplay = array(0 => get_string('emaildisplayno'), 1 => get_string('emaildisplayyes'), 2 => get_string('emaildisplaycourse'));
        $autosubscribe = array(0 => get_string('autosubscribeno'), 1 => get_string('autosubscribeyes'));
        $trackforums = array(0 => get_string('trackforumsno'), 1 => get_string('trackforumsyes'));
        $htmleditor = array('default' => get_string('defaulteditor'));
        $editors = editors_get_enabled();
        foreach ($editors as $name => $editor) {
            $htmleditor[$name] = get_string('pluginname', 'editor_' . $name);
        }
        $texteditingpref = get_user_preferences('htmleditor', null, $user);
        if (empty($texteditingpref)) {
            $texteditingpref = 'default';
        }
        $table = new html_table();
        $table->head = array('Field', 'User Information');
        $table->data = array(
            array('Moodle ' . get_string('userid', 'grades'), $user->id),
            array('Authentication method', $user->auth),
            array(get_string('username'), $user->username),
            array(get_string('fullnameuser'), fullname($user, true)),
            array(get_string('alternatename'), $user->alternatename),
            array('Email status', $emailstop[$user->emailstop]),
            array(get_string('lastlogin'), userdate($user->lastlogin)."&nbsp; (".format_time(time() - $user->lastlogin).")"), 
            array(get_string('emaildisplay'), $maildisplay[$user->maildisplay]),
            array(get_string('emaildigest'),$maildigest[$user->maildigest]),
            array(get_string('textediting'), $htmleditor[$texteditingpref]),
            array(get_string('autosubscribe'), $autosubscribe[$user->autosubscribe]),
            array(get_string('trackforums'), $trackforums[$user->trackforums]),
            array('Time modified', userdate($user->timemodified)."&nbsp; (".format_time(time() - $user->timemodified).")"), 
        );
        echo html_writer::table($table);
    }

    /**
     * Given a course ID, retrieve the course's number, term, and department.
     * 
     * @param int $courseid
     */
    public static function profile_get_course_info($courseid) {
        global $DB;

        // Ignore if the course we are processing is a collab site.
        if (is_collab_site($courseid)) {
            return false;
        }

        $term = $subjectarea = $catalognum = null;
        $courseinfos = ucla_get_course_info($courseid);
        foreach ($courseinfos as $courseinfo) {
            if ($courseinfo->hostcourse == 1) {
                $term       = $courseinfo->term;
                $catalognum = $courseinfo->crsidx;
                $section    = $courseinfo->classidx;
                // Check if the category was already queried for before doing another DB lookup.
                if (!isset(self::$profilecategorycached[$courseinfo->subj_area])) {
                    $subjareafull = $DB->get_field('ucla_reg_subjectarea', 'subj_area_full',
                            array('subjarea' => $courseinfo->subj_area));
                    self::$profilecategorycached[$courseinfo->subj_area] = ucla_format_name($subjareafull);
                }
                $subjectarea = self::$profilecategorycached[$courseinfo->subj_area];
                break;
            }
        }

        return array($term, $subjectarea, $catalognum, $section);
    }

    /**
     * Sorts the $profilesrscourses and $profilecollabsites arrays.
     */
    public static function profile_sort_courses() {
        // Sort the terms in reverse chronological order.
        $sortedcourses = terms_arr_sort(array_keys(self::$profilesrscourses), true);
        foreach (self::$profilesrscourses as $term => $subjectarea) {
            $sortedcourses[$term] = $subjectarea;
        }
        // Convert the terms into a nice format.
        foreach ($sortedcourses as $term => $subjectarea) {
            $sortedcourses[ucla_term_to_text($term)] = $subjectarea;
            unset($sortedcourses[$term]);
        }

        // Sort by subject area, course number, and section.
        foreach ($sortedcourses as $term => &$subjectarea) {
            ksort($subjectarea);
            foreach ($subjectarea as $index => $courses) {
                ksort($courses);                
                // Sort by section and then flatten array.
                $termsubjectcourses = array();
                foreach ($courses as &$sections) {
                    ksort($sections);
                    foreach ($sections as $section) {
                        $termsubjectcourses[] = $section;
                    }
                }
                $sortedcourses[$term][$index] = $termsubjectcourses;
            }
        }
        self::$profilesrscourses = $sortedcourses;

        // Sort collaboration sites via title.
        ksort(self::$profilecollabsites);
    }

    /**
     * Function will return true if theme is uclashared/uclasharedcourse.
     *
     * @return boolean
     */
    public static function using_ucla_theme() {
        global $CFG;
        // Checks if $CFG->theme is set.
        if (isset($CFG->theme)) {
            // Checks if theme is uclashared/uclasharedcourse.
            if ($CFG->theme == 'uclashared' ||
                $CFG->theme == 'uclasharedcourse') {
                return true;
            }
        }
        return false;
    }
}
