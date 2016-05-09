<?php
// This file is part of the UCLA TA sites block for Moodle - http://moodle.org/
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
 * Class to unit test certain aspects of the edits to enrol_meta class.
 *
 * @package    block_ucla_tasites
 * @category   test
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/meta/lib.php');

/**
 * Testcase class.
 *
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_meta_test extends advanced_testcase {
    /**
     * Setup.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Tests that role promotion works as expected.
     */
    public function test_get_role_promotion() {
        // TA site belongs to them.
        $ra = new stdClass();
        $ra->roleid = 16;
        $ra->promotoroleid = 15;
        $ra->tasiteowners = '123456789';
        $ra->idnumber = '123456789';
        $promotion = enrol_meta_plugin::get_role_promotion($ra);
        $this->assertEquals($ra->promotoroleid, $promotion);

        // TA site belongs to them with another person.
        $ra->tasiteowners = '123456789,987654321';
        $promotion = enrol_meta_plugin::get_role_promotion($ra);
        $this->assertEquals($ra->promotoroleid, $promotion);
        $ra->tasiteowners = '987654321,123456789';
        $promotion = enrol_meta_plugin::get_role_promotion($ra);
        $this->assertEquals($ra->promotoroleid, $promotion);

        // TA site doesn't belong to user.
        $ra->idnumber = '111222333';
        $promotion = enrol_meta_plugin::get_role_promotion($ra);
        $this->assertEquals($ra->roleid, $promotion);
    }
}
