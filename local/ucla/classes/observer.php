<?php
// This file is part of the local UCLA plugin for Moodle - http://moodle.org/
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
 * Event handler class.
 *
 * @package    local_ucla
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event handler class file.
 *
 * @copyright  2016 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ucla_observer {

    /**
     * When a course is restored, check if it has duplicate Announcement
     *
     * @param \core\event\course_restored $event
     * @return boolean
     */
    public static function course_restored_dedup_default_forums(\core\event\course_restored $event) {
        global $DB;

        // Only respond to course restores.
        if ($event->other['type'] != backup::TYPE_1COURSE) {
            return true;
        }

        // Check if restored course has duplicate "Announcements" and/or
        // "Discussion forum" forums. Array is indexed as type => default name.
        $defaultforums = array('news' => get_string('namenews', 'forum'),
            'general' => get_string('discforum', 'format_ucla'));

        foreach ($defaultforums as $type => $defaultname) {
            // Get forum and course module information.
            $sql = "SELECT   cm.id AS cmid,
                            f.id AS id,
                            f.name AS name
                   FROM     {forum} f
                   JOIN     {course_modules} cm ON (f.id=cm.instance)
                   JOIN     {modules} m ON (cm.module=m.id)
                   WHERE    f.course = ? AND
                            f.type = ? AND
                            m.name='forum'
                   ORDER BY f.id ASC";
            if ($forums = $DB->get_records_sql($sql, array($event->courseid, $type))) {
                if (count($forums) > 1) {
                    $forumdelete = array();
                    /* Try to keep at least 1 default forum type:
                     * 1) Check if forum has any content, if so, then don't delete
                     *    it. Skip to end.
                     * 2) Check if forum has changed its name from the default, if
                     *    so, then don't delete it. Skip to end.
                     * 3) If forum does not have any content or changed its title,
                     *    then mark it for deletion.
                     * 4) If no $forumdelete is the same as the number of $forums,
                     *    then choose the first entry from $forumdelete to keep and
                     *    delete the rest.
                     */
                    foreach ($forums as $forum) {
                        // Any content?
                        if ($DB->record_exists('forum_discussions',
                                        array('forum' => $forum->id))) {
                            continue;
                        }
                        // Changed default name?
                        if ($defaultname != $forum->name) {
                            continue;
                        }
                        $forumdelete[] = $forum;
                    }

                    if (count($forumdelete) == count($forums)) {
                        // All forums are eligible to be deleted, so keep first.
                        array_shift($forumdelete);
                    }

                    if (!empty($forumdelete)) {
                        foreach ($forumdelete as $todelete) {
                            // Delete course module entry.
                            course_delete_module($todelete->cmid);
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Makes sure that restored courses have the UCLA database enrollment plugin
     * enabled, if it exists.
     *
     * @param \core\event\course_restored $event
     * @return boolean
     */
    public static function course_restored_enrol_check(\core\event\course_restored $event) {
        global $DB;
        // Only respond to course restores.
        if ($event->other['type'] == backup::TYPE_1COURSE) {
            $record = $DB->get_record('enrol',
                    array('enrol' => 'database',
                          'courseid' => $event->courseid,
                          'status' => ENROL_INSTANCE_DISABLED));
            if (!empty($record)) {
                ucla_reg_enrolment_plugin_cron::update_plugin($event->courseid,
                        $record->id);
            }
        }

        return true;
    }

    /**
     * After courses are built, run prepop on them.
     *
     * @param \tool_uclacoursecreator\event\course_creator_finished $event
     * @return boolean
     */
    public static function ucla_sync_built_courses(\tool_uclacoursecreator\event\course_creator_finished $event) {
        $edata = json_decode($event->other);

        // Don't run during unit tests. Can be triggered via
        // course_creator_finished event.
        if (defined('PHPUNIT_TEST') and PHPUNIT_TEST) {
            return true;
        }

        // This hopefully means that this plugin IS enabled.
        $enrol = enrol_get_plugin('database');
        if (empty($enrol)) {
            debugging('Database enrolment plugin is not installed');
            return false;
        }
        
        $trace = null;
        if (debugging()) {
            $trace = new text_progress_trace();
        } else {
            $trace = new null_progress_trace();
        }
        $courseids = array();
        foreach ($edata->completed_requests as $request) {
            if (empty($request->courseid)) {
                continue;
            }
            $courseids[] = $request->courseid;
        }

        foreach ($courseids as $courseid) {
            // This will handle auto-groups via events api.
            $enrol->sync_enrolments($trace, $courseid);
        }

        return true;
    }
}