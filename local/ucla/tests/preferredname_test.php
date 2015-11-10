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
 * Tests the UCLA modifications to implement preferred name (CCLE-4521).
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * PHPunit testcase class.
 *
 * @copyright  2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class preferredname_test extends advanced_testcase {

    /**
     * Makes sure that fullname works as expected for a user with an alternative name.
     *
     * If the current logged in user has the capability "moodle/site:viewfullnames",
     * then name should be displayed as follows:
     *  - Hybrid Legal Last name, Preferred first name (Legal first name Legal middle name in parentheses)
     *           Legal Last name, Legal first name Legal middle name
     *
     * Otherwise the name should be displayed as follows:
     *  - Preferred Name (Legal Last name, Preferred first name, no middle name)
     *
     * @param object $user
     * @param boolean $viewfullnamecap
     */
    protected function assertPreferredNameFormat($user, $viewfullnamecap) {
        // Check if need to display full name with legal name in parenthesis or not.
        $name = fullname($user, $viewfullnamecap);
        $legalname = empty($user->middlename) ? $user->firstname : $user->firstname . ' ' . $user->middlename;
        if (!empty($user->alternatename)) {
            if ($viewfullnamecap) {
                // Display legal name in parenthesis.
                $this->assertEquals(sprintf('%s, %s (%s)', $user->lastname, $user->alternatename, $legalname), $name);
            } else {
                // Display only preferred name.
                $this->assertEquals(sprintf('%s, %s', $user->lastname, $user->alternatename), $name);
            }
        } else {
            // If no preferred name is set, then just display name as usual.
            $this->assertEquals(sprintf('%s, %s', $user->lastname, $legalname), $name);
        }
    }

    /**
     * Set fullnamedisplay to what UCLA has set and enable preferred name handling.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        set_config('fullnamedisplay', 'lastname, firstname');
        set_config('handlepreferredname', true, 'local_ucla');
    }

    /**
     * Makes sure that fullname works as expected for a user without an alternative name.
     */
    public function test_fullname_preferredname_empty() {
        $user = $this->getDataGenerator()->create_user(array('firstname' => 'afirstname', 'lastname' => 'alastname',
                'alternatename' => '', 'middlename' => 'Test'));

        // Make sure that alternatename is not set.
        $this->assertEmpty($user->alternatename);
        $this->assertNotEmpty($user->middlename);

        $name = fullname($user);
        $this->assertEquals(sprintf('%s, %s', $user->lastname, $user->firstname), $name);
        $name = fullname($user, true);
        $this->assertEquals(sprintf('%s, %s %s', $user->lastname, $user->firstname, $user->middlename), $name);

        // See if we handle middle name properly as well.
        $user = $this->getDataGenerator()->create_user(array('alternatename' => '', 'middlename' => ''));
        $this->assertEmpty($user->alternatename);
        $this->assertEmpty($user->middlename);

        $name = fullname($user);
        $this->assertEquals(sprintf('%s, %s', $user->lastname, $user->firstname), $name);
        $name = fullname($user, true);
        $this->assertEquals(sprintf('%s, %s', $user->lastname, $user->firstname), $name);
    }

    /**
     * Makes sure that name is displayed properly at the site level context.
     */
    public function test_fullname_preferredname_set() {
        global $COURSE;
        // Import all UCLA roles.
        $roles = $this->getDataGenerator()
            ->get_plugin_generator('local_ucla')
            ->create_ucla_roles();

        // Make sure that we have a user with alternatename set.
        $user = $this->getDataGenerator()->create_user(array('firstname' => 'afirstname', 'lastname' => 'alastname',
            'alternatename' => 'Test'));
        $this->assertNotEmpty($user->alternatename);

        // Check that name displays properly for different users.

        // Admins should see full name.
        $this->setAdminUser();
        $systemcontext = context_system::instance();
        $this->assertPreferredNameFormat($user,
                has_capability('moodle/site:viewfullnames', $systemcontext));

        // Managers should see full name.
        $manager = $this->getDataGenerator()->create_user();
        role_assign($roles['manager'], $manager->id, $systemcontext);
        $this->setUser($manager);
        $this->assertPreferredNameFormat($user,
                has_capability('moodle/site:viewfullnames', $systemcontext));

        // But manager limited should not.
        $managerlimited = $this->getDataGenerator()->create_user();
        role_assign($roles['manager_limited'], $managerlimited->id, $systemcontext);
        $this->setUser($managerlimited);
        $this->assertPreferredNameFormat($user,
                has_capability('moodle/site:viewfullnames', $systemcontext));

        // Setup course.
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $COURSE = $course;  // Set global course object.

        // Instructing roles should see full name.
        $instructingroles = array('editinginstructor', 'ta_admin', 'supervising_instructor');
        foreach ($instructingroles as $role) {
            $userrole = $this->getDataGenerator()->create_user();
            role_assign($roles[$role], $userrole->id, $context);
            $this->setUser($userrole);
            $viewfullnamecap = has_capability('moodle/site:viewfullnames', $context);
            $this->assertTrue($viewfullnamecap);
            $this->assertPreferredNameFormat($user, $viewfullnamecap);
        }

        // Non-instructing roles should not see full name.
        $noninstructingroles = array('student', 'participant', 'ta');
        foreach ($noninstructingroles as $role) {
            $userrole = $this->getDataGenerator()->create_user();
            role_assign($roles[$role], $userrole->id, $context);
            $this->setUser($userrole);
            $viewfullnamecap = has_capability('moodle/site:viewfullnames', $context);
            $this->assertFalse($viewfullnamecap);
            $this->assertPreferredNameFormat($user, $viewfullnamecap);
        }
    }

    /**
     * Test that if the local_ucla|handlepreferredname setting is off, that
     * names are displayed as normal so Moodle core tests work.
     */
    public function test_preferredname_off() {
        // This will force fullname to use fullnamedisplay from lang string.
        set_config('fullnamedisplay', null);
        set_config('handlepreferredname', false, 'local_ucla');

        // Make sure that cached static variable is reset.
        local_ucla_core_edit::$handlepreferredname = null;

        // Make sure that we have a user with alternatename set.
        $user = $this->getDataGenerator()->create_user(array('alternatename' => 'Test'));
        $this->assertNotEmpty($user->alternatename);

        $name = fullname($user);
        $this->assertEquals(get_string('fullnamedisplay', null, $user), $name);
        $name = fullname($user, true);
        $this->assertEquals(get_string('fullnamedisplay', null, $user), $name);
    }
}
