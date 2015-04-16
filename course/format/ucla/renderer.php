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
 * Renderer for outputting the ucla course format. Based off the topic course 
 * format.
 *
 * @package format_ucla
 * @copyright 2012 UCLA Regent
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/topics/renderer.php');
require_once($CFG->dirroot . '/course/format/ucla/lib.php');
require_once($CFG->dirroot.'/local/publicprivate/lib.php');

/**
 * Basic renderer for ucla format. Based off the topic renderer.
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ucla_renderer extends format_topics_renderer {
    // course info, may contain reginfo
    private $courseinfo = array();
    
    // parsed version of $courseinfo, used to display course sections
    private $displayinfo = array();
    
    // instructors for course
    private $instructors = array();
    
    // course object
    private $course = null;
    
    // context object
    private $context = null;
    
    // term for course that is being rendered
    private $term = null;
    
    // strings to generate jit links
    private $jit_links = array();
    
    /**
     * Constructor method, do necessary setup for UCLA format.
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        
        // get reg info, if any
        $this->courseinfo = ucla_get_course_info($page->course->id);
        
        // parse that reg info
        $this->parse_courseinfo();
        
        // get instructors, if any
        $this->instructors = course_get_format($page->course)->display_instructors();
        
        // save course object
        $this->course = course_get_format($page->course)->get_course();
        
        // save context object
        $this->context =& $page->context;        
        
        // CCLE-2800 - cache strings for JIT links
        $this->jit_links = array('file' => get_string('file', 'format_ucla'),
                                 'link' => get_string('link', 'format_ucla'),
                                 'text' => get_string('text', 'format_ucla'),
                                 'subheading' => get_string('subheading', 'format_ucla'));     
        
        // Use the public/private renderer.  This will permit us to override the
        // way we render course modules
        $this->courserenderer = $this->page->get_renderer('local_publicprivate');
    }
    
    /**
     * Calls ucla_format_notices event and sees if any notices are returned.
     * Expects notices to be returned in an array of HTML content. Just displays
     * content as is.
     *
     * Then echos those notices out.
     *
     * @param int sectionnum            Section being displayed
     */
    public function print_external_notices($sectionnum, $course) {
        global $OUTPUT, $PAGE, $USER;

        // maybe some external notice system is redirecting back with a message
        flash_display();

        /* show notices if:
         * 1) user is on landing page
         * 2) user is on show_all and section is 0
         */
        $format = course_get_format($course);
        $prefs = $format->get_format_options();
        
        if (isset($prefs['landing_page']) && $prefs['landing_page'] != $sectionnum) {
            return;
        }
        if ($sectionnum != 0 && $format->figure_section() === $format::UCLA_FORMAT_DISPLAY_ALL) {
            return;
        }


        // Provide following information to event:
        //      userid, course, user_is_editing, roles, term (if any), notices
        // Expects functions that respond to ucla_format_notices to modify
        // notices and add notices.
        $eventdata = new stdClass();
        $eventdata->userid = $USER->id;
        $eventdata->course = $this->course;
        $eventdata->user_is_editing = $PAGE->user_is_editing();
        $eventdata->roles = get_user_roles($this->context, $USER->id);

        // check if courseinfo is set, so that we can get a possible term
        if (!empty($this->courseinfo)) {
            // use reset instead of array_pop, because pop alters the array
            $courseinfo = reset($this->courseinfo);
            $eventdata->term = $courseinfo->term;
        } else {
            $eventdata->term = null;
        }
        
        $eventdata->notices = array();  // populated by external sources

        events_trigger_legacy('ucla_format_notices', $eventdata);

        if (!empty($eventdata->notices)) {
            // until we can get a better, more compact notice display, we are
            // only going to display the last notice
            $notice = array_pop($eventdata->notices);
            echo $OUTPUT->box($notice, 'ucla-format-notice-box');
//            // we got something back! let's display it
//            foreach ($eventdata->notices as $notice) {
//                echo $OUTPUT->box($notice, 'ucla-format-notice-box');
//            }
        }
    }
    
    /**
     * Returns a label class name for a given term
     * 
     * @param string $term in the form of: 131, 13F, ...
     * @return string css label class
     */
    private function get_term_label($term) {
        $term = substr($term, 2);
        
        switch ($term) {
            case '1':
                return 'label-summer';
            case 'S':
                return 'label-spring';
            case 'W':
                return 'label-winter';
            case 'F':
                return 'label-fall';
        }
        
        return '';
    }
    
    /**
     * Output the html for the page header. For SRS courses will display 
     * reginfo content. Also displays public/private message if user is not 
     * logged in.
     */
    public function print_header() {
        global $CFG, $OUTPUT;
        
        // Formatting and determining information to display for these courses
        $regcoursetext = '';
        $termtext = '';
        
        foreach($this->courseinfo as $c) {
            if($c->hostcourse == 1) {
                $hostcourse = str_replace(' ', '', ucla_make_course_title($c));
                break;
            }
        }
        
        if (!empty($this->courseinfo)) {
            // don't show too many
            $regcourseinfo = implode(' / ', $this->displayinfo);
            $hostfocus = html_writer::tag('span', $hostcourse, 
                    array('class' => 'reg-hostcourse'));
            $regcourseinfo = str_replace($hostcourse, $hostfocus, $regcourseinfo);
            
            $regcoursetext = html_writer::tag('span', $regcourseinfo,
                    array('class' => 'reg-courses'));
            $termtext = html_writer::tag('span', ucla_term_to_text($this->term),
                    array('class' => 'label-term ' . $this->get_term_label($this->term)));
            
        }

        // This is for the sets of instructors in a course
        $imploder = array();
        $inst_text = '';
        if (!empty($this->instructors)) {
            foreach ($this->instructors as $instructor) {
                if (in_array($instructor->shortname, $CFG->instructor_levels_roles['Instructor'])
                        || in_array($instructor->shortname, $CFG->instructor_levels_roles['Student Facilitator'])) {
                    $imploder[$instructor->id] = $instructor->lastname;
                }
            }
        }

        if (empty($imploder)) {
            $inst_text = 'N/A';
        } else {
            $inst_text = implode(' / ', $imploder);
        }

        $heading_text = '';
        if (!empty($termtext)) {
            $heading_text = $termtext . ' - ' . $regcoursetext . ' - ' . $inst_text;
            $heading_text = html_writer::tag('div', $heading_text, array('class' => 'site-meta'));
        }
        
        // Check if this site has a custom course logo.  If so, then the title
        // will be rendered by the theme.
        $courselogos = null;
        if (method_exists($OUTPUT, 'course_logo')) {
            $courselogos = $OUTPUT->course_logo();
        }
        if (empty($courselogos)) {
            echo $OUTPUT->heading($this->course->fullname, 1, 'site-title');
        }
        
        echo $heading_text;
        echo html_writer::tag('span', '', array('class' => 'site-title-divider'));
        
        // display page header

        // Handle cancelled classes
        if (is_course_cancelled($this->courseinfo)) {
            echo $OUTPUT->notification(get_string('coursecancelled', 'format_ucla'), 'notifywarning');
        } else {
            // display message if user is viewing an old course
            $notice = notice_course_status($this->course);
            if (!empty($notice)) {
                echo $notice;
            } else {
                // display public/private notice, if applicable
                echo notice_nonenrolled_users($this->course);
            }
        }
    }
    
    /**
     * Output the html for a multiple section page.
     * 
     * Copied from base class method with following differences:
     *  - print section 0 related stuff
     *  - always show section content, even if editing is off
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods used for print_section()
     * @param array $modnames used for print_section()
     * @param array $modnamesused used for print_section()
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course);

        // Now the list of sections..
        echo $this->start_section_list();

        // Section 0, aka "Site info"
        $thissection = $sections[0];
        unset($sections[0]);
        // do not display section summary/header info for section 0
        echo $this->section_header($thissection, $course, false);

//        print_section($course, $thissection, $mods, $modnamesused, true);
//        $courserenderer = $PAGE->get_renderer('core', 'course'); 
        echo $this->courserenderer->course_section_cm_list($course, $thissection);
        
        if ($PAGE->user_is_editing()) {
//            print_section_add_menus($course, 0, $modnames);
//            $courserenderer = $PAGE->get_renderer('core', 'course'); 
            $output = $this->courserenderer->course_section_add_cm_control($course, 0); 
            echo $output; // if $return argument in print_section_add_menus() set to false
        }
        echo $this->section_footer();

        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);
        for ($section = 1; $section <= $course->numsections; $section++) {
            if (!empty($sections[$section])) {
                $thissection = $sections[$section];
            } else {
                // This will create a course section if it doesn't exist..
                $thissection = get_fast_modinfo($course->id)->get_section_info($section);

                // The returned section is only a bare database object rather than
                // a section_info object - we will need at least the uservisible
                // field in it.
                $thissection->uservisible = true;
                $thissection->availableinfo = null;
                $thissection->showavailability = 0;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but showavailability is turned on (and there is some available info text).
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available && $thissection->showavailability
                    && !empty($thissection->availableinfo));
            if (!$showsection) {
//                // Hidden section message is overridden by 'unavailable' control
//                // (showavailability option).
//                if (!$course->hiddensections && $thissection->available) {
//                    echo $this->section_hidden($section);
//                }

                unset($sections[$section]);
                continue;
            }

            // always show section content, even if editing is off
            echo $this->section_header($thissection, $course, false);
            if ($thissection->uservisible) {
//                print_section($course, $thissection, $mods, $modnamesused);
//                $courserenderer = $PAGE->get_renderer('core', 'course'); 
                echo $this->courserenderer->course_section_cm_list($course, $thissection);
       
                if ($PAGE->user_is_editing()) {
//                    print_section_add_menus($course, $section, $modnames);
//                    $courserenderer = $PAGE->get_renderer('core', 'course'); 
                    $output = $this->courserenderer->course_section_add_cm_control($course, $section); 
                    echo $output;
                }
            }
            echo $this->section_footer();

            unset($sections[$section]);
        }

        if ($PAGE->user_is_editing()) {
            // Print stealth sections if present.
            $modinfo = get_fast_modinfo($course);
            foreach ($sections as $section => $thissection) {
                if (empty($modinfo->sections[$section])) {
                    continue;
                }
                echo $this->stealth_section_header($section);
                print_section($course, $thissection, $mods, $modnamesused);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

        } else {
            echo $this->end_section_list();
        }

    }
    
    /**
     * Output html for content that belong in section 0, such as course 
     * description, final location, registrar links and the office hours block.
     */
    public function print_section_zero_content() {
        global $CFG, $OUTPUT, $PAGE;

        $center_content = '';
        
        // Course Information specific has a different section header
        if (!empty($this->courseinfo)) {
            // We need the stuff...
            $regclassurls = array();
            $regfinalurls = array();
            $num_displayinfo = count($this->displayinfo);
            for ($key = 0; $key < $num_displayinfo; $key++) {
                $displayinfo = $this->displayinfo[$key];
                $courseinfo = $this->courseinfo[$key];

                $url = new moodle_url($courseinfo->url);
                $regclassurls[$key] = html_writer::link($url, $displayinfo);

                $regfinalurls[$key] = html_writer::link(
                        build_registrar_finals_url($courseinfo), $displayinfo
                );
            }

            $registrar_info = get_string('reg_listing', 'format_ucla');

            $registrar_info .= implode(', ', $regclassurls);
            $registrar_info .= html_writer::empty_tag('br');

            $registrar_info .= get_string('reg_finalcd', 'format_ucla');
            $registrar_info .= implode(', ', $regfinalurls);

        }

        $supresscoursesummary = false;
        if (!empty($this->courseinfo)) {
            $hideregsummary = get_config('format_ucla', 'hideregsummary');
            if (!$hideregsummary) {
                $regsummary = '';
                $regsummarycontent = '';
                foreach ($this->courseinfo as $courseinfo) {
                    if (!empty($courseinfo->hostcourse)) {
                        if (!empty($courseinfo->crs_desc)) {
                            $regsummary .= html_writer::tag('p',
                                    html_writer::tag('strong',
                                            get_string('coursedescription', 'format_ucla'))
                                    . ': ' . $courseinfo->crs_desc);
                        } 
                        if (!empty($courseinfo->crs_summary)) {
                            $regsummary .= html_writer::tag('p',
                                    html_writer::tag('strong',
                                            get_string('classdescription', 'format_ucla'))
                                    . ': ' . $courseinfo->crs_summary);
                        }
                        break;
                    }
                }

                // If there's a modified course summary, then collapse registrar descriptions.
                $formattedregsummary = format_text($regsummary);
                if (!empty($this->course->summary) && $this->course->summary != $courseinfo->crs_desc) {
                    $toggle = html_writer::span(
                                html_writer::link('#',
                                    get_string('collapsedshow', 'format_ucla'),
                                    array('class' => 'collapse-toggle')),
                            'collapse-toggle-container');
                    $regsummarycontent .= html_writer::tag('div', $formattedregsummary . $toggle, array('class' => 'registrar-summary-hidden'));
                    $center_content .= html_writer::tag('div', $registrar_info, array('class' => 'registrar-info registrar-summary-hidden'));
                } else {
                    $center_content .= html_writer::tag('div', $registrar_info, array('class' => 'registrar-info'));
                    $regsummarycontent .= $formattedregsummary;
                    $supresscoursesummary = true;
                }
                $center_content .= html_writer::tag('div', $regsummarycontent, array('class' => 'registrar-summary'));
            } else {
                $center_content .= html_writer::tag('div', $registrar_info, array('class' => 'registrar-info'));
            }
        }

        // Editing button for course summary
        if ($PAGE->user_is_editing()) {
            $streditsummary = get_string('editcoursetitle', 'format_ucla');
            $url_options = array(
                'id' => $this->course->id,
            );

            $link_options = array('title' => $streditsummary, 'class' => 'edit_course_summary');

            $moodle_url = new moodle_url('edit.php', $url_options);

            $img_options = array(
                    'class' => 'icon edit iconsmall',
                    'alt' => $streditsummary
                );

            $innards = new pix_icon('t/edit', $link_options['title'], 
                'moodle', $img_options);

            $center_content .= html_writer::tag('span', 
                $OUTPUT->render(new action_link($moodle_url, 
                    $innards, null, $link_options)),
                array('class' => 'editbutton'));
        }

        $center_content .= html_writer::start_tag('div', array('class' => 'summary'));
        // If something is entered for the course summary then display that.        
        if (!empty($this->course->summary) && !$supresscoursesummary) {
            $context = context_course::instance($this->course->id);
            $summary = file_rewrite_pluginfile_urls($this->course->summary, 'pluginfile.php', $context->id, 'course', 'summary', NULL);
            $center_content .= format_text($summary);
        } 
  
        $center_content .= html_writer::end_tag('div');

        // Instructor information
        if (!empty($this->instructors)) {
            include_once($CFG->dirroot . '/blocks/ucla_office_hours/block_ucla_office_hours.php');
            $instr_info = block_ucla_office_hours::render_office_hours_table(
                    $this->instructors, $this->course, $this->context);

            $center_content .= html_writer::tag('div', $instr_info, array('class' => 'instr-info'));
        }        
        
        echo $center_content;
    }
    
    /**
     * Output the html for a single section page .
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
//        $course = course_get_format($course)->get_course();

        // Can we view the section in question?
        if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
            // This section doesn't exist
            print_error('unknowncoursesection', 'error', null, $course->fullname);
            return;
        }

        if (!$sectioninfo->uservisible) {
            if (!$course->hiddensections) {
                echo $this->start_section_list();
                echo $this->section_hidden($displaysection);
                echo $this->end_section_list();
            }
            // Can't view this section.
            return;
        }

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, $displaysection);
        $thissection = $modinfo->get_section_info(0);
//        if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
//            echo $this->start_section_list();
//            echo $this->section_header($thissection, $course, true, $displaysection);
//            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
//            echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
//            echo $this->section_footer();
//            echo $this->end_section_list();
//        }

        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);

        // Title with section navigation links.
//        $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
//        $sectiontitle = '';
//        $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation header headingblock'));
//        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
//        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
        // Title attributes
//        $titleattr = 'mdl-align title';
//        if (!$thissection->visible) {
//            $titleattr .= ' dimmed_text';
//        }
//        $sectiontitle .= html_writer::tag('div', get_section_name($course, $displaysection), array('class' => $titleattr));
//        $sectiontitle .= html_writer::end_tag('div');
//        echo $sectiontitle;

        // Now the list of sections..
        echo $this->start_section_list();

        echo $this->section_header($thissection, $course, true, $displaysection);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();

        echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
        echo $this->section_footer();
        echo $this->end_section_list();

        // Display section bottom navigation.
//        $sectionbottomnav = '';
//        $sectionbottomnav .= html_writer::start_tag('div', array('class' => 'section-navigation mdl-bottom'));
//        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
//        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
//        $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection),
//            array('class' => 'mdl-align'));
//        $sectionbottomnav .= html_writer::end_tag('div');
//        echo $sectionbottomnav;

        // Close single-section div.
        echo html_writer::end_tag('div');
    }

    protected function section_edit_controls($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }
        
        $controls = parent::section_edit_controls($course, $section, $onsectionpage);
        
        // We're expecting section 'hightlight' and 'hide', but we want 
        // to override 'highlight' for 'section edit'.
        $url = new moodle_url('/course/editsection.php', 
                array('id'=> $section->id, 'sr' => $section->section));
        
        $controls[0] = html_writer::link($url, html_writer::img($this->output->pix_url('t/edit'),
                get_string('editsectiontitle', 'format_ucla'), array('class' => 'icon edit')),
                array('title' => get_string('editsectiontitle', 'format_ucla'), 
                      'class' => 'editing_section'));

        return $controls;
    }
 
    /**
     * Generate the display of the header part of a section before
     * course modules are included
     * 
     * Copied from base class method with following differences:
     *  - do not display section summary/edit link for section 0
     *  - always display section title
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=0) {
        global $PAGE;

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section) ) {
                $sectionstyle = ' current';
            }
        }
        
        $o.= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
            'class' => 'section main clearfix'.$sectionstyle));

        // print any external notices
        $this->print_external_notices($section->section, $course);

        // For site info, instead of printing section title/summary, just 
        // print site info releated stuff instead
        if ($section->section == 0) {
            $this->print_section_zero_content();
            $o.= html_writer::start_tag('div', array('class' => 'content'));
        } else {
            $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
            $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

            $o.= html_writer::start_tag('div', array('class' => 'content'));

            // Start section header with section links!
            $o.= html_writer::start_tag('div', array('class' => 'sectionheader'));
            $o.= $this->output->heading($this->section_title($section, $course), 3, 'sectionname');
            
            $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
            $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
            $o.= html_writer::end_tag('div');
            // End section header
            
            if ($PAGE->user_is_editing()) {
                $o .= $this->get_jit_links($section->section);
            }
            
            $o.= html_writer::start_tag('div', array('class' => 'summary'));
            $o.= $this->format_summary_text($section);

            $o.= html_writer::end_tag('div');

            $context = context_course::instance($course->id);
            $o .= $this->section_availability_message($section,
                    has_capability('moodle/course:viewhiddensections', $context));
        }

        return $o;
    }
    
    /**
     * Creates the UCLA format classes, sets up editing icons pref.
     * 
     * @return html
     */
    protected function start_section_list() {
        $classes = 'ucla-format';
        return html_writer::start_tag('ul', array('class' => $classes));
    } 

    /**
     * Generates JIT links for given section.
     * 
     * @param int $section  Section we are on
     * 
     * @return string       Returns JIT link html
     */
    private function get_jit_links($section) {
        $ret_val = html_writer::start_tag('div',
                array('class' => 'jit-links '));

        foreach ($this->jit_links as $jit_type => $jit_string) {
            $link = new moodle_url('/blocks/ucla_easyupload/upload.php',
                    array('course_id' => $this->course->id,
                          'type' => $jit_type,
                          'section' => $section));
            $ret_val .= html_writer::link($link, $jit_string, array('class' => ''));
        }

        $ret_val .= html_writer::end_tag('div');        
        return $ret_val;
    }

    /**
     * Generate the section title with permament section link
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        $title = get_section_name($course, $section);
        $url = new moodle_url('/course/view.php', array('id' => $course->id, 'sectionid' => $section->id));
        return html_writer::link($url, $title);;
    }
    
    /**
     * If courseinfo is not empty, then will parse its contents into user 
     * displayable strings so that course sections can be printed.
     */
    private function parse_courseinfo() {
        if (empty($this->courseinfo)) {
            return false;
        }
        
        $theterm = false;
        $maxcrosslistshown = get_config('local_ucla', 'maxcrosslistshown');
        foreach ($this->courseinfo as $key => $courseinfo) {
            if (count($this->displayinfo) >= $maxcrosslistshown) {
                // going over the limit of crosslists to show, replace them
                // with ...
                $this->displayinfo[$key] = '...';
                break;
            }

            $thisterm = $courseinfo->term;
            if (!$theterm) {
                $theterm = $thisterm;
            } else if ($theterm != $thisterm) {
                debugging('Mismatching terms in crosslisted course.'
                        . $theterm . ' vs ' . $thisterm);
            }

            $course_text = $courseinfo->subj_area . $courseinfo->coursenum . '-' .
                    $courseinfo->sectnum;

            // if section is cancelled, then cross it out
            if (enrolstat_is_cancelled($courseinfo->enrolstat)) {
                $course_text = html_writer::tag('span', $course_text, array('class' => 'cancelled-course'));
            }

            // save section info
            $this->displayinfo[$key] = $course_text;
        }
        
        $this->term = $theterm; // save term for course being displayed
    }
    
}
