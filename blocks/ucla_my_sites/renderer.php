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

/**
 * My_sites block rendrer
 *
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_my_sites_renderer {

    /**
     * Construct contents of course_overview block
     *
     * @param array $classsites List of courses in sorted order
     * @return array content to be displayed in ucla_my_sites block
     */
    public function class_sites_overview($classsites) {
        global $PAGE;
        $content = array();
        $PAGE->requires->jquery();

        $content[] = html_writer::start_tag('div', array('class' => 'class_sites_div'));
        foreach ($classsites as $x => $class) {
            // Build class title.

            // There might be multiple reg_info records for cross-listed
            // courses, so find section user is enrolled in or use hostcourse.
            $hostreginfo = null; $enrolledreginfo = null;
            $numenrolledfound = 0;
            $titlereginfo = null;   // Which reginfo to use to create title.
            foreach ($class->reg_info as $reginfo) {
                if (isset($reginfo->enrolled) && $reginfo->enrolled) {
                    $enrolledreginfo = $reginfo;
                    ++$numenrolledfound;    // Instructors would have multiple.
                }
                if ($reginfo->hostcourse) {
                    $hostreginfo = $reginfo;
                }
            }
            // If found multiple enrolled or did not find enrolled course, just
            // use hostcourse.
            if ($numenrolledfound > 1 || empty($enrolledreginfo)) {
                $titlereginfo = $hostreginfo;
            } else {
                $titlereginfo = $enrolledreginfo;
            }
            $subjareafull = $this->get_registrar_translation('ucla_reg_subjectarea',
                    $titlereginfo->subj_area, 'subjarea', 'subj_area_full');
            $title = sprintf('%s<br>%s, %s %s - %s', $subjareafull[1],
                    $titlereginfo->coursenum, $titlereginfo->acttype,
                    $titlereginfo->sectnum, $class->fullname);

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
     * Construct contents of collab_overview block.
     *
     * @param array $collaborationsites list of courses in sorted order
     * @param object $cathierarchy hierarchy of all categories for sites
     * @param string $sortoptstring HTML string for the sort options form
     * @param string $sortorder string param for the sort order of collab sites
     *
     * @return array content to be displayed in ucla_my_sites block
     */
    public function collab_sites_overview($collaborationsites, $cathierarchy, $sortoptstring, $sortorder) {
        $content = array();

        $content[] = html_writer::tag('h3', get_string('collaborationsites',
                'block_ucla_my_sites'), array('class' => 'mysitesdivider'));

        // Add the form string to the $content array.
        $content[] = $sortoptstring;
        $content[] = html_writer::start_tag('div', array('class' => 'collab_sites_div'));

        // Traverse the simplified category hierarchy and add the categories and collab sites to
        // $content.
        self::visit_top_categories($cathierarchy, $content, $sortorder);

        $content[] = html_writer::end_tag('div');
        return implode($content);
    }

    /**
     * Takes a bunch of collab sites and adds their links to a passed in
     * content array
     *
     * @param array $collaborationsites Contains stdClass with collab sites.
     * @param array $content The content array to which HTML elements should be added.
     * @param string    $sortorder string param for the sort order of collab sites
     */
    public function display_collab_sites($collaborationsites, &$content, $sortorder) {
        // Sort the collaboration sites now.
        usort($collaborationsites, function($a, $b) use ($sortorder) {
            if ($sortorder === 'sitename') {
                return strcmp($a->fullname, $b->fullname);
            }
            return -1 * strcmp($a->startdate, $b->startdate);
        });
        foreach ($collaborationsites as $x => $collab) {
            // Make link.
            $collablink = html_writer::link(new moodle_url('/course/view.php',
                    array('id' => ($collab->id))), $collab->fullname,
                    array('class' => 'course_title'));

            $collablink .= '<br>';
            $content[] = $collablink;
            if ($x < count($collaborationsites)) {
                $content[] = '<hr class="course_divider">';
            }
        }
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
     * @param string $tofield     The field that we are going to return if we
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

    /**
     * Visits each of the top-level categories from the simplified hierarchy and
     * display their collaboration sites.
     *
     * @param stdClass $cathierarchy   The simplified hierarchy of the collab sites
     * @param array $content   The content array to which HTML strings are added
     * @param string $sortorder   The sortorder of the collab sites
     */
    public function visit_top_categories($cathierarchy, &$content, $sortorder) {
        foreach (array_values($cathierarchy->children) as $index => $category) {
            $content[] = html_writer::tag('h4', $category->name);
            $content[] = html_writer::start_div("collab_sites_container");
            self::display_collab_sites($category->collabsites, $content, $sortorder);
            $content[] = html_writer::end_div();
        }
    }
}
