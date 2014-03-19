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
 * Override Moodle's core calendar renderer.
 *
 * @package    theme_uclashared
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/course/renderer.php');

/**
 * Overriding the core course renderer (course/renderer.php).
 *
 * @copyright  UC Regents 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class theme_uclashared_core_course_renderer extends core_course_renderer {

    /**
     * List the courses, but using our advanced search highlighting.
     *
     * @param array $courses array of course records (or instances of course_in_list) to show on this page
     * @param bool $showcategoryname whether to add category name to the course description
     * @param string $additionalclasses additional CSS classes to add to the div.courses
     * @param moodle_url $paginationurl url to view more or url to form links to the other pages in paging bar
     * @param int $totalcount total number of courses on all pages, if omitted $paginationurl will be displayed as 'View more' link
     * @param int $page current page number (defaults to 0 referring to the first page)
     * @param int $perpage number of records per page (defaults to $CFG->coursesperpage)
     * @return string
     */
    public function courses_list($courses, $showcategoryname = false, $additionalclasses = null,
            $paginationurl = null, $totalcount = null, $page = 0, $perpage = null) {
        global $CFG;

        // Create instance of coursecat_helper to pass display options to the
        // public function rendering the courses list.
        $chelper = new coursecat_helper();
        if ($showcategoryname) {
            $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT);
        } else {
            $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED);
        }

        if ($totalcount !== null && $paginationurl !== null) {
            // Add options to display pagination.
            if ($perpage === null) {
                $perpage = $CFG->coursesperpage;
            }
            $chelper->set_courses_display_options(array(
                'limit' => $perpage,
                'offset' => ((int) $page) * $perpage,
                'paginationurl' => $paginationurl,
            ));
        } else if ($paginationurl !== null) {
            // Add options to display 'View more' link.
            $chelper->set_courses_display_options(array('viewmoreurl' => $paginationurl));
            $totalcount = count($courses) + 1;
        }

        // Perform search highlighting for the advanced search.
        $searchcriteria = optional_param('search', '', PARAM_TEXT);
        $chelper->set_search_criteria(array('search' => $searchcriteria));

        // When displaying the course summary in the search results, we have
        // three potential sources: the course summary, the registrar summary,
        // and the registrar description. We want to show the source which
        // contains the search term. But if none of the sources contains the
        // term (for example if the term comes from the course title/category)
        // then we seek to simply show a non-empty one.
        foreach ($courses as $course) {
            if (stripos($course->summary, $searchcriteria) !== false) {
                // Course summary contains search term.
                $summarysource = $course->summary;
            } else if (stripos($course->reg_summary, $searchcriteria) !== false) {
                // Registrar summary contains search term.
                $summarysource = $course->reg_summary;
            } else if (stripos($course->reg_desc, $searchcriteria) !== false) {
                // Registrar description contains search term.
                $summarysource = $course->reg_desc;
            } else if (!empty($course->summary)) {
                // No search term, but course summary is non-empty.
                $summarysource = $course->summary;
            } else if (!empty($course->reg_summary)) {
                // No search term, but registrar summary is non-empty.
                $summarysource = $course->reg_summary;
            } else if (!empty($course->reg_desc)) {
                // No search term, but registrar description is non-empty.
                $summarysource = $course->reg_desc;
            } else {
                // There is no summary/description of the course anywhere.
                $summarysource = '';
            }
            $course->summary = $summarysource;
        }

        $chelper->set_attributes(array('class' => $additionalclasses));
        $content = $this->coursecat_courses($chelper, $courses, $totalcount);
        return $content;
    }

}