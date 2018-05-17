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
 * This file contains general functions for the course format UCLA.
 *
 * Based off the topic format.
 *
 * @package format_ucla
 * @copyright 2012 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/publicprivate/lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasiteindicator/lib.php');
require_once($CFG->dirroot. '/course/format/topics/lib.php');

define('UCLA_FORMAT_DISPLAY_SYLLABUS', -3);
define('UCLA_FORMAT_DISPLAY_ALL', -2);
define('UCLA_FORMAT_DISPLAY_LANDING', -4);
define('UCLA_FORMAT_SITE_INFO', 0);


/**
 * Main class for the Topics course format
 *
 * @package    format_ucla
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ucla extends format_topics {

    /** This is used to get the syllabus.*/
    const UCLA_FORMAT_DISPLAY_SYLLABUS = -3;

    /** This is used to specify display all.*/
    const UCLA_FORMAT_DISPLAY_ALL = -2;

    /** This is used to display landing.*/
    const UCLA_FORMAT_DISPLAY_LANDING = -4;

    /** This is a constant for "Site Info" section number.*/
    const UCLA_FORMAT_SITE_INFO = 0;

    /**
     *  Figures out the section to display. Specific only to the UCLA course format.
     *  Uses a $_GET or $_POST param to figure out what's going on.
     *
     * @param stdClass $course course the user is viewing
     * @param array $courseprefs course preferences
     * @return int       Returns section number that user is viewing
     */
    public function figure_section($course = null, $courseprefs = null) {

        $course = $this->get_course();

        // Set landing page according to "Landing Page by Dates" settings.
        $this->check_landing_page_by_dates();

        // See if user is requesting a permalink section.
        $sectionid = optional_param('sectionid', null, PARAM_INT);
        if (!is_null($sectionid)) {
            // NOTE: use section.
            global $section;
            // This means that a sectionid was explicitly declared, so just use
            // $displaysection, because it has been converted to a section number.
            return $section;
        }

        // See if user is requesting a specific section.
        $section = optional_param('section', null, PARAM_INT);
        if (!is_null($section)) {
            // CCLE-3740 - section === -1 is an alias for section 0 (Site info)
            // This is set by uclatheme renderer so that we can handle this redirect correctly.
            if ($section === -1) {
                $section = 0;
            }
            // This means that a section was explicitly declared.
            return $section;
        }

        // No specific section was requested, so see if user was looking for
        // "Show all" option.
        if (optional_param('show_all', 0, PARAM_BOOL)) {
            return self::UCLA_FORMAT_DISPLAY_ALL;
        }

        // Default to course marker (usually section 0 (site info)) if there are no
        // landing page preference.
        $prefs = $this->get_format_options();

        $landingpage = isset($prefs['landing_page']) ? $prefs['landing_page'] : false;

        if ($landingpage === false) {
            $landingpage = $course->marker;
        }

        return $landingpage;
    }

    /**
     * Make category a required field.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection) {
            $category = $mform->getElement('category');
            if (get_class($category) != 'HTML_QuickForm_Error' && get_class($category) != 'MoodleQuickForm_hidden') {
                $mform->addRule('category', get_string('req_category_error', 'tool_uclasiteindicator'), 'required', null, 'client');
            }
        }
        return $elements;
    }

    /**
     * Gets and determines if the format should display instructors.
     *
     * @return mixed            If course should display instructions, will query
     *                          database for instructor information, else returns
     *                          false.
     */
    public function display_instructors() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');

        // Only display office hours for registrar sites or instructional, tasite
        // or test collaboration sites.
        $sitetype = siteindicator_site::load($this->courseid);
        if (!empty($sitetype) && !in_array($sitetype->property->type,
               array('instruction', 'tasite', 'test'))) {
            return false;
        }

        // Note that untagged collaboration websites will also show the office hours
        // block, but that is okay; they should be tagged anyways.

        // Now get instructors.
        $params = array();
        $params[] = $this->courseid;
        $instructortypes = $CFG->instructor_levels_roles;

        // Map-reduce-able.
        $roles = array();
        foreach ($instructortypes as $instructor) {
            foreach ($instructor as $role) {
                $roles[$role] = $role;
            }
        }

        // Get the people with designated roles.
        try {
            if (!isset($roles) || empty($roles)) {
                // Hardcoded defaults.
                $roles = array(
                    'editingteacher',
                    'teacher'
                );
            }

            list($inroles, $newparams) = $DB->get_in_or_equal($roles);
            $additionalsql = ' AND r.shortname ' . $inroles;
            $params = array_merge($params, $newparams);
        } catch (coding_exception $e) {
            // Coding exception...
            $additionalsql = '';
        }

        // Join on office hours info as well to get all information in one query.
        $sql = "
            SELECT DISTINCT
                CONCAT(u.id, '-', r.id) as recordset_id,
                u.id,
                u.firstname,
                u.lastname,
                u.email,
                u.maildisplay,
                u.url,
                u.lastnamephonetic,
                u.firstnamephonetic,
                u.middlename,
                u.alternatename,
                u.idnumber,
                r.shortname,
                oh.officelocation,
                oh.officehours,
                oh.email as officeemail,
                oh.phone
            FROM {course} c
            JOIN {context} ct
                ON (ct.instanceid = c.id AND ct.contextlevel= ".CONTEXT_COURSE.")
            JOIN {role_assignments} ra
                ON (ra.contextid = ct.id)
            JOIN {role} r
                ON (ra.roleid = r.id)
            JOIN {user} u
                ON (u.id = ra.userid)
            LEFT JOIN {ucla_officehours} oh
                ON (u.id = oh.userid AND c.id = oh.courseid)
            WHERE
                c.id = ?
                $additionalsql
            ORDER BY u.lastname, u.firstname";

        $instructors = $DB->get_records_sql($sql, $params);

        // Check that instructors are not suspended.
        $suspendedids = get_suspended_userids(
                context_course::instance($this->courseid), true);
        foreach ($instructors as $k => $instructor) {
            if (in_array($instructor->id, $suspendedids)) {
                unset($instructors[$k]);
            }
        }

        return $instructors;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                    array('context' => context_course::instance($this->courseid)));
        } else if ($section->section == 0) {
             return (string)new lang_string('section0name', 'format_ucla', null, 'en');
        } else {
            return new lang_string('week', '', null, 'en').' '.$section->section;
        }
    }

    /**
     * Course-specific information to be output immediately below content on any course page
     *
     * See {@link format_base::course_header()} for usage
     *
     * @return null|renderable null for no output or object with data for plugin renderer
     */
    public function course_content_footer() {
        global $PAGE;

        // Load format utilities.
        $PAGE->requires->yui_module('moodle-format_ucla-utils',
            'M.format_ucla.utils.init',
            array(array()));

        $PAGE->requires->strings_for_js(
                array(
                    'collapsedshow',
                    'collapsedhide',
                    ),
                'format_ucla'
                );
        if ($PAGE->user_is_editing()) {
            // If user is editing, load public/private plugin.
            $PAGE->requires->yui_module('moodle-local_publicprivate-util', 'M.local_publicprivate.init',
                    array(array('courseid' => $this->get_courseid())));

            $PAGE->requires->strings_for_js(
                array(
                    'publicprivatemakeprivate',
                    'publicprivatemakepublic',
                    'publicprivategroupingname'
                    ),
                'local_publicprivate'
                );
        }

        return parent::course_content_footer();
    }

    /**
     * Defines custom UCLA format options like 'landing_page'.  We can retrieve
     * these options as properties of the course object like so:
     *
     *      course_get_format($courseorid)->get_course()->landing_page
     *
     * @param bool $foreditform if we're going to retrieve options for a form
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        global $COURSE;
        $options = parent::course_format_options($foreditform);

        static $uclaoptions = false;
        $iscollabsite = is_collab_site($COURSE);

        if ($uclaoptions === false) {
            $uclaoptions = array(
                'landing_page' => array(
                    'default' => 0,
                    'type' => PARAM_ALPHANUMEXT
                ),
                'hide_autogenerated_content' => array(
                    'default' => false,
                    'type' => PARAM_BOOL
                ),
                // SSC-1205 - For "Landing Page by Dates" functionality.
                'enable_landingpage_by_dates' => array(
                    'default' => false,
                    'type' => PARAM_BOOL
                ),
                'coursedownload' => array(
                    'default' => true,
                    'type' => PARAM_BOOL
                ),
            );
            if (!$iscollabsite) {
                $uclaoptions['createtasite'] = array(
                    'default' => true,
                    'type' => PARAM_BOOL
                );
                $uclaoptions['enableoutoftermmessage'] = array(
                    'default' => true,
                    'type' => PARAM_BOOL
                );
                $uclaoptions['myuclagradelinkredirect'] = array(
                    'default' => false,
                    'type' => PARAM_BOOL
                );
            }
        }

        // Define preferences for course edit form.  Define them as 'hidden',
        // since modify_sections already provides this functionality.
        if ($foreditform) {
            // Need to set "label" to the default value, because hidden fields
            // will use it as its value (see CCLE-4322).
            $uclaoptionsedit = array(
                'landing_page' => array(
                    'label' => 0,
                    'element_type' => 'hidden'
                ),
                'hide_autogenerated_content' => array(
                    'label' => false,
                    'element_type' => 'hidden'
                ),
                'enable_landingpage_by_dates' => array(
                    'label' => false,
                    'element_type' => 'hidden'
                ),
                'coursedownload' => array(
                    'label' => get_string('coursedownload', 'format_ucla'),
                    'help' => 'coursedownload',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => get_string('yes'),
                            0 => get_string('no')
                        )
                    )
                )
            );

            if (!$iscollabsite) {
                $uclaoptionsedit['createtasite'] = array(
                    'label' => get_string('createtasite', 'format_ucla'),
                    'help' => 'createtasite',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => get_string('yes'),
                            0 => get_string('no')
                        )
                    )
                );
                $uclaoptionsedit['enableoutoftermmessage'] = array(
                    'label' => get_string('enableoutoftermmessage', 'format_ucla'),
                    'help' => 'enableoutoftermmessage',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => get_string('yes'),
                            0 => get_string('no')
                        )
                    )
                );
                $uclaoptionsedit['myuclagradelinkredirect'] = array(
                    'label' => get_string('myuclagradelinkredirect', 'format_ucla'),
                    'help' => 'myuclagradelinkredirect',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => get_string('gradebookmyucla', 'format_ucla'),
                            0 => get_string('gradebookccle', 'format_ucla')
                        )
                    )
                );
            }

            $uclaoptions = array_merge_recursive($uclaoptions, $uclaoptionsedit);
        }

        $options = array_merge_recursive($options, $uclaoptions);

        // Hide the course display option, since we always want it to be
        // COURSE_DISPLAY_MULTIPAGE.
        $options['coursedisplay']['element_type'] = 'hidden';

        return $options;
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        // All UCLA format courses use multipage display (one page per section),
        // except for the "Show all" page, which is a single page for all the sections.
        $url = new moodle_url('/course/view.php', array('id' => $this->get_courseid()));

        // Variable $sectionno denotes the section we want to return to.
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        // Variable $sr denotes the page we want to return to, either the same as $sectionno
        // or 0 / empty for "Show all".
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }

        if (isset($sectionno)) {
            // There is an ambiguous case if both $sr and $sectionno are empty:
            // go to site info (section 0) on the "Show all" page, or the site info page alone?
            // We interpret it as going to the site info page alone.
            if (empty($sr) && !empty($sectionno)) {
                // This section is needed for navigating back through breadcrumbs.
                if (!empty($options['navigation'])) {
                    $url->param('section', $sectionno);
                    return $url;
                }
                // Return to "Show all" page.
                $url->param('show_all', 1);
                $url->set_anchor('section-'.$sectionno);
            } else {
                $url->param('section', $sectionno);
            }
        }

        return $url;
    }

    /**
     * Allows course format to execute code on moodle_page::set_course()
     *
     * Checks that sections names are written to DB.
     *
     * @param moodle_page $page instance of page calling set_course
     */
    public function page_set_course(moodle_page $page) {
        parent::page_set_course($page);
        global $DB;

        $sections = $this->get_sections();

        foreach ($sections as $section) {
            if ($section->name == null) {
                $s = new stdClass();
                $s->id = $section->id;
                $s->name = $this->get_section_name($section);
                $DB->update_record('course_sections', $s);
            }
        }

        // Change the default for the course display to show one section per page.
        $course = $this->get_course();
        if ($course->coursedisplay == COURSE_DISPLAY_SINGLEPAGE) {
            $course->coursedisplay = COURSE_DISPLAY_MULTIPAGE;
            $DB->update_record('course', $course, true);
        }
    }

    /**
     * Extra form validation.
     *
     * Note that we still need to have this method here, even though we declare
     * the category to be a required field in create_edit_form_element for some
     * reason.
     *
     * @param array $data from form
     * @param array $files
     * @param array $errors already accumulated
     * @return array
     */
    public function edit_form_validation($data, $files, $errors) {
        $formaterrors = array();
        if (empty($data['category'])) {
            $formaterrors['category'] = get_string('req_category_error', 'tool_uclasiteindicator');
        }
        return $formaterrors;
    }

    /**
     * SSC-1205 - Landing Page by Dates.
     *
     * Check whether or not a given course has the appropriate set landingpage,
     * given appropriate date ranges and section numbers.
     */
    public function check_landing_page_by_dates() {
        global $DB;
        $course = $this->get_course();
        $format = course_get_format($course);
        $prefs = $format->get_format_options();
        $courseid = $course->id;
        $optionlpd = $prefs['enable_landingpage_by_dates'];
        $lpd = isset($optionlpd) ? $optionlpd : false;

        if ($lpd && $courseid != null) {
            // Create and update cache.
            $cachedb = cache::make('block_ucla_modify_coursemenu', 'landingpagebydatesdb');
            $cachedisplay = cache::make('block_ucla_modify_coursemenu', 'landingpagebydatesdisplay');

            // Get calls return false when the value is not available or is unitialized.
            // $cachedb is write and read locked, but $cachedisplay is neither.
            $displaytime = time();
            $cachedbtime = $cachedb->get($courseid);
            $cachedisplaytime = $cachedisplay->get($courseid);

            // If get call to cache returns false then we will still query and update.
            if (!$cachedbtime || !$cachedisplaytime || $cachedisplaytime <= $cachedbtime) {
                $sql = "SELECT sectionid, timestart, timeend
                          FROM {ucla_modify_coursemenu} AS mc
                         WHERE mc.courseid = $courseid";
                $records = $DB->get_records_sql($sql);
                // Cache our records and update display time.
                $cachedisplay->set_many(array(
                    $courseid => $displaytime,
                    $courseid . 'records' => $records
                ));
            }

            // Check cached data and perform filtering.
            $records = $cachedisplay->get($courseid . 'records');
            $format = course_get_format($course);
            $newlandingpage = $this->determine_landing_page_by_dates_section($records);
            $prefs = $format->get_format_options();
            if ($newlandingpage) {
                if ($prefs['landing_page'] != $newlandingpage) {
                    $format->update_course_format_options(array('landing_page' => $newlandingpage));
                }
            } else if ($prefs['landing_page'] != UCLA_FORMAT_SITE_INFO) { // Default to "Site Info" as landing page.
                $format->update_course_format_options(array('landing_page' => UCLA_FORMAT_SITE_INFO));
            }
        }
    }

    /**
     * SSC-1205 - Landing Page by Dates.
     *
     * Helper function for check_landing_page_by_dates().
     * It looks at our cached records and determines which section
     * should be the landing page for the current site.
     *
     * @param array $records from our db for a "Landing Page by Dates" course
     * @return int
     */
    public function determine_landing_page_by_dates_section($records) {
        $section = null;
        $currentTime = time();
        // Looks for date ranges that current time falls in. Make sure timeend is not null.
        foreach ($records as $obj) {
            if ($obj->timeend && $currentTime >= $obj->timestart && $currentTime <= $obj->timeend) {
                $section = $obj->sectionid;
            }
        }
        // Alternate loop to look for the latest valid start date that is not a date range.
        if (!$section) {
            $temp = array();
            // Find all potential sections that satisfy the criteria of not being a date range
            // and having a start time before our current time.
            foreach ($records as $obj) {
                if (is_null($obj->timeend) && $obj->timestart <= $currentTime) {
                    $temp[] = $obj;
                }
            }
            // Sort so that the first element of our array is the latest valid start date.
            usort($temp, function($a, $b)
            {
                return $a->timestart < $b->timestart;
            });
            $section = count($temp) > 0 ? $temp[0]->sectionid : null;
        }
        return $section;
    }
}

/**
 * Used to display the course structure for a course where format=topic
 *
 * This is called automatically by {@link load_course()} if the current course
 * format = ucla.
 *
 * @param navigation_node $navigation Description
 * @param stdClass $course Description
 * @param navigation_node $coursenode Description
 * @return bool Returns true
 */
function callback_ucla_load_content(&$navigation, $course, $coursenode) {
    global $DB, $CFG;

    // Sort of a dirty hack, but this so far is the best way to manipulate the
    // navbar since these callbacks are called before the format is included.

    // This is to prevent further diving and incorrect associations in the
    // navigation bar.
    $logicallimitations = array('subjarea', 'division');

    $subjareanode = null;
    $divisionnode = null;

    $division = false;
    $subjarea = false;

    // Browse-by hooks for categories.
    if (block_instance('ucla_browseby')) {
        // Term is needed for browseby.
        $courseinfos = ucla_map_courseid_to_termsrses($course->id);
        $parentnode =& $coursenode->parent;

        if ($courseinfos) {
            $first = reset($courseinfos);
            $term = $first->term;

            // Find the nodes that represent the division and subject areas.
            while ($parentnode->type == navigation_node::TYPE_CATEGORY) {
                if ($subjareanode == null) {
                    $subjarea = $DB->get_field('ucla_reg_subjectarea', 'subjarea',
                        array('subj_area_full' => $parentnode->text));

                    if ($subjarea) {
                        $subjareanode =& $parentnode;
                    }
                } else if ($divisionnode == null) {
                    $division = $DB->get_field('ucla_reg_division', 'code',
                        array('fullname' => $parentnode->text));

                    if ($division) {
                        $divisionnode =& $parentnode;
                        break;
                    }
                }

                $parentnode =& $parentnode->parent;
            }

            // Replace the link in the navbar for subject areas and divisions
            // with respective browseby links.
            if ($divisionnode != null) {
                $divisionnode->action = new moodle_url(
                        '/blocks/ucla_browseby/view.php',
                        array(
                            'type' => 'subjarea',
                            'division' => $division,
                            'term' => $term
                        )
                    );
            }

            if ($subjareanode != null) {
                $subjareaparams = array(
                        'type' => 'course',
                        'subjarea' => $subjarea,
                        'term' => $term
                    );

                if ($division) {
                    $subjareaparams['division'] = $division;
                }

                $subjareanode->action = new moodle_url(
                    '/blocks/ucla_browseby/view.php',
                    $subjareaparams
                );
            }
        } else if ($siteindicator = siteindicator_site::load($course->id)) {
            // Use browse-by collab functions to find collab categories.
            $bbhf = new browseby_handler_factory();
            $browsebycollab = $bbhf->get_type_handler('collab');

            $collabcat = $browsebycollab->get_collaboration_category();
            siteindicator_manager::filter_category_tree($collabcat);

            $collabcatparams = array(
                'type' => 'collab'
            );

            while ($parentnode->type == navigation_node::TYPE_CATEGORY) {
                // Extract out the category id.
                if ($parentnode->action->param('id')) {
                    $catid = $parentnode->action->param('id');

                    // See if the catid is within an accepted set of
                    // collaboration categories.
                    if ($browsebycollab->find_category(
                                $catid,
                                $collabcat->categories,
                                'id'
                            )) {

                        $collabcatparams['category'] = $catid;
                        $parentnode->action = new moodle_url(
                                '/blocks/ucla_browseby/view.php',
                                $collabcatparams
                            );
                    }
                }

                $parentnode =& $parentnode->parent;
            }
        }
    }

    return $navigation->load_generic_course_sections($course, $coursenode, 'ucla');
}
