<?php
// This file is part of the UCLA TA site creator plugin for Moodle - http://moodle.org/
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
require_once($CFG->dirroot . '/blocks/ucla_tasites/tests/generator/lib.php');

/**
 * PHPUnit data generator testcase
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group block_ucla_tasites
 */
class block_ucla_tasites_generator_testcase extends advanced_testcase {

    /**
     * Try to create a tasite using the basic "create_instance" generator method
     * with no parameters.
     */
    public function test_create_instance_basic() {
        // Try to create tasite with generator creating everything it needs.
        $tasite = $this->getDataGenerator()
                ->get_plugin_generator('block_ucla_tasites')
                ->create_instance();
        $this->assertFalse(empty($tasite));

        // Make sure that someone has ta_admin role in new course.
        $coursecontext = context_course::instance($tasite->id);
        $taadminid = $this->getDataGenerator()
                        ->get_plugin_generator('block_ucla_tasites')
                ->taadminid;
        $users = get_role_users($taadminid, $coursecontext);
        $this->assertFalse(empty($users));

        $istasite = block_ucla_tasites::is_tasite($tasite->id);
        $this->assertTrue($istasite);
    }

    /**
     * Try to create a tasite using a ta_admin and a UCLA course.
     */
    public function test_create_instance_ta_admin() {
        global $DB;

        // Create a random UCLA course.
        $param = array(array(), array());
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class($param);
        $this->assertFalse(empty($class));
        $termsrs = array_pop($class);

        $courseid = ucla_map_termsrs_to_courseid($termsrs->term, $termsrs->srs);
        $course = $DB->get_record('course', array('id' => $courseid));

        // Create a random user.
        $ta = $this->getDataGenerator()->create_user();
        $this->assertFalse(empty($ta));

        // Try to create tasite for ta role.
        $tasite = $this->getDataGenerator()
                ->get_plugin_generator('block_ucla_tasites')
                ->create_instance_with_role($course, (array) $ta, 'ta_admin');
        $this->assertFalse(empty($tasite));

        // Make sure user has proper role in newly created course (ta_admin).
        $coursecontext = context_course::instance($tasite->id);
        $taadminid = $this->getDataGenerator()
                ->get_plugin_generator('block_ucla_tasites')
                ->taadminid;
        $users = get_role_users($taadminid, $coursecontext);
        $user = $users[$ta->id];
        $this->assertEquals($taadminid, $user->roleid);
        $this->assertEquals('Teaching Assistant (admin)', $user->rolename);

        $istasite = block_ucla_tasites::is_tasite($tasite->id);
        $this->assertTrue($istasite);
    }

    /**
     * Setup the UCLA ta site data generator.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
        $this->getDataGenerator()
                ->get_plugin_generator('block_ucla_tasites')
                ->setup();
    }

}
