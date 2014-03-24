<?php
// This file is part of the UCLA theme plugin for Moodle - http://moodle.org/
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
 * Extends Moodle's Bootstrap core renderer.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/theme/bootstrapbase/renderers/core_renderer.php');

/**
 * Overriding the bootstrap core render (theme/bootstrapbase/renderers/core_renderer.php).
 *
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_core_renderer extends theme_bootstrapbase_core_renderer {

    /**
     * Separator.
     * @var string
     */
    private $sep = null;

    /**
     * Theme name.
     * @var string
     */
    private $theme = 'theme_uclashared';

    /**
     * Returns separator used in header and footer links.
     * @return string
     */
    public function separator() {
        if ($this->sep == null) {
            $this->sep = get_string('separator__', $this->theme);
        }

        return $this->sep;
    }

    /**
     * Attaches the meta tag needed for mobile display support.
     *
     * @return string
     */
    public function standard_head_html() {
        global $CFG;

        $out = parent::standard_head_html();

        // Add mobile support with option to switch.
        if (get_user_device_type() != 'default') {
            $out .= '<meta name="viewport" content="width=device-width; ' .
                    'initial-scale=1.0; maximum-scale=2.0; user-scalable=1;" />' . "\n";
        }

        // Attach print CSS.
        $out .= '<link rel="stylesheet" type="text/css" media="print" href="' .
                $CFG->wwwroot . '/theme/uclashared/style/print.css" />' . "\n";

        return $out;
    }

    /**
     * Displays what user you are logged in as, and if needed, along with the
     * user you are logged-in-as.
     *
     * @param boolean $withlinks    Not used.
     * @return string
     */
    public function login_info($withlinks = null) {
        global $CFG, $DB, $SESSION, $USER;

        $course = $this->page->course;

        // This will have login informations
        // [0] == Login information
        // - Format  [REALLOGIN] (as \(ROLE\))|(DISPLAYLOGIN) (from MNET)
        // [1] == H&Fb link
        // [2] == Logout/Login button.
        $logininfo = array();

        $loginurl = get_login_url();
        $addloginurl = ($this->page->url != $loginurl);

        $addlogouturl = false;

        $loginstr = '';

        if (isloggedin()) {
            $addlogouturl = true;
            $addloginurl = false;

            $usermurl = new moodle_url('/user/profile.php',
                    array(
                'id' => $USER->id
            ));

            // In case of mnet login.
            $mnetfrom = '';
            if (is_mnet_remote_user($USER)) {
                $idprovider = $DB->get_record('mnet_host',
                        array(
                    'id' => $USER->mnethostid
                ));

                if ($idprovider) {
                    $mnetfrom = html_writer::link($idprovider->wwwroot,
                                    $idprovider->name);
                }
            }

            $realuserinfo = '';
            if (session_is_loggedinas()) {
                $realuser = session_get_realuser();
                $realfullname = fullname($realuser, true);
                $dest = new moodle_url('/course/loginas.php',
                        array(
                    'id' => $course->id,
                    'sesskey' => sesskey()
                ));

                $realuserinfo = '[' . html_writer::link($dest, $realfullname) . ']'
                        . get_string('loginas_as', 'theme_uclashared');
            }

            $fullname = fullname($USER, true);
            $userlink = html_writer::link($usermurl, $fullname);

            $rolename = '';
            // I guess only guests cannot switch roles.
            if (isguestuser()) {
                $userlink = get_string('loggedinasguest');
                $addloginurl = true;
            } else if (is_role_switched($course->id)) {
                $context = get_context_instance(CONTEXT_COURSE, $course->id);

                $role = $DB->get_record('role',
                        array(
                    'id' => $USER->access['rsw'][$context->path]
                ));

                if ($role) {
                    $rolename = ' (' . format_string($role->name) . ') ';
                }
            }

            $loginstr = $realuserinfo . $rolename . $userlink;
        } else {
            $loginstr = get_string('loggedinnot', 'moodle');
        }

        if (isset($SESSION->justloggedin)) {
            unset($SESSION->justloggedin);
            if (!empty($CFG->displayloginfailures) && !isguestuser()) {
                if ($count = count_login_failures($CFG->displayloginfailures,
                        $USER->username, $USER->lastlogin)) {

                    $loginstr .= '&nbsp;<div class="loginfailures">';

                    if (empty($count->accounts)) {
                        $loginstr .= get_string('failedloginattempts', '',
                                $count);
                    } else {
                        $loginstr .= get_string('failedloginattemptsall', '',
                                $count);
                    }

                    if (has_capability('coursereport/log:view',
                                    get_context_instance(CONTEXT_SYSTEM))) {
                        $loginstr .= ' (' . html_writer::link(new moodle_url(
                                        '/course/report/log/index.php',
                                        array(
                                    'chooselog' => 1,
                                    'id' => 1,
                                    'modid' => 'site_errors'
                                        )), get_string('logs')) . ')';
                    }

                    $loginstr .= '</div>';
                }
            }
        }

        $logininfo[] = $loginstr;

        // The help and feedback link.
        $fbl = $this->help_feedback_link();
        if ($fbl) {
            $logininfo[] = $fbl;
        }

        // The actual login link.
        if ($addloginurl) {
            $logininfo[] = html_writer::link($loginurl, get_string('login'));
        } else if ($addlogouturl) {
            $logininfo[] = html_writer::link(
                            new moodle_url('/login/logout.php',
                            array('sesskey' => sesskey())), get_string('logout')
            );
        }

        $separator = $this->separator();
        $loginstring = implode($separator, $logininfo);

        return $loginstring;
    }

    /**
     * Displays link to use to login or logout on frontpage.
     * 
     * @return string
     */
    public function login_link() {
        $link = null;
        // Note, the id fields are needed for Behat steps "I log in" and
        // "I log out" to work.
        if (!isloggedin()) {
            $link = html_writer::link(get_login_url(), get_string('login'),
                    array('class' => 'login', 'id' => 'Login'));
        } else {
            $link = html_writer::link(
                        new moodle_url('/login/logout.php',
                        array('sesskey' => sesskey())), get_string('logout'),
                        array('class' => 'login', 'id' => 'Logout')
            );
        }
        return $link;
    }

    /**
     * Returns the HTML link for the help and feedback.
     *
     * @return string
     */
    public function help_feedback_link() {
        $helplocale = $this->call_separate_block_function(
                'ucla_help', 'get_action_link'
        );

        if (!$helplocale) {
            return false;
        }

        $hflink = get_string('help_n_feedback', $this->theme);

        return html_writer::link($helplocale, $hflink);
    }

    /**
     * Calls the hook public function that will return the current week we are on.
     */
    public function weeks_display() {
        $weekstext = $this->call_separate_block_function(
                'ucla_weeksdisplay', 'get_week_display'
        );

        if (!$weekstext) {
            return false;
        }

        return $weekstext;
    }

    /**
     * This a wrapper around pix().
     *
     * It will make a picture of the logo, and turn it into a link.
     *
     * @param string $pix Passed to pix().
     * @param string $pixloc Passed to pix().
     * @param moodle_url $address Destination of anchor.
     * @return string The logo HTML element.
     */
    public function logo($pix, $pixloc, $address = null) {
        global $CFG;

        if ($address == null) {
            $address = new moodle_url($CFG->wwwroot);
        }

        // Get UCLA logo image.
        $pixurl = $this->pix_url($pix, $pixloc);
        $logoalt = get_string('UCLA_CCLE_text', 'theme_uclashared');
        $logoimg = html_writer::empty_tag('img',
                        array('src' => $pixurl, 'alt' => $logoalt));

        // Build new logo in a single link.
        $link = html_writer::link($address,
                        html_writer::span($logoimg, 'logo-ucla') .
                        html_writer::span('CCLE', 'logo-ccle') .
                        html_writer::span(
                                html_writer::span('common collaboration',
                                        'logo-cc') .
                                html_writer::span('& learning environment',
                                        'logo-le'), 'logo-ccle-full')
        );

        return $link;
    }

    /**
     * Displays the text underneath the UCLA | CCLE logo.
     */
    public function sublogo() {
        $displaytext = '';
        $url = $this->get_config('theme_uclashared', 'system_link');
        $text = $this->get_config('theme_uclashared', 'system_name');
        if (!empty($url) && !empty($text)) {
            $displaytext = html_writer::link($url, $text,
                            array('class' => 'system-name'));
        }
        return $displaytext;
    }

    /**
     * Displays control panel button.
     *
     * @return string
     */
    public function control_panel_button() {
        global $OUTPUT;

        // Hack since contexts and pagelayouts are different things.
        // Hack to fix: display control panel link when updating a plugin.
        if ($this->page->context == context_system::instance()) {
            return '';
        }

        $cptext = get_string('control_panel', $this->theme);

        $cplink = $this->call_separate_block_function(
                'ucla_control_panel', 'get_action_link'
        );

        if (!$cplink) {
            return '';
        }

        return $OUTPUT->single_button($cplink, $cptext, 'get');
    }

    /**
     * Returns string of links to be used in footer.
     *
     * @return string
     */
    public function footer_links() {
        $links = array(
            'contact_ccle',
            'about_ccle',
            'privacy',
            'copyright',
            'uclalinks',
            'separator',
            'school',
            'registrar',
            'myucla',
            'disability'
        );

        $footerstring = '';

        $customtext = trim(get_config($this->theme, 'footer_links'));
        if (!empty($customtext)) {
            $footerstring = $customtext;
            array_unshift($links, 'separator');
        }

        // Keep all links before separator from opening into new window.
        $opennewwindow = false;
        foreach ($links as $link) {
            if ($link == 'separator') {
                $footerstring .= '&nbsp;';
                $footerstring .= $this->separator();
                $opennewwindow = true;
            } else {
                $linkdisplay = get_string('foodis_' . $link, $this->theme);
                $linkhref = get_string('foolin_' . $link, $this->theme);
                if (empty($opennewwindow)) {
                    $params = array('href' => $linkhref);
                } else {
                    $params = array('href' => $linkhref, 'target' => '_blank');
                }

                $linka = html_writer::tag('a', $linkdisplay, $params);

                $footerstring .= '&nbsp;' . $linka;
            }
        }

        return $footerstring;
    }

    /**
     * Returns copyright information used in footer.
     *
     * @return string
     */
    public function copyright_info() {
        return get_string('copyright_information', $this->theme, date('Y'));
    }

    /**
     * Attempts to get a feature of another block to generate special text or
     * link to put into the theme.
     *
     * @param string $blockname
     * @param string $functionname
     * @return string
     */
    public function call_separate_block_function($blockname, $functionname) {
        if (during_initial_install()) {
            return '';
        }

        return block_method_result($blockname, $functionname,
                $this->page->course);
    }

    /**
     * Returns which environment we are running.
     *
     * @return string   Either prod, stage, test, or dev.
     */
    public function get_environment() {
        $c = $this->get_config($this->theme, 'running_environment');

        if (!$c) {
            return 'prod';
        }

        return $c;
    }

    /**
     * Wrapper to get_config to prevent crashes during initial install.
     *
     * @param string $plugin
     * @param string $var
     * @return mixed    Returns false if during initial install.
     */
    public function get_config($plugin, $var) {
        if (!during_initial_install()) {
            return get_config($plugin, $var);
        }

        return false;
    }

    /**
     * Displays "Turn editing on/off" button.
     *
     * @param moodle_url $url
     * @return string HTML fragment
     */
    public function edit_button(moodle_url $url) {
        // CCLE-3740 - In order to handle correct redirects for landing
        // page, we use an alias for section 0 that UCLA format expects.
        $section = optional_param('section', null, PARAM_INT);
        if (!is_null($section) && $section === 0) {
            $url->param('section', -1);
        }

        $url->param('sesskey', sesskey());
        if ($this->page->user_is_editing()) {
            $url->param('edit', 'off');
            $editstring = get_string('turneditingoff');
        } else {
            $url->param('edit', 'on');
            $editstring = get_string('turneditingon');
        }

        return $this->single_button($url, $editstring);
    }

}