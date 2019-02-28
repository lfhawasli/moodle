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
 * Unit tests for local_publicprivate\task\cleanup task.
 *
 * @package     local_publicprivate
 * @copyright   2019 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Unit test file.
 *
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_testcase extends advanced_testcase {

    /**
     * Course object.
     * @var PublicPrivate_Course
     */
    private $course = null;

    /**
     * Task object.
     * @var local_publicprivate\task\cleanup
     */
    private $task = null;

    /**
     * Setup up environment.
     */
    public function setUp() {
        $this->resetAfterTest();
        set_config('enablepublicprivate', 1);

        // Create course with public/private enabled.
        $course = $this->getDataGenerator()->create_course();
        $this->course = new PublicPrivate_Course($course->id);
        $this->assertTrue($this->course->is_activated());

        $this->task = new local_publicprivate\task\cleanup();
    }

    /**
     * Tests the fix_membership method.
     */
    public function test_fix_membership_exception() {
        global $DB;

        // Try to create an exception by having course with invalid
        // group/grouping.

        // Turn off public/private for course.
        $this->course->deactivate();
        $course = $this->course->get_course();

        // Enroll a student.
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id);

        // Set invalid group/grouping ids.
        $course->enablepublicprivate = 1;   // Fake that public/private is set.
        $course->grouppublicprivate = 123;
        $course->groupingpublicprivate = 321;
        $DB->update_record('course', $course);

        // Update enrollment method so course gets processed.
        $enrol = $DB->get_record('enrol', array('courseid' => $course->id,
            'enrol' => 'manual'));
        $enrol->timemodified += 1;
        $DB->update_record('enrol', $enrol);

        // Now exception should be triggered.
        $this->expectOutputRegex('/ERROR: Could not check enrollment for userid/');
        $this->task->fix_membership();
    }

    /**
     * Tests the fix_missing_group_groupings method.
     */
    public function test_fix_missing_group_groupings() {
        // Delete public/private group.
        $ppgroupid = $this->course->get_group();
        groups_delete_group($ppgroupid);
        $this->assertFalse(groups_get_group($ppgroupid));

        // Verify that group is created.
        $this->task->fix_missing_group_groupings();
        $this->assertNotEmpty(groups_get_group($ppgroupid));

        // Delete public/private grouping.
        $ppgroupingid = $this->course->get_grouping();
        groups_delete_grouping($ppgroupingid);
        $this->assertFalse(groups_get_grouping($ppgroupingid));

        // Verify that grouping is created.
        $this->task->fix_missing_group_groupings();
        $this->assertNotEmpty(groups_get_grouping($ppgroupingid));

        // Delete public/private group and grouping.
        groups_delete_group($ppgroupid);
        $this->assertFalse(groups_get_group($ppgroupid));
        groups_delete_grouping($ppgroupingid);
        $this->assertFalse(groups_get_grouping($ppgroupingid));

        // Verify that group and grouping is created.
        $this->task->fix_missing_group_groupings();
        $this->assertNotEmpty(groups_get_group($ppgroupid));
        $this->assertNotEmpty(groups_get_grouping($ppgroupingid));
    }

}
