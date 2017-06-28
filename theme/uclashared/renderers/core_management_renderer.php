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
 * Override Moodle's core course management renderer.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/classes/management_renderer.php');

/**
 * Overriding the core course management render (/course/classes/management_renderer.php).
 *
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_core_course_management_renderer extends core_course_management_renderer {

    /**
     * Renderers a course list item with visual cues to indicate term.
     *
     * This function will be called for every course being displayed by course_listing.
     *
     * @param coursecat $category The currently selected category and the category the course belongs to.
     * @param course_in_list $course The course to produce HTML for.
     * @param int $selectedcourse The id of the currently selected course.
     * @return string
     */
    public function course_listitem(coursecat $category, course_in_list $course, $selectedcourse) {

        $text = $course->get_formatted_name();
        $attributes = array(
            'class' => 'listitem listitem-course',
            'data-id' => $course->id,
            'data-selected' => ($selectedcourse == $course->id) ? '1' : '0',
            'data-visible' => $course->visible ? '1' : '0'
        );

        $bulkcourseinput = array(
            'type' => 'checkbox',
            'name' => 'bc[]',
            'value' => $course->id,
            'class' => 'bulk-action-checkbox',
            'aria-label' => get_string('bulkactionselect', 'moodle', $text),
            'data-action' => 'select'
        );
        if (!$category->has_manage_capability()) {
            // Very very hardcoded here.
            $bulkcourseinput['style'] = 'visibility:hidden';
        }

        $viewcourseurl = new moodle_url($this->page->url, array('courseid' => $course->id));

        $html  = html_writer::start_tag('li', $attributes);
        $html .= html_writer::start_div('clearfix');

        if ($category->can_resort_courses()) {
            // In order for dnd to be available the user must be able to resort the category children..
            $html .= html_writer::div($this->output->pix_icon('i/move_2d', get_string('dndcourse')), 'float-left drag-handle');
        }

        $html .= html_writer::start_div('ba-checkbox float-left');
        $html .= html_writer::empty_tag('input', $bulkcourseinput).'&nbsp;';
        $html .= html_writer::end_div();
        $html .= html_writer::link($viewcourseurl, $text, array('class' => 'float-left coursename', 'data-name' => $text));
        $html .= html_writer::start_div('float-right');
        if ($course->idnumber) {
            $html .= html_writer::tag('span', s($course->idnumber), array('class' => 'dimmed idnumber'));
        }
        $html .= $this->course_listitem_actions($category, $course);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('li');
        return $html;
    }

    /**
     * Re-renders the pagination list as a boostrap pagination list.
     *
     * @param coursecat $category The category to produce pagination for.
     * @param int $page The current page.
     * @param int $perpage The number of courses to display per page.
     * @param bool $showtotals Set to true to show the total number of courses and what is being displayed.
     * @return string
     */
    protected function listing_pagination(coursecat $category, $page, $perpage, $showtotals = false) {
        $out = parent::listing_pagination($category, $page, $perpage, $showtotals);

        // Remove the original class in favor of bootstrap class.
        $out = str_replace('listing-pagination', 'pagination', $out);

        // Remove the yui3-button class.
        $out = str_replace('yui3-button', '', $out);

        // Remove ...
        $out = str_replace('...', '', $out);

        // Wrap the <a> inslide <li>.
        $out = str_replace('<a', '<li><a', $out);
        $out = str_replace('</a>', '</a></li>', $out);

        // Return wrapped in a 'center' div.
        return html_writer::div($out, 'center-text');
    }
}