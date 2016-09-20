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
 * My sites block
 *
 * Based off of blocks/course_overview.
 *
 * @package   blocks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

class block_ucla_my_sites extends block_base {
    private $cache = array();

    /**
     * block initializations
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_ucla_my_sites');
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

        // Include module.js.
        $PAGE->requires->jquery();
        $PAGE->requires->js('/blocks/ucla_my_sites/module.js', true);

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

        // Render favorite UCLA support tools.
        if (has_capability('local/ucla_support_tools:view', context_system::instance())) {
            $render = $PAGE->get_renderer('local_ucla_support_tools');
            $content[] = $render->mysites_favorites();
            $content[] = $OUTPUT->single_button
                    (new moodle_url('/local/ucla_support_tools'),
                    get_string('mysiteslink', 'local_ucla_support_tools'));
            // Logging.
            $PAGE->requires->yui_module('moodle-block_ucla_my_sites-usagelog', 'M.block_ucla_my_sites.usagelog.init', array());
        }

        // NOTE: this thing currently takes the term in the get param
        // so you may have some strange behavior if this block is not
        // in the my-home page.
        $showterm = optional_param('term', false, PARAM_RAW);
        if (!$showterm && isset($CFG->currentterm)) {
            $showterm = $CFG->currentterm;
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

        // Check for Course Overview alerts/notifications.
        $overviews = $this->get_overviews($courses);

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

                // If this particular course already exists locally, then
                // Overwrite the roles with the registrar's data.
                $key = make_idnumber($rreginfo);
                $localexists = false;
                foreach ($classsites as $k => $classsite) {
                    foreach ($classsite->reg_info as $reginfo) {
                        if ($key == make_idnumber($reginfo)) {
                            if (!is_array($classsites[$k]->roles)) {
                                $classsites[$k]->roles = array();
                            }
                            $classsites[$k]->roles[] = $rrole->name;
                            $localexists = true;
                        }
                    }
                }

                if (!$localexists) {
                    $rclass->roles = $rrole->name;
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

        // Figure out what to display in the Roles column.
        foreach ($classsites as $k => $classsite) {
            $classsite->rolestr = $this->format_roles($classsite->roles);
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

        // Add a collapse/expand icon if any class sites have notifications.
        $collapser = '';
        foreach ($overviews as $id => $value) {
            foreach ($classsites as $classsite) {
                if (property_exists($classsite, 'id') && $classsite->id == $id) {
                    $collapser = html_writer::tag('a', html_writer::tag('img', '', array(
                        'src' => new moodle_url('/blocks/ucla_my_sites/img/expanded.svg'),
                        'class' => 'class_course_expand')), array('href' => '#/'));
                    break;
                }
            }
            if ($collapser != '') {
                break;
            }
        }

        $renderer = $PAGE->get_renderer('block_ucla_my_sites');

        // Display Class sites.
        if (!isset($noclasssitesoverride)) {
            $content[] = html_writer::tag('h3',
                    get_string('classsites', 'block_ucla_my_sites').$collapser,
                    array('class' => 'mysitesdivider'));
            $content[] = $termoptstr;
            if (!empty($classsites)) {
                $content[] = $renderer->class_sites_overview($classsites, $overviews);
            } else {
                $content[] = html_writer::tag('p', get_string('noclasssites',
                        'block_ucla_my_sites', ucla_term_to_text($showterm)));
            }
        }

        // Display Collaboration sites.
        if (!empty($collaborationsites)) {
            $content[] = $renderer->collab_sites_overview($collaborationsites, $overviews);
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
     * Display overview for courses
     *
     * @param array $courses courses for which overview needs to be shown
     * @return array html overview
     */
    private function get_overviews($courses) {
        $htmlarray = array();

        if ($modules = get_plugin_list_with_function('mod', 'print_overview')) {

            // Split courses list into batches with no more than MAX_MODINFO_CACHE_SIZE courses in one batch.
            // Otherwise we exceed the cache limit in get_fast_modinfo() and rebuild it too often.
            if (defined('MAX_MODINFO_CACHE_SIZE') && MAX_MODINFO_CACHE_SIZE > 0 && count($courses) > MAX_MODINFO_CACHE_SIZE) {
                $batches = array_chunk($courses, MAX_MODINFO_CACHE_SIZE, true);
            } else {
                $batches = array($courses);
            }

            foreach ($batches as $courses) {
                foreach ($modules as $fname) {
                    try {
                        $fname($courses, $htmlarray);
                    } catch (Exception $ex) {
                        // Ignore. Related to CCLE-6291. Course module belongs
                        // to non-existent section.
                    }
                }
            }
        }

        return $htmlarray;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }
    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }

    /**
     * Creates the javascript-activated drop-down menu for terms selection.
     *
     * @param  $terms  Array of terms
     * @param  $default    Term to select initially.
     * @return url_select  A list of terms that are drop-down-onchange-go
     **/
    public function make_terms_selector($terms, $default=false) {
        global $CFG, $PAGE;
        $urls = array();
        $page = $PAGE->url;
        // Hack to stop debugging message that says that the current
        // term is not a local relative url.
        $defaultfound = false;
        foreach ($terms as $term) {
            $thisurl = clone($page);
            $thisurl->param('term', $term);
            $url = $thisurl->out(false);
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
     *  Used with usort(), sorts a bunch of entries returned via
     *  ucla_get_reg_classinfo.
     *    https://jira.ats.ucla.edu:8443/browse/CCLE-2832
     *  Sorts via term, subject area, cat_num, sec_num
     **/
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
        // Compare roles.
        $rolenamecmp = strcmp($areginfo->rolestr, $breginfo->rolestr);
        if ($rolenamecmp != 0) {
            return $rolenamecmp;
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
            // debugging('no roles');
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
     * Prevent block from being collapsed.
     *
     * @return bool
     */
    public function instance_can_be_collapsed() {
        return false;
    }
}
