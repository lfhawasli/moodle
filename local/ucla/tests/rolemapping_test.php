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
 * Unit tests for role mapping functions.
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
 * PHPunit testcase class.
 *
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class rolemapping_test extends advanced_testcase {

    /**
     * Mapping of role shortname to roleid.
     * @var array
     */
    private $createdroles = array();

    /**
     * Make sure that get_moodlerole is returning the appropiate data from 
     * local/ucla/rolemappings.php.
     */
    public function test_get_moodlerole() {
        global $CFG, $DB;
        require($CFG->dirroot . '/local/ucla/rolemappings.php');

        foreach ($role as $pseudorole => $results) {
            foreach ($results as $subjectarea => $moodlerole) {
                // Find the moodle role id for given moodle role.
                $roleentry = $DB->get_record('role',
                        array('shortname' => $moodlerole));
                if (empty($roleentry)) {
                    $this->assertTrue(false,
                            sprintf('No moodle role "%s" not found',
                                    $moodlerole));
                } else {
                    $result = get_moodlerole($pseudorole, $subjectarea);
                    $this->assertEquals($roleentry->id, $result,
                            sprintf('Failed for pseudorole: %s|subjectarea: %s|moodlerole:%s. Expecting: %d. Actual: %d',
                                    $pseudorole, $subjectarea, $moodlerole,
                                    $roleentry->id, $result));
                }
            }
        }
    }

    /**
     * Call get_moodlerole with a subject area not defined in the config file
     * to make sure that it returns the default value.
     */
    public function test_get_moodlerole_with_default() {
        global $CFG, $DB;
        require($CFG->dirroot . '/local/ucla/rolemappings.php');

        foreach ($role as $pseudorole => $results) {
            foreach ($results as $subjectarea => $moodlerole) {
                // Only test *SYSTEM* subject areas.
                if ($subjectarea != '*SYSTEM*') {
                    continue;
                }

                // Find the moodle role id for given moodle role.
                $roleentry = $DB->get_record('role',
                        array('shortname' => $moodlerole));
                if (empty($roleentry)) {
                    $this->assertTrue(false,
                            sprintf('No moodle role "%s" not found',
                                    $moodlerole));
                } else {
                    $defaultresult = get_moodlerole($pseudorole, $subjectarea);
                    // Now get result for a non-defined subject area.
                    $undefinedresult = get_moodlerole($pseudorole,
                            'NON-EXISTENT SUBJECT AREA');

                    $this->assertEquals($defaultresult, $undefinedresult);
                }
            }
        }
    }

    /**
     * Make sure that get_pseudorole always returns editingteacher if passing in
     * anyone with a role code of 01.
     * 
     * @dataProvider role_combo_provider
     *
     * @param array $rolecombo
     */
    public function test_get_pseudorole_instructor($rolecombo) {
        $params[] = array('primary' => array('01'));
        $params[] = array('secondary' => array('01'));
        $params[] = array('primary' => array('01'),
            'secondary' => array('01'));

        foreach ($params as $param) {
            $pseudorole = get_pseudorole($param, $rolecombo);
            $this->assertEquals('editingteacher', $pseudorole);
        }
    }

    /**
     * Make sure that get_pseudorole always returns supervising_instructor if 
     * passing in anyone with a role code of 03.
     * 
     * @dataProvider role_combo_provider
     *
     * @param array $rolecombo
     */
    public function test_get_pseudorole_supervising_instructor($rolecombo) {
        $params[] = array('primary' => array('03'));
        $params[] = array('secondary' => array('03'));
        $params[] = array('primary' => array('03'),
            'secondary' => array('03'));

        foreach ($params as $param) {
            $pseudorole = get_pseudorole($param, $rolecombo);
            $this->assertEquals('supervising_instructor', $pseudorole);
        }
    }

    /**
     * Test get_pseudorole to see if the following conditions for the 02 role
     * work:
     *  - Anyone with 02 on a course with an 01 is a ta
     *  - If someone is an 02 in the primary section, and there is an 03, they 
     *    are a ta_instructor (assumes no 01, because of first condition)
     *  - All other 02 cases, default to ta
     * 
     * @dataProvider role_combo_provider
     *
     * @param array $rolecombo
     */
    public function test_get_pseudorole_ta($rolecombo) {
        $params['primary'] = array('primary' => array('02'));
        $params['secondary'] = array('secondary' => array('02'));
        $params['both'] = array('primary' => array('02'),
            'secondary' => array('02'));

        // Anyone with 02 on a course with an 01 is a ta.
        if (in_array('01', $rolecombo['primary']) ||
                in_array('01', $rolecombo['secondary'])) {
            foreach ($params as $param) {
                $pseudorole = get_pseudorole($param, $rolecombo);
                $this->assertEquals('ta', $pseudorole);
            }
            return; // Exit out from further testing.
        }

        // If someone is an 02 in the primary section, and there is an 03, they
        // are a ta_instructor (assumes no 01, because of first condition).
        if (in_array('03', $rolecombo['primary']) ||
                in_array('03', $rolecombo['secondary'])) {
            $pseudorole = get_pseudorole($params['primary'], $rolecombo);
            $this->assertEquals('ta_instructor', $pseudorole);
            $pseudorole = get_pseudorole($params['secondary'], $rolecombo);
            $this->assertEquals('ta', $pseudorole);
            $pseudorole = get_pseudorole($params['both'], $rolecombo);
            $this->assertEquals('ta_instructor', $pseudorole);
            return; // Exit out from further testing.
        }

        // All other 02 cases, default to ta.
        foreach ($params as $param) {
            $pseudorole = get_pseudorole($param, $rolecombo);
            $this->assertEquals('ta', $pseudorole);
        }
    }

    /**
     * Make sure that get_pseudorole always returns student_instructor if
     * passing in anyone with a role code of 22.
     * 
     * @dataProvider role_combo_provider
     *
     * @param array $rolecombo
     */
    public function test_get_pseudorole_student_instructor($rolecombo) {
        $params[] = array('primary' => array('22'));
        $params[] = array('secondary' => array('22'));
        $params[] = array('primary' => array('22'),
            'secondary' => array('22'));

        foreach ($params as $param) {
            $pseudorole = get_pseudorole($param, $rolecombo);
            $this->assertEquals('student_instructor', $pseudorole);
        }
    }

    /**
     * Make sure that get_student_pseudorole returns the proper results.
     */
    public function test_get_student_pseudorole() {
        $shouldreturnwaitlisted = array('W', 'H', 'P');
        foreach ($shouldreturnwaitlisted as $code) {
            $result = get_student_pseudorole($code);
            $this->assertEquals('waitlisted', $result);
        }

        $shouldreturnstudent = array('E', 'A');
        foreach ($shouldreturnstudent as $code) {
            $result = get_student_pseudorole($code);
            $this->assertEquals('student', $result);
        }

        $shouldreturnfalse = array('D', 'C');
        foreach ($shouldreturnfalse as $code) {
            $result = get_student_pseudorole($code);
            $this->assertFalse($result);
        }
    }

    /**
     * Test role mapping with repeated calls to check if cache is working
     * properly.
     */
    public function test_role_mapping_cache() {
        $profcode = array('primary' => array('01'));
        $othercodes = array('secondary' => array('02'));
        $roleid = role_mapping($profcode, $othercodes);
        $this->assertEquals($this->createdroles['editinginstructor'], $roleid);
        $roleid = role_mapping($profcode, $othercodes);
        $this->assertEquals($this->createdroles['editinginstructor'], $roleid);
    }

    /**
     * Add roles used by UCLA.
     */
    protected function setUp() {
        $uclagenerator = $this->getDataGenerator()->get_plugin_generator('local_ucla');
        $this->createdroles = $uclagenerator->create_ucla_roles();

        // Very important step to include if modifying db.
        $this->resetAfterTest(true);
    }

    /*     * *******  HELPER FUNCTIONS FOR UNIT TESTING  ******* */

    /**
     * Provides a multitude of role combinations for primary and secondary 
     * sections with all possible mixes of 01, 02, and 03.
     */
    public function role_combo_provider() {
        $retval = array();
        $rolecodes = array('01', '02', '03');

        // Get all the role combos (also include empty sets).
        $primaryrolecombos = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->power_set($rolecodes, 0);
        $secondaryrolecombos = $primaryrolecombos;

        $index = 0;
        foreach ($primaryrolecombos as $primary) {
            foreach ($secondaryrolecombos as $secondary) {
                if (empty($primary) && empty($secondary)) {
                    continue;
                }
                $retval[$index][0]['primary'] = $primary;
                $retval[$index][0]['secondary'] = $secondary;
                ++$index;
            }
        }

        return $retval;
    }

}
