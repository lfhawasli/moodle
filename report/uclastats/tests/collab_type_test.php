<?php
// This file is part of the UCLA stats console for Moodle - http://moodle.org/
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
 * Unit tests for UCLA stats collab type class.
 *
 * @package    report_uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/uclastats/reports/collab_type.php');

class collab_types_test extends advanced_testcase {
    /**
     * Setup.
     */
    public function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Test query to make sure the divisions for collaboration sites are
     * determined top down, than bottom up.
     */
    public function test_query() {
        global $DB;

        // Create categories. Note that report is looking for sentence casing.
        $parentcategory = $this->getDataGenerator()
                ->create_category(array('name' => 'Parent Category'));
        $childcategory = $this->getDataGenerator()->create_category(
                array('name' => 'Child Category', 'parent' => $parentcategory->id));

        // Add them both to the ucla_reg_division table. Note, that divisions
        // are entered into table as all caps.
        $record = new stdClass();
        $record->code = 'PC';
        $record->fullname = textlib::strtoupper($parentcategory->name);
        $DB->insert_record('ucla_reg_division', $record);
        $record->code = 'CC';
        $record->fullname = textlib::strtoupper($childcategory->name);
        $DB->insert_record('ucla_reg_division', $record);

        $divs = $DB->get_records('ucla_reg_division');

        // Create 2 collaboration sites, 1 in each category.
        $collab1 = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_collab(array('type' => 'instruction',
                                      'category' => $parentcategory->id));
        $collab2 = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_collab(array('type' => 'research',
                                      'category' => $childcategory->id));

        // Now run report.
        $report = new collab_type(get_admin());
        $results = $report->query(array());

        // The parent category should have 1 instruction and 1 research site.
        // The child category should have nothing.
        $this->assertEquals(1, $results[$parentcategory->name]['instruction']);
        $this->assertEquals(1, $results[$parentcategory->name]['research']);
        $this->assertEquals(2, $results[$parentcategory->name]['total']);
        $this->assertFalse(isset($results[$childcategory->name]));
    }
}
