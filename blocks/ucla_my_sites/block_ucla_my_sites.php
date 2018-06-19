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

    private $showclasstab = false;
    private $showcollabtab = false;

    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }

    /**
     * Get user's classes.
     *
     * @param array $classsites
     * @param array $availableterms
     * @param string $paramterm
     *
     * @return array    Returns an array of $classsites and $availableterms.
     */
    private function get_classes($classsites, $availableterms, $paramterm) {
        global $CFG, $USER;

        // Append the list of sites from our stored procedure.
        if (empty($USER->idnumber)) {
            $remotecourses = false;
        } else {
            ucla_require_registrar();
            $spparam = array('uid' => $USER->idnumber, 'term' => $paramterm);
            $remotecourses = registrar_query::run_registrar_query(
                    'ucla_get_user_classes', $spparam
                );
        }

        if ($remotecourses) {
            // In order to translate values returned by get_moodlerole.
            $allroles = get_all_roles();
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
                list($term, $srs) = explode('-', $remotecourse['termsrs']);
                $rrole = $allroles[get_moodlerole($remotecourse['role'],
                    $subjarea)];

                // Remote courses are filtered slightly more liberally.
                if (!term_role_can_view($term, $rrole->shortname)) {
                    continue;
                }

                // Save the term.
                $availableterms[$term] = $term;

                // We're going to format this object to return something similar
                // to what locally-existing courses return.
                $rclass = new stdClass();
                $rclass->url = $remotecourse['url'];
                $rclass->fullname = $remotecourse['course_title'];

                $rreginfo = new stdClass();
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
            if ($firstreg->term != $paramterm) {
                unset($classsites[$k]);
                continue;
            }
        }

        // If $availableterms is not empty, but current term is not there add it
        // so users can switch to the term they have access to.
        if (!empty($availableterms) && !isset($availableterms[$CFG->currentterm])) {
            $availableterms[$CFG->currentterm] = $CFG->currentterm;
        }

        return array($classsites, $availableterms);
    }

    /**
     * Finds collaboration sites.
     *
     * @param array $collaborationsites
     * @param array $categoryids
     *
     * @return array    Collaboration sites broken up by category.
     */
    private function get_collabs($collaborationsites, $categoryids) {
        global $DB;

        if (empty($collaborationsites)) {
            return array();
        }

        // Get course categories from ID's.
        $categories = $DB->get_records_list('course_categories', 'id', $categoryids);

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
        foreach ($categories as &$category) {
            $category->collabsites = array();
            foreach ($collaborationsites as $index => $c) {
                if ($c->category == $category->id) {
                    $category->collabsites[] = $c;
                    // Unset added collab sites so the inner loop goes faster.
                    unset($collaborationsites[$index]);
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

        return $cathierarchy;
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
        global $USER, $CFG, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $templatecontext = array();

        // NOTE: guest have access to "My moodle" for some strange reason, so
        // display a login notice for them.
        if (isguestuser($USER)) {
            $content = array();
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
                $alerts = $OUTPUT->container_start('alert alert-info');
                $alerts .= get_string('changeemail', 'block_ucla_my_sites', $instructor);
                ob_start();
                $mysites->display();
                $alerts .= ob_get_clean();
                $alerts .= $OUTPUT->container_end();
                $templatecontext['alerts'] = $alerts;
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

        $params = $this->get_params();

        // Get Moodle courses enrolled in.
        $courses = enrol_get_my_courses('id, shortname',
            'visible DESC, sortorder ASC');

        list ($classsites, $collaborationsites, $availableterms) =
                $this->get_sites($courses, $params);

        // Setup tabs (sanity check on tabs).
        if (empty($availableterms) && empty($collaborationsites)) {
            // Nothing to show.
            $templatecontext['nolisting'] = true;
        } else if (empty($availableterms) && !empty($collaborationsites)) {
            $params['viewmy'] = 'collab';
        } else if (!empty($availableterms) && empty($collaborationsites)) {
            $params['viewmy'] = 'class';
        }

        $renderer = $PAGE->get_renderer('block_ucla_my_sites');
        if (!empty($availableterms)) {
            $template = array();

            // Display term selector.
            $termoptstr = '';
            // Sort them descending.
            $availableterms = terms_arr_sort($availableterms, true);
            $termoptstr = get_string('term', 'local_ucla') . ': '
                    . $OUTPUT->render($this->make_terms_selector(
                        $availableterms, $params['term']));
            $template['selector'] = html_writer::tag('div', $termoptstr,
                    array('class' => 'termselector'));

            if (!empty($classsites)) {
                // We want to sort things.
                usort($classsites, array(get_class(), 'registrar_course_sort'));

                // If viewing courses from 12S or earlier, give notice about archive
                // server. Only display this info if 'archiveserver' config is set.
                if ((term_cmp_fn($params['term'], '12S') == -1) &&
                        (get_config('local_ucla', 'archiveserver'))) {
                    $content[] = $OUTPUT->notification(get_string('shared_server_archive_notice',
                        'block_ucla_my_sites'), 'notifymessage');
                }

                $template['listing'] = $renderer->class_sites_overview($classsites);
            } else {
                $template['listing'] = get_string('noclassforterm', 'block_ucla_my_sites',
                        ucla_term_to_text($params['term']));
            }

            $template['id'] = 'tabclass';
            $template['name'] = get_string('tabclasstext', 'block_ucla_my_sites');

            if ($params['viewmy'] == 'class') {
                $template['active'] = 'active';
                $template['ariaexpanded'] = 'true';
            }

            $templatecontext['content'][] = $template;
        }

        if (!empty($collaborationsites)) {
            $template = array();

            $sortoptstring = self::make_sort_form($params['sortorder']);
            $templatecontext['selector'] = html_writer::tag('div', $sortoptstring,
                    array('class' => 'sortselector'));

            $template['listing'] = $renderer->collab_sites_overview($collaborationsites,
                    $params['sortorder']);
            $template['id'] = 'tabcollab';
            $template['name'] = get_string('tabcollabtext', 'block_ucla_my_sites');

            if ($params['viewmy'] == 'collab') {
                $template['active'] = 'active';
                $template['ariaexpanded'] = 'true';
            }

            $templatecontext['content'][] = $template;
        }

        $this->content->text = $OUTPUT->render_from_template('block_ucla_my_sites/mysites', $templatecontext);

        return $this->content;
    }

    /**
     * Gets parameters for page display.
     *
     * @return array
     */
    private function get_params() {
        global $CFG, $USER;
        $retval = array('term' => null, 'sortorder' => null, 'viewmy' => null);

        $retval['term'] = optional_param('term', false, PARAM_ALPHANUM);
        if (!ucla_validator('term', $retval['term']) && isset($CFG->currentterm)) {
            $retval['term'] = $CFG->currentterm;
        }

        // Figure out the sort order for the collab sites.
        $newsortorder = optional_param('sortorder', '', PARAM_ALPHA);
        if ($newsortorder !== 'startdate' && $newsortorder !== 'sitename') {
            $newsortorder = '';
        }

        // Get the existing sort order from the user preferences if it exists.
        $retval['sortorder'] = get_user_preferences('mysites_collab_sortorder', 'sitename', $USER);

        // Check if the GET param is valid and different from the existing sortorder.
        if ($newsortorder !== '' && $newsortorder !== $retval['sortorder']) {
            // If so, replace the user preference and the $params['sortorder'] variable.
            set_user_preference('mysites_collab_sortorder', $newsortorder, $USER);
            $retval['sortorder'] = $newsortorder;
        }

        // Get tab user wants to view.
        $retval['viewmy'] = optional_param('viewmy', 'class', PARAM_ALPHA);

        return $retval;
    }

    /**
     * Returns all the sites user is in.
     *
     * @param array $courses
     * @param array $params
     * @return array    Array of class and collaboration sites and available
     *                  terms.
     */
    private function get_sites($courses, $params) {
        global $CFG;

        $classsites = $availableterms = array();
        $collaborationsites = $categoryids = array();
        foreach ($courses as $c) {
            // Don't bother displaying sites that cannot be accessed.
            if (!can_access_course($c, null, '', true)) {
                continue;
            }

            $reginfo = ucla_get_course_info($c->id);
            if (!empty($reginfo)) {
                // Found a class site.
                $courseterm = false;
                foreach ($reginfo as $ri) {
                    $c->reg_info[make_idnumber($ri)] = $ri;
                    $courseterm = $ri->term;
                }

                $availableterms[$courseterm] = $courseterm;

                // Ignore terms that isn't currently selected.
                if ($params['term'] != $courseterm) {
                    continue;
                }

                $c->url = sprintf('%s/course/view.php?id=%d', $CFG->wwwroot,
                    $c->id);

                // We need to toss local information, or at least not
                // display it twice.
                $classsites[] = $c;
            } else {
                // Found a collaboration site.

                // Ignore tasites.
                if ($siteindicator = siteindicator_site::load($c->id)) {
                    $sitetype = $siteindicator->property->type;
                    unset($siteindicator);
                    if (siteindicator_manager::SITE_TYPE_TASITE == $sitetype) {
                        continue;
                    }
                }
                $collaborationsites[] = $c;
                $categoryids[$c->category] = $c->category;
                $this->showcollabtab = true;
            }
        }

        list($classses, $terms) = $this->get_classes($classsites, $availableterms, $params['term']);
        $collaborations = $this->get_collabs($collaborationsites, $categoryids);

        return array($classses, $collaborations, $terms);
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
        $this->title = '';  // We display title in template.
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
        global $PAGE;
        $sortarrs = array('startdate' => array('type' => 'radio', 'name' => 'sortorder', 'value' => 'startdate', 'onclick' => 'this.form.submit();'),
            'sitename' => array('type' => 'radio', 'name' => 'sortorder', 'value' => 'sitename', 'onclick' => 'this.form.submit();'));
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
                html_writer::div(get_string('sortby', 'block_ucla_my_sites') .
                        ': ', '', array('class' => 'sortby')) .
                html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'viewmy', 'value' => 'collab')) .
                html_writer::start_div('radio') .
                html_writer::tag('label',
                        html_writer::tag('input', '', $sortarrs['sitename'])
                        . get_string('sitename', 'block_ucla_my_sites')) .
                html_writer::end_div() .
                html_writer::start_div('radio') .
                html_writer::tag('label',
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
     * @return url_select
     */
    public function make_terms_selector($terms, $default = false) {
        global $PAGE;
        $urls = array();
        $page = $PAGE->url;
        $defaultfound = false;
        foreach ($terms as $term) {
            $thisurl = clone($page);
            $url = $thisurl->out(false, array('viewmy' => 'class', 'term' => $term));
            $urls[$url] = ucla_term_to_text($term);
            if ($default !== false && $default == $term) {
                $default = $url;
                $defaultfound = true;
            }
        }
        if (!$defaultfound) {
            $default = false;
        }
        return new url_select($urls, $default, null);
    }

    /**
     * Used with usort(), sorts a bunch of entries returned via
     * ucla_get_reg_classinfo (CCLE-2832).
     *
     * Sorts via term, subject area, cat_num, sec_num.
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
        $breginfo = $b->reg_info[$brik];

        // Compare terms.
        $termcmp = term_cmp_fn($areginfo->term, $breginfo->term);
        if ($termcmp != 0) {
            return $termcmp * -1;
        }

        // This is an array of fields to compare by.
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
