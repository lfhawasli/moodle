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
 * Unit tests for PublicPrivate_Course.
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
class course_testcase extends advanced_testcase {

    /**
     * Course object.
     * @var PublicPrivate_Course
     */
    private $ppcourse = null;

    /**
     * Helper function to make sure public/private is disabled.
     *
     * @param int $courseid
     */
    public function assertPublicPrivateDisabled($courseid) {
        $course = get_course($courseid);
        $this->assertFalse((bool) $course->enablepublicprivate);
        $this->assertEmpty(groups_get_group($course->grouppublicprivate));
        $this->assertEmpty(groups_get_grouping($course->groupingpublicprivate));
    }

    /**
     * Helper function to make sure public/private is enabled.
     *
     * @param int $courseid
     */
    public function assertPublicPrivateEnabled($courseid) {
        $ppcourse = new \PublicPrivate_Course($courseid);
        $this->assertTrue($ppcourse->is_activated());
        $this->assertNotEmpty(groups_get_group($ppcourse->get_group()));
        $this->assertNotEmpty(groups_get_grouping($ppcourse->get_grouping()));
    }

    /**
     * Creates test class.
     */
    public function setUp() {
        $this->resetAfterTest();

        // Create course with public/private enabled.
        $generator = $this->getDataGenerator();
        $creqarr = $generator->get_plugin_generator('local_ucla')->create_class(array(array()));
        $courserecord = reset($creqarr);
        $this->ppcourse = new \PublicPrivate_Course($courserecord->courseid);

        // Make sure public/private is enabled.
        $this->assertPublicPrivateEnabled($courserecord->courseid);
    }

    /**
     * Tests ability to activate/deactivate public/private for a course.
     */
    public function test_activate() {
        // Turn off public/private. Should trigger course_updated event handler.
        $course = $this->ppcourse->get_course();
        $course->enablepublicprivate = 0;
        update_course($course);

        // Make sure public/private is disabled and group/grouping deleted.
        $this->assertPublicPrivateDisabled($course->id);

        // Turn on public/private. Should trigger course_updated event handler.
        $course = get_course($course->id);
        $course->enablepublicprivate = 1;
        update_course($course);

        // Make sure public/private is enabled and group/grouping exists.
        $this->assertPublicPrivateEnabled($course->id);
    }

    /**
     * For some reason, the public/private group/grouping can exist when the
     * feature is turned off. So you cannot activate the course later.
     *
     * See CCLE-8155.
     */
    public function test_broken_activate() {
        global $DB;

        // Test 1: Enable public/private with group/grouping non-existing.
        $course = $this->ppcourse->get_course();
        $course->enablepublicprivate = 0;
        $DB->update_record('course', $course);

        // Delete public/private group/grouping.
        $ppgroupid = $course->grouppublicprivate;
        groups_delete_group($ppgroupid);
        $this->assertFalse(groups_get_group($ppgroupid));
        $ppgroupingid = $course->groupingpublicprivate;
        groups_delete_grouping($ppgroupingid);
        $this->assertFalse(groups_get_grouping($ppgroupingid));

        // Make sure you can activate course.
        $course->enablepublicprivate = 1;
        update_course($course);

        // Make sure public/private is enabled and group/grouping exists.
        $this->assertPublicPrivateEnabled($course->id);

        // Test 2: Enable public/private with group/grouping existing.
        $course = get_course($course->id);
        $course->enablepublicprivate = 0;
        $DB->update_record('course', $course);

        // Make sure you can activate course.
        $course->enablepublicprivate = 1;
        update_course($course);

        // Make sure public/private is enabled and group/grouping exists.
        $this->assertPublicPrivateEnabled($course->id);

        // Test 3: Try to disable public/private with group/grouping non-existing.

        // Delete public/private group/grouping.
        $ppgroupid = $course->grouppublicprivate;
        groups_delete_group($ppgroupid);
        $this->assertFalse(groups_get_group($ppgroupid));
        $ppgroupingid = $course->groupingpublicprivate;
        groups_delete_grouping($ppgroupingid);
        $this->assertFalse(groups_get_grouping($ppgroupingid));

        // Deactivate course.
        $course->enablepublicprivate = 0;
        update_course($course);

        // Make sure public/private is disabled and group/grouping do not exist.
        $this->assertPublicPrivateDisabled($course->id);

        // Test 4: Try to enable a course with public/private group/grouping existing.
        $ppcourse = new \PublicPrivate_Course($course->id);
        $ppcourse->activate();
        $this->assertPublicPrivateEnabled($course->id);
        $course = $ppcourse->get_course();
        $course->enablepublicprivate = 0;
        $DB->update_record('course', $course);

        // Make sure you can activate course.
        $course->enablepublicprivate = 1;
        update_course($course);

        // Make sure public/private is enabled and group/grouping exists.
        $this->assertPublicPrivateEnabled($course->id);
    }

}
