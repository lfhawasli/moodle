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
 * my_sites block renderer
 *
 * @package    block_ucla_my_sites
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot.'/blocks/course_overview/renderer.php');
/**
 * My_sites block rendrer
 *
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_my_sites_renderer extends block_course_overview_renderer {

    /**
     * Construct contents of course_overview block
     *
     * @param array     $courses list of courses in sorted order
     * @param array     $overviews list of course overviews
     * @return array content to be displayed in ucla_my_sites block
     */
    public function class_sites_overview($classsites, $overviews) {
        global $USER, $CFG, $OUTPUT;
        $content = array();

        foreach ($classsites as $class) {
            // Build class title in following format.
            // <subject area> <cat_num>, <activity_type e.g. Lec, Sem> <sec_num> (<term name e.g. Winter 2012>): <full name>.

            // There might be multiple reg_info records for cross-listed courses.
            $classtitle = ''; $firstentry = true; $numentries = 0;
            $maxcrosslistshown = get_config('local_ucla', 'maxcrosslistshown');
            foreach ($class->reg_info as $reginfo) {
                $firstentry ? $firstentry = false : $classtitle .= '/';

                // Don't show too many cross-listed entries.
                if ($numentries >= $maxcrosslistshown) {
                    $classtitle .= '...';
                    break;
                }

                $classtitle .= sprintf('%s %s, %s %s',
                        $reginfo->subj_area,
                        $reginfo->coursenum,
                        $reginfo->acttype,
                        $reginfo->sectnum);
                ++$numentries;
            }

            $reginfo = reset($class->reg_info);
            $title = sprintf('%s (%s): %s',
                    $classtitle,
                    ucla_term_to_text($reginfo->term,
                        $reginfo->session_group), $class->fullname);

            // Add link.
            if (!empty($class->url)) {
                $classlink = ucla_html_writer::external_link(
                    new moodle_url($class->url),
                    $title);
            } else {
                // Courses without urls should not have information
                // stating that they are crosslisted.
                if (count($class->reg_info) != 1) {
                    debugging('strangeness!');
                } else {
                    // This external link generation mechanism should
                    // be pulled outside this block.
                    $classlink = "$title " . html_writer::link(
                        new moodle_url(
                            course_handler::registrar_url(reset(
                                $class->reg_info))
                        ),
                        html_writer::tag(
                                'span',
                                get_string('registrar_link',
                                    'block_ucla_browseby'),
                                array('class' => 'registrar-link')
                            )
                    );
                }
            }
            if (property_exists($class, 'id') && isset($overviews[$class->id])) {
                $classlink .= $this->activity_display_opt($class->id, $overviews[$class->id], false);
            } else {
                $classlink .= '<br>';
            }
            $content[] = $classlink;
        }
        // Add spacing between My sites & Collaboration sites sections.
        $content[] = '<br>';
        return implode($content);
    }

    /**
     * Construct contents of course_overview block
     *
     * @param array     $courses list of courses in sorted order
     * @param array     $overviews list of course overviews
     * @return array content to be displayed in ucla_my_sites block
     */
    public function collab_sites_overview($collaborationsites, $overviews) {
        global $USER, $CFG, $OUTPUT, $PAGE;
        $content = array();

        // Sort a bunch of collabortation sites via fullname.
        array_alphasort($collaborationsites, "fullname");

        $content[] = html_writer::tag('h3', get_string('collaborationsites',
                'block_ucla_my_sites'), array('class' => 'mysitesdivider'));

        foreach ($collaborationsites as $collab) {
            // Make link.
                $collablink = html_writer::link(new moodle_url('/course/view.php',
                    array('id' => ($collab->id))), $collab->fullname);

            // Append here to $class_link.
            if (property_exists($collab, 'id') && isset($overviews[$collab->id])) {
                $collablink .= $this->activity_display_opt($collab->id,
                        $overviews[$collab->id], true);
            } else {
                $collablink .= '<br>';
            }
            $content[] = $collablink;
        }
        return implode($content);
    }

    /**
     * Coustuct activities overview for a course
     *
     * @param int $cid course id
     * @param array $overview overview of activities in course
     * @param bool $default initial collapsed state to use if the user_preference it not set.
     * @return string html of activities overview
     */
    protected function activity_display_opt($cid, $overview, $default) {
        $output = html_writer::start_tag('div', array('class' => 'activity_info'));
        foreach (array_keys($overview) as $module) {
            $output .= html_writer::start_tag('div', array('class' => 'activity_overview'));
            $url = new moodle_url("/mod/$module/index.php", array('id' => $cid));
            $modulename = get_string('modulename', $module);
            $icontext = html_writer::link($url, $this->output->pix_icon('icon',
                    $modulename, 'mod_'.$module, array('class' => 'iconlarge')));
            if (get_string_manager()->string_exists("activityoverview", $module)) {
                $icontext .= get_string("activityoverview", $module);
            } else {
                $icontext .= get_string("activityoverview", 'block_course_overview', $modulename);
            }

            // Add collapsible region with overview text in it.
            $output .= $this->collapsible_region($overview[$module], '', 'region_'.$cid.'_'.$module, $icontext, '', $default);

            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div');
        return $output;
    }
    /**
     * Print (or return) the start of a collapsible region
     *
     * The collapsibleregion has a caption that can be clicked to expand or collapse the region.
     * If JavaScript is off, then the region will always be expanded.
     *
     * @param string $classes class names added to the div that is output.
     * @param string $id id added to the div that is output. Must not be blank.
     * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
     * @param string $userpref the name of the user preference that stores the user's preferred default state.
     *      (May be blank if you do not wish the state to be persisted.
     * @param boolean $default Initial collapsed state to use if the user_preference it not set.
     * @return string returns a string of HTML.
     */
    protected function collapsible_region_start($classes, $id, $caption, $userpref = '', $default = false) {
        // Expand by default.
        $expand = false;
        return print_collapsible_region_start($classes, $id, $caption, $userpref, $default, true);
    }
}
