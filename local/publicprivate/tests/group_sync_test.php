<?php
// This file is part of the UCLA public/private plugin for Moodle - http://moodle.org/
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
 * Unit tests for implementation of public/private groups & groupings.
 *
 * @package     local_publicprivate
 * @copyright   2015 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once($CFG->dirroot . '/local/publicprivate/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');
require_once($CFG->dirroot . '/group/externallib.php');

/**
 * Unit test file.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_sync_testcase extends advanced_testcase {

    /**
     * Stores mocked version of ucla_group_manager.
     * @var ucla_group_manager
     */
    private $mockuclagroupmanager = null;

    /**
     * Used by mocked_query_registrar to return data for a given stored
     * procedure, term, and srs.
     * @var array
     */
    private $mockregdata = array();

    /**
     * Stores data generator.
     * @var testing_data_generator
     */
    protected $gen;

    /**
     * Stores test class.
     * @var stdClass ucla_request_classes record
     */
    protected $class;

    /**
     * Stubs the query_registrar method of ucla_group_manager class, so we
     * aren't actually making a live call to the Registrar.
     *
     * Must call set_mockregdata() beforehand to set what data should be
     * returned.
     *
     * @param string $sp        Stored procedure to call.
     * @param array $data       Expecting an array with term stored at index 0 and srs stored at
     *                          index 1.
     * @param bool $filter      This is used in query_registrar but is not necessary for the mock.
     *
     * @return array            Returns corresponding value in $mockregdata.
     */
    public function mocked_query_registrar($sp, $data, $filter) {
        /* The $mockregdata array is indexed as follows:
         *  [storedprocedure] => [term] => [srs] => [array of results]
         */
        $retval = $this->mockregdata[$sp][$data[0]][$data[1]];
        return $retval;
    }

    /**
     * Prepares data that will be returned by mocked_query_registrar.
     *
     * @param string $sp
     * @param string $term
     * @param string $srs
     * @param array $results
     */
    protected function set_mockregdata($sp, $term, $srs, array $results) {
        $this->mockregdata[$sp][$term][$srs] = $results;
    }

    /**
     * Creates mock registrar and test class.
     */
    public function setUp() {
        $this->resetAfterTest();

        // Create mocked version of the ucla_group_manager class.
        // Only stub the query_registrar method.
        $this->mockuclagroupmanager = $this->getMockBuilder('ucla_group_manager')
                ->setMethods(array('query_registrar'))
                ->getMock();

        // Method $this->mocked_query_registrar will be called instead of
        // ucla_reg_classinfo_cron->query_registrar.
        $this->mockuclagroupmanager->expects($this->any())
                ->method('query_registrar')
                ->will($this->returnCallback(array($this, 'mocked_query_registrar')));

        // Remove any previous registrar data.
        unset($this->mockregdata);
        $this->mockregdata = array();

        // Create a new course and enable public/private. We pass an array containing
        // an empty array so that only one course is generated. (No crosslisting).
        $this->gen = $this->getDataGenerator();
        $creqarr = $this->gen
                ->get_plugin_generator('local_ucla')
                ->create_class(array(array()));
        $this->class = reset($creqarr);
        $this->setAdminUser();
    }

    /**
     * Tests that a course with a section in the registrar will have a corresponding section group
     * created/updated and added to the public/private grouping.
     */
    public function test_section_group_sync() {
        // Set things up.
        $nonsecgroup = $this->gen->create_group(array('courseid' => $this->class->courseid));
        $sections = $this->gen
                ->get_plugin_generator('local_ucla')
                ->generate_section_regdata($this->class->id, 1);
        $this->set_mockregdata('ccle_class_sections', $this->class->term, $this->class->srs, $sections);
        $section = reset($sections);
        // Let's not worry about enrolled students in this test. Set class and section rosters
        // to empty.
        $this->set_mockregdata('ccle_roster_class', $this->class->term, $this->class->srs, array());
        $this->set_mockregdata('ccle_roster_class', $this->class->term, $section['srs_crs_no'], array());
        // Start and clean the output buffer to make test cleaner. Comment out ob_start()
        // and ob_end_clean() to debug.
        ob_start();
        $this->mockuclagroupmanager->sync_course($this->class->courseid);
        ob_end_clean();

        // Verify that a section group has been created.
        $ppcourse = PublicPrivate_Course::build($this->class->courseid);
        $groups = $this->get_ppgrouping_groups($ppcourse);
        // Should be two groups: a single section group plus 'Course Members' pub/priv group.
        $this->assertCount(2, $groups);
        // Check that section details appear in one of the group names.
        $sectioningrouping = false;
        foreach ($groups as $group) {
            if (strpos($group['name'], $section['cls_act_typ_cd'] . ' ' . $section['sect_no'])) {
                $sectioningrouping = true;
            }
            $this->assertEquals($this->class->courseid, $group['courseid']);
            // The non-section group is not in the 'Private course materials' grouping.
            $this->assertNotEquals($group['id'], $nonsecgroup->id);
        }
        $this->assertTrue($sectioningrouping);
    }

    /**
     * Tests that sections are added and removed from the public/private grouping
     * as they are added and removed from the registrar.
     */
    public function test_sync_add_and_remove() {
        // Set up.
        $ppcourse = PublicPrivate_Course::build($this->class->courseid);

        // Empty registrar results.
        $this->set_mockregdata('ccle_class_sections', $this->class->term, $this->class->srs, array());
        $this->set_mockregdata('ccle_roster_class', $this->class->term, $this->class->srs, array());
        ob_start();
        $this->mockuclagroupmanager->sync_course($this->class->courseid);
        ob_end_clean();
        $groups = $this->get_ppgrouping_groups($ppcourse);
        $this->assertCount(1, $groups);
        // We should just have the default 'Course members' group in the 'Private course materials'
        // grouping.
        $this->assertEquals('Course members', $groups[0]['name']);

        // With sections added to registrar.
        $sections = $this->gen
                ->get_plugin_generator('local_ucla')
                ->generate_section_regdata($this->class->id, 2);
        $this->set_mockregdata('ccle_class_sections', $this->class->term, $this->class->srs, $sections);
        // Set mock registrar to return empty section rosters.
        foreach ($sections as $section) {
            $this->set_mockregdata('ccle_roster_class', $this->class->term, $section['srs_crs_no'], array());
        }
        ob_start();
        $this->mockuclagroupmanager->sync_course($this->class->courseid);
        ob_end_clean();
        $groups = $this->get_ppgrouping_groups($ppcourse);
        $this->assertCount(3, $groups);
        $sectionsingrouping = [false, false];
        foreach ($groups as $group) {
            foreach ($sections as $key => $section) {
                if (strpos($group['name'], $section['cls_act_typ_cd'] . ' ' . $section['sect_no'])) {
                    $sectionsingrouping[$key] = true;
                }
            }
        }
        $this->assertTrue($sectionsingrouping[0]);
        $this->assertTrue($sectionsingrouping[1]);

        // With a section removed from the registrar. Check that it is removed.
        array_pop($sections);
        $this->set_mockregdata('ccle_class_sections', $this->class->term, $this->class->srs, $sections);
        ob_start();
        $this->mockuclagroupmanager->sync_course($this->class->courseid);
        ob_end_clean();
        $groups = $this->get_ppgrouping_groups($ppcourse);
        $this->assertCount(2, $groups);
        $sectionsingrouping = [false, false];
        foreach ($groups as $group) {
            foreach ($sections as $key => $section) {
                if (strpos($group['name'], $section['cls_act_typ_cd'] . ' ' . $section['sect_no'])) {
                    $sectionsingrouping[$key] = true;
                }
            }
        }
        $this->assertTrue($sectionsingrouping[0]);
        $this->assertFalse($sectionsingrouping[1]);
    }

    /**
     * Simplify the process of getting the groups in the 'Private course materials' grouping given
     * the PublicPrivate_Course object.
     *
     * @param PublicPrivate_Course $ppcourse
     * @return 2-D array of group arrays containing data from records in the 'groups' table in the
     * following format:    array('id' => $grouprecord->groupid,
     *                            'name' => $grouprecord->name,
     *                            'description' => $grouprecord->description,
     *                            'descriptionformat' => $grouprecord->descriptionformat,
     *                            'enrolmentkey' => $grouprecord->enrolmentkey,
     *                            'courseid' => $grouprecord->courseid
     *                            );
     */
    private function get_ppgrouping_groups($ppcourse) {
        $ppgroupingid = $ppcourse->get_grouping();
        $ppgroupings = core_group_external::get_groupings(array($ppgroupingid), true);
        $ppgrouping = reset($ppgroupings);
        return $ppgrouping['groups'];
    }

}
