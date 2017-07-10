<?php
// This file is part of the UCLA browse-by plugin for Moodle - http://moodle.org/
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
 * Class file to handle Browse-By listings.
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 *  This is the base class. Very abstract indeed...
 *
 * @package    block_ucla_browseby
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
abstract class browseby_handler {
    /**
     * Returns what parameters are required for this handler.
     *
     * @return Array( ... ) strings that we should require_param, you do
     *      not need to include 'term'
     **/
    abstract public function get_params();

    /**
     * Fetches a list with an alphabetized index.
     *
     * @param Array $args
     * @return Array(string $title, string $content)
     **/
    abstract public function handle($args);

    /**
     *  Hook function to do some checks before running the handler.
     *
     * @param Array $args
     * @return Array(string $title, string $content)
     **/
    public function run_handler($args) {
        $return = $this->handle($args);
        return $return;
    }

    /**
     *  A highly-specific convenience function. That feeds into the
     *  renderer.
     *
     * A little unclear about these parameters...
     *
     * @param array $data
     * @param string $keyfield
     * @param string $dispfield
     * @param string $type
     * @param string $get
     * @param string $term
     * @param array $evenmore
     * @return array
     **/
    public function list_builder_helper($data, $keyfield, $dispfield, $type, $get,
                                 $term=false, $evenmore=false) {
        global $PAGE;

        $table = array();
        foreach ($data as $datum) {
            if (!empty($datum->no_display)) {
                continue;
            }

            $k = $datum->{$keyfield};
            $queryterms = array('type' => $type, $get => $k);

            if ($term) {
                $queryterms['term'] = $term;
            }

            if ($evenmore) {
                $queryterms = array_merge($queryterms, $evenmore);
            }

            $urlobj = clone($PAGE->url);
            $urlobj->params($queryterms);

            $table[$k] = html_writer::link($urlobj,
                ucla_format_name(strtolower($datum->{$dispfield}))
            );
        }

        return $table;
    }

    /**
     * Returns a display-ready string for subject areas.
     *
     * @param string $subjarea
     **/
    public function get_pretty_subjarea($subjarea) {
        global $DB;

        $sa = $DB->get_field('ucla_reg_subjectarea',
            'subj_area_full', array('subjarea' => $subjarea));

        if ($sa) {
            return $sa;
        }

        return $subjarea;
    }

    /**
     *  Determines whether the course should not be displayed if it doesn't
     *  a website.
     *
     * @param object $course
     **/
    public static function ignore_course($course) {
        // If the course is a particular number.
        if (!empty($course->course_code)) {
            $coursecode = intval(substr($course->course_code, 0, 4));

            $ignorecoursenums = get_config('block_ucla_browseby',
                    'ignore_coursenum');
            if ($ignorecoursenums) {

                // Special formatting.
                if (!is_array($ignorecoursenums)) {
                    $ignorecoursenums = explode(',', $ignorecoursenums);
                }

                foreach ($ignorecoursenums as $ignorecoursenum) {
                    $ignorecoursenum = trim($ignorecoursenum);
                    if ($coursecode == $ignorecoursenum) {
                        return true;
                    }
                }
            }
        }

        // If the course is NOT a particular activity type.
        if (!empty($course->activitytype)) {
            $allowacttypes = get_config('block_ucla_browseby',
                'allow_acttypes');
            if (empty($allowacttypes)) {
                return false;
            } else {
                if (is_string($allowacttypes)) {
                    $acttypes = explode(',', $allowacttypes);
                } else {
                    $acttypes = $allowacttypes;
                }

                foreach ($acttypes as $acttype) {
                    if ($course->activitytype == trim($acttype)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Decoupled function.
     *
     * @param string $name
     * @return boolean
     **/
    protected function get_config($name) {
        if (!isset($this->configs)) {
            $this->configs = get_config('block_ucla_browseby');
        }

        if (empty($this->configs->{$name})) {
            return false;
        }

        return $this->configs->{$name};
    }
    /**
     * Decoupled function.
     *
     * @param string $sql
     * @param array $params
     */
    protected function get_records_sql($sql, $params=null) {
        global $DB;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Decoupled function.
     *
     * @param string $cap
     * @return array
     */
    protected function get_roles_with_capability($cap) {
        return get_roles_with_capability($cap);
    }

    /**
     * Decoupled function.
     *
     * @param int $pc
     * @param int $o
     * @param string $sa
     */
    protected function role_mapping($pc, $o, $sa="*SYSTEM*") {
        return role_mapping($pc, $o, $sa);
    }

    /**
     * Decoupled function.
     *
     * @param string $divisioncode
     */
    protected function get_division($divisioncode) {
        global $DB;

        return ucla_format_name($DB->get_field('ucla_reg_division', 'fullname',
            array('code' => $divisioncode)));
    }
}

