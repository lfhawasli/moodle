<?php
// This file is part of the UCLA shared theme for Moodle - http://moodle.org/
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
 * UCLA core renderer and overrides Boost core_renderer.
 *
 * @package    theme_uclashared
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_uclashared\output;

use html_writer;
use moodle_url;
use custom_menu_item;
use custom_menu;
use action_link;
use action_menu_filler;
use action_menu_link_secondary;
use action_menu;
use help_icon;
use pix_icon;
use block_contents;
use stdClass;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/user/lib.php');

/**
 * UCLA core renderer and overrides Boost core_renderer.
 *
 * @package    theme_uclashared
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Shows sitewide 'alert' banner.
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
            $banner = new \ucla_alert_banner(SITEID);
            $out = $banner->render();
        }

        return $out;
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
     * Returns which environment we are running.
     *
     * @return string   Either prod, stage, test, or dev.
     */
    public function get_environment() {
        $c = $this->get_config('theme_uclashared', 'running_environment');
        if (!$c) {
            return 'prod';
        }
        return $c;
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
     * Returns copyright information used in footer.
     *
     * @return string
     */
    public function copyright_info() {
        return get_string('copyright_information', 'theme_uclashared', date('Y'));
    }

    /**
     * Returns HTML to display a "Turn editing on/off" button in a form.
     *
     * Restores editing button that Boost theme takes away.
     *
     * @param moodle_url $url The URL + params to send through when clicking the button
     * @return string HTML the button
     */
    public function edit_button(moodle_url $url = null) {
        global $PAGE;

        if (empty($url)) {
            $url = $PAGE->url;
        }

        if ($PAGE->user_allowed_editing()) {
            // Add the turn on/off settings.
            if ($url->compare(new \moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                // We are on the course page, retain the current page params e.g. section.
                $baseurl = clone($url);
                $baseurl->param('sesskey', sesskey());
            } else {
                // Edit on the main course page.
                $baseurl = new \moodle_url('/course/view.php',
                        array('id' => $PAGE->course->id,
                              'return' => $url->out_as_local_url(false),
                              'sesskey' => sesskey()));
            }
            $editurl = clone($baseurl);
            if ($PAGE->user_is_editing()) {
                $editurl->param('edit', 'off');
                $editstring = get_string('turneditingoff');
            } else {
                $editurl->param('edit', 'on');
                $editstring = get_string('turneditingon');
            }
            return $this->single_button($editurl, $editstring);
        }
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

        $opennewwindow = false;
        foreach ($links as $link) {

            if ($link == 'separator') {
                $footerstring .= \html_writer::tag('li', ' | ');
                $opennewwindow = true;
            } else {
                $linkdisplay = get_string('foodis_' . $link, 'theme_uclashared');
                $linkhref = get_string('foolin_' . $link, 'theme_uclashared');
                if (empty($opennewwindow)) {
                    $params = array('href' => $linkhref);
                } else {
                    $params = array('href' => $linkhref, 'target' => '_blank');
                }

                $linka = \html_writer::tag('a', $linkdisplay, $params);

                $footerstring .= \html_writer::tag('li', $linka);
            }
        }
        return $footerstring;
    }

    /**
     * Returns the HTML button for the help and feedback with a
     * dropdown menu when available.
     *
     * @return string
     */
    public function help_feedback_link() {
        global $CFG;

        $helplocale = $this->call_separate_block_function(
                'ucla_help', 'get_action_link'
        );

        if (!$helplocale) {
            return false;
        }

        // Main Help & Feedback link.
        $hflinktext = html_writer::span(get_string('help_n_feedback', 'theme_uclashared'), 'need_help');
        $icon = html_writer::tag('i', '', array('class' => 'fa fa-question-circle fa-fw'));

        $opts = new stdClass();
        $opts->navitems = array();

        // Custom-defined items.
        $customitems = \user_convert_text_to_menu_items($CFG->custommenuitems, $this->page);
        foreach ($customitems as $item) {
            $opts->navitems[] = $item;
        }

        // Create a divider (well, a filler).
        $divider = new action_menu_filler();
        $divider->primary = false;

        $am = new action_menu();
        $am->set_menu_trigger($hflinktext . $icon);

        $am->set_alignment(action_menu::TR, action_menu::BR);
        $am->set_nowrap_on_items();
        $navitemcount = count($opts->navitems);
        $idx = 0;
        foreach ($opts->navitems as $key => $value) {

            switch ($value->itemtype) {
                case 'divider':
                    // If the nav item is a divider, add one and skip link processing.
                    $am->add($divider);
                    break;

                case 'invalid':
                    // Silently skip invalid entries (should we post a notification?).
                    break;

                case 'link':
                    // Process this as a link item.
                    $pix = null;
                    if (isset($value->pix) && !empty($value->pix)) {
                        $pix = new pix_icon($value->pix, $value->title, null, array('class' => 'iconsmall'));
                    } else if (isset($value->imgsrc) && !empty($value->imgsrc)) {
                        $value->title = html_writer::img(
                            $value->imgsrc,
                            $value->title,
                            array('class' => 'iconsmall')
                        ) . $value->title;
                    }

                    $al = new action_menu_link_secondary(
                        $value->url,
                        $pix,
                        $value->title,
                        array('class' => 'icon')
                    );
                    if (!empty($value->titleidentifier)) {
                        $al->attributes['data-title'] = $value->titleidentifier;
                    }
                    $am->add($al);
                    break;
            }

            $idx++;

            // Add dividers after the first item and before the last item.
            if ($idx == 1 || $idx == $navitemcount - 1) {
                $am->add($divider);
            }
        }

        return html_writer::div($this->render($am), 'btn-header btn-help-feedback');

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
     * Construct a user menu, returning HTML that can be echoed out by a
     * layout file.
     *
     * @param stdClass $user A user object, usually $USER.
     * @param bool $withlinks true if a dropdown should be built.
     * @return string HTML fragment.
     */
    public function user_menu($user = null, $withlinks = null) {
        global $CFG, $PAGE, $USER;
        require_once($CFG->dirroot . '/user/lib.php');

        if (is_null($user)) {
            $user = $USER;
        }

        // Note: this behaviour is intended to match that of core_renderer::login_info,
        // but should not be considered to be good practice; layout options are
        // intended to be theme-specific. Please don't copy this snippet anywhere else.
        if (is_null($withlinks)) {
            $withlinks = empty($this->page->layout_options['nologinlinks']);
        }

        // Add a class for when $withlinks is false.
        $usermenuclasses = 'usermenu';
        if (!$withlinks) {
            $usermenuclasses .= ' withoutlinks';
        }

        // Are we on the frontpage?
        $onfrontpage = $PAGE->pagelayout == 'frontpage';

        $returnstr = "";

        // If during initial install, return the empty return string.
        if (during_initial_install()) {
            return $returnstr;
        }

        $loginpage = $this->is_login_page();
        $loginurl = get_login_url();
        // If not logged in, show the login button.
        if (!isloggedin()) {
            if (!$loginpage) {
                // Removed 'You are not logged in." text and converted login link to login button.
                $loginstr = get_string('login');
                $returnstr = "<a href=\"$loginurl\"><button class=\"btn btn-header\" id=\"btn-login\">$loginstr</button></a>";
            }

            // If user is on the front page, return button without usermenu classes.
            if ($onfrontpage) {
                return $returnstr;
            } else {
                return html_writer::div(
                    html_writer::span(
                        $returnstr,
                        'login'
                    ),
                    $usermenuclasses
                );
            }
        }

        // If logged in as a guest user, show a string to that effect.
        if (isguestuser()) {
            if (!$loginpage && $withlinks) {
                // Removed 'You are currently logged as guest." text and converted login link to login button.
                $loginstr = get_string('login');
                $returnstr = "<a href=\"$loginurl\"><button class=\"btn btn-header\" id=\"btn-login\">$loginstr</button></a>";
            }

            // If user is on the front page, always return the button without usermenu classes.
            if ($onfrontpage) {
                return "<a href=\"$loginurl\"><button class=\"btn btn-header\" id=\"btn-login\">$loginstr</button></a>";
            } else {
                return html_writer::div(
                    html_writer::span(
                        $returnstr,
                        'login'
                    ),
                    $usermenuclasses
                );
            }
        }
        
        // If logged in and on the front page, hide the login button.
        if (isloggedin() && $onfrontpage) {
            return "";
        }

        // Get some navigation opts.
        $opts = user_get_user_navigation_info($user, $this->page);

        $avatarclasses = "avatars";
        $avatarcontents = html_writer::span($opts->metadata['useravatar'], 'avatar current');
        $usertextcontents = $opts->metadata['userfullname'];

        // Other user.
        if (!empty($opts->metadata['asotheruser'])) {
            $avatarcontents .= html_writer::span(
                $opts->metadata['realuseravatar'],
                'avatar realuser'
            );
            $usertextcontents = $opts->metadata['realuserfullname'];
            $usertextcontents .= html_writer::tag(
                'span',
                get_string(
                    'loggedinas',
                    'moodle',
                    html_writer::span(
                        $opts->metadata['userfullname'],
                        'value'
                    )
                ),
                array('class' => 'meta viewingas')
            );
        }

        // Role.
        if (!empty($opts->metadata['asotherrole'])) {
            $role = \core_text::strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['rolename'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['rolename'],
                'meta role role-' . $role
            );
        }

        // User login failures.
        if (!empty($opts->metadata['userloginfail'])) {
            $usertextcontents .= html_writer::span(
                $opts->metadata['userloginfail'],
                'meta loginfailures'
            );
        }

        // MNet.
        if (!empty($opts->metadata['asmnetuser'])) {
            $mnet = strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['mnetidprovidername'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['mnetidprovidername'],
                'meta mnet mnet-' . $mnet
            );
        }

        $returnstr .= html_writer::span(
            html_writer::span($usertextcontents, 'usertext') .
            html_writer::span($avatarcontents, $avatarclasses),
            'userbutton'
        );

        // Create a divider (well, a filler).
        $divider = new action_menu_filler();
        $divider->primary = false;

        $am = new action_menu();
        $am->set_menu_trigger(
            $returnstr
        );
        $am->set_alignment(action_menu::TR, action_menu::BR);
        $am->set_nowrap_on_items();
        if ($withlinks) {
            $navitemcount = count($opts->navitems);
            $idx = 0;
            foreach ($opts->navitems as $key => $value) {

                switch ($value->itemtype) {
                    case 'divider':
                        // If the nav item is a divider, add one and skip link processing.
                        $am->add($divider);
                        break;

                    case 'invalid':
                        // Silently skip invalid entries (should we post a notification?).
                        break;

                    case 'link':
                        // Process this as a link item.
                        $pix = null;
                        if (isset($value->pix) && !empty($value->pix)) {
                            $pix = new pix_icon($value->pix, $value->title, null, array('class' => 'iconsmall'));
                        } else if (isset($value->imgsrc) && !empty($value->imgsrc)) {
                            $value->title = html_writer::img(
                                $value->imgsrc,
                                $value->title,
                                array('class' => 'iconsmall')
                            ) . $value->title;
                        }

                        $al = new action_menu_link_secondary(
                            $value->url,
                            $pix,
                            $value->title,
                            array('class' => 'icon')
                        );
                        if (!empty($value->titleidentifier)) {
                            $al->attributes['data-title'] = $value->titleidentifier;
                        }
                        $am->add($al);
                        break;
                }

                $idx++;

                // Add dividers after the first item and before the last item.
                if ($idx == 1 || $idx == $navitemcount - 1) {
                    $am->add($divider);
                }
            }
        }

        return html_writer::div(
            $this->render($am),
            $usermenuclasses
        );
    }

    /**
     * This code renders the navbar button to control the display of the custom menu
     * on smaller screens.
     *
     * Do not display the button if the menu is empty.
     *
     * @return string HTML fragment
     */
    public function navbar_button() {
        global $CFG;

        if (empty($CFG->custommenuitems) && $this->lang_menu() == '') {
            return '';
        }

        $iconbar = html_writer::tag('span', '', array('class' => 'icon-bar'));
        $button = html_writer::tag('a', $iconbar . "\n" . $iconbar. "\n" . $iconbar, array(
            'class'       => 'btn btn-navbar',
            'data-toggle' => 'collapse',
            'data-target' => '.nav-collapse'
        ));
        return $button;
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
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header() {
        global $PAGE, $COURSE;

        if ($COURSE->format !== 'ucla') {
            return self::full_header_orig_with_week();
        }

        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => 'row'));
        $html .= html_writer::start_div('col-12 pt-3 pb-3');
        $html .= html_writer::start_div('card');
        $html .= html_writer::start_div('card-body');
        if (empty($PAGE->layout_options['nonavbar'])) {
            $html .= html_writer::start_div('clearfix w-100 float-sm-left', array('id' => 'page-navbar'));
            $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
            $html .= $this->weeks_display();
            $html .= html_writer::end_div();
        }

        $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
        $html .= html_writer::start_div('page-context-header-container float-sm-left');
        $html .= $this->context_header();
        $html .= html_writer::end_div();

        if ($COURSE->id != SITEID) {
            $renderer = $PAGE->get_renderer('format_ucla');
            if (!empty($renderer->print_site_meta_text())) {
                $html .= html_writer::div($renderer->print_site_meta_text(), 'clearfix float-sm-left course-details');
            }
        }

        $html .= html_writer::start_div('d-none d-md-block float-sm-right');

        if (has_capability('format/ucla:viewadminpanel', $PAGE->context)) {
            $html .= self::admin_panel();
        }

        if (empty($PAGE->layout_options['nonavbar'])) {
             // If the page has editing button, then display it.
            $pageeditingbutton = $this->page_heading_button();
            if (!empty($pageeditingbutton)) {
                $html .= html_writer::div($pageeditingbutton, 'float-sm-right header-editing-button');
            }
        }
        $html .= html_writer::end_div();

        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('header');
        return $html;
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header_orig_with_week() {
        global $PAGE, $USER;

        // On My sites, fix fullname in header to be firstname, lastname.
        $headerinfo = array();
        if ($PAGE->pagetype == 'my-index') {
            $headerinfo['heading'] = $USER->firstname . ' ' . $USER->lastname;
        }

        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => 'row'));
        $html .= html_writer::start_div('col-12 pt-3 pb-3');
        $html .= html_writer::start_div('card');
        $html .= html_writer::start_div('card-body');
        $html .= html_writer::div($this->context_header_settings_menu(), 'float-sm-right context-header-settings-menu');
        // Adding weeks display to original parent full_header code.
        $html .= html_writer::start_div('clearfix w-100 float-sm-left');
        $html .= $this->weeks_display();
        $html .= html_writer::start_div('float-sm-left');
        $html .= $this->context_header($headerinfo);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        // End weeks display.
        $pageheadingbutton = $this->page_heading_button();
        if (empty($PAGE->layout_options['nonavbar'])) {
            $html .= html_writer::start_div('clearfix w-100 float-sm-left', array('id' => 'page-navbar'));
            $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
            $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button float-sm-right');
            $html .= html_writer::end_div();
        } else if ($pageheadingbutton) {
            $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button nonavbar float-sm-right');
        }
        $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('header');
        return $html;
    }

    /**
     * Generates "Admin panel" button.
     *
     * @return string   HTML for admin panel.
     */
    public function admin_panel() {
        global $PAGE;

        $adminicon = html_writer::tag('i', '', array('class' => 'fa fa-cog fa-fw'));
        $adminbutton = html_writer::div($adminicon .' '. get_string('adminpanel', 'format_ucla'),
                'btn btn-secondary header-admin-panel-button');

        $params = array('courseid' => $PAGE->course->id, 'section' => course_get_format($PAGE->course)->figure_section());
        $adminlink = new moodle_url('/course/format/ucla/admin_panel.php', $params);
        $html = html_writer::link($adminlink, $adminbutton,
                array('class' => 'admin-panel-link float-sm-right'
                . ($PAGE->url->compare($adminlink) ? ' font-weight-bold' : '')));

        return $html;
    }

    /**
     * Renders an action menu component.
     *
     * @param action_menu $menu
     * @return string HTML
     */
    public function render_action_menu(action_menu $menu) {

        // We don't want the class icon there!
        foreach ($menu->get_secondary_actions() as $action) {
            if ($action instanceof \action_menu_link && $action->has_class('icon')) {
                $action->attributes['class'] = preg_replace('/(^|\s+)icon(\s+|$)/i', '', $action->attributes['class']);
            }
        }

        if ($menu->is_empty()) {
            return '';
        }
        $context = $menu->export_for_template($this);

        return $this->render_from_template('theme_uclashared/action_menu', $context);
    }

    /**
     * This renders the navbar.
     * Uses bootstrap compatible html.
     */
    public function navbar() {
        $originalnavbar = $this->page->navbar->get_items();
        $originalnavbarsize = count($originalnavbar);
        // If the original navbar has 'Course'/'My courses' and there are
        // nodes further ahead in the breadcrumb, then create a new navbar.
        if ($originalnavbarsize > 2 &&
                ($originalnavbar[1]->text == get_string('mycourses', 'core') ||
                $originalnavbar[1]->text == get_string('courses', 'core'))) {
            $newnavbar = new \navbar($this->page);
            // Clear out previously created navbar, created by constructor.
            $newnavbar->ignore_active();
            // Keep track of duplicate nodes.
            $filter = array();
            // Create new navbar excluding the 'Course'/'My courses' node.
            for ($i = 2; $i < $originalnavbarsize; $i++) {
                $node = $originalnavbar[$i];
                if (!isset($filter[$node->key])) {
                    $newnavbar->add($node->text, $node->action, $node->type,
                            $node->shorttext, $node->key, $node->icon);
                    $filter[$node->key] = 1;
                }
            }
        } else {
            $newnavbar = $this->page->navbar;
        }
        return $this->render_from_template('core/navbar', $newnavbar);
    }
}
