<?php
// This file is part of the UCLA data source sychronization plugin for Moodle - http://moodle.org/
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
 * Tests validation functions.
 *
 * @package    tool_ucladatasourcesync
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../lib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group tool_ucladatasourcesync
 */
class validators_test extends basic_testcase {

    /**
     * Tests that dates with slashes are handled correctly.
     */
    public function test_valid_date_slashed() {
        $result = validate_field('date_slashed', '06/03/2012');
        $this->assertEquals($result, '06/03/2012');
        $result = validate_field('date_slashed', '02/29/2012');
        $this->assertEquals($result, '02/29/2012');
    }

    /**
     * Tests that invalid dates with slashes are handled correctly.
     */
    public function test_invalid_date_slashed() {
        $result = validate_field('date_slashed', '06/40/2012');
        $this->assertFalse($result);
        $result = validate_field('date_slashed', '02/29/2011');
        $this->assertFalse($result);
    }

    /**
     * Tests that dates with dashes are handled correctly.
     */
    public function test_valid_date_dashed() {
        $result = validate_field('date_dashed', '2012-06-03');
        $this->assertEquals($result, '2012-06-03');
        $result = validate_field('date_dashed', '2012-02-29');
        $this->assertEquals($result, '2012-02-29');
    }

    /**
     * Tests that invalid dates with dashes are handled correctly.
     */
    public function test_invalid_date_dashed() {
        $result = validate_field('date_dashed', '2012-06-40');
        $this->assertFalse($result);
        $result = validate_field('date_dashed', '2011-02-29');
        $this->assertFalse($result);
    }

}