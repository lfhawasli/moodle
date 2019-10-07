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
     * UCLA data generator.
     * @var local_ucla_generator
     */
    private $uclagen;

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
        $this->uclagen = $this->getDataGenerator()->get_plugin_generator('local_ucla');
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
        $createdclass = $this->uclagen->create_class($param);
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
        $createdclass = $this->uclagen->create_class($param);
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
        $this->uclagen->create_class($param);
        // This should raise an exception.
        $this->uclagen->create_class($param);
    }

    /**
     * Make sure that public/private is properly setup for test class.
     */
    public function test_create_class_publicprivate() {        
        $class = $this->uclagen->create_class(array());
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
        
        $createdclass = $this->uclagen->create_class(array());
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
        $class1 = $this->uclagen->create_class(array());
        $class2 = $this->uclagen->create_class(array());

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
        $createdclass = $this->uclagen->create_class($param);
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
        $createdclass = $this->uclagen->create_class($param);
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
        $createdclass = $this->uclagen->create_class($param);
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
        $createdclass = $this->uclagen->create_class($param);
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
            $collab = $this->uclagen->create_collab($course);

            // Verify that site is the given type.
            $site = new siteindicator_site($collab->id);
            $this->assertEquals($type, $site->property->type);
        }
    }

    /**
     * Test creating collab sites under a given category.
     */
    public function test_create_collab_category() {        
        $category = $this->getDataGenerator()->create_category(
            array('idnumber' => 'test'));

        // Try adding to a category via idnumber.
        $collab1 = $this->uclagen->create_collab(array('type' => 'instructional',
                'category' => $category->idnumber));
        $this->assertEquals($collab1->category, $category->id);

        // Try adding to a category via id.
        $collab2 = $this->uclagen->create_collab(array('type' => 'instructional',
                'category' => $category->id));
        $this->assertEquals($collab2->category, $category->id);
    }

    /**
     * Try creating a collaboration site with a nonexistent type.
     */
    public function test_create_collab_nonexistent() {
        $course = array();
        $course['type'] = 'nonexistent';
        $collab = $this->uclagen->create_collab($course);

        // Verify that site is the "test" type.
        $site = new siteindicator_site($collab->id);
        $this->assertEquals('test', $site->property->type);
    }

    /**
     * Make sure that all UCLA roles mentioned in the fixtures directory were
     * created.
     */
    public function test_create_ucla_roles() {
        global $CFG;

        $createdroles = $this->uclagen->create_ucla_roles();
        $shortnames = array();

        // Go through each xml file in the fixtures folder, creating a $shortnames
        // array for us to check with $createdroles.
        foreach (glob($CFG->dirroot. '/local/ucla/tests/fixtures/roles/*.xml') as $file) {
            $xml = file_get_contents($file);
            if ($this->assertTrue(tool_uclarolesmigration_cleanxml::is_valid_preset($xml))) {
                $info = tool_uclarolesmigration_cleanxml::parse_preset($xml);
                $shortnames[] = $info['shortname'];
            }
        }

        // Check if each role in $shortnames is in $createdroles.
        $createdroleshortnames = array_keys($createdroles);
        foreach ($shortnames as $shortname) {
            $this->assertTrue(in_array($shortname, $createdroleshortnames));
        }
    }

    /**
     * Test creating a user.
     */
    public function test_create_user() {        
        $user = $this->uclagen->create_user();
        $this->assertNotEmpty($user->username);
        $this->assertNotEmpty($user->idnumber);

        // Create user with predefined username and idnumbers.
        $presetuser['username'] = 'test@ucla.edu';
        $presetuser['idnumber'] = '123456789';
        $user = $this->uclagen->create_user($presetuser);
        $this->assertNotEmpty($user->username);
        $this->assertNotEmpty($user->idnumber);
        $this->assertDebuggingNotCalled();

        // Create user with improperly predefined username and idnumbers.
        $improperusername['username'] = 'test';
        $user = $this->uclagen->create_user($improperusername);
        $this->assertDebuggingCalled('Given username does not end with @ucla.edu');
        $improperidnumber['idnumber'] = '12345678';
        $user = $this->uclagen->create_user($improperidnumber);
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
                $parent = $this->uclagen->create_class($params[$parentparam]);

                // Make sure ucla_map_courseid_to_termsrses returns only the
                // term/srs of the parent.
                $firstparent = reset($parent);
                $result = $this->match_termsrses($firstparent->courseid, $parent);
                $this->assertTrue($result);

                $children = $this->uclagen->create_class($params[$childparams]);

                // Now crosslist the 2 courses.
                $this->expectOutputRegex('/Deleted - Grades/');
                $result = $this->uclagen->crosslist_courses($firstparent, $children);
                $this->resetDebugging();    // Ignore Event 1 API warnings.
                $this->assertTrue($result);

                // Make sure that ucla_map_courseid_to_termsrses returns the
                // term/srs of both parent and children now.
                $result = $this->match_termsrses($firstparent->courseid,
                        array_merge($parent, $children));

                $this->assertTrue($result);
            }
        }
    }

    /**
     * Test enrol_reg_user() with a crosslisted course.
     */
    public function test_enrol_reg_user_crosslisted() {
        global $DB;
        
        $roles = $this->uclagen->create_ucla_roles(array('student'));

        $classrequests = $this->uclagen->create_class(array(array(), array(), array()));
        $course = reset($classrequests);
        $coursecontext = context_course::instance($course->courseid);

        foreach ($classrequests as $classrequest) {
            $student = $this->uclagen->create_user();

            // Make sure that student is enrolled in course with given role
            // and is in ccle_roster_class_cache.
            $result = $this->uclagen->enrol_reg_user($student->id, $classrequest->courseid,
                            $roles['student'], $classrequest->srs);
            $this->assertTrue($result);
            $userroles = get_user_roles($coursecontext, $student->id);
            $this->assertEquals($roles['student'], array_pop($userroles)->roleid);
            $result = $DB->record_exists('ccle_roster_class_cache',
                    array('param_term'  => $classrequest->term,
                          'param_srs'   => $classrequest->srs,
                          'stu_id'      => $student->idnumber));
            $this->assertTrue($result);
        }
    }

    /**
     * Call enrol_reg_user() with a bunch of bad data and make sure it returns
     * false.
     */
    public function test_enrol_reg_user_errors() {        
        $roles = $this->uclagen->create_ucla_roles(array('student'));
        $student = $this->uclagen->create_user();

        // Course not a Registrar course.
        $collab = $this->getDataGenerator()->create_course();
        $result = $this->uclagen->enrol_reg_user($student->id, $collab->id, $roles['student']);
        $this->assertFalse($result);

        // Enrolling in srs that does not exist.
        $class = $this->uclagen->create_class(array(array('srs' => '123456789'),
                                     array('srs' => '987654321')));
        $course = reset($class);
        $result = $this->uclagen->enrol_reg_user($student->id, $course->courseid,
                $roles['student'], '111222333');
        $this->assertFalse($result);
    }

    /**
     * Test enrol_reg_user() with a non-crosslisted course.
     */
    public function test_enrol_reg_user_noncrosslisted() {
        global $DB;
        
        $roles = $this->uclagen->create_ucla_roles(array('editinginstructor', 'student'));

        $class = $this->uclagen->create_class(array());
        $course = reset($class);
        $coursecontext = context_course::instance($course->courseid);

        $instructor = $this->uclagen->create_user();
        $student = $this->uclagen->create_user();

        // Make sure that instructor is enrolled in course with given role
        // and is not in ccle_roster_class_cache.
        $result = $this->uclagen->enrol_reg_user($instructor->id, $course->courseid,
                $roles['editinginstructor']);
        $this->assertTrue($result);
        $userroles = get_user_roles($coursecontext, $instructor->id);
        $this->assertEquals($roles['editinginstructor'], array_pop($userroles)->roleid);

        // Make sure that student is enrolled in course with given role
        // and is in ccle_roster_class_cache.
        $result = $this->uclagen->enrol_reg_user($student->id, $course->courseid, $roles['student']);
        $this->assertTrue($result);
        $userroles = get_user_roles($coursecontext, $student->id);
        $this->assertEquals($roles['student'], array_pop($userroles)->roleid);
        $result = $DB->record_exists('ccle_roster_class_cache',
                array('param_term'  => $course->term,
                      'param_srs'   => $course->srs,
                      'stu_id'      => $student->idnumber));
        $this->assertTrue($result);
    }
}
