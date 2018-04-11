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
 * My sites block.
 *
 * @package    block_ucla_my_sites
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

require_once($CFG->dirroot.'/local/ucla/lib.php');

// Need this to build course titles.
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uclacoursecreator/uclacoursecreator.class.php');

// Need this for host-course information.
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uclacourserequestor/lib.php');

// Need this to get site types.
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uclasiteindicator/lib.php');

require_once($CFG->dirroot.'/blocks/ucla_browseby/handlers/browseby.class.php');
require_once($CFG->dirroot.'/blocks/ucla_browseby/handlers/course.class.php');

require_once($CFG->dirroot . '/blocks/ucla_my_sites/alert_form.php');

/**
 * My sites block implementation.
 *
 * @package    block_ucla_my_sites
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_my_sites extends block_base {

    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }

    /**
     * Given a role array or string, will format the roles into a display
     * friendly format.
     *
     * @param mixed $roles      A comma deliminated string or array
     *
     * @return string           If error, then returns false, otherwise returns
     *                          a string of comma deliminated roles
     */
    private function format_roles($roles) {
        if (empty($roles)) {
            return '';
        }

        // If roles is a string, then parse it.
        if (is_string($roles)) {
            // Most likely string from get_user_roles_in_course().
            $roles = explode(',', strip_tags($roles));
        } else if (!is_array($roles)) {
            return false;
        }

        $rolenames = array();
        foreach ($roles as $role) {
            $rolenames[] = trim($role);
        }
        $rolenames = array_unique($rolenames);
        $rolenames = implode(', ', $rolenames);

        return $rolenames;
    }

    /**
     * block contents
     *
     * Get courses that user is currently assigned to and display them either as
     * class or collaboration sites.
     *
     * @return object
     */
    public function get_content() {
        global $USER, $CFG, $OUTPUT, $PAGE, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $content = array();

        // NOTE: guest have access to "My moodle" for some strange reason, so
        // display a login notice for them.
        if (isguestuser($USER)) {
            $content[] = $OUTPUT->box_start('alert alert-warning alert-login');

            $content[] = get_string('loginrequired', 'block_ucla_my_sites');
            $loginbutton = new single_button(new moodle_url($CFG->wwwroot
                                    . '/login/index.php'), get_string('login'));
            $loginbutton->class = 'continuebutton';

            $content[] = $OUTPUT->render($loginbutton);
            $content[] = $OUTPUT->box_end();
            $this->content->text = implode($content);

            return $this->content;
        }

        // Notification if user with instructor role has an alternate email set.
        if (local_ucla_core_edit::is_instructor($USER)) {
            $mysites = new my_sites_form(new moodle_url('/blocks/ucla_my_sites/alert.php'));
            $instructor = get_user_preferences('message_processor_email_email');
            if (!empty($instructor) && (!get_user_preferences('ucla_altemail_noprompt_' . $USER->id))) {
                $content[] = $OUTPUT->container_start('alert alert-info');
                $content[] = get_string('changeemail', 'block_ucla_my_sites', $instructor);
                ob_start();
                $mysites->display();
                $content[] = ob_get_clean();
                $content[] = $OUTPUT->container_end();
            }
        }

        // Uncomment when following ticket is fixed:
        // CCLE-7380 - Fix UCLA support tools.
//        // Render favorite UCLA support tools.
//        if (has_capability('local/ucla_support_tools:view', context_system::instance())) {
//            $render = $PAGE->get_renderer('local_ucla_support_tools');
//            $content[] = $render->mysites_favorites();
//            $content[] = $OUTPUT->single_button
//                    (new moodle_url('/local/ucla_support_tools'),
//                    get_string('mysiteslink', 'local_ucla_support_tools'));
//            // Logging.
//            $PAGE->requires->yui_module('moodle-block_ucla_my_sites-usagelog', 'M.block_ucla_my_sites.usagelog.init', array());
//        }



        // NOTE: this thing currently takes the term in the get param
        // so you may have some strange behavior if this block is not
        // in the my-home page.
        $showterm = optional_param('term', false, PARAM_ALPHANUM);
        if (!ucla_validator('term', $showterm) && isset($CFG->currentterm)) {
            $showterm = $CFG->currentterm;
        }

        // First figure out the sort order for the collab sites.
        // See if there's any GET param trying to change the sort order.
        $newsortorder = optional_param('sortorder', '', PARAM_ALPHA);
        if ($newsortorder !== 'startdate' && $newsortorder !== 'sitename') {
            $newsortorder = '';
        }

        // Get the existing sort order from the user preferences if it exists.
        $sortorder = get_user_preferences('mysites_collab_sortorder', 'sitename', $USER);
        if ($sortorder !== 'startdate' && $sortorder !== 'sitename') {
            $sortorder = 'sitename';
        }

        // Check if the GET param is valid and different from the existing sortorder.
        if ($newsortorder !== '' && $newsortorder !== $sortorder) {
            // If so, replace the user preference and the $sortorder variable.
            set_user_preference('mysites_collab_sortorder', $newsortorder, $USER);
            $sortorder = $newsortorder;
        }

        // Get courses enrolled in.
        $courses = enrol_get_my_courses('id, shortname',
            'visible DESC, sortorder ASC');

        $site = get_site();
        $course = $site; // Just in case we need the old global $course hack.
        if (array_key_exists($site->id, $courses)) {
            unset($courses[$site->id]);
        }

        // Add 'lastaccess' field to course object.
        foreach ($courses as $c) {
            if (isset($USER->lastcourseaccess[$c->id])) {
                $courses[$c->id]->lastaccess = $USER->lastcourseaccess[$c->id];
            } else {
                $courses[$c->id]->lastaccess = 0;
            }
        }

        // These are all the terms in the dropdown.
        $availableterms = array();

        // Filter enrolled classes into srs class sites and collab. sites.
        $classsites = array(); $collaborationsites = array();
        foreach ($courses as $c) {
            // Don't bother displaying sites that cannot be accessed.
            if (!can_access_course($c, null, '', true)) {
                continue;
            }

            $reginfo = ucla_get_course_info($c->id);
            if (!empty($reginfo)) {
                $courseterm = false;
                // TODO optimize here by making another table to reference
                // each course object by its term-srs.
                foreach ($reginfo as $ri) {
                    $c->reg_info[make_idnumber($ri)] = $ri;
                    $courseterm = $ri->term;
                }

                $c->url = sprintf('%s/course/view.php?id=%d', $CFG->wwwroot,
                    $c->id);

                $courseroles = get_user_roles_in_course($USER->id, $c->id);
                $courseroles = explode(',', strip_tags($courseroles));
                $c->roles = $courseroles;

                $availableterms[$courseterm] = $courseterm;

                // We need to toss local information, or at least not
                // display it twice.
                $classsites[] = $c;
            } else {
                // Ignore tasites.
                if ($siteindicator = siteindicator_site::load($c->id)) {
                    $sitetype = $siteindicator->property->type;
                    unset($siteindicator);
                    if (siteindicator_manager::SITE_TYPE_TASITE == $sitetype) {
                        continue;
                    }
                }
                $collaborationsites[] = $c;
            }
        }

        // Append the list of sites from our stored procedure.
        ucla_require_registrar();

        if (empty($USER->idnumber)) {
            $remotecourses = false;
        } else {
            $spparam = array('uid' => $USER->idnumber);
            $remotecourses = registrar_query::run_registrar_query(
                    'ucla_get_user_classes', $spparam
                );
        }

        // In order to translate values returned by get_moodlerole.
        $allroles = get_all_roles();

        if ($remotecourses) {
            foreach ($remotecourses as $remotecourse) {
                // Do not use this object after this, this is because
                // browseby_handler::ignore_course uses an object.
                $objrc = (object) $remotecourse;

                $objrc->activitytype = $objrc->act_type;
                $objrc->course_code = $objrc->catlg_no;
                if (empty($objrc->url)
                        && browseby_handler::ignore_course($objrc)) {
                    continue;
                }

                $subjarea = $remotecourse['subj_area'];

                list($term, $srs) = explode('-',
                    $remotecourse['termsrs']);

                $rrole = $allroles[get_moodlerole($remotecourse['role'],
                    $subjarea)];

                // Remote courses are filtered slightly more liberally.
                if (!term_role_can_view($term, $rrole->shortname)) {
                    continue;
                }

                // Save the term.
                $availableterms[$term] = $term;

                // We're going to format this object to return
                // something similar to what locally-existing courses
                // return.
                $rclass = new stdclass();
                $rclass->url = $remotecourse['url'];
                $rclass->fullname = $remotecourse['course_title'];

                $rreginfo = new stdclass();
                $rreginfo->subj_area = $subjarea;
                $rreginfo->acttype = $remotecourse['act_type'];
                $rreginfo->coursenum = ltrim(trim($remotecourse['catlg_no']),
                    '0');
                $rreginfo->sectnum = ltrim($remotecourse['sect_no'], '0');
                $rreginfo->term = $term;
                $rreginfo->srs = $srs;
                $rreginfo->session_group = $remotecourse['session_group'];
                $rreginfo->course_code = $remotecourse['catlg_no'];
                $rreginfo->hostcourse = 1;

                $rclass->reg_info = array($rreginfo);

                // If this particular course already exists locally, try to find
                // section that user is enrolled in.
                $key = make_idnumber($rreginfo);
                $localexists = false;
                foreach ($classsites as $k => $classsite) {
                    foreach ($classsite->reg_info as &$reginfo) {
                        if ($key == make_idnumber($reginfo)) {
                            $localexists = true;
                            $reginfo->enrolled = 1;
                        }
                    }
                }

                if (!$localexists) {
                    $classsites[] = $rclass;
                }
            }
        }

        // Filter out courses that are not part of the proper term.
        foreach ($classsites as $k => $classsite) {
            $firstreg = reset($classsite->reg_info);
            $courseterm = $firstreg->term;

            if ($showterm && $courseterm != $showterm) {
                unset($classsites[$k]);
                continue;
            }
        }

        if (!empty($classsites)) {
            // We want to sort things, so that it appears classy yo.
            usort($classsites, array(get_class(), 'registrar_course_sort'));

            // If viewing courses from 12S or earlier, give notice about archive
            // server. Only display this info if 'archiveserver' config is set.
            if ((term_cmp_fn($showterm, '12S') == -1) &&
                    (get_config('local_ucla', 'archiveserver'))) {
                $content[] = $OUTPUT->notification(get_string('shared_server_archive_notice',
                    'block_ucla_my_sites'), 'notifymessage');
            }
        }

        // Display term selector.
        $termoptstr = '';
        if (!empty($availableterms)) {
            // Leaves them descending.
            $availableterms = array_reverse(terms_arr_sort($availableterms));

            $termoptstr = get_string('term', 'local_ucla') . ': '
                    . $OUTPUT->render(self::make_terms_selector(
                        $availableterms, $showterm));
        } else {
            $noclasssitesoverride = 'noclasssitesatall';
        }
        $termoptstr = html_writer::tag('div', $termoptstr,
                array('class' => 'termselector'));

        $renderer = $PAGE->get_renderer('block_ucla_my_sites');

        // Display Class sites.
        if (!isset($noclasssitesoverride)) {
            $content[] = html_writer::tag('h3',
                    get_string('classsites', 'block_ucla_my_sites'),
                    array('class' => 'mysitesdivider'));
            $content[] = $termoptstr;
            if (!empty($classsites)) {
                $content[] = $renderer->class_sites_overview($classsites);
            } else {
                $content[] = html_writer::tag('p', get_string('noclasssites',
                        'block_ucla_my_sites', ucla_term_to_text($showterm)));
            }
        }

        // Get course categories from ID's.
        $categoryids = array_map(function($c) {
            return $c->category;
        }, $collaborationsites);
        $categories = $DB->get_records_list('course_categories', 'id', array_unique($categoryids));

        // Holds the ID's of the parent categories to the base categories.
        $parentcategoryids = array();
        // Holds the paths to the categories.
        $categorypaths = array();

        // Extract parent ID's from path.
        foreach ($categories as $category) {
            // Chop off leading slash in the path.
            if ($category->path[0] === '/') {
                $category->path = substr($category->path, 1);
            }
            // Split by slashes and add to base category paths.
            $categorypaths[$category->id] = explode('/', $category->path);
            // Add the ID's in the path to the list of parent category ID's.
            $parentcategoryids = array_merge($categorypaths[$category->id], $parentcategoryids);
        }

        // Get categories for parent ID's that haven't already been looked up.
        $parentcategories = $DB->get_records_list('course_categories', 'id', array_diff(
            array_unique($parentcategoryids), $categoryids
        ));

        // Also add parent paths to combined paths dict.
        foreach ($parentcategories as $category) {
            // Chop off leading slash in the path.
            if ($category->path[0] === '/') {
                $category->path = substr($category->path, 1);
            }
            // Split by slashes and add to all category paths.
            $categorypaths[$category->id] = explode('/', $category->path);
        }

        // From now on we can combine the base and parent categories.
        $categories = array_merge($parentcategories, $categories);

        // Attach collab sites as properties of base categories.
        // Note how each category is passed by reference.
        $tempcollaborationsites = $collaborationsites;
        foreach ($categories as &$category) {
            $category->collabsites = array();
            foreach ($tempcollaborationsites as $index => $c) {
                if ($c->category == $category->id) {
                    $category->collabsites[] = $c;
                    // Unset added collab sites so the inner loop goes faster.
                    unset($tempcollaborationsites[$index]);
                }
            }
        }

        // Build the simple single-level category hierarchy.
        foreach ($categories as &$category) {
            // If the parent is 0, then it is a top level category.
            if ($category->parent == '0') {
                // Create a collabsite property if it doesn't have one.
                if (!isset($category->collabsites)) {
                    $category->collabsites = array();
                }
                // Go through the categories again and find the subcategories of
                // the current category.
                foreach ($categories as $index => &$subcategory) {
                    // We know we've found a subcategory when the current category
                    // shows up in the subcategory's path. Exclude categories that
                    // match the current category's ID and that don't have any
                    // collabsites.
                    if ($subcategory->id !== $category->id
                            && in_array($category->id, $categorypaths[$subcategory->id])
                            && isset($subcategory->collabsites)) {
                        // Add the subcategory's collab sites the current one's collab sites.
                        $category->collabsites = array_merge($category->collabsites, $subcategory->collabsites);
                        // Unset the subcategory so that only the top-level category remains.
                        unset($categories[$index]);
                    }
                }
            }
        }

        // Package the array of top-level categories into a class to replace the
        // full category hierarchy used before.
        $cathierarchy = new stdClass();
        $cathierarchy->children = $categories;

        // Display Collaboration sites.
        if (!empty($collaborationsites)) {
            $sortoptstring = self::make_sort_form($sortorder);
            $sortoptstring = html_writer::tag('div', $sortoptstring,
                    array('class' => 'sortselector'));
            $content[] = $renderer->collab_sites_overview($collaborationsites,
                    $cathierarchy, $sortoptstring, $sortorder);
        } else {
            // If there are no enrolled srs courses in any term and no sites, print msg.
            if (isset($noclasssitesoverride)) {
                $content[] = html_writer::tag('p', get_string('notenrolled',
                        'block_ucla_my_sites'));
            }
        }

        $this->content->text = implode($content);
        return $this->content;
    }

    /**
     * Disallow the block to have a configuration page.
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * Block initialization.
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_ucla_my_sites');
    }

    /**
     * Prevent block from being collapsed.
     *
     * @return bool
     */
    public function instance_can_be_collapsed() {
        return false;
    }

    /**
     * Creates an HTML form allowing the user to change the sorting of collab
     * sites.
     *
     * @param string $sortorder  Used to figure out which radio button to check
     * @return string
     */
    public function make_sort_form($sortorder) {
        global $CFG, $PAGE;
        $sortarrs = array('startdate' => array('type' => 'radio'),
            'sitename' => array('type' => 'radio'));
        // Get the term parameter if it exists.
        $term = optional_param('term', false, PARAM_ALPHANUM);
        $extraparams = '';
        // If it does exist, add it to a hidden form element so it gets submitted.
        if ($term) {
            $extraparams .= html_writer::tag('input', '', array(
                'type' => 'hidden',
                'name' => 'term',
                'value' => $term
            ));
        }

        // Check the correct radio button.
        $sortarrs[$sortorder]['checked'] = 'checked';

        // Create the form itself.
        $radiobuttons = html_writer::div(
                html_writer::div("Sort by: ", '', array('class' => 'title')) .
                html_writer::start_div('radio') .
                html_writer::tag('label',
                        html_writer::tag(
                                'button', '', array(
                                    'class' => 'transparent',
                                    'name' => 'sortorder',
                                    'value' => 'sitename',
                                    'type' => 'submit'
                                )
                        ) .
                        html_writer::tag('input', '', $sortarrs['sitename'])
                        . get_string('sitename', 'block_ucla_my_sites')) .
                html_writer::end_div() .
                html_writer::start_div('radio') .
                html_writer::tag('label',
                        html_writer::tag(
                                'button', '', array(
                                    'class' => 'transparent',
                                    'name' => 'sortorder',
                                    'value' => 'startdate',
                                    'type' => 'submit'
                                )
                        ) .
                        html_writer::tag('input', '', $sortarrs['startdate'])
                        . get_string('startdate', 'block_ucla_my_sites')) .
                html_writer::end_div()
        );
        $form = html_writer::tag('form',
                html_writer::tag('fieldset', $radiobuttons) . $extraparams,
                array('class' => '', 'action' => $PAGE->url, 'method' => 'GET')
        );
        return $form;
    }

    /**
     * Creates the javascript-activated drop-down menu for terms selection.
     *
     * @param  array $terms  Array of terms
     * @param  string $default    Term to select initially.
     *
     * @return url_select  A list of terms that are drop-down-onchange-go
     */
    public function make_terms_selector($terms, $default = false) {
        global $PAGE;
        $urls = array();
        $page = $PAGE->url;
        // Hack to stop debugging message that says that the current
        // term is not a local relative url.
        $defaultfound = false;
        foreach ($terms as $term) {
            $thisurl = clone($page);
            $url = $thisurl->out(false, array('term' => $term));
            $urls[$url] = ucla_term_to_text($term);
            if ($default !== false && $default == $term) {
                $default = $url;
                $defaultfound = true;
            }
        }
        if (!$defaultfound) {
            $default = false;
        }
        return $selects = new url_select($urls, $default);
    }

    /**
     * Used with usort(), sorts a bunch of entries returned via
     * ucla_get_reg_classinfo (CCLE-2832).
     *
     * Sorts via term, subject area, cat_num, sec_num
     *
     * @param object $a
     * @param object $b
     *
     * @return int
     */
    public function registrar_course_sort($a, $b) {
        if (empty($a->reg_info) || empty($b->reg_info)) {
            throw new moodle_exception('cannotcomparecourses');
        }
        // Find the host course.
        $ariarr = array();
        foreach ($a->reg_info as $k => $v) {
            $ariarr[$k] = get_object_vars($v);
        }
        foreach ($b->reg_info as $k => $v) {
            $briarr[$k] = get_object_vars($v);
        }
        $arik = set_find_host_key($ariarr);
        $brik = set_find_host_key($briarr);
        // If they're indeterminate.
        if ($arik === false || $brik === false) {
            throw new moodle_exception(UCLA_REQUESTOR_BADHOST);
        }
        // Fetch the ones that are relevant to compare.
        $areginfo = $a->reg_info[$arik];
        if (isset($a->rolestr)) {
            $areginfo->rolestr = $a->rolestr;
        }

        $breginfo = $b->reg_info[$brik];
        if (isset($b->rolestr)) {
            $breginfo->rolestr = $b->rolestr;
        }
        // Compare terms.
        $termcmp = term_cmp_fn($areginfo->term, $breginfo->term);
        if ($termcmp != 0) {
            return $termcmp * -1;
        }
        // This is an array of fields to compare by after the off-set
        // term and role.
        $comparr = array('subj_area', 'course_code', 'sectnum');
        // Go through each of those fields until we hit an imbalance.
        foreach ($comparr as $field) {
            $anotisset = !isset($areginfo->{$field});
            $bnotisset = !isset($breginfo->{$field});
            if ($anotisset && $bnotisset) {
                continue;
            } else {
                if ($anotisset) {
                    return -1;
                }

                if ($bnotisset) {
                    return 1;
                }
            }
            $strcmpv = strcmp($areginfo->{$field}, $breginfo->{$field});
            if ($strcmpv != 0) {
                return $strcmpv;
            }
        }
        return 0;
    }
}
