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
        global $USER, $CFG, $OUTPUT, $PAGE;
        $content = array();
        $PAGE->requires->jquery();

        $content[] = html_writer::start_tag('div', array('class' => 'class_sites_div'));
        foreach ($classsites as $x => $class) {
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
            $subjareafull = $this->get_registrar_translation('ucla_reg_subjectarea',
                    $reginfo->subj_area, 'subjarea', 'subj_area_full');
            $reginfo = reset($class->reg_info);
            $title = sprintf('%s<br>%s, %s %s - %s', $subjareafull[1],
                    $reginfo->coursenum, $reginfo->acttype,
                    $reginfo->sectnum, $class->fullname);

            // Add link.
            if (!empty($class->url)) {
                $classlink = ucla_html_writer::external_link(
                    new moodle_url($class->url),
                    $title, array('class' => 'course_title'));
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
            // Add Activity Log under each course if notifications exist.
            if (property_exists($class, 'id') && isset($overviews[$class->id])) {
                $alerts = count($overviews[$class->id]);
                // Create Notification Icon with number of alerts.
                $classlink .= html_writer::tag('a', $alerts, array(
                        'href' => '#/', 'class' => 'alertCount',
                        'id' => 'expand_course', 'style' => 'background-image: url("'.
                        new moodle_url('/blocks/ucla_my_sites/img/message.png').'")',
                        'title' => get_string('clicktohideshow')));

                // Add activity display inside div so it can be expanded/collapsed
                // by above icon.
                $classlink .= html_writer::tag('div', $this->activity_display_opt
                        ($class->id, $overviews[$class->id], true),
                        array('class' => 'course_div'));
            }

            $classlink .= '<br>';
            $content[] = $classlink;
            if ($x < count($classsites) - 1) {
                $content[] = '<hr class="course_divider">';
            }
        }
        $content[] = html_writer::end_tag('div');
        // Add spacing between My sites & Collaboration sites sections.
        $content[] = '<br><br>';
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

        $collapser = '';
        // Add a collapse/expand icon if any class sites have notifications.
        foreach ($overviews as $id => $value) {
            foreach ($collaborationsites as $collabsite) {
                if ($collabsite->id == $id) {
                    $collapser = 'T';
                    break;
                }
            }
            if ($collapser == 'T') {
                break;
            }
        }
        if ($collapser == 'T') {
            $collapser = html_writer::tag('a', html_writer::tag('img', '', array('src'
                    => new moodle_url('/blocks/ucla_my_sites/img/expanded.svg'),
                    'class' => 'collab_course_expand')), array('href' => '#/'));
        }

        $content[] = html_writer::tag('h3', get_string('collaborationsites',
                'block_ucla_my_sites').$collapser, array('class' => 'mysitesdivider'));

        $content[] = html_writer::start_tag('div', array('class' => 'collab_sites_div'));
        foreach ($collaborationsites as $x => $collab) {
            // Make link.
            $collablink = html_writer::link(new moodle_url('/course/view.php',
                    array('id' => ($collab->id))), $collab->fullname,
                    array('class' => 'course_title'));

            // Add Activity Log under each course if notifications exist.
            if (property_exists($collab, 'id') && isset($overviews[$collab->id])) {
                $alerts = count($overviews[$collab->id]);
                // Create Notification Icon with number of alerts.
                $collablink .= html_writer::tag('a', $alerts, array(
                        'href' => '#/', 'class' => 'alertCount',
                        'id' => 'expand_course', 'style' => 'background-image: url("'.
                        new moodle_url('/blocks/ucla_my_sites/img/message.png').'")',
                        'title' => get_string('clicktohideshow')));
                // Add activity display inside div so it can be expanded/collapsed
                // by above icon.
                $collablink .= html_writer::tag('div', $this->activity_display_opt($collab->id,
                        $overviews[$collab->id], true), array('class' => 'course_div'));
            }
            $collablink .= '<br>';
            $content[] = $collablink;
            if ($x < count($collaborationsites) - 1) {
                $content[] = '<hr class="course_divider">';
            }
        }
        $content[] = html_writer::end_tag('div');
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

    /**
     *  Returns the long name of the target if found.
     *
     *  This is used for getting the long name for divisions and
     *  subject areas.
     *
     *  May alter the state of the object.
     *
     * @param string $table        The table to use.
     * @param string $target       The string we are translating.
     * @param string $fromfield   The field that we are using to search if the
     *                             target exists.
     * @param string $to_field     The field that we are going to return if we
     *                             find the target entry.
     * @return An array containing both the short and long name of the target.
     *         If a long name was not found, will return the short name again.
     */
    private function get_registrar_translation($table, $target, $fromfield,
            $tofield) {
        global $DB;

        if (!isset($this->reg_trans[$table]) || $this->reg_trans == null) {
            $this->reg_trans = array();

            $indexedsa = array();

            $translations = $DB->get_records($table);

            foreach ($translations as $translate) {
                $indexedsa[$translate->$fromfield] = $translate->$tofield;
            }

            $this->reg_trans[$table] = $indexedsa;
        }

        if (!isset($this->reg_trans[$table][$target])) {
            return array($target, $target);
        }

        // Format result nicely, not in all caps.
        return array($target,
                ucla_format_name($this->reg_trans[$table][$target], true));
    }
}
