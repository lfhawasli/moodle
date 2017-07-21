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
 * Tests the course_visibility_task class.
 *
 * @package    local_ucla
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * PHPunit testcase class.
 *
 * @copyright  2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_visibility_task_test extends advanced_testcase {

    /**
     * Database changes are made.
     */
    protected function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Test that the execute() method works as expected.
     */
    public function test_execute() {
        // Course id => expected visible value.
        $expectations = array();
        $now = time();

        // 1. Create normal course with nothing set.
        $course = $this->getDataGenerator()->create_course();
        $expectations[$course->id] = 1;

        // 2. Set hidestartdate not passed and course is visible.
        $course = $this->getDataGenerator()->create_course(
                array('hidestartdate' => $now + 5 * MINSECS));
        $expectations[$course->id] = 1;

        // 3. Set hidestartdate not passed and course is hidden
        $course = $this->getDataGenerator()->create_course(
                array('hidestartdate' => $now + 5 * MINSECS));
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 1;

        // 4. Set hidestartdate so it has passed.
        $course = $this->getDataGenerator()->create_course(
                array('hidestartdate' => $now - 5 * MINSECS));
        $expectations[$course->id] = 0;

        // 5. Set hideenddate so it hasn't passed and course is visible.
        $course = $this->getDataGenerator()->create_course(
                array('hideenddate' => $now + 5 * MINSECS));
        $expectations[$course->id] = 0;

        // 6. Set hideenddate so it hasn't passed and course is hidden.
        $course = $this->getDataGenerator()->create_course(
                array('hideenddate' => $now + 5 * MINSECS));
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 0;

        // 7. Set hideenddate so it has passed and course is visible.
        $course = $this->getDataGenerator()->create_course(
                array('hideenddate' => $now - 5 * MINSECS));
        $expectations[$course->id] = 1;

        // 8. Set hideenddate so it has passed and course is hidden.
        $course = $this->getDataGenerator()->create_course(
                array('hideenddate' => $now - 5 * MINSECS));
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 1;

        // 9. Set hidestartdate and hideenddate now is between and course is visible.
        $course = $this->getDataGenerator()->create_course(
                array('hidestartdate' => $now - 5 * MINSECS,
                    'hideenddate' => $now + 5 * MINSECS));
        $expectations[$course->id] = 0;

       // 10. Set hidestartdate and hideenddate now is between and course is hidden.
        $course = $this->getDataGenerator()->create_course(
                array('hidestartdate' => $now - 5 * MINSECS,
                    'hideenddate' => $now + 5 * MINSECS));
        course_change_visibility($course->id, 0);
        $expectations[$course->id] = 0;

        // Run execute.
        $task = new \local_ucla\task\course_visibility_task();
        $task->execute();

        $testcase = 1;
        foreach ($expectations as $courseid => $expectation) {
            $this->assertEquals($expectation, get_course($courseid)->visible,
                    'Failed testcase ' . $testcase);
            ++$testcase;
        }
    }

    /**
     * Test that the set_visiblity() method works as expected.
     */
    public function test_set_visiblity() {
        $now = time();

        // Test that course with no hidestartdate/hideenddate is unaffected.
        $course = $this->getDataGenerator()->create_course();
        $this->assertEquals(1, $course->visible);
        \local_ucla\task\course_visibility_task::set_visiblity($course);
        $newcourse = get_course($course->id);
        $this->assertEquals(1, $newcourse->visible);

        // Set hidestartdate not passed and course is visible.
        $course = $this->getDataGenerator()->create_course(
                array('hidestartdate' => $now + 5 * MINSECS));
        $this->assertEquals(1, $course->visible);
        \local_ucla\task\course_visibility_task::set_visiblity($course);
        $newcourse = get_course($course->id);
        $this->assertEquals(1, $newcourse->visible);

        // Set hidestartdate not passed and course is hidden
        $course = $this->getDataGenerator()->create_course(
                array('hidestartdate' => $now + 5 * MINSECS));
        course_change_visibility($course->id, 0);
        $oldcourse = get_course($course->id);
        $this->assertEquals(0, $oldcourse->visible);
        \local_ucla\task\course_visibility_task::set_visiblity($oldcourse);
        $newcourse = get_course($course->id);
        $this->assertEquals(1, $newcourse->visible);

        // Set hidestartdate so it has passed.
        $course = $this->getDataGenerator()->create_course(
                array('hidestartdate' => $now - 5 * MINSECS));
        $this->assertEquals(1, $course->visible);
        \local_ucla\task\course_visibility_task::set_visiblity($course);
        $newcourse = get_course($course->id);
        $this->assertEquals(0, $newcourse->visible);

        // Set hideenddate so it hasn't passed and course is visible.
        $course = $this->getDataGenerator()->create_course(
                array('hideenddate' => $now + 5 * MINSECS));
        $this->assertEquals(1, $course->visible);
        \local_ucla\task\course_visibility_task::set_visiblity($course);
        $newcourse = get_course($course->id);
        $this->assertEquals(0, $newcourse->visible);

        // Set hideenddate so it hasn't passed and course is hidden.
        $course = $this->getDataGenerator()->create_course(
                array('hideenddate' => $now + 5 * MINSECS));
        course_change_visibility($course->id, 0);
        $oldcourse = get_course($course->id);
        $this->assertEquals(0, $oldcourse->visible);
        \local_ucla\task\course_visibility_task::set_visiblity($oldcourse);
        $newcourse = get_course($course->id);
        $this->assertEquals(0, $newcourse->visible);

        // Set hideenddate so it has passed and course is visible.
        $course = $this->getDataGenerator()->create_course(
                array('hideenddate' => $now - 5 * MINSECS));
        $this->assertEquals(1, $course->visible);
        \local_ucla\task\course_visibility_task::set_visiblity($course);
        $newcourse = get_course($course->id);
        $this->assertEquals(1, $newcourse->visible);

        // Set hideenddate so it has passed and course is hidden.
        $course = $this->getDataGenerator()->create_course(
                array('hideenddate' => $now - 5 * MINSECS));
        course_change_visibility($course->id, 0);
        $oldcourse = get_course($course->id);
        $this->assertEquals(0, $oldcourse->visible);
        \local_ucla\task\course_visibility_task::set_visiblity($oldcourse);
        $newcourse = get_course($course->id);
        $this->assertEquals(1, $newcourse->visible);

        // Set hidestartdate and hideenddate in future and course is hidden.
        $course = $this->getDataGenerator()->create_course(
                array('hidestartdate' => $now + 1 * DAYSECS,
                    'hideenddate' => $now + 2 * DAYSECS));
        course_change_visibility($course->id, 0);
        $oldcourse = get_course($course->id);
        $this->assertEquals(0, $oldcourse->visible);
        \local_ucla\task\course_visibility_task::set_visiblity($oldcourse);
        $newcourse = get_course($course->id);
        $this->assertEquals(1, $newcourse->visible);
    }
}
