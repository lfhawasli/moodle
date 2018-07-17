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
 * Fixes problems with course sections that might be caused by Moodle plugins.
 *
 * @package     local_ucla
 * @copyright   2013 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Class definition.
 *
 * @copyright   2013 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_course_section_fixer {

    /**
     * Checks if the order of sections in the DB is sequential.
     *
     * @param stdClass $course
     *
     * @return boolean          Returns true if no problems found, otherwise
     *                          returns false.
     */
    static public function check_section_order(stdClass $course) {
        global $DB;

        $sections = $DB->get_records('course_sections',
                array('course' => $course->id), 'section', 'section');

        $current = 0;
        foreach ($sections as $section) {
            if ($section->section != $current) {
                return false;
            }
            ++$current;
        }

        return true;
    }

    /**
     * Check that all sections mentioned in course_modules table exist in
     * course_sections table.
     *
     * @param stdClass $course
     * @return boolean
     */
    static public function check_sections_exist(stdClass $course) {
        global $DB;

        // Get all sections.
        $sections = $DB->get_records('course_sections', array('course' => $course->id));

        // Get all sections that course modules belong to and see if they exist.
        $cms = $DB->get_records('course_modules', array('course' => $course->id),
                null, 'id,section');
        foreach ($cms as $cm) {
            if (!array_key_exists($cm->section, $sections)) {
                // Section doesn't exist.
                return false;
            } else {
                // Make sure course module exists in section.
                if (strpos($sections[$cm->section]->sequence, $cm->id) === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Find and fix any problems with a course's sections.
     *
     * If any problems were found and fixed, will rebuild the course cache.
     *
     * @param stdClass $course
     * @return array            Returns an array of number of sections that were
     *                          added, deleted, or updated.
     */
    static public function fix_problems(stdClass $course) {
        global $DB;
        $retval = array('added' => 0, 'deleted' => 0, 'updated' => 0);

        if (!self::has_problems($course)) {
            return $retval;
        }

        // Fix problems and keep tally.
        $methods = get_class_methods(get_called_class());
        foreach ($methods as $method) {
            if (strpos($method, 'handle_') === 0) {
                $results = self::$method($course);
                foreach ($results as $type => $num) {
                    $retval[$type] += $num;
                }
            }
        }

        // If any changes were made, then we need to rebuild the course cache.
        if ($retval['added'] > 0 || $retval['deleted'] > 0 || $retval['updated'] > 0) {
            rebuild_course_cache($course->id);
        }

        return $retval;
    }

    /**
     * Renumbers course sections so that they are sequential.
     *
     * @param stdClass $course
     *
     * @return array            Returns an array of number of sections that were
     *                          added, deleted, or updated.
     */
    static public function handle_section_order(stdClass $course) {
        global $DB;
        $retval = array('added' => 0, 'deleted' => 0, 'updated' => 0);

        $sections = $DB->get_records('course_sections',
                array('course' => $course->id), 'section', 'id,section');

        $current = 0;
        foreach ($sections as $section) {
            if ($section->section != $current) {
                // Section not in expected order, so renumber it.
                $section->section = $current;
                $DB->update_record('course_sections', $section);
                ++$retval['updated'];
            }
            ++$current;
        }

        return $retval;
    }

    /**
     * Adds sections that belong to course modules, but are now missing.
     *
     * Missing sections are hidden and added to the end of the section list with
     * the title "Recovered section".
     *
     * @param stdClass $course
     *
     * @return array            Returns an array of number of sections that were
     *                          added, deleted, or updated.
     */
    static public function handle_sections_exist(stdClass $course) {
        global $DB;
        $retval = array('added' => 0, 'deleted' => 0, 'updated' => 0);

        // Get all sections.
        $sections = $DB->get_records('course_sections', array('course' => $course->id));

        // Get all sections that course modules belong to and see if they exist.
        $cmsections = $DB->get_fieldset_select('course_modules', 'DISTINCT section', 'course = ?', array($course->id));
        foreach ($cmsections as $cmsection) {
            // We need to create a section before we can use the Moodle API to
            // delete it and all other course modules.
            if (!array_key_exists($cmsection, $sections)) {
                // Found missing section. Add it.
                $section = new stdClass();
                $section->id = $cmsection;
                $section->course = $course->id;
                $section->name = 'Recovered section';
                $section->visible = 1;  // Visible so that we can hide it later.

                // Add section to last section number.
                $maxsection = $DB->get_field('course_sections', 'MAX(section)',
                        array('course' => $course->id));
                $section->section = $maxsection + 1;

                // Find all course modules for this section and add them.
                $cmids = $DB->get_fieldset_select('course_modules', 'id',
                        'course = ? AND section = ?', array($course->id, $section->id));
                $section->sequence = implode(',', $cmids);

                // Need to use insert raw so that id can be specified.
                try {
                    $DB->insert_record_raw('course_sections', $section, false, false, true);
                } catch (Exception $ex) {
                    // And error can result if trying to add a section that
                    // already exists. That happens if section belonged to
                    // another course.
                    // Add new section and set section to newly created section
                    // id.
                    $section->id = $DB->insert_record('course_sections', $section);
                    $sql = "UPDATE {course_modules}
                               SET section=:newsectionid
                             WHERE course=:course
                               AND section=:oldsectionid";
                    $DB->execute($sql, array('newsectionid' => $section->id,
                        'course' => $course->id, 'oldsectionid' => $cmsection));
                }
                rebuild_course_cache($course->id, true);  // Since we added section.

                // Now delete section.
                try {
                    course_delete_section($course, $section);
                    ++$retval['deleted'];
                } catch (Exception $ex) {
                    // One reason why a section cannot be deleted is because it
                    // has invalid course modules with an instance id of 0.
                    // We cannot delete the module.

                    // Hide section and the modules inside.
                    course_update_section($course->id, $section, array('visible' => 0));
                    ++$retval['added'];
                }
            }
        }

        return $retval;
    }

    /**
     * Calls check methods and returns true if problems were found, otherwise
     * returns false.
     *
     * @param stdClass $course
     *
     * @return boolean
     */
    static public function has_problems(stdClass $course) {
        global $DB;

        // Call check methods and exit out if they all return true.
        $methods = get_class_methods(get_called_class());
        $noproblems = true;
        foreach ($methods as $method) {
            if (!$noproblems) {
                break;
            }
            if (strpos($method, 'check_') === 0) {
                $noproblems = self::$method($course);
            }
        }

        return !$noproblems;
    }
}
