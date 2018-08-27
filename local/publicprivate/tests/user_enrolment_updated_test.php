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

defined('MOODLE_INTERNAL') || die();

use core_availability\info_module;

global $CFG;
require_once($CFG->dirroot . '/local/publicprivate/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/lib.php');
require_once($CFG->dirroot . '/group/externallib.php');
require_once($CFG->dirroot . '/enrol/database/lib.php');
require_once($CFG->dirroot . '/enrol/self/lib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');

/**
 * Unit test file.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_enrolment_updated_testcase extends advanced_testcase {

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
     * Creates test class.
     */
    public function setUp() {
        $this->resetAfterTest();

        // Create a new course and enable public/private. We pass an array
        // containing  an empty array so that only one course is generated.
        // (No crosslisting).
        $this->gen = $this->getDataGenerator();
        $creqarr = $this->gen->get_plugin_generator('local_ucla')->create_class(array(array()));
        $this->class = reset($creqarr);
        $this->setAdminUser();
    }

    /**
     * When a user is enrolled/unenrolled to a course make sure they do or do
     * not have access to private course material.
     */
    public function test_access() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $student = $DB->get_record('role', array('shortname' => 'student'));

        // Create private material.
        $resourcegen = $this->getDataGenerator()->get_plugin_generator('mod_resource');
        $file = $resourcegen->create_instance(array('course' => $this->class->courseid));
        $ppfile = PublicPrivate_Module::build($file->cmid);
        $this->assertTrue($ppfile->is_private());

        // Set student as current user to test availability conditions.
        $this->setUser($user);

        // User should not have access if not enrolled.
        $information = '';
        $modinfo = get_fast_modinfo($this->class->courseid);
        $cm = $modinfo->get_cm($file->cmid);
        $info = new info_module($cm);
        $this->assertFalse($info->is_available($information));

        // Enroll user and should have access now.
        $this->getDataGenerator()->enrol_user($user->id, $this->class->courseid, $student->id, 'manual');
        get_fast_modinfo($this->class->courseid, $user->id, true);  // Get fresh modinfo.
        $info->is_available($information);
        $this->assertTrue($info->is_available($information));

        // Unrenroll user and should not have access again.
        $enrol = enrol_get_plugin('manual');
        $enrolinstance = $DB->get_record('enrol', array('courseid' => $this->class->courseid,
            'enrol' => 'manual'), '*', MUST_EXIST);
        get_fast_modinfo($this->class->courseid, $user->id, true);  // Get fresh modinfo.
        $enrol->unenrol_user($enrolinstance, $user->id);
        $this->assertFalse($info->is_available($information));
    }

    /**
     * Try combos of plugins.
     *
     * @dataProvider enrolment_plugin_provider
     *
     * @param array $enable     The set of plugins to enable
     * @param array $suspend    The set to suspend the user from
     */
    public function test_combo_enrolment_plugins($enable, $suspend) {
        global $DB;
        $plugins = array();
        $user = $this->getDataGenerator()->create_user();
        $student = $DB->get_record('role', array('shortname' => 'student'));

        // Enable each plugin and enrol the user in them.
        foreach ($enable as $eplugin) {
            if ($eplugin == 'guest') {
                continue;
            }
            $params = array('courseid' => $this->class->courseid, 'enrol' => $eplugin);
            $instance = $DB->get_record('enrol', $params);
            $plugin = enrol_get_plugin($eplugin);

            $plugin->update_status($instance, ENROL_INSTANCE_ENABLED);

            $this->getDataGenerator()->enrol_user($user->id, $this->class->courseid, $student->id, $eplugin);

            $instances[$eplugin] = $instance;
            $plugins[$eplugin] = $plugin;
        }

        // Check that the user is in "Course members" now.
        $ppc = PublicPrivate_Course::build($this->class->courseid);
        $this->assertTrue(groups_is_member($ppc->get_group(), $user->id));

        // Suspend user from the to suspend plugins.
        foreach ($suspend as $splugin) {
            if ($splugin == 'guest') {
                continue;
            }
            $plugins[$splugin]->update_user_enrol($instances[$splugin], $user->id, ENROL_USER_SUSPENDED);
        }

        // If there are no more active plugins, make sure the user is not a member of "Course members".
        // Otherwise, they should still be a member.
        $testval = groups_is_member($ppc->get_group(), $user->id);
        $dif = array_diff($enable, $suspend);
        $remains = count($dif);

        if ($remains > 1 || ($remains == 1 && array_pop($dif) != 'guest')) {
            $this->assertTrue($testval);
        } else {
            $this->assertFalse($testval);
        }
    }

    /**
     * Provides combinations of manual, self, and guest plugins.
     */
    public function enrolment_plugin_provider() {
        $a = array('manual');
        $b = array('manual', 'self');
        $c = array('manual', 'self', 'guest');
        $d = array('manual', 'guest');

        return array(
            array($a, $a),
            array($b, $b),
            array($b, $a),
            array($c, $b),
            array($c, $a),
            array($d, $a)
        );
    }

    /**
     * Make sure that being suspended from all plugins means that the user is
     * dropped from all course groups.
     */
    public function test_multiple_groups() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $this->class->courseid, $student->id, 'manual');

        // Make sure the user is put in the "Course members" group.
        $ppc = PublicPrivate_Course::build($this->class->courseid);
        $this->assertTrue(groups_is_member($ppc->get_group(), $user->id));

        // Create an additional group.
        $data = new stdClass();
        $data->courseid = $this->class->courseid;
        $data->name = 'testgroup';

        $groupid = groups_create_group($data);
        groups_add_member($groupid, $user->id);
        $this->assertTrue(groups_is_member($groupid, $user->id));

        // Suspend the user from the only plugin.
        $instance = $DB->get_record('enrol', array('courseid' => $this->class->courseid, 'enrol' => 'manual'));
        $plugin = enrol_get_plugin('manual');
        $plugin->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);

        // Assert that the user is no longer in either group.
        $this->assertFalse(groups_is_member($ppc->get_group(), $user->id));
        $this->assertFalse(groups_is_member($groupid, $user->id));
    }

    /**
     * Make sure that when the user has no roles, we don't re-add them to
     * "Course members".
     */
    public function test_readd_no_roles() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $this->class->courseid, $student->id, 'manual');

        $ppc = PublicPrivate_Course::build($this->class->courseid);
        $this->assertTrue(groups_is_member($ppc->get_group(), $user->id));

        $instance = $DB->get_record('enrol', array('courseid' => $this->class->courseid, 'enrol' => 'manual'));
        $plugin = enrol_get_plugin('manual');

        // Suspend the user from the only enrolment.
        $plugin->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
        $this->assertFalse(groups_is_member($ppc->get_group(), $user->id));

        // Remove the roles.
        role_unassign_all(array('userid' => $user->id));

        // Reactivate it.
        $plugin->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE);
        $this->assertFalse(groups_is_member($ppc->get_group(), $user->id));
    }

    /**
     * Make sure that suspending a user or disabling the enrolment plugin will
     * remove user.
     */
    public function test_single_enrolment_plugin() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $student = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $this->class->courseid, $student->id, 'manual');

        // Check to make sure user is a member of "Course members" now.
        $ppc = PublicPrivate_Course::build($this->class->courseid);
        $this->assertTrue(groups_is_member($ppc->get_group(), $user->id));

        $instance = $DB->get_record('enrol', array('courseid' => $this->class->courseid, 'enrol' => 'manual'));
        $plugin = enrol_get_plugin('manual');

        // Suspend the user from the only enrolment.
        $plugin->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
        $this->assertFalse(groups_is_member($ppc->get_group(), $user->id));

        // Reactivate it.
        $plugin->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE);
        $this->assertTrue(groups_is_member($ppc->get_group(), $user->id));
    }
}
