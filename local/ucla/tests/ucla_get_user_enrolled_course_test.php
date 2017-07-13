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
 * Unit tests for ucla_get_user_enrolled_course.
 *
 * @package    local_ucla
 * @subpackage test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class ucla_get_user_enrolled_course_test extends advanced_testcase {

    /**
     * Setup.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Test enrolled student.
     */
    public function test_enrolled_student() {
        global $DB;

        $uclagen = $this->getDataGenerator()->get_plugin_generator('local_ucla');

        // Create class.
        $classes = $uclagen->create_class(array());
        $class = reset($classes);

        $student = $uclagen->create_user();

        // Get the student roleid.
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // Enroll student.
        $this->getDataGenerator()->enrol_user($student->id, $class->courseid,
                $studentroleid, 'database');

        // Need to populate ccle_roster_class_cache. Cannot mock
        // registrar_ccle_roster_class because it uses static function calls.
        // If we refactor the registrar classes to be better mockable, this test
        // should be changed.
        $record = array('param_term' => $class->term,
                        'param_srs' => $class->srs,
                        'expires_on' => time() + 600,
                        'term_cd' => $class->term,
                        'stu_id' => $student->idnumber,
                        'full_name_person' => fullname($student),
                        'enrl_stat_cd' => 'E',
                        'ss_email_addr' => $student->email);
        $DB->insert_record('ccle_roster_class_cache', $record);

        $results = ucla_get_user_enrolled_course($class->courseid, $student->id);
        $this->assertEquals(1, count($results));
        $result = reset($results);
        $this->assertEquals($class->term, $result->term);
        $this->assertEquals($class->srs, $result->srs);
    }

    /**
     * Test enrolled student in cross-listed course.
     */
    public function test_enrolled_student_crosslisted() {
        global $DB;

        $uclagen = $this->getDataGenerator()->get_plugin_generator('local_ucla');

        // Create class.
        $classes = $uclagen->create_class(array(), array());
        $class = reset($classes);

        $student = $uclagen->create_user();

        // Get the student roleid.
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // Enroll student.
        $this->getDataGenerator()->enrol_user($student->id, $class->courseid,
                $studentroleid, 'database');

        // Need to populate ccle_roster_class_cache. Cannot mock
        // registrar_ccle_roster_class because it uses static function calls.
        // If we refactor the registrar classes to be better mockable, this test
        // should be changed.
        $record = array('param_term' => $class->term,
                        'param_srs' => $class->srs,
                        'expires_on' => time() + 600,
                        'term_cd' => $class->term,
                        'stu_id' => $student->idnumber,
                        'full_name_person' => fullname($student),
                        'enrl_stat_cd' => 'E',
                        'ss_email_addr' => $student->email);
        $DB->insert_record('ccle_roster_class_cache', $record);

        $results = ucla_get_user_enrolled_course($class->courseid, $student->id);
        $this->assertEquals(1, count($results));
        $result = reset($results);
        $this->assertEquals($class->term, $result->term);
        $this->assertEquals($class->srs, $result->srs);
    }

    /**
     * Test non-enrolled student.
     */
    public function test_nonenrolled_student() {
        global $DB;

        $uclagen = $this->getDataGenerator()->get_plugin_generator('local_ucla');

        // Create class we are looking for.
        $classes = $uclagen->create_class(array('term' => '14S'));
        $class = reset($classes);

        // Create another class we are not looking for.
        $classes = $uclagen->create_class(array('term' => '14S'));
        $otherclass = reset($classes);

        $student = $uclagen->create_user();

        // Get the student roleid.
        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

        // Enroll student in other class.
        $this->getDataGenerator()->enrol_user($student->id, $otherclass->courseid,
                $studentroleid, 'database');

        // Need to populate ccle_roster_class_cache. Cannot mock
        // registrar_ccle_roster_class because it uses static function calls.
        // If we refactor the registrar classes to be better mockable, this test
        // should be changed.
        $record = array('param_term' => $otherclass->term,
                        'param_srs' => $otherclass->srs,
                        'expires_on' => time() + 600,
                        'term_cd' => $otherclass->term,
                        'stu_id' => $student->idnumber,
                        'full_name_person' => fullname($student),
                        'enrl_stat_cd' => 'E',
                        'ss_email_addr' => $student->email);
        $DB->insert_record('ccle_roster_class_cache', $record);

        $results = ucla_get_user_enrolled_course($class->courseid, $student->id);
        $this->assertEquals(0, count($results));
    }

}
