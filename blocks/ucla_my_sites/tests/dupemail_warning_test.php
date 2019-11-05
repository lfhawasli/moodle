<?php
// This file is part of the UCLA My sites block for Moodle - http://moodle.org/
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
 * Tests core edit for CCLE-8913 - Notify when user changes email to existing email address.
 *
 * @package    block_ucla_my_sites
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Test class file.
 *
 * Extends auth_db_testcase so we can fake users being created via external
 * server to simulate what would happen with Shibboleth logins.
 *
 * @package    block_ucla_my_sites
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dupemail_warning_test extends advanced_testcase {
    /**
     * Reset database on every run.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Make sure that user gets 'show_dupemail_warning' flag if changed to email
     * that already exists.
     */
    public function test_duplicate_email() {
        global $CFG, $DB;

        // Add two users.
        $result = ['username' => 'joebruin@ucla.edu', 'email' => 'joebruin@ucla.edu', 'institution' => ''];
        $user1 = $this->getDataGenerator()->create_user($result);
        $user2 = $this->getDataGenerator()->create_user([
            'username' => 'josephinebruin@ucla.edu', 'email' => 'josephinebruin@ucla.edu']);

        // User 1 should not have warning yet.
        $warning = get_user_preferences('show_dupemail_warning', false, $user1);

        // Create Shibboleth $result array with email same as user2.
        $oldemail = $result['email'];
        $result['email'] = $user2->email;

        // Run shib_transform that would be used by Shibboleth login.
        include($CFG->dirroot . '/shib_transform.php');

        // User1 should be prevented from updating email and got warning.
        $this->assertSame($oldemail, $result['email']);        
        // Need to pass in $user1->id to bypass caching issues.
        $warning = get_user_preferences('show_dupemail_warning', false, $user1->id);
        $this->assertNotFalse($warning);
        // Should retain copy of what email address user was trying to change to.
        $this->assertSame($user2->email, $warning);
    }
}
