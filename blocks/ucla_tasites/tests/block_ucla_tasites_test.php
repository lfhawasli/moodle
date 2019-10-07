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
 * Class to unit test certain aspects of the block_ucla_tasites class.
 *
 * @package    block_ucla_tasites
 * @category   test
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');

/**
 * Testcase class.
 *
 * @copyright  2016 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ucla_tasites_test extends advanced_testcase {
    /**
     * Setup.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Make sure TA roles exists.
        $this->getDataGenerator()->get_plugin_generator('local_ucla')
                ->create_ucla_roles(['ta', 'ta_admin']);
    }

    /**
     * Makes sure that new_name handles section types.
     */
    public function test_new_name_bysection() {
        $course = $this->getDataGenerator()->create_course();
        $typeinfo = array();
        $typeinfo['bysection']['001C']['secsrs'][] = '142042310';
        $shortname = block_ucla_tasites::new_shortname($course->shortname, $typeinfo);
        $this->assertEquals($course->shortname . '-1C', $shortname);

        // See if fullname is created properly as well.
        $fullname = block_ucla_tasites::new_fullname($course->fullname, $typeinfo);
        $a = new stdClass();
        $a->fullname = $course->fullname;
        $a->text = '1C';
        $this->assertEquals(get_string('tasitefullname', 'block_ucla_tasites', $a), $fullname);

        // See if this handles shortname collisions as well.
        $test = $this->getDataGenerator()->create_course(array('shortname' => $shortname));
        $anothershortname = block_ucla_tasites::new_shortname($course->shortname, $typeinfo);
        $this->assertEquals($course->shortname . '-1C-1', $anothershortname);
    }

    /**
     * Makes sure that new_name handles ta types.
     */
    public function test_new_name_byta() {
        $course = $this->getDataGenerator()->create_course();
        $ta = $this->getDataGenerator()->create_user(array('idnumber' => '325783542'));
        $typeinfo = array();
        $taname = fullname($ta);
        $escchars = array(" ", ",", "'");
        $tanameformated = str_replace($escchars, "", $taname);

        $typeinfo['byta'][$taname]['ucla_id'] = '325783542';
        $shortname = block_ucla_tasites::new_shortname($course->shortname, $typeinfo);
        $this->assertEquals($course->shortname . '-'.$tanameformated, $shortname);

        // See if fullname is created properly as well.
        $fullname = block_ucla_tasites::new_fullname($course->fullname, $typeinfo);
        $a = new stdClass();
        $a->fullname = $course->fullname;
        $a->text = $taname;
        $this->assertEquals(get_string('tasitefullname', 'block_ucla_tasites', $a), $fullname);

        // See if this handles shortname collisions as well.
        $test = $this->getDataGenerator()->create_course(array('shortname' => $shortname));
        $anothershortname = block_ucla_tasites::new_shortname($course->shortname, $typeinfo);
        $this->assertEquals($course->shortname . '-'.$tanameformated.'-1', $anothershortname);
    }
}
