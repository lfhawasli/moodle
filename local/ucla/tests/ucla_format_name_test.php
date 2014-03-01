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
 * Unit tests for the ucla_format_name public function.
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
class ucla_format_name_test extends basic_testcase {

    /**
     * Tests that spaces are handled properly.
     */
    public function test_space() {
        $result = ucla_format_name('FIRST LAST');
        $this->assertTrue((bool) $result, 'First Last');
        // Should also trim.
        $result = ucla_format_name('AN N EA	');
        $this->assertEquals($result, 'An N Ea');
    }

    /**
     * Tests that hypens are handled properly.
     */
    public function test_hypen() {
        $result = ucla_format_name('FIRST-LAST');
        $this->assertEquals($result, 'First-Last');
    }

    /**
     * Tests that aprostrophes are handled properly.
     */
    public function test_aprostrophe() {
        $result = ucla_format_name("FIRST'LAST");
        $this->assertEquals($result, "First'Last");
    }

    /**
     * Tests that names with "Mc" are handled properly.
     */
    public function test_mc() {
        $result = ucla_format_name("OLD MCDONALD");
        $this->assertEquals($result, "Old McDonald");
        // Should also trim.
        $result = ucla_format_name("OLD   MCDONALD");
        $this->assertEquals($result, "Old McDonald");
    }

    /**
     * Note, when a name has an ampersand it will have spaces around it. Else
     * the second word wouldn't be capitalized. This test case is when we need
     * to format divison/subject area long names.
     */
    public function test_ampersand() {
        $result = ucla_format_name("FIRST & LAST");
        $this->assertEquals($result, "First & Last");
        // Should also trim.
        $result = ucla_format_name("FIRST     &      LAST");
        $this->assertEquals($result, "First & Last");
    }

    /**
     * Note, when a name has an slash it will have spaces around it. Else
     * the second word wouldn't be capitalized. This test case is when we need
     * to format divison/subject area long names.
     */
    public function test_slash() {
        // Public function should still have spaces around /.
        $result = ucla_format_name("DESIGN / MEDIA ARTS");
        $this->assertEquals($result, "Design / Media Arts");
        // Should also trim.
        $result = ucla_format_name("DESIGN    /    MEDIA ARTS");
        $this->assertEquals($result, "Design / Media Arts");
    }

    /**
     * When formatting a name, if is something in the format of 
     * "WOMEN'S STUDIES", then the "S" after the apostrophe should not be 
     * capitalized.
     */
    public function test_posessive_s() {
        $result = ucla_format_name("WOMEN'S STUDIES");
        $this->assertEquals($result, "Women's Studies");
    }

    /**
     * Make sure that conjunctions are not capitlized, e.g. "and", "of", "the", 
     * "as", "a". Needed when formatting subject areas.
     */
    public function test_conjunctions() {
        $result = ucla_format_name("Conservation Of Archaeological And Ethnographic Materials",
                true);
        $this->assertEquals($result,
                "Conservation of Archaeological and Ethnographic Materials");

        $result = ucla_format_name("Indigenous Languages OF THE Americas", true);
        $this->assertEquals($result, "Indigenous Languages of the Americas");
    }

    /**
     * Now test a complex string with every special case in it.
     */
    public function test_complex_string() {
        $result = ucla_format_name("MCMARY HAD A LITTLE-LAMB & IT'S FLEECE / WAS WHITE AS SNOW",
                true);
        $this->assertEquals($result,
                "McMary Had a Little-Lamb & It's Fleece / Was White as Snow");
    }

    /**
     * Make sure that European multipart names are handled properly.
     */
    public function test_multipart_names() {
        $testcases = array('VAN GOGH' => 'van Gogh',
            'DE BEAUVOIR' => 'de Beauvoir',
            'VAN DER BRUIN' => 'van der Bruin',
            'DA DABS' => 'da Dabs');
        foreach ($testcases as $testcase => $expected) {
            $actual = ucla_format_name($testcase);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Handle people with initials in their names. Should left capital if an
     * initial is preceeded by exactly 1 letter only and ends in a period.
     */
    public function test_initials() {
        $testcases = array('P.J.' => 'P.J.',
            'p.j.' => 'P.J.',
            ' p. j. ' => 'P. J.',
            ' j.r. ewing' => 'J.R. Ewing',
            'Sentance string.' => 'Sentance String.');
        foreach ($testcases as $testcase => $expected) {
            $actual = ucla_format_name($testcase);
            $this->assertEquals($expected, $actual);
        }
    }

}