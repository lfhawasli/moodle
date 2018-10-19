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
 * Class to unit test certain aspects of the observer class.
 *
 * @package    block_ucla_tasites
 * @category   test
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');

/**
 * Testcase class.
 *
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer_test extends advanced_testcase {
    /**
     * Array of shortname->roleid mappings.
     * @var array
     */
    private $rolescreated;

    /**
     * Setup.
     */
    protected function setUp() {
        // Import TA roles.
        $uclagen = $this->getDataGenerator()->get_plugin_generator('local_ucla');
        $this->rolescreated = $uclagen->create_ucla_roles(['ta', 'ta_admin']);

        $this->resetAfterTest(true);
    }

    /**
     * Makes sure that new_name handles section types.
     */
    public function test_clear_tasitemapping_cache() {
        $course = $this->getDataGenerator()->create_course();
        $ta = $this->getDataGenerator()->create_user();
        $taadmin = $this->getDataGenerator()->create_user();

        // Check cache.
        $cache = cache::make('block_ucla_tasites', 'tasitemapping');
        $this->assertEmpty($cache->get($course->id));

        // Populate tasitemapping cache.
        $cache->set($course->id, ['somevalues']);
        $this->assertNotEmpty($cache->get($course->id));

        // Add TA role and make sure cache is purged.
        $this->getDataGenerator()->enrol_user($ta->id, $course->id,
                $this->rolescreated['ta']);
        $this->assertEmpty($cache->get($course->id));

        // Fill cache again.
        $cache->set($course->id, ['somevalues']);
        $this->assertNotEmpty($cache->get($course->id));

        // Add TA role and make sure cache is purged.
        $this->getDataGenerator()->enrol_user($taadmin->id, $course->id,
                $this->rolescreated['ta_admin']);
        $this->assertEmpty($cache->get($course->id));

        // Fill cache again.
        $cache->set($course->id, ['somevalues']);
        $this->assertNotEmpty($cache->get($course->id));

        // Remove role from user and make sure cache is purged.
        $param = array('roleid' => $this->rolescreated['ta'],
                'userid' => $ta->id,
                'contextid' => context_course::instance($course->id)->id);
        role_unassign_all($param);
        $this->assertEmpty($cache->get($course->id));
    }
}
