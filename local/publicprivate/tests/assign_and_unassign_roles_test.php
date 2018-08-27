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
 * Unit tests for role_assigned and role_unassigned observer.
 *
 * @package    local_publicprivate
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/publicprivate/lib.php');

/**
 * Unit test file.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assigned_and_unassigned_testcase extends advanced_testcase {

    /**
     * @var stdClass  Course record from the database.
     */
    private $course;

    /**
     * @var stdClass  User object from the database.
     */
    private $user;

    /**
     * @var Array  Array contains all roles needed in this test.
     */
    private $roles;

    protected function setUp() {
        $this->resetAfterTest(true);

        // Create course.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array());
        $course = array_pop($class);
        $this->course = get_course($course->courseid);

        // Create a roles.
        $this->roles = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_ucla_roles(array('editinginstructor', 'student'));

        // Create a user. Assign role student to user.
        $this->user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user();
        $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->enrol_reg_user($this->user->id,
                             $this->course->id,
                             $this->roles['student']);
        $this->setAdminUser();
    }

    public function test_single_role_assigned_and_unassigned() {
        $ppcourse = PublicPrivate_Course::build($this->course->id);
        // Test whether user belongs to group PublicPrivate.
        $this->assertTrue(groups_is_member($ppcourse->get_group(), $this->user->id));
        $context = context_course::instance($ppcourse->get_course()->id);
        $param = array('roleid' => $this->roles['student'],
                     'userid' => $this->user->id, 'contextid' => $context->id);
        // Unassign role 'student' from user.
        role_unassign_all($param);
        $this->assertFalse(groups_is_member($ppcourse->get_group(), $this->user->id));
        // Assign role ' editinginstructor' to user.
        $context = context_course::instance($ppcourse->get_course()->id);
        role_assign($this->roles['editinginstructor'], $this->user->id, $context->id);
        $this->assertTrue(groups_is_member($ppcourse->get_group(), $this->user->id));
    }

    public function test_mult_roles_assigned_and_unassigned() {
        $ppcourse = PublicPrivate_Course::build($this->course->id);
        $this->assertTrue(groups_is_member($ppcourse->get_group(), $this->user->id));
        $context = context_course::instance($ppcourse->get_course()->id);
        // Assign another role ' editinginstructor' to user.
        role_assign($this->roles['editinginstructor'], $this->user->id, $context->id);
        $this->assertTrue(groups_is_member($ppcourse->get_group(), $this->user->id));
        $param = array('roleid' => $this->roles['editinginstructor'], 'userid' => $this->user->id, 'contextid' => $context->id);
        // Remove role 'editinginstructor' from user.
        role_unassign_all($param);
        $this->assertTrue(groups_is_member($ppcourse->get_group(), $this->user->id));
        $param = array('roleid' => $this->roles['student'], 'userid' => $this->user->id, 'contextid' => $context->id);
        // Remove role 'student' from user.
        role_unassign_all($param);
        $this->assertFalse(groups_is_member($ppcourse->get_group(), $this->user->id));
    }
}