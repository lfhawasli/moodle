<?php
// This file is part of the UCLA local plugin for Moodle - http://moodle.org/
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
 * Unit tests for term related functions.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class term_test extends basic_testcase {

    /**
     * Added an optional parameter to sort descending (most recent first).
     */
    public function test_decreasing_sort() {
        // Test terms decreasing sort.
        $testcases[] = array(
            '11F',  // Newest term on record.
            '111',
            '11S',
            '11W'
        );

        // Test mixed terms decreasing sort.
        $testcases[] = array(
            '12F',  // Newest term on record.
            '121',
            '12S',
            '12W',
            '11F'
        );

        foreach ($testcases as $orderedlist) {
            $tmplist = $orderedlist;
            shuffle($tmplist);

            // Maybe once in a blue moon this will fail?
            $this->assertNotEquals($orderedlist, $tmplist);

            $tmplist = terms_arr_sort($tmplist, true);
            $this->assertEquals($orderedlist, array_values($tmplist));
        }
    }

    /**
     * Tests terms_range() and terms_arr_fill() functions.
     */
    public function test_fills() {
        // Argument.
        $a = array(
            '11F',
            '12F',
            '13F'
        );

        // Final.
        $f = array(
            '11F', '12W', '12S', '121',
            '12F', '13W', '13S', '131',
            '13F'
        );

        // Result.
        $r = terms_range('11F', '13F');
        $this->assertEquals($r, $f);

        $r = terms_arr_fill($a);
        $this->assertEquals($r, $f);
    }

    /**
     * Tests term sorting.
     */
    public function test_sorts() {
        // Test year sort.
        $testcases[] = array(
            '02F',
            '03F',
            '09F',
            '11F',
            '13F',
        );

        // Test terms sort.
        $testcases[] = array(
            '11W',
            '11S',
            '111',
            '11F',
        );

        // Test mixed sort.
        $testcases[] = array(
            '11W',
            '11S',
            '111',
            '11F',
            '12W',
            '12S',
            '121',
            '12F',
        );

        // Test pre-y2k terms.
        $testcases[] = array(
            '65F',  // Oldest term on record at Registrar.
            '81F',
            '99S',
            '00W',
            '641'
        );

        foreach ($testcases as $orderedlist) {
            $tmplist = $orderedlist;
            shuffle($tmplist);

            // Maybe once in a blue moon this will fail?
            $this->assertNotEquals($orderedlist, $tmplist);

            $tmplist = terms_arr_sort($tmplist);
            $this->assertEquals($orderedlist, array_values($tmplist));
        }
    }

    /**
     * Tests term_enum.
     */
    public function test_term_enum() {
        try {
            $r = term_enum('3232');
        } catch (Exception $e) {
            $this->assertEqual($e->getMessage(), 'error/improperenum');
        }

        try {
            $r = term_enum('32K');
        } catch (Exception $e) {
            $this->assertEqual($e->getMessage(), 'error/improperenum');
        }
    }

    /**
     * Test function term_get_next.
     */
    public function test_term_get_next() {
        // Input => expected output.
        $testcases = array('99F' => '00W', '13W' => '13S', '13S' => '131',
            '131' => '13F');

        foreach ($testcases as $input => $expectedoutput) {
            $this->assertEquals($expectedoutput, term_get_next($input));
        }

        // Also test null/invalid case.
        $this->assertEquals(null, term_get_next(null));
        $this->assertEquals(null, term_get_next('123'));
    }

    /**
     * Test function term_get_prev.
     */
    public function test_term_get_prev() {
        // Input => expected output.
        $testcases = array('00W' => '99F', '13S' => '13W', '131' => '13S',
            '13F' => '131');

        foreach ($testcases as $input => $expectedoutput) {
            $this->assertEquals($expectedoutput, term_get_prev($input));
        }

        // Also test null/invalid case.
        $this->assertEquals(null, term_get_prev(null));
        $this->assertEquals(null, term_get_prev('123'));
    }

    /**
     * Tests term_role_can_view() function.
     */
    public function test_termrolefilter() {
        // Previous term, before cutoff week.
        $r = term_role_can_view('11F', 'student', '12W', 1, 2);
        $this->assertTrue((bool)($r !== false));

        // Previous term, after cutoff week.
        $r = term_role_can_view('11F', 'student', '12W', 3, 2);
        $this->assertTrue((bool)($r === false));

        // Future term, after cutoff week.
        $r = term_role_can_view('121', 'student', '12W', 3, 2);
        $this->assertTrue((bool)($r !== false));

        // Previous term, after cutoff week, with powerful role.
        $r = term_role_can_view('11F', 'editinginstructor', '12W', 3, 2);
        $this->assertTrue((bool)($r !== false));
    }


    /**
     * Tests ucla_term_to_text.
     */
    public function test_ucla_term_to_text() {
        $result = ucla_term_to_text('11F');
        $this->assertEquals($result, 'Fall 2011');
        $result = ucla_term_to_text('09W');
        $this->assertEquals($result, 'Winter 2009');
        $result = ucla_term_to_text('13S');
        $this->assertEquals($result, 'Spring 2013');
        $result = ucla_term_to_text('121');
        $this->assertEquals($result, 'Summer 2012');
        // Pass in session.
        $result = ucla_term_to_text('121', 'A');
        $this->assertEquals($result, 'Summer 2012 - Session A');
    }

}
