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
include_once($CFG->dirroot.'/user/lib.php');

/**
 * UCLA specific renderers and overrides Boost renders.
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
      * Calls weeks display function and returns an array with current week and quarter
      * @return array Current week and quarter strings separated into an array
      */
    public function parsed_weeks_display() {
      $parsed = array();
      $weeksdata = self::weeks_display();
      $weekpos = strpos($weeksdata, '<span class="week">');
      $quarter = substr($weeksdata, 0, $weekpos);
      $quarter = strip_tags($quarter);
      $week = substr($weeksdata, $weekpos);
      $week = strip_tags($week);
      array_push($parsed, $quarter, $week);
      return $parsed;
    }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header() {
        global $PAGE, $COURSE;

        if (!empty($PAGE->layout_options['noheader']) &&
                $PAGE->url->get_path() !== '/my/indexsys.php') {
            // We still need header when customizing Dashboard.
            return '';
        }

        if ($COURSE->format !== 'ucla') {
            return self::full_header_orig_with_week();
        }

        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => 'row'));
        $html .= html_writer::start_div('col-xs-12 p-a-1');
        $html .= html_writer::start_div('card');
        $html .= html_writer::start_div('card-block');
        $pageheadingbutton = $this->page_heading_button();
        if (empty($PAGE->layout_options['nonavbar'])) {
            $html .= html_writer::start_div('clearfix w-100 pull-xs-left', array('id' => 'page-navbar'));
            $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
            $html .= self::weeks_display();
            $html .= html_writer::end_div();

            // If the page is a course page or a section of a course, then display the editing button
            if ($PAGE->url->compare(new \moodle_url('/course/view.php'), URL_MATCH_BASE) ||
                    $PAGE->url->compare(new \moodle_url('/local/ucla_syllabus/index.php'), URL_MATCH_BASE)) {
                $html .= html_writer::div($this->header_editing_mode_button(), 'pull-xs-right header-editing-button');
            }
            $html .= html_writer::div($pageheadingbutton, 'pull-xs-right');
        } else if ($pageheadingbutton) {
            $html .= html_writer::div($pageheadingbutton, 'nonavbar pull-xs-right');
        }

        $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
        $html .= html_writer::start_div('page-context-header-container pull-xs-left');
        $html .= $this->context_header();
        $html .= html_writer::end_div();

        $adminicon = html_writer::tag('i', '', array('class' => 'fa fa-cog fa-fw'));
        $params = array('courseid' => $PAGE->course->id, 'section' => course_get_format($COURSE)->figure_section($COURSE));
        $adminlink = new moodle_url('/course/format/ucla/admin_panel.php', $params);
        $html .= html_writer::link($adminlink, $adminicon . get_string('adminpanel', 'format_ucla'),
                array('class' => 'admin-panel-link hidden-sm-down pull-xs-right'
                . ($PAGE->url->compare($adminlink) ? ' font-weight-bold' : '')));

        if ($COURSE->id != SITEID) {
            $renderer = $PAGE->get_renderer('format_ucla');
            $html .= html_writer::div($renderer->print_site_meta_text(), 'clearfix w-100 pull-xs-left');
        }
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
        global $PAGE;

        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => 'row'));
        $html .= html_writer::start_div('col-xs-12 p-a-1');
        $html .= html_writer::start_div('card');
        $html .= html_writer::start_div('card-block');
        $html .= html_writer::div($this->context_header_settings_menu(), 'pull-xs-right context-header-settings-menu');
        // Adding weeks display to original parent full_header code.
        $html .= html_writer::start_div('clearfix w-100 pull-xs-left');
        $html .= self::weeks_display();
        $html .= html_writer::start_div('pull-xs-left');
        $html .= $this->context_header();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        // End weeks display.
        $pageheadingbutton = $this->page_heading_button();
        if (empty($PAGE->layout_options['nonavbar'])) {
            $html .= html_writer::start_div('clearfix w-100 pull-xs-left', array('id' => 'page-navbar'));
            $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
            $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button pull-xs-right');
            $html .= html_writer::end_div();
        } else if ($pageheadingbutton) {
            $html .= html_writer::div($pageheadingbutton, 'breadcrumb-button nonavbar pull-xs-right');
        }
        $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('header');
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
     * This returns a rendered button for the turn editing on/off in the course header.
     *
     * @return string
     */
    private function header_editing_mode_button() {
        global $PAGE;

        if ($PAGE->user_allowed_editing()) {
            // Add the turn on/off settings.
            if ($PAGE->url->compare(new \moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                // We are on the course page, retain the current page params e.g. section.
                $baseurl = clone($PAGE->url);
                $baseurl->param('sesskey', sesskey());
            } else {
                // Edit on the main course page.
                $baseurl = new \moodle_url('/course/view.php',
                        array('id' => $PAGE->course->id,
                              'return' => $PAGE->url->out_as_local_url(false),
                              'sesskey' => sesskey()));
            }

            $editurl = clone($baseurl);
            if ($PAGE->user_is_editing()) {
                $editurl->param('edit', 'off');
                $editstring = get_string('turneditingoff');
                $editmodeclass = 'edit-mode';
            } else {
                $editurl->param('edit', 'on');
                $editstring = get_string('turneditingon');
                $editmodeclass = 'non-edit-mode';
            }

            $edit_button = new \single_button($editurl, $editstring);
            $edit_button->class = 'header-editing-button ' . $editmodeclass;
            return $this->render($edit_button);
        }
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
            // Create new navbar excluding the 'Course'/'My courses' node.
            for ($i = 2; $i < $originalnavbarsize; $i++) {
                $node = $originalnavbar[$i];
                $newnavbar->add($node->text, $node->action, $node->type,
                        $node->shorttext, $node->key, $node->icon);
            }
        } else {
            $newnavbar = $this->page->navbar;
        }
        return $this->render_from_template('core/navbar', $newnavbar);
    }
}
