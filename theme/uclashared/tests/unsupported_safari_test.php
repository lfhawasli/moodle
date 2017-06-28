<?php
// This file is part of the UCLA theme plugin for Moodle - http://moodle.org/
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
 * Tests the is_unsupported_safari method.
 *
 * @package    theme_uclashared
 * @category   test
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/theme/uclashared/renderers/core_renderer.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unsupported_safari_testcase extends basic_testcase {

    /**
     * Tests the is_unsupported_safari method.
     */
    public function test_is_unsupported_safari() {
        $page = new moodle_page();
        $renderer = new theme_uclashared_core_renderer($page, '');

        // Tests safari versions that should return true.
        $validcases = array('Safari 6.1.4', 'Safari 6.0', 'Safari 6.1', 'Safari 5.1.10', 'Safari 0.8', 'Safari 3.0');
        foreach ($validcases as $case) {
            $this->assertTrue($renderer->is_unsupported_safari('Mac OS X 10.6', $case));
        }

        // Test safari versions that should return false.
        $invalidcases = array('Safari 6.1.5', 'Safari 7.0', 'Safari 6.1.5', 'Safari 8.0.5');
        foreach ($invalidcases as $case) {
            $this->assertFalse($renderer->is_unsupported_safari('Mac OS X 10.6.8', $case));
        }

        // Test OSX versions that should return false.
        $invalidoses = array('Mac OS X 10.10', 'Mac OS X 10.5.8', 'iOS 7.1', 'Windows 7');
        foreach ($invalidoses as $case) {
            $this->assertFalse($renderer->is_unsupported_safari($case, $validcases[0]));
        }

        // Make sure that we are only checking Safari versions.
        $otherbrowsers = array('Chrome 21.0.1180', 'Firefox 36.0');
        foreach ($otherbrowsers as $case) {
            $this->assertFalse($renderer->is_unsupported_safari('Mac OS X 10.7.1', $case));
        }
    }

}