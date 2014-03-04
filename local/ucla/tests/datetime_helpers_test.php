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
 * Unit tests for datetimehelpers.php.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/datetimehelpers.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class datetimehelpers_test extends basic_testcase {

    /**
     * Test distance_of_time_in_words when using seconds.
     */
    public function test_distance_of_time_in_words_seconds() {
        $starttime = time();

        // Less than 5 seconds.
        $result = distance_of_time_in_words($starttime, $starttime + 4, true);
        $this->assertEquals($result,
                get_string('less_than_x_seconds', 'local_ucla', 5));

        // Less than 20 seconds.
        $result = distance_of_time_in_words($starttime, $starttime + 19, true);
        $this->assertEquals($result,
                get_string('less_than_x_seconds', 'local_ucla', 20));

        // Exactly 1 minute.
        $result = distance_of_time_in_words($starttime, $starttime + 60, true);
        $this->assertEquals($result, get_string('a_minute', 'local_ucla'));
        $result = distance_of_time_in_words($starttime, $starttime + 60);
        $this->assertEquals($result, get_string('a_minute', 'local_ucla'));
    }

    /**
     * Test distance_of_time_in_words minutes (without seconds).
     */
    public function test_distance_of_time_in_words_minutes() {
        $starttime = time();

        // Less than a minute.
        $result = distance_of_time_in_words($starttime, $starttime + 5);
        $this->assertEquals($result, get_string('less_minute', 'local_ucla'));

        // A minute.
        $result = distance_of_time_in_words($starttime, $starttime + 59);
        $this->assertEquals($result, get_string('a_minute', 'local_ucla'));

        // In x_minutes.
        $result = distance_of_time_in_words($starttime, $starttime + 60 * 44);
        $this->assertEquals($result, get_string('x_minutes', 'local_ucla', 44));
    }

    /**
     * Test distance_of_time_in_words hours.
     */
    public function test_distance_of_time_in_words_hours() {
        $starttime = time();

        // About_hour.
        $result = distance_of_time_in_words($starttime, $starttime + 60 * 46);
        $this->assertEquals($result, get_string('about_hour', 'local_ucla'));
        $result = distance_of_time_in_words($starttime, $starttime + 60 * 89);
        $this->assertEquals($result, get_string('about_hour', 'local_ucla'));

        // In about_x_hour.
        $result = distance_of_time_in_words($starttime, $starttime + 60 * 60 * 6);
        $this->assertEquals($result,
                get_string('about_x_hours', 'local_ucla', 6));
        $result = distance_of_time_in_words($starttime, $starttime + 60 * 60 * 23);
        $this->assertEquals($result,
                get_string('about_x_hours', 'local_ucla', 23));
    }

    /**
     * Test distance_of_time_in_words days.
     */
    public function test_distance_of_time_in_words_days() {
        $starttime = time();

        // In a_day (Less than 2 days).
        $result = distance_of_time_in_words($starttime, $starttime + 60 * 60 * 24);
        $this->assertEquals($result, get_string('a_day', 'local_ucla'));
        $result = distance_of_time_in_words($starttime, $starttime + 60 * 60 * 47);
        $this->assertEquals($result, get_string('a_day', 'local_ucla'));

        // In x_days.
        $result = distance_of_time_in_words($starttime,
                $starttime + 60 * 60 * 24 * 2);
        $this->assertEquals($result, get_string('x_days', 'local_ucla', 2));
        $result = distance_of_time_in_words($starttime,
                $starttime + 60 * 60 * 24 * 30);
        $this->assertEquals($result, get_string('x_days', 'local_ucla', 30));
    }

}