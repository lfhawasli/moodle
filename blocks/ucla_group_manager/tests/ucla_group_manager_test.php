<?php
// This file is part of the UCLA group management plugin for Moodle - http://moodle.org/
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
 * Unit tests for the UCLA group management plugin.
 *
 * @package    block_ucla_group_manager
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/ucla_synced_group.class.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group block_ucla_group_manager
 */
class ucla_group_manager_testcase extends advanced_testcase {

    /**
     * Stores mocked version of ucla_group_manager.
     * @var ucla_group_manager
     */
    private $mockgroupmanager = null;

    /**
     * Used by mocked_query_registrar to return data for a given stored 
     * procedure, term, and srs.
     * @var array
     */
    private $mockregdata = array();

    /**
     * Stubs the query_registrar method of ucla_group_manager class,
     * so we aren't actually making a live call to the Registrar.
     *
     * Must call set_mockregdata() beforehand to set what data should be
     * returned.
     *
     * @param string $sp        Stored procedure to call.
     * @param array $reqarr     Array of results.
     * @param bool $filter
     *
     * @return array            Returns corresponding value in $mockregdata.
     */
    public function mocked_query_registrar($sp, $reqarr, $filter) {
        /* The $mockregdata array is indexed as follows:
         *  [storedprocedure] => [term] => [srs] => [array of results]
         */
        return $this->mockregdata[$sp][$reqarr[0]][$reqarr[1]];
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
     * Set up registrar_query() stub. 
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Only stub the query_registrar method.
        $this->mockgroupmanager = $this->getMockBuilder('ucla_group_manager')
                ->setMethods(array('query_registrar'))
                ->getMock();

        // Method $this->mocked_query_registrar will be called instead of
        // local_ucla_enrollment_helper->query_registrar.
        $this->mockgroupmanager->expects($this->any())
                ->method('query_registrar')
                ->will($this->returnCallback(array($this, 'mocked_query_registrar')));

        // Remove any previous registrar data.
        unset($this->mockregdata);
        $this->mockregdata = array();
    }

    /**
     * Test that student is unenrolled from previous section and enrolled
     * in new section when student switches to another section via registrar.
     * In this test case the registrar returns the student as having
     * dropped the class with enrollment status 'D'.  Student A will start in
     * section A and student B will begin in section B.  This will test that 
     * student A can correctly transfer into section B.
     */
    public function test_sync_course() {
        // Create a non-crosslisted class.
        $courses = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());

        $class = array_pop($courses);
        $term = $class->term;
        $srs = $class->srs;
        $courseid = $class->courseid;

        // Create students that will switch sections.
        $studenta = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user();
        $studentb = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user();

        // Enroll students in the course.
        $this->getDataGenerator()->enrol_user($studenta->id, $courseid);
        $this->getDataGenerator()->enrol_user($studentb->id, $courseid);

        // Set up mock sections.
        $students = array();
        $students['studenta'] = $studenta;
        $students['studentb'] = $studentb;

        $sections['001A'] = array('sect_no' => '001A',
            'srs_crs_no' => $srs - 800);

        $sections['001B'] = array('sect_no' => '001B',
            'srs_crs_no' => $srs - 700);

        // Set up mock data for ccle_class_sections.
        $sectionresults = array();
        foreach ($sections as $section) {
            $sectionresults[] = array('sect_no' => $section['sect_no'],
                'cls_act_typ_cd' => 'DIS',
                'sect_enrl_stat_cd' => 'O',
                'srs_crs_no' => $section['srs_crs_no']);
        }
        $this->set_mockregdata('ccle_class_sections', $term, $srs,
                $sectionresults);

        // Set up mock data for ccle_roster_class for class.
        $classroster = array();
        foreach ($students as $bol => $student) {
            $classroster[] = array('term_cd' => $term,
                'stu_id' => $student->idnumber,
                'full_name_person' => $student->firstname . ' ' . $student->lastname,
                'enrl_stat_cd' => 'E',
                'ss_email_addr' => $student->email,
                'bolid' => $bol);
        }
        $this->set_mockregdata('ccle_roster_class', $term, $srs, $classroster);

        // Set up mock data for ccle_roster_class for each section.
        // Student A will be in section A.
        $sectionrostera = array();
        $sectionrostera[0] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term,
                $sections['001A']['srs_crs_no'], $sectionrostera);

        // Student B will be in section B.
        $sectionrosterb = array();
        $sectionrosterb[0] = array('term_cd' => $term,
            'stu_id' => $studentb->idnumber,
            'full_name_person' => $studentb->firstname . ' ' . $studentb->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studentb->email,
            'bolid' => 'studentb');

        $this->set_mockregdata('ccle_roster_class', $term,
                $sections['001B']['srs_crs_no'], $sectionrosterb);

        // Sync the groups and check that the rosters are correctly configured.
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupa = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001A']['srs_crs_no'],
            'courseid' => $courseid));

        $this->assertEquals(array($studenta->id => $studenta->id),
                $groupa->memberships);

        $groupb = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001B']['srs_crs_no'],
            'courseid' => $courseid));

        $this->assertEquals(array($studentb->id => $studentb->id),
                $groupb->memberships);

        unset($groupa);
        unset($groupb);

        // Change student enrollments so that both are in section B.
        // Set student A to have 'D' enrollment status in sectionA.
        $sectionrostera[0] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'D',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term,
                $sections['001A']['srs_crs_no'], $sectionrostera);

        // Add studenta to section B.
        $sectionrosterb[1] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term,
                $sections['001B']['srs_crs_no'], $sectionrosterb);

        // Sync changes to groups.
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupa = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001A']['srs_crs_no'],
            'courseid' => $courseid));

        // Check that section A is empty.
        $this->assertEmpty($groupa->memberships);

        $groupb = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001B']['srs_crs_no'],
            'courseid' => $courseid));

        // Check that section B has students A and B.
        $this->assertEquals(array($studentb->id => $studentb->id,
            $studenta->id => $studenta->id), $groupb->memberships);
    }

    /**
     * Test that student is unenrolled from previous section when student 
     * switches sections and eventually drops the course via registrar.
     * In this test case the registrar does NOT return the student in the
     * roster for the section or course which was dropped.
     */
    public function test_sync_course2() {
        // Create a non-crosslisted class.
        $course = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());

        $class = array_pop($course);
        $term = $class->term;
        $srs = $class->srs;
        $courseid = $class->courseid;

        // Create a student that will drop the class.
        $studenta = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_user();

        // Enroll student in the course.
        $this->getDataGenerator()->enrol_user($studenta->id, $courseid);

        // Set up mock sections.
        $sections['001A'] = array('sect_no' => '001A',
            'srs_crs_no' => $srs - 800);

        $sections['001B'] = array('sect_no' => '001B',
            'srs_crs_no' => $srs - 700);

        // Set up mock data for ccle_class_sections.
        $sectionresults = array();
        foreach ($sections as $section) {
            $sectionresults[] = array('sect_no' => $section['sect_no'],
                'cls_act_typ_cd' => 'DIS',
                'sect_enrl_stat_cd' => 'O',
                'srs_crs_no' => $section['srs_crs_no']);
        }
        $this->set_mockregdata('ccle_class_sections', $term, $srs,
                $sectionresults);

        // Set up mock data for ccle_roster_class for class.
        $classroster = array();
        $classroster[0] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term, $srs, $classroster);

        // Set up mock data for ccle_roster_class for each section.
        // Student A is enrolled in section A initially.
        $sectionrostera = array();
        $sectionrostera[0] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term,
                $sections['001A']['srs_crs_no'], $sectionrostera);

        // Section B is empty.
        $sectionrosterb = array();
        $this->set_mockregdata('ccle_roster_class', $term,
                $sections['001B']['srs_crs_no'], $sectionrosterb);

        // Sync the groups and check that the rosters are correctly configured.
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupa = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001A']['srs_crs_no'],
            'courseid' => $courseid));

        $this->assertEquals(array($studenta->id => $studenta->id),
                $groupa->memberships);

        $groupb = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001B']['srs_crs_no'],
            'courseid' => $courseid));

        $this->assertEmpty($groupb->memberships);

        unset($groupa);
        unset($groupb);

        // Student A now switches frmom section A to B.
        // Section A is empty.
        unset($sectionrostera[0]);
        $this->set_mockregdata('ccle_roster_class', $term,
                $sections['001A']['srs_crs_no'], $sectionrostera);

        // Section B contains student A.
        $sectionrosterb[0] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term,
                $sections['001B']['srs_crs_no'], $sectionrosterb);

        // Sync the groups and check that the rosters are correctly configured.
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupa = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001A']['srs_crs_no'],
            'courseid' => $courseid));

        $this->assertEmpty($groupa->memberships);

        $groupb = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001B']['srs_crs_no'],
            'courseid' => $courseid));

        $this->assertEquals(array($studenta->id => $studenta->id),
                $groupb->memberships);

        unset($groupa);
        unset($groupb);

        // Class roster is empty.
        unset($classroster[0]);
        $this->set_mockregdata('ccle_roster_class', $term, $srs, $classroster);

        // Section rosters are now empty.
        unset($sectionrosterb[0]);
        $this->set_mockregdata('ccle_roster_class', $term,
                $sections['001B']['srs_crs_no'], $sectionrosterb);

        // Sync the groups and check that the rosters are correctly configured.
        $sync = $this->mockgroupmanager->sync_course($courseid);
        $this->assertTrue($sync);

        $groupa = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001A']['srs_crs_no'],
            'courseid' => $courseid));

        $this->assertEmpty($groupa->memberships);

        $groupb = new ucla_synced_group(array('term' => $term,
            'srs' => $sections['001B']['srs_crs_no'],
            'courseid' => $courseid));

        $this->assertEmpty($groupb->memberships);
    }

    /**
     * Tests that a student is able to switch sections within a crosslisted
     * course. Student starts in section 1A and switches to section 2B where
     * 1 and 2 are crosslisted courses.
     */
    public function test_sync_course_crosslisted() {
        // Create crosslisted courses.
        $crosslisted = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_class(array(
            array(), array()));

        // Pop classes from back of return array.
        $class2 = array_pop($crosslisted);
        $class1 = array_pop($crosslisted);

        $term = $class1->term;
        $srs1 = $class1->srs;
        $courseid1 = $class1->courseid;

        // Expect term to be the same for class 2.
        $srs2 = $class2->srs;
        $courseid2 = $class2->courseid;

        // Create a student that will switch sections.
        $studenta = $this->getDataGenerator()->get_plugin_generator('local_ucla')->create_user();

        // Enroll student in class 1 initially.
        $this->getDataGenerator()->enrol_user($studenta->id, $courseid1, null,
                'manual');

        // Set up mock sections.
        // Class 1 sections.
        $sections1 = array();
        $sections1['001A'] = array('sect_no' => '001A',
            'srs_crs_no' => $srs1 - 800);

        $sections1['001B'] = array('sect_no' => '001B',
            'srs_crs_no' => $srs1 - 700);

        // Class 2 sections.
        $sections2 = array();
        $sections2['002A'] = array('sect_no' => '002A',
            'srs_crs_no' => $srs2 - 800);

        $sections2['002B'] = array('sect_no' => '002B',
            'srs_crs_no' => $srs2 - 700);

        // Set up mock data for ccle_class_sections for each class.
        $sectionresults1 = array();
        foreach ($sections1 as $section) {
            $sectionresults1[] = array('sect_no' => $section['sect_no'],
                'cls_act_typ_cd' => 'DIS',
                'sect_enrl_stat_cd' => 'O',
                'srs_crs_no' => $section['srs_crs_no']);
        }
        $this->set_mockregdata('ccle_class_sections', $term, $srs1,
                $sectionresults1);

        $sectionresults2 = array();
        foreach ($sections2 as $section) {
            $sectionresults2[] = array('sect_no' => $section['sect_no'],
                'cls_act_typ_cd' => 'DIS',
                'sect_enrl_stat_cd' => 'O',
                'srs_crs_no' => $section['srs_crs_no']);
        }
        $this->set_mockregdata('ccle_class_sections', $term, $srs2,
                $sectionresults2);

        // Set up mock data for ccle_roster_class for each class.
        $classroster1 = array();
        $classroster1[] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term, $srs1, $classroster1);

        $classroster2 = array();
        $this->set_mockregdata('ccle_roster_class', $term, $srs2, $classroster2);

        // Set up mock data for ccle_roster_class for each section.
        $sectionroster1a = array();
        $sectionroster1a[0] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term,
                $sections1['001A']['srs_crs_no'], $sectionroster1a);

        $sectionroster1b = array();
        $this->set_mockregdata('ccle_roster_class', $term,
                $sections1['001B']['srs_crs_no'], $sectionroster1b);

        $sectionroster2a = array();
        $this->set_mockregdata('ccle_roster_class', $term,
                $sections2['002A']['srs_crs_no'], $sectionroster2a);

        $sectionroster2b = array();
        $this->set_mockregdata('ccle_roster_class', $term,
                $sections2['002B']['srs_crs_no'], $sectionroster2b);

        // Sync the groups and check that the rosters are correctly configured.
        $sync1 = $this->mockgroupmanager->sync_course($courseid1);
        $this->assertTrue($sync1);

        $group1a = new ucla_synced_group(array('term' => $term,
            'srs' => $sections1['001A']['srs_crs_no'],
            'courseid' => $courseid1));

        // Student should be enrolled in section 1A.
        $this->assertEquals(array($studenta->id => $studenta->id),
                $group1a->memberships);

        $group1b = new ucla_synced_group(array('term' => $term,
            'srs' => $sections1['001B']['srs_crs_no'],
            'courseid' => $courseid1));

        $this->assertEmpty($group1b->memberships);

        $sync2 = $this->mockgroupmanager->sync_course($courseid2);
        $this->assertTrue($sync2);

        $group2a = new ucla_synced_group(array('term' => $term,
            'srs' => $sections2['002A']['srs_crs_no'],
            'courseid' => $courseid2));

        $this->assertEmpty($group2a->memberships);

        $group2b = new ucla_synced_group(array('term' => $term,
            'srs' => $sections2['002B']['srs_crs_no'],
            'courseid' => $courseid2));

        $this->assertEmpty($group2b->memberships);

        unset($group1a);
        unset($group1b);
        unset($group2a);
        unset($group2b);

        // Change student enrollments so that student is in class 2 section 2B.
        // Unenroll student from class 1 and enroll them in class 2.
        $enrol = enrol_get_plugin('manual');
        $instances = enrol_get_instances($courseid1, true);
        foreach ($instances as $instance) {
            if ($instance->enrol == 'manual') {
                $enrol->unenrol_user($instance, $studenta->id);
                break;
            }
        }

        $this->getDataGenerator()->enrol_user($studenta->id, $courseid2);

        // Remove student from class 1 and section 1A in mock data.
        unset($classroster1[0]);
        $this->set_mockregdata('ccle_roster_class', $term, $srs1, $classroster1);
        unset($sectionroster1a[0]);
        $this->set_mockregdata('ccle_roster_class', $term,
                $sections1['001A']['srs_crs_no'], $sectionroster1a);

        // Add student to class 2 and section 2B in mock data.
        $classroster2[0] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term, $srs2, $classroster2);

        $sectionroster2b[0] = array('term_cd' => $term,
            'stu_id' => $studenta->idnumber,
            'full_name_person' => $studenta->firstname . ' ' . $studenta->lastname,
            'enrl_stat_cd' => 'E',
            'ss_email_addr' => $studenta->email,
            'bolid' => 'studenta');

        $this->set_mockregdata('ccle_roster_class', $term,
                $sections2['002B']['srs_crs_no'], $sectionroster2b);

        // Sync the groups and check that the rosters are correctly configured.
        $sync1 = $this->mockgroupmanager->sync_course($courseid1);
        $this->assertTrue($sync1);

        $group1a = new ucla_synced_group(array('term' => $term,
            'srs' => $sections1['001A']['srs_crs_no'],
            'courseid' => $courseid1));

        // Class 1 section A is now empty.
        $this->assertEmpty($group1a->memberships);

        $group1b = new ucla_synced_group(array('term' => $term,
            'srs' => $sections1['001B']['srs_crs_no'],
            'courseid' => $courseid1));

        $this->assertEmpty($group1b->memberships);

        $sync2 = $this->mockgroupmanager->sync_course($courseid2);
        $this->assertTrue($sync2);

        $group2a = new ucla_synced_group(array('term' => $term,
            'srs' => $sections2['002A']['srs_crs_no'],
            'courseid' => $courseid2));

        $this->assertEmpty($group2a->memberships);

        $group2b = new ucla_synced_group(array('term' => $term,
            'srs' => $sections2['002B']['srs_crs_no'],
            'courseid' => $courseid2));

        // Class 2 section B now has a student.
        $this->assertEquals(array($studenta->id => $studenta->id),
                $group2b->memberships);
    }

}

