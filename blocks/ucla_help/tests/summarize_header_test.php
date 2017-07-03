<?php
// This file is part of the UCLA local help plugin for Moodle - http://moodle.org/
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
 * Tests the ucla_help package functionality.
 *
 * @package    block_ucla_help
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');
require_once($CFG->dirroot . '/blocks/ucla_help/help_form.php');


/**
 * Test to see if the function create_description works
 *
 * @package    block_ucla_help
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summarize_header_test extends basic_testcase {

    /**
     * Test that create_description limits the title length.
     */
    public function test_create_description_limit() {
        $fromform = new stdClass();
        $fromform->ucla_help_description = "I swore I filled out all of the "
               . "grades for this class, but the students say they don't have "
               . "them and now I cannot for the life of me find the gradebook "
               . "where I filled them in...I want to get this taken care of "
               . "promptly. thanks";
        $this->assertEquals("I swore I filled out all of the grades",
               create_description($fromform));
    }

    /**
     * Test that create_description keeps punctuation.
     */
    public function test_create_description_punctuation() {
        $fromform = new stdClass();
        $fromform->ucla_help_description = "I can't find the button to start "
               . "the math diagnostic test after i type in my enrolment key";
        $this->assertEquals("I can't find the button to start the",
               create_description($fromform));
    }
}