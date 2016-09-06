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
     * Public theme name.
     *
     * @var string
     */
    public $theme_name = 'uclashared';

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
     * Attaches custom theme head html.
     *
     * @return string
     */
    public function standard_head_html() {
        global $CFG;

        // Get base theme output.
        $out = parent::standard_head_html();

        // Apple touch icons.
        $out .= '<link rel="shortcut icon" href="' . $this->pix_url('favicon', 'theme') . '" /> ' . "\n"
                . '<link rel="apple-touch-icon" href="' . $this->pix_url('apple-touch-icon', 'theme') . '" />' . "\n";

        // Need to know what OS we have to determine font rendering.
        // On Windows OSes Chrome and Firefox don't have proper font-smoothing.
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $iswindowsos = strpos($agent, 'Windows') ? true : false;

        $fontlink = html_writer::empty_tag('link',
                array(
                    'href' => 'https://fonts.googleapis.com/css?family=Lato:300,400,400italic,700,900',
                    'rel' => 'stylesheet',
                    'type' => 'text/css'
                    )
                );
        if (!$iswindowsos) {
            $out .= $fontlink . "\n";
        } else {
            // IE does have font-smoothing, so load font for IE 8 and above.
            $out .= "<!--[if gt IE 8]>\n" . $fontlink . "\n" . "<![endif]-->\n";

            // Show an unsupported browser message for IE 8 and lower.
            $unsupportedbrowser = html_writer::tag('script', '',
                array(
                    'type' => 'text/javascript',
                    'src' => $CFG->wwwroot . '/theme/uclashared/javascript/unsupported-browser-ie.js'
                )
            );
            $out .= "<!--[if lte IE 8]>\n" . $unsupportedbrowser . "\n" . "<![endif]-->\n";
        }

        // Show an unsupported browser message for Safari 6.1.4 or lower.
        if (strpos($agent, 'Safari') !== false) {
            require_once($CFG->dirroot.'/vendor/autoload.php');
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $parser = UAParser\Parser::create();
            $result = $parser->parse($ua);

            if ($this->is_unsupported_safari($result->os->toString(), $result->ua->toString())) {
                $unsupportedbrowser = html_writer::tag('script', '',
                    array(
                        'type' => 'text/javascript',
                        'src' => $CFG->wwwroot . '/theme/uclashared/javascript/unsupported-browser-safari.js'
                    )
                );
                $out .= $unsupportedbrowser . "\n";
            }
        }

        // Add mobile support with option to switch.
        if (core_useragent::get_user_device_type() != 'default') {
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
            $addloginurl = false;

            if (isguestuser()) {
                 $userlink = html_writer::span(get_string('loggedinasguest'), 'btn-header-text visible-md-inline-block visible-lg-inline-block');
                 $addloginurl = true;
                 $loginstr = $userlink;
            }

        } else {
            $loginstr = html_writer::span(get_string('loggedinnot', 'moodle'), 'btn-header-text visible-md-inline-block visible-lg-inline-block');
        }

        // The help and feedback link.
        $fbl = $this->help_feedback_link();
        if ($fbl) {
            $logininfo[] = $fbl;
        }
        $logininfo[] = $loginstr;

        // The actual login link.
        if ($addloginurl) {
            $logininfo[] = html_writer::link($loginurl,
                    get_string('login'),
                    array('class' => 'btn-header btn-login')
            );
        }

        $loginstring = implode('', $logininfo);

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
        if (!isloggedin() || isguestuser()) {
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
     * Returns the HTML button for the help and feedback with a
     * dropdown menu when available.
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

        // Main Help & Feedback link.
        $hflinktext = html_writer::span(get_string('help_n_feedback', $this->theme), '');
        $icon = html_writer::tag('i', '', array('class' => 'fa fa-question-circle fa-fw'));
        $outlink = html_writer::link($helplocale, $hflinktext . $icon, array('class' => 'btn-header btn-help-feedback'));

        // Show dropdown menu.
        $menu = $this->custom_menu();

        // Return full menu with link.
        return html_writer::span($outlink . $menu, 'help-dropdown');
    }

    /**
     * Renders the Help & Feedback dropdown menu using Moodle's own config.
     * The menu items can be modified in Appearance > Themes > Theme settings.
     *
     * @see $CFG->custommenuitems
     * @param custom_menu $menu
     * @return string HTML output
     */
    protected function render_custom_menu(custom_menu $menu) {
        if (!$menu->has_children()) {
            return '';
        }

        $items = array();
        foreach ($menu->get_children() as $k => $child) {

            // Show an arrow above first item.
            $arrow = $k === 0 ? html_writer::span('', 'arrow-up') : '';
            $url = $child->get_url();

            // For help requests, get URL with courseid.
            // Assume this link is the first item in the menu.
            if ($k === 0) {
                $url = $this->call_separate_block_function('ucla_help', 'get_action_link');
            }

            $items[] = html_writer::tag('li',
                html_writer::link($url, $arrow . $child->get_text())
            );
        }

        $menu = html_writer::tag('ul', implode('', $items),
            array('class' => 'help-dropdown-menu hidden', 'role' => 'menu')
        );

        return $menu;
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
        $logoimg = html_writer::img($pixurl, $logoalt, array('class' => 'logo-ucla'));

        // Build new logo in a single link.
        $link = html_writer::link($address,
                        $logoimg .
                        html_writer::span('CCLE', 'logo-ccle') .
                        html_writer::span(
                                html_writer::span('common collaboration',
                                        'logo-cc') .
                                html_writer::span('& learning environment',
                                        'logo-le'), 'logo-ccle-sub hidden-xs hidden-sm')
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
                            array('class' => 'header-system-name'));
        }
        return $displaytext;
    }

    /**
     * Displays control panel button.
     *
     * @return string
     */
    public function control_panel_button() {
        global $OUTPUT, $COURSE;

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

        // Show grades button ONLY for students.
        $context = context_course::instance($COURSE->id);
        if (has_role_in_context('student', $context)) {

            $buttons = $OUTPUT->single_button('/grade/report/index.php?id=' . $COURSE->id .'#grade-view', get_string('grades'),
                'get', array('class' => 'btn-grades-container'));
            $buttons .= $OUTPUT->single_button($cplink, $cptext, 'get', array('class' => 'btn-cpanel-container'));

            return $buttons;
        }

        // Show regular control panel button.
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
            'disability',
            'caps'
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
     * Shows sitewide 'alert' banner.
     *
     * @todo: right now it only works for 'red' alerts.
     *
     * @return string HTML
     */
    public function alert_banner() {
        global $CFG;

        $out = '';

        if (!during_initial_install() && get_config('block_ucla_alert', 'alert_sitewide')) {

            if (!class_exists('ucla_alert_banner_site')) {
                $file = $CFG->dirroot . '/blocks/ucla_alert/locallib.php';
                require_once($file);
            }

            // Display banner.
            $banner = new ucla_alert_banner(SITEID);
            $out = $banner->render();
        }

        return $out;
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

    /**
     * Set for custom course logos.  This is meant to be overridden by child themes.
     *
     * @return empty string
     */
    public function course_logo() {
        return '';
    }

    /**
     * Override confirmation dialog to be able to use different styles.
     * See documentation for confirm() in /lib/outputrenderers.php
     *
     * @param string $message
     * @param single_button|moodle_url|string $continue
     * @param single_button|moodle_url|string $cancel
     * @param string $style The style of the confirmation dialog.
     * Currently only 'success' is implemented; anything else uses the default appearance.
     * @return string
     */
    public function confirm($message, $continue, $cancel, $style = '') {
        if (is_string($continue)) {
            $continue = new single_button(new moodle_url($continue), get_string('continue'), 'post');
        } else if ($continue instanceof moodle_url) {
            $continue = new single_button($continue, get_string('continue'), 'post');
        } else if (!($continue instanceof single_button)) {
            throw new coding_exception(
                    'The continue param to $OUTPUT->confirm() must be either ' .
                    'a URL (string/moodle_url) or a single_button instance.');
        }

        if (is_string($cancel)) {
            $cancel = new single_button(new moodle_url($cancel), get_string('cancel'), 'get');
        } else if ($cancel instanceof moodle_url) {
            $cancel = new single_button($cancel, get_string('cancel'), 'get');
        } else if (!($cancel instanceof single_button)) {
            throw new coding_exception(
                    'The cancel param to $OUTPUT->confirm() must be either ' .
                    'a URL (string/moodle_url) or a single_button instance.');
        }

        if ($style == 'success') {
            $output = $this->box_start('generalbox alert alert-success');
        } else {
            $output = $this->box_start('generalbox', 'notice');
        }
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::tag('div', $this->render($continue) . $this->render($cancel), array('class' => 'buttons'));
        $output .= $this->box_end();
        return $output;
    }

    /*
     * Override the notification in order to include the warning style.
     *
     * @param string $message
     * @param string $classes
     * @return string HTML
     */
    public function notification($message, $classes = 'notifyproblem') {
        $message = clean_text($message);
        $type = '';

        if ($classes == 'notifywarning') {
            $type = 'alert alert-warning';
            return "<div class=\"$type\">$message</div>";
        } else {
            return parent::notification($message, $classes);
        }
    }

    /**
     * Checks if given browser string is an unsupported Safari version (6.1.4 or lower).
     *
     * @param string $osstring
     * @param string $browserstring
     */
    public function is_unsupported_safari($osstring, $browserstring) {
        // Only care about OSX 10.6 and OSX 10.7.
        if (strrpos($osstring, 'Mac OS X 10.6') === false  &&
                strrpos($osstring, 'Mac OS X 10.7') === false) {
            return false;
        }

        // Want just the version number. Currently in format such as "Safari 6.1.4".

        // Get rid of "Safari".
        $parts = explode(' ', $browserstring);

        // Make sure that we are only looking at Safari browsers.
        if ($parts[0] != 'Safari') {
            return false;
        }

        // Now get version string and look at the X.Y.Z versions.
        $versionparts = explode('.', $parts[1]);
        if (isset($versionparts[0])) {
            if ($versionparts[0] == 6) {
                if (isset($versionparts[1])) {
                    if ($versionparts[1] == 1) {
                        if (isset($versionparts[2])) {
                            if ($versionparts[2] <= 4) {
                                return true;
                            }
                        } else {
                            return true;
                        }
                    } else if ($versionparts[1] < 1) {
                        return true;
                    }
                } else {
                    return true;
                }
            } else if ($versionparts[0] < 6) {
                return true;
            }
        }

        return false;
    }

    /**
     * Change "Update this module" button to "Edit settings".
     *
     * @param string $cmid the course_module id.
     * @param string $modulename the module name, eg. "forum", "quiz" or "workshop"
     * @return string  Empty string.
     */
    public function update_module_button($cmid, $modulename) {
        global $OUTPUT;
        if (has_capability('moodle/course:manageactivities', context_module::instance($cmid))) {
            $url = new moodle_url("/course/mod.php", array('update' => $cmid, 'return' => true, 'sesskey' => sesskey()));
            return $OUTPUT->single_button($url, get_string('editsettings', 'moodle'));
        } else {
            return '';
        }
    }

    /**
     * Force addition of our footer, which includes javascript to make toggling public/private
     * make dynamic changes in the page.
     *
     * @return string HTML that you must output this, preferably immediately.
     */
    public function header() {
        // If this theme version is below 2.4 release and this is a course view page.

        if ($this->page->pagelayout === 'course' && $this->page->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
            // Check if course content header/footer have not been output during render of theme layout.
            $coursecontentfooter = $this->course_content_footer(true);
        }
        return  parent::header();
    }
}
