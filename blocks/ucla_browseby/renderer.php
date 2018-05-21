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
 * Class file to handle Browse-By renderer.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Browseby renderer.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_browseby_renderer extends plugin_renderer_base {
    /**
     * @var BROWSEBYTABLEID set to browse by courses list.
     */
    const BROWSEBYTABLEID = 'browsebycourseslist';

    /**
     * Renders the UCLA custom list.
     *
     * @param string $data
     * @param int $min
     * @param int $split
     * @param string $customclass
     */
    static public function ucla_custom_list_render($data, $min=8, $split=2,
                                            $customclass='') {
        $s = '';

        $lists = array();
        $cdata = count($data);
        if ($cdata < $min) {
            $lists[] = self::ucla_custom_list_render_helper($data);
        } else {
            $splitted = ceil($cdata / $split);

            for ($i = 0; $i < $split; $i++) {
                if (count($data) < $splitted) {
                    $smaller = $data;
                } else {
                    $smaller = array_splice($data, 0, $splitted);
                }

                $lists[] = self::ucla_custom_list_render_helper($smaller);
            }
        }

        if (!empty($lists)) {
            $ringer = count($lists) - 1;
            foreach ($lists as $list) {
                $s .= html_writer::tag('ul', $list, array(
                    'class' => 'list' . $ringer . " $customclass"
                ));
            }
        }

        return html_writer::tag('div', $s, array('class' => 'browsebylist'));
    }

    /**
     * Helper function for rendering.
     *
     * @param string $data
     * @return string
     */
    static public function ucla_custom_list_render_helper($data) {
        $s = '';

        foreach ($data as $d) {
            $s .= html_writer::tag('li', $d);
        }

        return $s;
    }

    /**
     * Renders the giant list of courses.
     *
     * @param array $courses
     *     Array (
     *         Object {
     *             url => url of course
     *             dispname => displayed for link
     *             instructors => Array ( Instructor names )
     *             fullname => the fullname of the course
     *         }
     *     )
     */
    static public function ucla_browseby_courses_list($courses) {
        global $CFG;

        $disptable = new html_table();
        $disptable->id = self::BROWSEBYTABLEID;
        $disptable->head = self::ucla_browseby_course_list_headers();

        $data = array();

        // Once a ugrad or grad course is found, then print out an anchor tag.
        $foundugrad = false;
        $foundgrad = false;

        if (!empty($courses)) {
            $publicworldsyllabusstring = get_string('icon_public_world_syllabus', 'local_ucla_syllabus');
            $publicuclasyllabusstring = get_string('icon_public_ucla_syllabus', 'local_ucla_syllabus');
            $privatesyllabusstring = get_string('icon_private_syllabus', 'local_ucla_syllabus');

            foreach ($courses as $termsrs => $course) {
                if (!empty($course->nonlinkdispname)) {
                    $courselink = $course->nonlinkdispname . ' '
                        . html_writer::link(new moodle_url(
                            $course->url), $course->dispname);
                } else {
                    $courselink = ucla_html_writer::external_link(
                        new moodle_url($course->url), $course->dispname);
                }

                if (!$foundugrad && intval($course->coursenum) < 200) {
                    $anchor = html_writer::tag('a', '', array('name' => 'ugrad'));
                    $courselink = $anchor . $courselink;
                    $foundugrad = true;
                }
                if (!$foundgrad && intval($course->coursenum) >= 200) {
                    $anchor = html_writer::tag('a', '', array('name' => 'grad'));
                    $courselink = $anchor . $courselink;
                    $foundgrad = true;
                }

                // Generate icon for course syllabus link.
                $syllabus = '';
                if (isset($course->has_public_world_syllabus) ||
                        isset($course->has_public_ucla_syllabus) ||
                        isset($course->has_private_syllabus)) {
                    $syllabus = html_writer::start_tag('a',
                        array('href' => $CFG->wwwroot . '/local/ucla_syllabus/index.php?id=' . $course->courseid));

                    if (isset($course->has_public_world_syllabus)) {
                        $syllabus .= html_writer::tag('img', '',
                            array('src' => $CFG->wwwroot . '/local/ucla_syllabus/pix/public_world.png',
                                'alt' => $publicworldsyllabusstring,
                                'title' => $publicworldsyllabusstring
                                )
                            );
                    } else if (isset($course->has_public_ucla_syllabus)) {
                        $syllabus .= html_writer::tag('img', '',
                            array('src' => $CFG->wwwroot . '/local/ucla_syllabus/pix/public_ucla.png',
                                'alt' => $publicuclasyllabusstring,
                                'title' => $publicuclasyllabusstring
                                )
                            );
                    } else {
                        $syllabus .= html_writer::tag('img', '',
                            array('src' => $CFG->wwwroot . '/local/ucla_syllabus/pix/private.png',
                                'alt' => $privatesyllabusstring,
                                'title' => $privatesyllabusstring
                                )
                            );
                    }
                    $syllabus .= html_writer::end_tag('a');
                }

                $data[] = array($syllabus, $courselink,
                    $course->instructors, $course->fullname);
            }
            $disptable->data = $data;
        } else {
            $cell = new html_table_cell(get_string('noresults', 'admin'));
            $cell->colspan = 4;
            $cell->style = 'text-align: center';
            $row = new html_table_row(array($cell));
            $disptable->data[] = $row;
        }

        return $disptable;
    }

    /**
     * Renders the headers.
     *
     * @return array
     */
    static public function ucla_browseby_course_list_headers() {
        $headelements = array('syllabus', 'course', 'instructors', 'coursetitle');

        foreach ($headelements as $headelement) {
            $headstrs[] = get_string($headelement,
                'block_ucla_browseby');
        }

        return $headstrs;
    }

    /**
     * Convenience function for drawing a terms-drop down
     *
     * @param boolean $defaultterm
     * @param boolean $sql
     * @param array $sqlparams
     */
    static public function render_terms_selector($defaultterm=false,
                                   $sql=false,
                                   $sqlparams=null) {
        global $OUTPUT;

        $contents = get_string('term', 'local_ucla') . ': '
            . $OUTPUT->render(self::terms_selector($defaultterm,
                $sql, $sqlparams));
        return html_writer::tag('div', $contents, array('class' => 'termselector'));
    }

    /**
     * Builds a automatic-redirecting drop down menu, populated
     * with terms. Returns a thing you $OUTPUT->render()
     *
     * @param boolean $defaultterm
     * @param boolean $sql
     * @param array $sqlparams
     */
    static public function terms_selector($defaultterm=false,
            $sql=false, $sqlparams=null) {
        global $DB, $PAGE;

        if (!empty($sql)) {
            $termobjs = $DB->get_records_sql($sql, $sqlparams);
        } else {
            $termobjs = $DB->get_records('ucla_reg_classinfo', null, '',
                'DISTINCT term');
        }

        foreach ($termobjs as $term) {
            $terms[] = $term->term;
        }

        $terms = terms_arr_sort($terms, true);

        // CCLE-3526 - Dynamic selection of archive server notice.
        $precutoffterm = term_get_prev(
            get_config('local_ucla', 'remotetermcutoff')
        );

        if (!$precutoffterm) {
            $precutoffterm = '12W';
        }

        // CCLE-3141 - Prepare for post M2 deployment.
        $terms[] = $precutoffterm;   // make this say Winter 2012 or earlier.

        $urls = array();
        $page = $PAGE->url;
        $default = '';
        foreach ($terms as $term) {
            $thisurl = clone($page);
            $thisurl->param('term', $term);
            $url = $thisurl->out(false);

            if (term_cmp_fn($term, $precutoffterm) < 0) {
                // We have an option for cut-off term and earlier,
                // so no point in displaying terms before the cut-off.
                continue;
            } else if ($term == $precutoffterm) {
                $urls[$url] = ucla_term_to_text($term) . ' or earlier'; // Yes, going to hardcode this...
            } else {
                $urls[$url] = ucla_term_to_text($term);
            }

            if ($defaultterm !== false && $defaultterm == $term) {
                $default = $url;
            }
        }

        $selects = new url_select($urls, $default, null);

        return $selects;
    }
}

