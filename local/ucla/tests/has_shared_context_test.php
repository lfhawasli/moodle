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
 * Unit tests for has_shared_context.
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
class has_shared_context_test extends advanced_testcase {

    /**
     * Columns to query on in the role_assignments table.
     * @var array
     */
    public $roleassignmentscolumns = array('userid', 'contextid');

    /**
     * Test when two users do share a single context.
     */
    public function test_single_shared_context() {
        $data['role_assignments'][] = $this->roleassignmentscolumns;
        $data['role_assignments'][] = array(1, 1);
        $data['role_assignments'][] = array(1, 2);
        $data['role_assignments'][] = array(2, 2);
        $data['role_assignments'][] = array(3, 2);
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);

        $result = has_shared_context(1, 2);
        $this->assertTrue((bool) $result);
    }

    /**
     * Test when two users do share multiple contexts.
     */
    public function test_multiple_shared_context() {
        $data['role_assignments'][] = $this->roleassignmentscolumns;
        $data['role_assignments'][] = array(1, 1);
        $data['role_assignments'][] = array(1, 2);
        $data['role_assignments'][] = array(1, 3);
        $data['role_assignments'][] = array(2, 1);
        $data['role_assignments'][] = array(2, 2);
        $data['role_assignments'][] = array(2, 4);

        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        $result = has_shared_context(1, 2);
        $this->assertTrue((bool) $result);
    }

    /**
     * Test when two users do not share a context.
     */
    public function test_no_shared_context() {
        $data['role_assignments'][] = $this->roleassignmentscolumns;
        $data['role_assignments'][] = array(1, 1);
        $data['role_assignments'][] = array(1, 2);
        $data['role_assignments'][] = array(2, 3);

        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        $result = has_shared_context(1, 2);
        $this->assertFalse((bool) $result);
    }

    /**
     * Reset database after each test.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

}