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
 * Unit tests for the data generator for UCLA local plugin.
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
 * PHPUnit data generator testcase.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class local_ucla_generator_testcase extends advanced_testcase {

    /**
     * Helper method for test_crosslist_courses() to make sure that results
     * returned by ucla_map_courseid_to_termsrses() contain the same term/srs
     * combos are in the provided $courses parameter.
     *
     * @param int $courseid
     * @param array $courses
     */
    private function match_termsrses($courseid, array $courses) {
        $matched = true;
        $termsrses = ucla_map_courseid_to_termsrses($courseid);

        if (count($termsrses) != count($courses)) {
            $matched = false;
        } else {
            foreach ($termsrses as $termsrs) {
                $found = false;
                foreach ($courses as $course) {
                    if ($course->term == $termsrs->term &&
                            $course->srs == $termsrs->srs) {
                        $found = true;
                        break;
                    }
                }
                if (empty($found)) {
                    $matched = false;
                    break;
                }
            }
        }

        return $matched;
    }

    /**
     * Clear database after every test.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Try to pass an array of two empty arrays to tell the random class creator
     * to create a random crosslisted class.
     */
    public function test_create_class_empty_crosslisted() {
        global $DB;

        $beforecourse = $DB->count_records('course');
        $beforerequest = $DB->count_records('ucla_request_classes');
        $beforeclassinfo = $DB->count_records('ucla_reg_classinfo');

        $param = array(array(), array());
        $createdclass = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class($param);
        $this->assertFalse(empty($createdclass));

        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');

        $this->assertEquals($beforecourse + 1, $aftercourse);
        $this->assertEquals($beforerequest + 2, $afterrequest);
        $this->assertEquals($beforeclassinfo + 2, $afterclassinfo);
    }

    /**
     * Try to pass an empty array to tell the random class creator to create a
     * random noncrosslisted class.
     */
    public function test_create_class_empty_noncrosslisted() {
        global $DB;

        $beforecourse = $DB->count_records('course');
        $beforerequest = $DB->count_records('ucla_request_classes');
        $beforeclassinfo = $DB->count_records('ucla_reg_classinfo');

        $param = array();
        $createdclass = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class($param);
        $this->assertFalse(empty($createdclass));

        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');

        $this->assertEquals($beforecourse + 1, $aftercourse);
        $this->assertEquals($beforerequest + 1, $afterrequest);
        $this->assertEquals($beforeclassinfo + 1, $afterclassinfo);
    }

    /**
     * Try to create a duplicate class.
     *
     * @expectedException dml_exception
     */
    public function test_create_class_exception_manual() {
        $param = array('term' => '13F', 'srs' => '262508200',
            'subj_area' => 'MATH', 'crsidx' => '0135    ',
            'secidx' => ' 001  ', 'division' => 'PS');
        $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class($param);
        // This should raise an exception.
        $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class($param);
    }

    /**
     * Make sure that public/private is properly setup for test class.
     */
    public function test_create_class_publicprivate() {
        global $DB;

        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class();
        $course = array_pop($class);
        $courseid = $course->courseid;

        // Make sure that guest enrollment plugin is enabled.
        $plugins = enrol_get_instances($courseid, true);
        foreach ($plugins as $plugin) {
            if ($plugin->enrol == 'guest') {
                $this->assertEquals(ENROL_INSTANCE_ENABLED, $plugin->status);
            }
        }

        // Make sure groupings are set.
        $course = get_course($courseid);
        $pubprivcourse = new PublicPrivate_Course($course);
        $this->assertTrue($pubprivcourse->is_activated());
    }

    /**
     * Try to create a randomly created class.
     */
    public function test_create_class_random() {
        global $DB;

        $beforecourse = $DB->count_records('course');
        $beforerequest = $DB->count_records('ucla_request_classes');
        $beforeclassinfo = $DB->count_records('ucla_reg_classinfo');

        $createdclass = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class();
        $this->assertFalse(empty($createdclass));

        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');

        $this->assertGreaterThan($beforecourse, $aftercourse);
        $this->assertGreaterThan($beforerequest, $afterrequest);
        $this->assertGreaterThan($beforeclassinfo, $afterclassinfo);
    }

    /**
     * Try to create 2 randomly created classes and make sure they are different.
     */
    public function test_create_class_random_nodup() {
        $class1 = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $class2 = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());

        $this->assertNotEquals(array_pop($class1)->srs, array_pop($class2)->srs);
    }

    /**
     * Try to create a bunch of classes for a given term.
     */
    public function test_create_class_term() {
        global $DB;

        $numcourses = $DB->count_records('course');
        $numrequests = $DB->count_records('ucla_request_classes');
        $numclassinfos = $DB->count_records('ucla_reg_classinfo');

        // Generate a class with all fields defined.
        $param = array('term' => '13F', 'srs' => '262508200',
            'subj_area' => 'MATH', 'crsidx' => '0135    ',
            'secidx' => ' 001  ', 'division' => 'PS');
        $createdclass = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class($param);
        $this->assertFalse(empty($createdclass));
        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');
        $this->assertEquals($numcourses + 1, $aftercourse);
        $this->assertEquals($numrequests + 1, $afterrequest);
        $this->assertEquals($numclassinfos + 1, $afterclassinfo);
        $numcourses = $aftercourse;
        $numrequests = $afterrequest;
        $numclassinfos = $afterclassinfo;

        // Generate a crosslisted class with all fields defined.
        $param = array(
            array('term' => '13F', 'srs' => '285061200',
                'subj_area' => 'NR EAST', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'),
            array('term' => '13F', 'srs' => '257060200',
                'subj_area' => 'ASIAN', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'));
        $createdclass = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class($param);
        $this->assertFalse(empty($createdclass));
        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');
        $this->assertEquals($numcourses + 1, $aftercourse);
        $this->assertEquals($numrequests + 2, $afterrequest);
        $this->assertEquals($numclassinfos + 2, $afterclassinfo);
        $numcourses = $aftercourse;
        $numrequests = $afterrequest;
        $numclassinfos = $afterclassinfo;

        // Make sure that all created courses belong to 13F.
        $terms = $DB->get_fieldset_select('ucla_request_classes', 'term', '',
                array());
        foreach ($terms as $term) {
            $this->assertEquals('13F', $term);
        }

        // Generate a random non-crosslisted class.
        $param = array(array('term' => '13F'));
        $createdclass = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class($param);
        $this->assertFalse(empty($createdclass));
        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');
        $this->assertEquals($numcourses + 1, $aftercourse);
        $this->assertEquals($numrequests + 1, $afterrequest);
        $this->assertEquals($numclassinfos + 1, $afterclassinfo);
        $numcourses = $aftercourse;
        $numrequests = $afterrequest;
        $numclassinfos = $afterclassinfo;

        // Make sure that all created courses belong to 13F.
        $terms = $DB->get_fieldset_select('ucla_request_classes', 'term', '',
                array());
        foreach ($terms as $term) {
            $this->assertEquals('13F', $term);
        }

        // Generate a random crosslisted class.
        $param = array(array('term' => '13F'), array('term' => '13F'));
        $createdclass = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class($param);
        $this->assertFalse(empty($createdclass));
        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');
        $this->assertEquals($numcourses + 1, $aftercourse);
        $this->assertEquals($numrequests + 2, $afterrequest);
        $this->assertEquals($numclassinfos + 2, $afterclassinfo);
        $numcourses = $aftercourse;
        $numrequests = $afterrequest;
        $numclassinfos = $afterclassinfo;

        // Make sure that all created courses belong to 13F.
        $terms = $DB->get_fieldset_select('ucla_request_classes', 'term', '',
                array());
        foreach ($terms as $term) {
            $this->assertEquals('13F', $term);
        }
    }

    /**
     * Test creating different types of collaboration sites.
     */
    public function test_create_collab() {
        // Create each type of collaborate site.
        $types = siteindicator_manager::get_types_list();
        foreach ($types as $type => $info) {
            $course = array();
            $course['type'] = $type;
            $collab = $this->getDataGenerator()
                    ->get_plugin_generator('local_ucla')
                    ->create_collab($course);

            // Verify that site is the given type.
            $site = new siteindicator_site($collab->id);
            $this->assertEquals($type, $site->property->type);
        }
    }

    /**
     * Try creating a collaboration site with a nonexistant type.
     */
    public function test_create_collab_nonexistant() {
        $course = array();
        $course['type'] = 'nonexistant';
        $collab = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_collab($course);

        // Verify that site is the "test" type.
        $site = new siteindicator_site($collab->id);
        $this->assertEquals('test', $site->property->type);
    }

    /**
     * Make sure that all UCLA roles mentioned in the fixtures directory were
     * created.
     */
    public function test_create_ucla_roles() {
        global $CFG, $DB;

        $createdroles = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_ucla_roles();

        // Load fixture of role data from PROD. This will grant access to a
        // new variable called $roles.
        include($CFG->dirroot . '/local/ucla/tests/fixtures/mdl_role.php');
        
        $createdroleshortnames = array_keys($createdroles);
        foreach ($roles as $role) {
            $this->assertTrue(in_array($role['shortname'], $createdroleshortnames));
        }

        // Also make sure that student is returned.
         $this->assertTrue(isset($createdroles['student']));
    }

    /**
     * Test creating a user.
     */
    public function test_create_user() {
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user();
        $this->assertNotEmpty($user->username);
        $this->assertNotEmpty($user->idnumber);

        // Create user with predefined username and idnumbers.
        $presetuser['username'] = 'test@ucla.edu';
        $presetuser['idnumber'] = '123456789';
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user($presetuser);
        $this->assertNotEmpty($user->username);
        $this->assertNotEmpty($user->idnumber);
        $this->assertDebuggingNotCalled();

        // Create user with improperly predefined username and idnumbers.
        $improperusername['username'] = 'test';
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user($improperusername);
        $this->assertDebuggingCalled('Given username does not end with @ucla.edu');
        $improperidnumber['idnumber'] = '12345678';
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user($improperidnumber);
        $this->assertDebuggingCalled('Given idnumber is not 9 digits long');
    }

    /**
     * Tests the crosslist_courses method to make sure that we can crosslist
     * with every combination of non-crosslist and crosslist courses.
     */
    public function test_crosslist_courses() {
        // Get all possible combinations of non-crosslist and crosslist courses.
        $combos = array('noncrosslist' => array('noncrosslist', 'crosslist'),
            'crosslist' => array('noncrosslist', 'crosslist'));

        // Make sure we are building courses in the same term.
        $params = array('noncrosslist' => array(array('term' => '13F')),
            'crosslist' => array(array('term' => '13F'),
                array('term' => '13F')));
        foreach ($combos as $parentparam => $childrenparams) {
            foreach ($childrenparams as $childparams) {
                // Create new parent for each type of child.
                $parent = $this->getDataGenerator()
                        ->get_plugin_generator('local_ucla')
                        ->create_class($params[$parentparam]);

                // Make sure ucla_map_courseid_to_termsrses returns only the
                // term/srs of the parent.
                $firstparent = reset($parent);
                $result = $this->match_termsrses($firstparent->courseid, $parent);
                $this->assertTrue($result);

                $children = $this->getDataGenerator()
                        ->get_plugin_generator('local_ucla')
                        ->create_class($params[$childparams]);

                // Now crosslist the 2 courses.
                $result = $this->getDataGenerator()
                        ->get_plugin_generator('local_ucla')
                        ->crosslist_courses($firstparent, $children);
                $this->assertTrue($result);

                // Make sure that ucla_map_courseid_to_termsrses returns the
                // term/srs of both parent and children now.
                $result = $this->match_termsrses($firstparent->courseid,
                        array_merge($parent, $children));

                $this->assertTrue($result);
            }
        }
    }

}
