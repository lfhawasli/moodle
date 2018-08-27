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
 * @copyright   2018 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');

/**
 * Unit test file.
 *
 * @copyright  2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_restore_testcase extends advanced_testcase {

    /**
     * Stores backup test class.
     * @var stdClass ucla_request_classes record
     */
    protected $backupclass;

    /**
     * Stores restore test class.
     * @var stdClass ucla_request_classes record
     */
    protected $restoreclass;

    /**
     * Backup course and restores, via delete contents, into another course.
     * @param int $backupcourseid
     * @param int $restorecourseid
     */
    protected function backup_and_restore($backupcourseid, $restorecourseid) {
        global $USER, $CFG;

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just
        // create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $backupcourseid,
                backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore to existing course and override it.
        $rc = new restore_controller($backupid, $restorecourseid, backup::INTERACTIVE_NO,
                backup::MODE_SAMESITE, $USER->id, backup::TARGET_EXISTING_DELETING);
        if ($rc->get_target() == backup::TARGET_CURRENT_DELETING || $rc->get_target() == backup::TARGET_EXISTING_DELETING) {
            restore_dbops::delete_course_content($rc->get_courseid(), ['keep_groups_and_groupings' => 0]);
        }

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
    }

    /**
     * Creates test class.
     */
    public function setUp() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a new course and enable public/private. We pass an array
        // containing  an empty array so that only one course is generated.
        // (No crosslisting).
        $this->gen = $this->getDataGenerator();
        $creqarr = $this->gen->get_plugin_generator('local_ucla')->create_class(array(array()));
        $this->backupclass = reset($creqarr);

        // Create private material.
        $resourcegen = $this->getDataGenerator()->get_plugin_generator('mod_resource');
        $file = $resourcegen->create_instance(array('course' => $this->backupclass->courseid));
        $ppfile = PublicPrivate_Module::build($file->cmid);
        $this->assertTrue($ppfile->is_private());

        $this->gen = $this->getDataGenerator();
        $creqarr = $this->gen->get_plugin_generator('local_ucla')->create_class(array(array()));
        $this->restoreclass = reset($creqarr);

        $this->setAdminUser();
    }

    /**
     * Makes sure that the public/private group/groupings are recreated when
     * a restore deletes all content.
     */
    public function test_group_grouping_remains_intact() {
        // Verify that both courses have public/private enabled.
        $courses = [$this->backupclass, $this->restoreclass];
        foreach ($courses as $course) {
            $ppcourse = PublicPrivate_Course::build($course->courseid);
            $this->assertTrue($ppcourse->is_activated());

            // Tests that group/groupings exists.
            $this->assertFalse($ppcourse->detect_problems());
        }

        $this->backup_and_restore($this->backupclass->courseid, $this->restoreclass->courseid);

        // Now verify that group/groupings are still intact.
        foreach ($courses as $course) {
            $ppcourse = PublicPrivate_Course::build($course->courseid);
            $this->assertTrue($ppcourse->is_activated());

            // Tests that group/groupings exists.
            $this->assertFalse($ppcourse->detect_problems());
        }
    }

}
