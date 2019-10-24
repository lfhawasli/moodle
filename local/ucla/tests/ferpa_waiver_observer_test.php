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
 * Tests the mod_casa_privacy_waiver_observer class.
 *
 * @package    local_ucla
 * @category   test
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * PHPunit testcase class.
 *
 * @copyright  2014 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group ucla
 * @group local_ucla
 */
class mod_casa_privacy_waiver_observer_testcase extends advanced_testcase {

    /**
     * Makes sure that waiver information is deleted if a related course is
     * deleted.
     */
    public function test_course_delete() {
        global $DB;

        $this->resetAfterTest(true);

        // Create course, user, and module.
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        $this->getDataGenerator()->enrol_user($student->id, $course->id);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        $cm = $generator->create_instance(array('course' => $course->id));

        // Create waiver entry.
        $context = context_module::instance($cm->cmid);
        mod_casa_privacy_waiver::sign($course->id, $context->id, $student->id);

        // Make sure entry exists.
        $numwaivers = $DB->count_records('lti_privacy_waiver');
        $this->assertEquals(1, $numwaivers);

        // Delete course.
        $this->expectOutputRegex('/Deleted - Grades/');
        delete_course($course->id);
        $this->resetDebugging();    // Ignore debugging about Event API 1.
        // Make sure no entries exists.
        $numwaivers = $DB->count_records('lti_privacy_waiver');
        $this->assertEquals(0, $numwaivers);
    }

    /**
     * Makes sure that waiver information is deleted if a related course
     * module is deleted.
     */
    public function test_course_module_delete() {
        global $DB;

        $this->resetAfterTest(true);

        // Create course, user, and module.
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        $this->getDataGenerator()->enrol_user($student->id, $course->id);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        $cm = $generator->create_instance(array('course' => $course->id));

        // Create waiver entry.
        $context = context_module::instance($cm->cmid);
        mod_casa_privacy_waiver::sign($course->id, $context->id, $student->id);

        // Make sure entry exists.
        $numwaivers = $DB->count_records('lti_privacy_waiver');
        $this->assertEquals(1, $numwaivers);

        // Delete course module.
        course_delete_module($cm->cmid);

        // Make sure no entries exists.
        $numwaivers = $DB->count_records('lti_privacy_waiver');
        $this->assertEquals(0, $numwaivers);
    }
}
