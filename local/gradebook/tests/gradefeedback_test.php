<?php
// This file is part of the UCLA local gradebook plugin for Moodle - http://moodle.org/
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
 * Tests the MyUCLA gradebook webservice integration by using mock objects.
 *
 * @package    local_gradebook
 * @category   phpunit
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
//require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot . '/local/gradebook/locallib.php');

/**
 * PHPunit testcase class.
 *
 * @package    local_gradebook
 * @category   phpunit
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradefeedback_test extends advanced_testcase {

    private $course;
    private $assign;
    private $student;

    /**
     * Given a string, return true or false if it contains invalid characters,
     * which arecharacters that have a demical value of 20 or less on the ASCII
     * table, except tabs, new lines, and carriage returns.
     *
     * See http://www.asciitable.com
     *
     * @param string $string
     * @return boolean
     */
    public function has_invalid_characters($string) {
        $length = textlib::strlen($string);
        for ($i=0; $i<$length; $i++) {
            $current = ord($string{$i});
            if (($current == 0x9) ||
                    ($current == 0xA) ||
                    ($current == 0xD) ||
                    (($current >= 0x20) && ($current <= 0xD7FF)) ||
                    (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                    (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                // Valid character.
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns sample feedback strings that have invalid characters inserted
     * into them.
     * 
     * @return array
     */
    public function invalidfeedback_provider() {
        // Create 5 random string with 1 or more invalid characters.
        $retval = array();
        while (count($retval) < 5) {
            $string = $this->rand_string(rand(5, 200), true);
            if ($this->has_invalid_characters($string)) {
                $retval[] = array($string);
            }
        }

        return $retval;
    }

    /**
     * Creates a random string of characters.
     *
     * From http://www.lateralcode.com/creating-a-random-string-with-php.
     *
     * @param int $length
     * @param boolean $includeinvalidchars  If true, will seed string with
     *                                      invalid characters, which are
     *                                      characters that have a demical value
     *                                      of 20 or less on the ASCII table,
     *                                      except tabs, new lines, and carriage
     *                                      returns.
     *                                      (see http://www.asciitable.com/).
     * @return string
     */
    private function rand_string($length, $includeinvalidchars=false) {
        $str = '';
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ";

        if ($includeinvalidchars) {
            for ($i=1; $i<=20; $i++) {
                if (in_array($i, array(9, 10, 13))) {
                    // Skip horizontal tab, new line, and carriage return.
                    continue;
                }
                $chars .= chr($i);
            }
        }

        $size = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[rand(0, $size - 1)];
        }

        return $str;
    }

    /**
     * Creates test course, student, and assignment to grade.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Create course.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = array_pop($class);
        $this->course = get_course($course->courseid);

        // Create graded activity.
        $this->assign = $this->getDataGenerator()->create_module('assign',
                array('course' => $this->course->id));

        $this->student = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user();

        // Set fake MyUCLA gradebook settings.
        set_config('gradebook_id', 99);
        set_config('gradebook_password', 'test');
    }

    /**
     * Tests the filtering of invalid characters in the grade_grades.feedback
     * column.
     *
     * @dataProvider invalidfeedback_provider
     */
    function test_invalid_feedback($feedback) {
        // Insert student grade/feedback.
        $gi = grade_item::fetch(array('itemtype' => 'mod', 'itemmodule' => 'assign',
            'iteminstance' => $this->assign->id, 'courseid' => $this->course->id));

        $grade = new ucla_grade_grade();
        $grade->itemid = $gi->id;
        $grade->userid = $this->student->id;
        $grade->rawgrade = 80;
        $grade->finalgrade = 80;
        $grade->rawgrademax = 100;
        $grade->rawgrademin = 0;
        $grade->feedback = $feedback;
        $grade->timecreated = time();
        $grade->timemodified = time();
        $grade->insert();

        // Now create the MyUCLA parameters and make sure feedback is filtered.
        $courseinfo = ucla_get_course_info($this->course->id);
        $courseinfo = reset($courseinfo);
        $courseinfo->uidstudent = $this->student->idnumber;
        $result = $grade->make_myucla_parameters($courseinfo, 1);

        $isinvalid = $this->has_invalid_characters($result['mGrade']['comment']);
        $this->assertFalse($isinvalid);
    }

    /**
     * Tests the triming of long feedback in the grade_grades.feedback column.
     */
    function test_long_feedback() {
        // Insert student grade/feedback.
        $gi = grade_item::fetch(array('itemtype' => 'mod', 'itemmodule' => 'assign',
                    'iteminstance' => $this->assign->id, 'courseid' => $this->course->id));

        $grade = new ucla_grade_grade();
        $grade->itemid = $gi->id;
        $grade->userid = $this->student->id;
        $grade->rawgrade = 80;
        $grade->finalgrade = 80;
        $grade->rawgrademax = 100;
        $grade->rawgrademin = 0;
        $grade->timemodified = time();
        $grade->timecreated = time();

        // Create super long feedback, over grade_reporter::MAX_COMMENT_LENGTH.
        $feedback = trim($this->rand_string(grade_reporter::MAX_COMMENT_LENGTH + 100));
        $feedbacklength = textlib::strlen($feedback);
        $this->assertTrue($feedbacklength > grade_reporter::MAX_COMMENT_LENGTH);
        $grade->feedback = $feedback;

        $grade->insert();

        // Now create the MyUCLA parameters and make sure feedback is trimed.
        $courseinfo = ucla_get_course_info($this->course->id);
        $courseinfo = reset($courseinfo);
        $courseinfo->uidstudent = $this->student->idnumber;
        $result = $grade->make_myucla_parameters($courseinfo, 1);

        $commentlength = textlib::strlen($result['mGrade']['comment']);
        $this->assertLessThan($feedbacklength, $commentlength);
        $endswithellipses = textlib::substr($result['mGrade']['comment'],
                -textlib::strlen(get_string('continue_comments', 'local_gradebook')))
                == get_string('continue_comments', 'local_gradebook');
        $this->assertTrue($endswithellipses);
    }
}
