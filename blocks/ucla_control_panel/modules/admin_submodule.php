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
 * This file defines ucla_cp_module_admin class
 * @package block_ucla_control_panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();
/**
 * This class is the admin module for the ucla control panel
 * @copyright  UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class ucla_cp_module_admin extends ucla_cp_module {
    /**
     * @var string $linkarguments args from the link
     */
    private $linkarguments;
    /**
     * @var bool $faulty determines if the contructor param is good
     */
    private $faulty = false;
    /**
     * Constructs your object
     * @param string $linkparam
     * @return void
     */
    public function __construct($linkparam) {
        global $CFG;
        $linkarguments = $linkparam;

        $initactions = new moodle_url($CFG->wwwroot
                . '/admin/tool/uclasupportconsole/index.php',
                $linkarguments);
        $this->faulty = $this->test_param($linkparam);
        parent::__construct($this->get_key(), $initactions);
    }

    /**
     * Returns array of tags for module
     * @return array
     */
    public function autotag() {
        if ($this->faulty) {
            return array('');
        } else {
            return array('ucla_cp_mod_admin_advanced');
        }
    }
    /**
     * Returns the key
     * @return string
     */
    public function get_key() {
        return '';
    }

    /**
     * Gets the term and srs
     * @param stdClass $course
     * @return string
     */
    public static function get_term_and_srs($course) {
        global $CFG, $DB;
        $idnumber = '';
        if (!empty($course->id)) {
            // Only query for term-srs if course exists.
            require_once($CFG->dirroot . '/local/ucla/lib.php');
            $courseinfo = ucla_get_course_info($course->id);
            if (!empty($courseinfo)) {
                // Create string.
                $firstentry = true;
                foreach ($courseinfo as $courserecord) {
                    $firstentry ? $firstentry = false : $idnumber .= ', ';
                    $idnumber .= make_idnumber($courserecord);
                }
            }
        }
        $idnumber = explode('-', $idnumber);
        return $idnumber;
    }

    /**
     * Checks for params
     * @param array $param
     * @return boolean
     */
    public static function test_param($param) {
        if (array_key_exists('term', $param) && ($param['term'] == false)) {
            return true;
        }
        if (array_key_exists('srs', $param) && $param['srs'] == false) {
            return true;
        }
        if (array_key_exists('courseid', $param) && $param['courseid'] == false) {
            return true;
        }
        if (array_key_exists('console', $param) && $param['console'] == false) {
            return true;
        }
        return false;
    }
}
