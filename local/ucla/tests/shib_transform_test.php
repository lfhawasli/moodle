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
 * Tests the shib_transform.php formatting.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * PHPunit testcase class.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class shib_transform_test extends basic_testcase {

    /**
     * Include script uses this method.
     *
     * From auth/shibboleth/auth.php
     *
     * @param string $string Possibly multi-valued attribute from Shibboleth
     * @return string
     */
    private function get_first_string($string) {
        $list = explode( ';', $string);
        $cleanstring = rtrim($list[0]);

        return $cleanstring;
    }

    /**
     * Test handling of alternative name.
     */
    public function test_alternative_name() {
        global $CFG;

        $result['firstname'] = 'Joe';
        $result['lastname'] = 'Bruin';
        $result['alternatename'] = 'Josephine';
        $result['institution'] = '';

        include($CFG->dirroot . '/shib_transform.php');

        // Check log result.
        $this->assertEquals($result['firstname'], 'Josephine');
        $this->assertEquals($result['alternatename'], 'Joe');
        $this->assertEquals($pnaction, "set alternatename was set");
    }

    /**
     * If a user is using a alternative name, then check if need to append
     * suffix.
     */
    public function test_alternative_name_suffix() {
        global $CFG;

        $result['firstname'] = 'Joe';
        $result['lastname'] = 'Bruin';
        $result['alternatename'] = 'Josephine';
        $result['institution'] = '';

        $_SERVER['SHIBUCLAPERSONNAMESUFFIX'] = 'JR';

        include($CFG->dirroot . '/shib_transform.php');

        // Suffix was added.
        $this->assertEquals($result['lastname'], 'Bruin, JR');

        $result['lastname'] = 'Bruin';
        $_SERVER['SHIBUCLAPERSONNAMESUFFIX'] = 'II';

        include($CFG->dirroot . '/shib_transform.php');

        // Suffix was added.
        $this->assertEquals($result['lastname'], 'Bruin II');
    }

    /**
     * If display name is different than a user's last, first middle name, then
     * parse display name and use that intead.
     */
    public function test_display_name_diff() {
        global $CFG;

        $result['firstname']    = 'JAMES';
        $result['middlename']   = 'MICHAEL';
        $result['lastname']     = 'FRANKS';
        $_SERVER['SHIBDISPLAYNAME'] = 'Franks, Mike';
        $result['institution'] = '';

        include($CFG->dirroot . '/shib_transform.php');

        $this->assertEquals($pnaction, 'set fn, ln to displayName and cleared mn');
        $this->assertEquals($result['firstname'], 'MIKE');
        $this->assertEmpty($result['middlename']);
    }

    /**
     * If the display name matches a user's last, first middle name, then that
     * means first and middle name were appended together.
     */
    public function test_display_name_same() {
        global $CFG;

        // No middle name.
        $result['firstname'] = 'Joe';
        $result['middlename'] = '';
        $result['lastname'] = 'Bruin';
        $_SERVER['SHIBDISPLAYNAME'] = 'BRUIN, JOE';
        $result['institution'] = '';

        include($CFG->dirroot . '/shib_transform.php');

        $this->assertEquals($pnaction, 'displayName equals ln, fn mn. Name unchanged.');

        // Middle name.
        $result['middlename'] = 'Awesome';
        $_SERVER['SHIBDISPLAYNAME'] = 'BRUIN, JOE AWESOME';

        include($CFG->dirroot . '/shib_transform.php');

        $this->assertEquals($pnaction, 'displayName equals ln, fn mn. Name unchanged.');
    }


    /**
     * Test handling special name cases.
     */
    public function test_special_cases() {
        global $CFG;

        $result['firstname'] = 'MI';
        $result['middlename'] = 'KYUNG';
        $result['lastname'] = 'KIM';
        $_SERVER['SHIBDISPLAYNAME'] = 'KIM, MI KYUNG';
        $result['institution'] = '';
        $result['idnumber'] = '900804617';

        include($CFG->dirroot . '/shib_transform.php');

        $this->assertEquals($pnaction, 'Special case found');
        $this->assertEquals($result['firstname'], 'MI KYUNG');
        $this->assertEmpty($result['middlename']);

        $result['firstname'] = 'SHAREE';
        $result['middlename'] = 'BANTAD';
        $result['lastname'] = 'ANZALDO';
        $_SERVER['SHIBDISPLAYNAME'] = 'ANZALDO, SHAREE BANTAD';
        $result['institution'] = '';
        $result['idnumber'] = '902840840';

        include($CFG->dirroot . '/shib_transform.php');

        $this->assertEquals($pnaction, 'Special case found');
        $this->assertEquals($result['firstname'], 'SHAREE BANTAD');
        $this->assertEmpty($result['middlename']);
    }
}
