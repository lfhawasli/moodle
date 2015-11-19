<?php
// This file is part of the OID WOWZA plugin for Moodle - http://moodle.org/
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
 *  Secure token tests
 *
 * @package    filter_oidwowza
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/oidwowza/filter.php');

/**
 * Secure token test class file.
 *
 * @package    filter_oidwowza
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class securetoken_testcase extends advanced_testcase {
    /**
     * Makes sure that the token generation works as expected.
     */
     public function test_generation() {
         $this->resetAfterTest(true);

         // Hash with ip.
         set_config('filter_oidwowza_hashclientip', 1);
         set_config('filter_oidwowza_sharedsecret', 'qwerty');
         $token = filter_oidwowza::generate_securetoken('15S-ENGL170C-1/mp4:ID4837_BladeRunner.mp4', 1448491221, '128.97.175.185');
         $this->assertEquals('gzwE-11KEpMiiII3CkohEgrtDjW7F2qzuUliqQKJTc8=', $token);

         // Hash without ip.
         set_config('filter_oidwowza_hashclientip', 0);
         $token = filter_oidwowza::generate_securetoken('15S-ENGL170C-1/mp4:ID4837_BladeRunner.mp4', 1448491221, '128.97.175.185');
         $this->assertEquals('hUmQYc0k8U9fgNUz31V4BjH5IVOuxvQFcesYg2Kmqmg=', $token);

         // Do not pass IP.
         $sametoken = filter_oidwowza::generate_securetoken('15S-ENGL170C-1/mp4:ID4837_BladeRunner.mp4', 1448491221);
         $this->assertEquals($token, $sametoken);
     }
 }
