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
 * Renderer for outputting the ucla course format.
 *
 * Based off the topic course format.
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
require_once($CFG->dirroot.'/enrol/locallib.php');

/**
 * Basic renderer for ucla format. Based off the topic renderer.
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ucla_renderer extends format_topics_renderer {
    /** @var $courseinfo course info, may contain reginfo.*/
    private $courseinfo = array();

    /** @var $displayinfo parsed version of $courseinfo, used to display course sections.*/
    private $displayinfo = array();

    /** @var $instructors array of instructors for course.*/
    private $instructors = array();

    /** @var $course course object.*/
    private $course = null;

    /** @var $context context object.*/
    private $context = null;

    /** @var $term term for course that is being rendered.*/
    private $term = null;

    /** @var $jitlinks strings to generate jit links.*/
    private $jitlinks = array();

    /**
     * Constructor method, do necessary setup for UCLA format.
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Get reg info, if any.
        $this->courseinfo = ucla_get_course_info($page->course->id);

        // Parse that reg info.
        $this->parse_courseinfo();

        // Get instructors, if any.
        $this->instructors = course_get_format($page->course)->display_instructors();

        // Save course object.
        $this->course = course_get_format($page->course)->get_course();

        // Save context object.
        $this->context =& $page->context;

        // Use the public/private renderer.  This will permit us to override the
        // way we render course modules.
        $this->courserenderer = $this->page->get_renderer('local_publicprivate');
    }

    /**
     * Calls ucla_format_notices event and sees if any notices are returned.
     * Expects notices to be returned in an array of HTML content. Just displays
     * content as is.
     *
     * Then echos those notices out.
     *
     * @param int $sectionnum            Section being displayed
     * @param stdClass $course           Course selected
     */
    public function print_external_notices($sectionnum, $course) {
        global $OUTPUT, $PAGE, $USER;

        // Maybe some external notice system is redirecting back with a message.
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

        // Check if courseinfo is set, so that we can get a possible term.
        $courseinfo = null;
        if (!empty($this->courseinfo)) {
            // Use reset instead of array_pop, because pop alters the array.
            $courseinfo = reset($this->courseinfo);

        }

        // Retrieve plugins' notices.
        if ($pluginsfunction = get_plugins_with_function('ucla_format_notices')) {
            foreach ($pluginsfunction as $plugintype => $plugins) {
                foreach ($plugins as $pluginfunction) {
                    $pluginfunction($course, $courseinfo);
                }
            }
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
     * Output the meta information about the course in the main course header part.
     */
    public function print_site_meta_text() {
        global $CFG;

        // Formatting and determining information to display for these courses.
        $regcoursetext = '';
        $termtext = '';

        foreach ($this->courseinfo as $c) {
            if ($c->hostcourse == 1) {
                $hostcourse = str_replace(' ', '', ucla_make_course_title($c));
                break;
            }
        }

        if (!empty($this->courseinfo)) {
            // Don't show too many.
            $regcourseinfo = implode(' / ', $this->displayinfo);
            $hostfocus = html_writer::tag('span', $hostcourse,
                    array('class' => 'reg-hostcourse'));
            $regcourseinfo = str_replace($hostcourse, $hostfocus, $regcourseinfo);

            $regcoursetext = html_writer::tag('span', $regcourseinfo,
                    array('class' => 'reg-courses'));
            $termtext = html_writer::tag('span', ucla_term_to_text($this->term),
                    array('class' => 'label-term ' . $this->get_term_label($this->term)));

        }

        // This is for the sets of instructors in a course.
        $imploder = array();
        $insttext = '';
        if (!empty($this->instructors)) {
            foreach ($this->instructors as $instructor) {
                if (in_array($instructor->shortname, $CFG->instructor_levels_roles['Instructor'])
                        || in_array($instructor->shortname, $CFG->instructor_levels_roles['Student Facilitator'])) {
                    $imploder[$instructor->id] = $instructor->lastname;
                }
            }
        }

        if (empty($imploder)) {
            $insttext = 'N/A';
        } else {
            $insttext = implode(' / ', $imploder);
        }

        $headingtext = '';
        if (!empty($termtext)) {
            $headingtext = $termtext . ' - ' . $regcoursetext . ' - ' . $insttext;
            $headingtext = html_writer::tag('div', $headingtext, array('class' => 'site-meta'));
        }

        return $headingtext;
    }

    /**
     * Output the html for the course section header. Also displays
     * public/private message if user is not logged in.
     */
    public function print_header() {
        global $OUTPUT;

        // Display page header.
        // Handle cancelled classes.
        if (is_course_cancelled($this->courseinfo)) {
            echo $OUTPUT->notification(get_string('coursecancelled', 'format_ucla'), 'notifywarning');
        } else {
            // Display message if user is viewing an old course.
            $notice = notice_course_status($this->course);
            // CCLE-5741 - Only show out of term message if it is enabled in course settings.
            if (!empty($notice)) {
                echo $notice;
            } else {
                // Display public/private notice, if applicable.
                echo notice_nonenrolled_users($this->course);
            }
        }

        // CCLE-6557 - Print easy to find "Enroll me" button.
        echo $this->print_self_enrollment_button();
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

        // Section 0, aka "Site info".
        $thissection = $sections[0];
        unset($sections[0]);
        // Do not display section summary/header info for section 0.
        echo $this->section_header($thissection, $course, false);

        echo $this->courserenderer->course_section_cm_list($course, $thissection);

        if ($PAGE->user_is_editing()) {
            $output = $this->courserenderer->course_section_add_cm_control($course, 0);
            echo $output; // If $return argument in print_section_add_menus() set to false.
        }
        echo $this->section_footer();

        $numsections = course_get_format($course)->get_last_section_number();
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);
        for ($section = 1; $section <= $numsections; $section++) {
            // People who cannot view hidden sections are not allowed to see sections titles with no content.
            $nocontent = empty($sections[$section]->sequence) && empty($sections[$section]->summary);
            if (empty($nocontent) || $canviewhidden) {
                if (!empty($sections[$section])) {
                    $thissection = $sections[$section];
                } else {
                    // This will create a course section if it doesn't exist.
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
                        ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo));
                if (!$showsection) {

                    unset($sections[$section]);
                    continue;
                }

                // Always show section content, even if editing is off.
                echo $this->section_header($thissection, $course, false);
                if ($thissection->uservisible) {
                    echo $this->courserenderer->course_section_cm_list($course, $thissection);

                    if ($PAGE->user_is_editing()) {
                        $output = $this->courserenderer->course_section_add_cm_control($course, $section);
                        echo $output;
                    }
                }
                echo $this->section_footer();
            }
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

        $centercontent = '';

        // Course Information specific has a different section header.
        if (!empty($this->courseinfo)) {
            // We need the stuff...
            $regclassurls = array();
            $regfinalurls = array();
            $numdisplayinfo = count($this->displayinfo);
            for ($key = 0; $key < $numdisplayinfo; $key++) {
                $displayinfo = $this->displayinfo[$key];
                $courseinfo = $this->courseinfo[$key];

                $url = new moodle_url($courseinfo->url);
                $regclassurls[$key] = html_writer::link($url, $displayinfo);

                $regfinalurls[$key] = html_writer::link(
                        build_registrar_finals_url($courseinfo), $displayinfo
                );
            }

            $registrarinfo = get_string('reg_listing', 'format_ucla');

            $registrarinfo .= implode(', ', $regclassurls);
            $registrarinfo .= html_writer::empty_tag('br');

            $registrarinfo .= get_string('reg_finalcd', 'format_ucla');
            $registrarinfo .= implode(', ', $regfinalurls);

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
                    $regsummarycontent .= html_writer::tag('div', $formattedregsummary . $toggle,
                            array('class' => 'registrar-summary-hidden'));
                    $centercontent .= html_writer::tag('div', $registrarinfo,
                            array('class' => 'registrar-info registrar-summary-hidden'));
                } else {
                    $centercontent .= html_writer::tag('div', $registrarinfo, array('class' => 'registrar-info'));
                    $regsummarycontent .= $formattedregsummary;
                    $supresscoursesummary = true;
                }
                $centercontent .= html_writer::tag('div', $regsummarycontent, array('class' => 'registrar-summary'));
            } else {
                $centercontent .= html_writer::tag('div', $registrarinfo, array('class' => 'registrar-info'));
            }
        }

        // Editing button for course summary.
        if ($PAGE->user_is_editing()) {
            $streditsummary = get_string('editcoursetitle', 'format_ucla');
            $urloptions = array(
                'id' => $this->course->id,
            );

            $linkoptions = array('title' => $streditsummary, 'class' => 'edit_course_summary');

            $moodleurl = new moodle_url('edit.php', $urloptions);

            $imgoptions = array(
                    'class' => 'icon edit iconsmall',
                    'alt' => $streditsummary
                );

            $innards = new pix_icon('t/edit', $linkoptions['title'],
                'moodle', $imgoptions);

            $centercontent .= html_writer::tag('span',
                $OUTPUT->render(new action_link($moodleurl,
                    $innards, null, $linkoptions)),
                array('class' => 'editbutton'));

        }

        $centercontent .= html_writer::start_tag('div', array('class' => 'summary'));
        // If something is entered for the course summary then display that.
        if (!empty($this->course->summary) && !$supresscoursesummary) {
            $context = context_course::instance($this->course->id);
            $summary = file_rewrite_pluginfile_urls($this->course->summary,
                    'pluginfile.php', $context->id, 'course', 'summary', null);
            $centercontent .= format_text($summary);
        }

        $centercontent .= html_writer::end_tag('div');

        // Instructor information.
        if (!empty($this->instructors)) {
            include_once($CFG->dirroot . '/blocks/ucla_office_hours/block_ucla_office_hours.php');
            $instrinfo = block_ucla_office_hours::render_office_hours_table(
                    $this->instructors, $this->course, $this->context);

            $centercontent .= html_writer::tag('div', $instrinfo, array('class' => 'instr-info'));
        }

        echo $centercontent;
    }

    /**
     * Output the html for a self-enrollment button.
     *
     * See CCLE-6557.
     *
     * @return string
     */
    private function print_self_enrollment_button() {
        global $OUTPUT, $PAGE;
        $retval = '';
        // Check if course has "self-enrollment" plugin enabled and user is not
        // enrolled.
        $selfenrol = enrol_selfenrol_available($this->course->id);
        if ($selfenrol == true && !is_enrolled($this->context)) {
            $enrolmestr = get_string('enrolme', 'enrol_self');
            $url = new moodle_url('/enrol/index.php', array('id' => $this->course->id));

            $enrols = enrol_get_plugins(true);
            $enrolinstances = enrol_get_instances($this->course->id, true);

            foreach ($enrolinstances as $instance) {
                if (!isset($enrols[$instance->enrol])) {
                    continue;
                }
                if ($instance->enrol == "self") {
                    $enrolmestr = get_string('enrolme', 'enrol_self');
                    $url = new moodle_url('/enrol/index.php',
                            array('id' => $this->course->id,
                            'sesskey' => sesskey(),
                            'instance' => $instance->id,
                            // This magic value is necessary to automatically
                            // enroll the user rather than have them land on
                            // the enroll index page.
                            '_qf__'.$instance->id.'_enrol_self_enrol_form' => 1));
                    $enrollmebtn = new enrol_user_button($url, $enrolmestr);
                    $enrollmebtn->class = 'text-xs-center';
                    $renderer = $PAGE->get_renderer('enrol');
                    $enrollalert = get_string('enrollmealert', 'format_ucla');
                    $enrollalert .= $renderer->render($enrollmebtn);
                    return $OUTPUT->box($enrollalert, 'alert alert-warning');
                }
            }
        }
        return $retval;
    }

    /**
     * Output the html for a single section page.
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

        // Can we view the section in question?
        if (!($sectioninfo = $modinfo->get_section_info($displaysection))) {
            // This section doesn't exist.
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

        // Copy activity clipboard.
        echo $this->course_activity_clipboard($course, $displaysection);
        $thissection = $modinfo->get_section_info(0);

        // Start single-section div.
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);

        // Now the list of sections.
        echo $this->start_section_list();

        echo $this->section_header($thissection, $course, true, $displaysection);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();

        echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);
        echo $this->section_footer();
        echo $this->end_section_list();

        // Close single-section div.
        echo html_writer::end_tag('div');
    }

    /**
     * Generate the edit controls of a section
     *
     * @param stdClass $course The course entry
     * @param stdClass $section The course_section entry
     * @param bool $onsectionpage true if being printed on a section page
     * @return array $controls array of links with edit controls
     */
    protected function section_edit_controls($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $controls = parent::section_edit_controls($course, $section, $onsectionpage);

        // We're expecting section 'highlight' and 'hide', but we want
        // to override 'highlight' for 'section edit'.
        $url = new moodle_url('/course/editsection.php',
                array('id' => $section->id, 'sr' => $section->section));

        $controls[0] = html_writer::link($url, html_writer::img($this->output->image_url('t/edit'),
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

        $o .= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
            'class' => 'section main clearfix'.$sectionstyle));

        // Print any external notices.
        $this->print_external_notices($section->section, $course);

        // For site info, instead of printing section title/summary, just
        // print site info releated stuff instead.
        if ($section->section == 0) {
            $this->print_section_zero_content();
            $o .= html_writer::start_tag('div', array('class' => 'content'));
        } else {
            $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
            $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

            $o .= html_writer::start_tag('div', array('class' => 'content'));

            // Start section header with section links!
            $o .= html_writer::start_tag('div', array('class' => 'sectionheader'));
            $o .= $this->output->heading($this->section_title($section, $course), 3, 'sectionname');

            $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
            $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side',
                    'style' => 'position: relative; top: -40px;'));
            $o .= html_writer::end_tag('div');
            // End section header.

            $o .= html_writer::start_tag('div', array('class' => 'summary'));
            $o .= $this->format_summary_text($section);

            $o .= html_writer::end_tag('div');

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
     * Generate the section title with permament section link.
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
                // Going over the limit of crosslists to show, replace them
                // with...
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

            $coursetext = $courseinfo->subj_area . $courseinfo->coursenum . '-' .
                    $courseinfo->sectnum;

            // If section is cancelled, then cross it out.
            if (enrolstat_is_cancelled($courseinfo->enrolstat)) {
                $coursetext = html_writer::tag('span', $coursetext, array('class' => 'cancelled-course'));
            }

            // Save section info.
            $this->displayinfo[$key] = $coursetext;
        }

        $this->term = $theterm; // Save term for course being displayed.
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        global $PAGE;
        
        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $controls = parent::section_edit_control_items($course, $section, $onsectionpage);

        // Removing moveup/movedown and highlight items from section link menu.
        unset($controls['moveup']);
        unset($controls['movedown']);
        unset($controls['highlight']);
        
        $sectionreturn = $onsectionpage ? $section->section : null;
        
        // Adding new menu items to section link menu.
        $newcontrols = array();

        $newcontrols['header_add'] = array('name' => get_string('add', 'format_'.$course->format));

        // Adding file to section links menu.
        $url = new moodle_url('modedit.php', array(
            'add' => 'resource',
            'course' => $course->id,
            'section' => $section->section,
            'return'=> 0,
            'sr'=> $sectionreturn
        ));
        $newcontrols['file'] = array(
            'url' => $url,
            'icon' => '',
            'name' => get_string('file', 'format_'.$course->format),
            'attr' => array('class' => 'center-action-link')
        );

        // Adding link to section links menu.
        $url = new moodle_url('modedit.php', array(
            'add' => 'url',
            'course' => $course->id,
            'section' => $section->section,
            'return'=> 0,
            'sr'=> $sectionreturn
        ));
        $newcontrols['link'] = array(
            'url' => $url,
            'icon' => '',
            'name' => get_string('link', 'format_'.$course->format),
            'attr' => array('class' => 'center-action-link')
        );

        // Adding label link to section links menu.
        $url = new moodle_url('modedit.php', array(
            'add' => 'label',
            'course' => $course->id,
            'section' => $section->section,
            'return'=> 0,
            'sr'=> $sectionreturn
        ));
        $newcontrols['label'] = array(
            'url' => $url,
            'icon' => '',
            'name' => get_string('label', 'format_'.$course->format),
            'attr' => array('class' => 'center-action-link')
        );

        // Adding Activity / Resource link to section links menu.
        if (course_ajax_enabled($course) && $course->id == $PAGE->course->id) {
            $newcontrols['activity'] = array(
                'name' => get_string('activity', 'format_'.$course->format),
                'icon' => '',
                'textattr' => array('class' => 'section-modchooser-text center-action-link'),
                'linkattr' => array('class' => 'section-modchooser-link')
            );
        }

        $newcontrols['header_manage'] = array('name' => get_string('manage', 'format_'.$course->format));

        // Reordering menu items.
        $sectionlinkmenu = $newcontrols + $controls;

        return $sectionlinkmenu;
    }

    /**
     * Generate the edit control action menu
     *
     * @param array $controls The edit control items from section_edit_control_items
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @return string HTML to output.
     */
    protected function section_edit_control_menu($controls, $course, $section) {
        $o = "";
        if (!empty($controls)) {
            $menu = new action_menu();
            $menu->set_menu_trigger(get_string('sectiontools', 'format_'.$course->format));
            $menu->attributes['class'] .= ' section-actions';
            foreach ($controls as $key => $value) {
                $url = empty($value['url']) ? '' : $value['url'];
                $name = empty($value['name']) ? '' : $value['name'];
                $attr = empty($value['attr']) ? array() : $value['attr'];
                $class = empty($value['pixattr']['class']) ? '' : $value['pixattr']['class'];
                $alt = empty($value['pixattr']['alt']) ? '' : $value['pixattr']['alt'];
                // Rewriting $icon, not all menu items have an icon.
                $icon = empty($value['icon']) ? null : new pix_icon($value['icon'], $alt, null, array('class' => "smallicon " . $class));

                if ($key == 'header_add' || $key == 'header_manage') {
                    // Created new action_menu_header object to handle subheaders.
                    $al = new theme_uclashared\action_menu_header($name);
                } else if ($key == 'activity') {
                    // Activity link needs to be handled differently from other links.
                    $textattr = empty($value['textattr']) ? array() : $value['textattr'];
                    $linkattr = empty($value['linkattr']) ? array() : $value['linkattr'];

                    $span = html_writer::tag('span', $name, $textattr);
                    $al = html_writer::tag('span', $span, $linkattr);
                } else {
                    $al = new action_menu_link_secondary(
                        new moodle_url($url),
                        $icon,
                        $name,
                        $attr
                    );
                }
                $menu->add($al);
            }

            $o .= html_writer::div($this->render($menu), 'section_action_menu',
                array('data-sectionid' => $section->id));
        }

        return $o;
    }
}
