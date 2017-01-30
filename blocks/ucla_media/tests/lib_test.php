<?php
// This file is part of the UCLA Media block for Moodle - http://moodle.org/
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
 * Simple Unit Testing for is_on_campus_ip() function of locallib.php in ucla_media.
 *
 * @package    block_ucla_media
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_media/locallib.php');

/**
 * Class file.
 *
 * @package    block_ucla_media
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blocks_ucla_media_lib_test extends basic_testcase {

    /**
     * Tests for on campus ip's.
     *
     */
    public function test_on_campus() {
        $test1 = is_on_campus_ip("128.97.0.1");
        $this->assertEquals($test1, true);
        $test2 = is_on_campus_ip("131.179.0.1");
        $this->assertEquals($test2, true);
        $test3 = is_on_campus_ip("149.142.0.1");
        $this->assertEquals($test3, true);
        $test4 = is_on_campus_ip("164.67.0.1");
        $this->assertEquals($test4, true);
        $test5 = is_on_campus_ip("169.232.0.1");
        $this->assertEquals($test5, true);
        $test6 = is_on_campus_ip("172.16.0.1");
        $this->assertEquals($test6, true);
        $test7 = is_on_campus_ip("192.35.210.1");
        $this->assertEquals($test7, true);
        $test8 = is_on_campus_ip("192.35.225.0");
        $this->assertEquals($test8, true);
        $test9 = is_on_campus_ip("192.154.2.1");
        $this->assertEquals($test9, true);
    }

    /**
     * Tests for off campus ip's.
     *
     */
    public function test_off_campus() {
        $test1 = is_on_campus_ip("128.96.0.1");
        $this->assertEquals($test1, false);
        $test2 = is_on_campus_ip("132.179.0.1");
        $this->assertEquals($test2, false);
        $test3 = is_on_campus_ip("141.142.0.1");
        $this->assertEquals($test3, false);
        $test4 = is_on_campus_ip("161.67.0.1");
        $this->assertEquals($test4, false);
        $test5 = is_on_campus_ip("168.232.0.1");
        $this->assertEquals($test5, false);
        $test6 = is_on_campus_ip("172.161.0.1");
        $this->assertEquals($test6, false);
        $test7 = is_on_campus_ip("191.35.210.1");
        $this->assertEquals($test7, false);
        $test8 = is_on_campus_ip("197.35.225.0");
        $this->assertEquals($test8, false);
        $test9 = is_on_campus_ip("192.159.2.1");
        $this->assertEquals($test9, false);
    }
}
