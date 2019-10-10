<?php
// This file is part of the UCLA course creator plugin for Moodle - http://moodle.org/
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
 * Tests the UCLA course requestor lib file.
 *
 * @package    tool_uclacourserequestor
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uclacourserequestor/lib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends basic_testcase {

    /**
     * Makes sure that the get_course_num() function returns the proper course
     * number.
     */
    public function test_get_course_num() {
        $testcases = array('7' => 7, 'M20' => 20, '270C' => 270, 'M119L' => 119);
        foreach ($testcases as $testcase => $expectedoutput) {
            $this->assertEquals($expectedoutput, get_course_num($testcase));
        }
    }

    /**
     * Makes sure that get_class_type() returns the proper course type for a
     * given request.
     */
    public function test_get_class_type() {
        // Tutorial class.
        $tutorial = array('activitytype' => 'TUT', 'catlg_no' => '0099    ');
        $this->assertEquals('tut', get_class_type($tutorial));

        // Create an undergraduate class.
        $ugrad = array('activitytype' => 'LEC', 'catlg_no' => '0019    ');
        $this->assertEquals('ugrad', get_class_type($ugrad));

        // Create a graduate class.
        $grad = array('activitytype' => 'LEC', 'catlg_no' => '0223C M ');
        $this->assertEquals('grad', get_class_type($grad));

    }
}