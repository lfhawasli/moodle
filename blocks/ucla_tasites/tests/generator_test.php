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
 * Unit tests for the data generator for UCLA TA site creator plugin.
 *
 * @package    block_ucla_tasites
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_tasites/tests/generator/lib.php');

/**
 * PHPUnit data generator testcase.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_tasites_generator_testcase extends advanced_testcase {

    /**
     * Stores mocked version of ucla_group_manager.
     *
     * @var ucla_group_manager
     */
    private $mockgroupmanager = null;

    /**
     * Used by mocked_query_registrar to return data for a given stored
     * procedure, term, and srs.
     *
     * @var array
     */
    private $mockregdata = array();

    /**
     * TA site generator.
     * @var block_ucla_tasites_generator
     */
    private $tasitegen;

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
     * Setup the UCLA ta site data generator.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        $this->tasitegen = $this->getDataGenerator()->get_plugin_generator('block_ucla_tasites');
        $this->tasitegen->setup();

        // Only stub the query_registrar method.
        $this->mockgroupmanager = $this->getMockBuilder('ucla_group_manager')
                ->setConstructorArgs(array(new null_progress_trace()))
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
     * Prepares data that will be returned by mocked_query_registrar.
     *
     * @param string $sp
     * @param string $term
     * @param string $srs
     * @param array $results    If null, will unset value.
     */
    protected function set_mockregdata($sp, $term, $srs, $results) {
        if (is_null($results)) {
            unset($this->mockregdata[$sp][$term][$srs]);
        } else {
            $this->mockregdata[$sp][$term][$srs] = $results;
        }
    }

    /**
     * Try to create a tasite using the basic "create_instance" generator method
     * with no parameters.
     */
    public function test_create_instance_basic() {
        // Try to create tasite with generator creating everything it needs.
        $tasite = $this->tasitegen->create_instance();
        $this->assertFalse(empty($tasite));

        // Make sure that someone has ta_admin role in new course.
        $coursecontext = context_course::instance($tasite->id);
        $taadminid = $this->tasitegen->taadminid;

        $users = get_role_users($taadminid, $coursecontext);
        $this->assertFalse(empty($users));

        $istasite = block_ucla_tasites::is_tasite($tasite->id);
        $this->assertTrue($istasite);
    }

    /**
     * Given a course with two TAs, create a TA site for 1 TA and make sure only
     * that TA has the promoted role.
     */
    public function test_create_instance_twotas() {

        // Create course site with two TAs.
        $class = $this->tasitegen->ucladatagen->create_class();
        $class = array_pop($class);
        $parentcourse = get_course($class->courseid);
        $ta1 = $this->tasitegen->ucladatagen->create_user();
        $ta2 = $this->tasitegen->ucladatagen->create_user();
        $this->getDataGenerator()->enrol_user($ta1->id, $parentcourse->id, $this->tasitegen->taid);
        $this->getDataGenerator()->enrol_user($ta2->id, $parentcourse->id, $this->tasitegen->taid);

        // Create TA site for one of the TAs.
        $typeinfo = array();
        $typeinfo['byta'][fullname($ta1)] = array('ucla_id' => $ta1->idnumber);
        $tasite = $this->tasitegen->create_instance($parentcourse, $typeinfo);
        $this->assertFalse(empty($tasite));

        // Make sure that only the TA the site was created for is the TA admin.
        $coursecontext = context_course::instance($tasite->id);
        $taadminid = $this->tasitegen->taadminid;
        $users = get_role_users($taadminid, $coursecontext);
        $this->assertEquals(1, count($users));
    }

    /**
     * Try to create a tasite for a registrar course for a specific section.
     */
    public function test_create_instance_ta_section() {
        $class = $this->tasitegen->ucladatagen->create_class();
        $class = array_pop($class);
        $parentcourse = get_course($class->courseid);

        // Add sections to course. First using the group management tool.

        // Set up mock sections.
        $sections['001A'] = array('sect_no' => '001A',
            'srs_crs_no' => $class->srs + 1);
        $sections['001B'] = array('sect_no' => '001B',
            'srs_crs_no' => $class->srs + 2);

        // Set up mock data for ccle_class_sections.
        $sectionresults = array();
        foreach ($sections as $section) {
            $sectionresults[] = array('sect_no' => $section['sect_no'],
                'cls_act_typ_cd' => 'DIS',
                'sect_enrl_stat_cd' => 'O',
                'srs_crs_no' => $section['srs_crs_no']);
        }
        $this->set_mockregdata('ccle_class_sections', $class->term, $class->srs,
                $sectionresults);
        // Do not handle students in this test.
        $this->set_mockregdata('ccle_roster_class', $class->term, $class->srs, array());
        $this->set_mockregdata('ccle_roster_class', $class->term,
                $sections['001A']['srs_crs_no'], array());
        $this->set_mockregdata('ccle_roster_class', $class->term,
                $sections['001B']['srs_crs_no'], array());
        $sync = $this->mockgroupmanager->sync_course($class->courseid);
        $this->assertTrue($sync);

        // Need to fake Registrar data by setting cache.
        $cache = cache::make('block_ucla_tasites', 'tasitemapping');
        $mapping = array();
        $mapping['term'] = $class->term;
        $mapping['bysection']['001A']['secsrs'] = array($sections['001A']['srs_crs_no']);
        $mapping['bysection']['001B']['secsrs'] = array($sections['001B']['srs_crs_no']);
        $cache->set($class->courseid, $mapping);
        $typeinfo = block_ucla_tasites::get_tasection_mapping($class->courseid);
        $this->assertEquals($mapping, $typeinfo);

        // Make TA site for both sections.
        $tasite = $this->tasitegen->create_instance($parentcourse, $typeinfo);
        $this->assertFalse(empty($tasite));

        // Make sure that TA site is titled properly.
        $this->assertEquals($parentcourse->shortname . '-1A-1B', $tasite->shortname);

        // Make sure that default grouping is the TA specific grouping.
        $tasite = get_course($tasite->id);  // Requery course because groupings changed.
        $defaultgrouping = groups_get_grouping($tasite->defaultgroupingid);
        $this->assertEquals(block_ucla_tasites::GROUPINGID, $defaultgrouping->idnumber);

        // Make sure that the TA specific grouping contains the right sections.
        $groups = groups_get_all_groups($tasite->id, 0, $defaultgrouping->id);
        $groupb = array_pop($groups);
        $groupa = array_pop($groups);
        $this->assertStringEndsWith('1A', $groupa->name);
        $this->assertStringEndsWith('1B', $groupb->name);

        // Now, make the TA site public and make sure the default grouping
        // changed to the public/private grouping.
        $result = block_ucla_tasites::change_default_grouping($tasite->id, $tasite->groupingpublicprivate);
        $this->assertTrue($result);
        $tasite = get_course($tasite->id);  // Requery course because groupings changed.
        $this->assertEquals($tasite->groupingpublicprivate, $tasite->defaultgroupingid);     
    }
}
