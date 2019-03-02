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
 * Combs through courses and cleans up public/private membership.
 *
 * @package    local_publicprivate
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_publicprivate\task;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');

/**
 * Task class.
 *
 * @copyright  2019 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup extends \core\task\scheduled_task  {

    /**
     * Cron for public/private to do some sanity checks:
     *  1) The public/private group and groupings specified in course table
     *     should exist.
     *  2) Courses with public/private enabled should have the public/private
     *     grouping as the default grouping, if no other grouping is set.
     *  3) Group members for public/private grouping should only be in group once.
     *  4) For courses with recently updated enrollment plugins, make sure users
     *     are in the correct public/private status.
     */
    public function execute() {
        // 1) The public/private group and groupings specified in course table
        // should exist.
        $this->fix_missing_group_groupings();

        // 2) Courses with public/private enabled should have the public/private
        // grouping as the default grouping, if no other grouping is set.
        $this->fix_default_grouping();

        // 3) Group members for public/private grouping should only be in group
        // once.
        $this->fix_duplicate_members();

        // 4) For courses with recently updated enrollment plugins, make sure
        // users are in the correct public/private status. See CCLE-5246.
        $this->fix_membership();
    }

    /**
     * Courses with public/private enabled should have the public/private
     * grouping as the default grouping, if no other grouping is set.
     */
    public function fix_default_grouping() {
        global $DB;

        mtrace('Looking for courses with invalid publicprivate groupings set');

        // First find all courses that have enablepublicprivate=1, but
        // have defaultgroupingid=0 (should be publicprivate grouping).
        $courses = $DB->get_recordset('course',
                array('enablepublicprivate' => 1, 'defaultgroupingid' => 0));
        if ($courses->valid()) {
            foreach ($courses as $course) {
                if (empty($course->groupingpublicprivate)) {
                    mtrace(sprintf('  Public/private for course %d not properly ' .
                                    'setup, skipping', $course->id));
                    continue;

                    // Public/private is enabled, but there is no public/private
                    // grouping, disable pp and then reenable.
                    $ppcourse = new \PublicPrivate_Course($course);
                    if ($ppcourse->is_activated()) {
                        // Is activated, but has no groupingpublicprivate.
                        // Need to redo this course, something wrong happened.
                        mtrace(sprintf('  Deactivating public/private for course %d',
                                        $course->id));
                        $ppcourse->deactivate();
                    }
                    mtrace(sprintf('  Activating public/private for course %d',
                                    $course->id, $course->groupingpublicprivate));
                    $ppcourse->activate();
                    $course->groupingpublicprivate = $ppcourse->get_grouping();
                    mtrace(sprintf('  Activated public/private for course %d, ' .
                                    'groupingpublicprivate now %d', $course->id,
                                    $course->groupingpublicprivate));
                }

                $course->defaultgroupingid = $course->groupingpublicprivate;
                mtrace(sprintf('  Seting defaultgroupingid to be %d for course %d',
                                $course->groupingpublicprivate, $course->id));
                $DB->update_record('course', $course, true);
            }
        }
    }

    /**
     * Group members for public/private grouping should only be in group once.
     */
    public function fix_duplicate_members() {
        global $DB;

        mtrace('Looking for duplicate groups_members entries');

        // Just find any duplicate entries in groups_members table, since they
        // shouldn't be there anyways.
        $sql = "SELECT  duplicate.*
                FROM    {groups_members} original,
                        {groups_members} duplicate
                WHERE   original.groupid=duplicate.groupid AND
                        original.userid=duplicate.userid AND
                        original.id!=duplicate.id";
        $results = $DB->get_records_sql($sql);
        if (!empty($results)) {
            $validgroupmembers = array(); // Track which group members to keep.
            foreach ($results as $result) {
                $groupid = $result->groupid;
                $userid = $result->userid;

                if (!isset($validgroupmembers[$groupid][$userid])) {
                    $validgroupmembers[$groupid][$userid] = true;
                    continue;
                }

                // Found duplicate, so delete it.
                mtrace(sprintf('  Deleting duplicate entry in groups_members for ' .
                                'groupid %d and userid %d', $groupid, $userid));
                $DB->delete_records('groups_members', array('id' => $result->id));
            }
        }
    }

    /**
     * For courses with recently updated enrollment plugins, make sure users
     * are in the correct public/private status.
     *
     * See CCLE-5246.
     *
     * @param moodle_recordset $courses If passed, will fix membership for given
     *                                  list of courses.
     */
    public function fix_membership(\moodle_recordset $courses = null) {
        global $DB;

        if (empty($courses)) {
            mtrace('Looking for courses with recently updated enrolment plugins');
            $lastcron = $this->get_last_run_time();
            $sql = "SELECT DISTINCT c.*
                      FROM {course} c
                      JOIN {enrol} e ON (e.courseid=c.id)
                     WHERE e.timemodified >= ? AND
                           c.enablepublicprivate=1 AND
                           e.timecreated!=e.timemodified";
            $courses = $DB->get_recordset_sql($sql, array($lastcron));
        } else {
            mtrace('Processing courses');
        }

        if ($courses->valid()) {
            foreach ($courses as $course) {
                mtrace(sprintf('  Checking course %d', $course->id), '');
                $ppcourse = new \PublicPrivate_Course($course);
                $context = \context_course::instance($course->id);
                $users = get_enrolled_users($context);
                foreach ($users as $user) {
                    // Print dot to give a sense of progression.
                    mtrace('.', '');
                    try {
                        $ppcourse->check_enrolment($user->id);
                    } catch (\Exception $e) {
                        mtrace(sprintf("\nERROR: Could not check enrollment " .
                            'for userid %d in courseid %d: %s',
                            $user->id, $course->id, $e->getMessage()));
                    }
                }
                mtrace('');
            }
        }
    }

    /**
     * The public/private group and groupings specified in course table
     * should exist.
     */
    public function fix_missing_group_groupings() {
        global $DB;

        // Find all missing public/private groups and groupings.
        $sql = "(
                SELECT c.*
                  FROM {course} c
             LEFT JOIN {groups} g ON c.grouppublicprivate=g.id
                 WHERE g.id IS NULL
                       AND c.enablepublicprivate=1
            )
            UNION (
                SELECT c.*
                  FROM {course} c
             LEFT JOIN {groupings} g ON c.groupingpublicprivate=g.id
                 WHERE g.id IS NULL
                       AND c.enablepublicprivate=1
            )";
        $courses = $DB->get_records_sql($sql);
        if (!empty($courses)) {
            foreach ($courses as $course) {
                $ppcourse = new \PublicPrivate_Course($course);
                $ppcourse->detect_problems(true);
            }
        }
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcleanup', 'local_publicprivate');
    }
}
